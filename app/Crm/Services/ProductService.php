<?php

namespace Crm\Services;

final class ProductService
{
    public const PRICING = ['unit', 'sqft', 'rft', 'qty', 'package'];

    public static function validate(array $d): array
    {
        $e = [];
        foreach (['sku' => 'SKU', 'name' => 'Name'] as $k => $l) {
            if (trim((string) ($d[$k] ?? '')) === '') {
                $e[$k] = "{$l} is required.";
            }
        }
        foreach (['base_price', 'standard_price', 'min_price'] as $k) {
            if (! isset($d[$k]) || ! is_numeric($d[$k]) || $d[$k] < 0) {
                $e[$k] = 'Enter a non-negative amount.';
            }
        }
        if (! $e) {
            if (Money::cmp((string) $d['min_price'], (string) $d['standard_price']) > 0) {
                $e['min_price'] = 'Minimum price cannot exceed the standard selling price.';
            }
            if (Money::cmp((string) $d['base_price'], (string) $d['min_price']) > 0) {
                $e['base_price'] = 'Base price cannot exceed the minimum selling price.';
            }
        }
        if (! empty($d['pricing_model']) && ! in_array($d['pricing_model'], self::PRICING, true)) {
            $e['pricing_model'] = 'Invalid pricing model.';
        }

        return $e;
    }

    public static function save(?int $id, array $d, array $actor, int $isDemo = 0): int
    {
        if (! AccessScope::can($actor, 'products.manage')) {
            throw new CrmException('You do not have permission to manage products.');
        }
        $db = Crm::db();
        if ($id) {
            $d += (array) $db->table('crm_products')->where('id', $id)->get()->getRowArray();
        }
        if ($err = self::validate($d)) {
            throw CrmException::validation($err);
        }

        return (int) Crm::tx(static function ($db) use ($id, $d, $actor, $isDemo) {
            $row = [
                'sku' => trim($d['sku']), 'name' => trim($d['name']), 'brand' => $d['brand'] ?? null, 'category_id' => (int) $d['category_id'], 'description' => $d['description'] ?? null,
                'specifications' => $d['specifications'] ?? null, 'image' => $d['image'] ?? null, 'unit' => $d['unit'] ?? 'nos', 'pricing_model' => $d['pricing_model'] ?? 'unit',
                'base_price' => $d['base_price'], 'standard_price' => $d['standard_price'], 'min_price' => $d['min_price'], 'tax_rate_id' => (int) $d['tax_rate_id'],
                'is_active' => isset($d['is_active']) ? (int) $d['is_active'] : 1,
            ];
            if ($id) {
                $old = $db->table('crm_products')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Product not found.');
                if ($db->table('crm_products')->where('sku', $row['sku'])->where('id !=', $id)->countAllResults()) {
                    throw CrmException::validation(['sku' => 'SKU already exists.']);
                }
                $db->table('crm_products')->where('id', $id)->update($row);
                if (Money::cmp($old['base_price'], (string) $row['base_price']) !== 0 || Money::cmp($old['standard_price'], (string) $row['standard_price']) !== 0 || Money::cmp($old['min_price'], (string) $row['min_price']) !== 0) {
                    $db->table('crm_product_price_history')->insert([
                        'product_id' => $id, 'old_base' => $old['base_price'], 'new_base' => $row['base_price'], 'old_standard' => $old['standard_price'], 'new_standard' => $row['standard_price'],
                        'old_min' => $old['min_price'], 'new_min' => $row['min_price'], 'changed_by' => $actor['id'], 'changed_at' => Crm::now(),
                    ]);
                }
                AuditService::audit('product.updated', 'product', $id, ['prev' => ['standard' => $old['standard_price'], 'min' => $old['min_price']], 'new' => ['standard' => $row['standard_price'], 'min' => $row['min_price']]]);

                return $id;
            }
            if ($db->table('crm_products')->where('sku', $row['sku'])->countAllResults()) {
                throw CrmException::validation(['sku' => 'SKU already exists.']);
            }
            $row['is_demo'] = $isDemo;
            $db->table('crm_products')->insert($row);
            $pid = (int) $db->insertID();
            $db->table('crm_product_price_history')->insert(['product_id' => $pid, 'old_base' => null, 'new_base' => $row['base_price'], 'old_standard' => null, 'new_standard' => $row['standard_price'], 'old_min' => null, 'new_min' => $row['min_price'], 'changed_by' => $actor['id'], 'changed_at' => Crm::now()]);
            if ($isDemo) {
                $db->query('INSERT IGNORE INTO crm_demo_registry (table_name,row_id) VALUES (?,?)', ['crm_products', $pid]);
            }
            AuditService::audit('product.created', 'product', $pid, ['new' => ['sku' => $row['sku'], 'standard' => $row['standard_price']]]);

            return $pid;
        });
    }

    /** List product variants, optionally filtered by product ID */
    public static function listVariants(?int $productId = null): array
    {
        $db = Crm::db();
        $builder = $db->table('crm_product_variants v')
            ->select('v.*, p.name as product_name, p.sku as parent_sku, b.name as brand_name')
            ->join('crm_products p', 'p.id = v.product_id', 'left')
            ->join('crm_product_brands b', 'b.id = v.brand_id', 'left');

        if ($productId !== null) {
            $builder->where('v.product_id', $productId);
        }

        return $builder->orderBy('v.id', 'ASC')->get()->getResultArray();
    }

    /** Save or update a product variant */
    public static function saveVariant(?int $id, array $data, array $actor): int
    {
        $db = Crm::db();
        $productId = (int) ($data['product_id'] ?? 0);
        $sku = trim((string) ($data['sku'] ?? ''));
        $name = trim((string) ($data['variant_name'] ?? ''));

        if ($productId <= 0) {
            throw new CrmException('A parent product is required.');
        }
        if ($sku === '') {
            throw new CrmException('Variant SKU is required.');
        }
        if ($name === '') {
            throw new CrmException('Variant name is required.');
        }

        $payload = [
            'product_id' => $productId,
            'sku' => $sku,
            'variant_name' => $name,
            'brand_id' => ! empty($data['brand_id']) ? (int) $data['brand_id'] : null,
            'finish' => trim((string) ($data['finish'] ?? '')),
            'colour' => trim((string) ($data['colour'] ?? '')),
            'glass_type' => trim((string) ($data['glass_type'] ?? '')),
            'tracks' => trim((string) ($data['tracks'] ?? '')),
            'width' => ! empty($data['width']) ? (float) $data['width'] : null,
            'height' => ! empty($data['height']) ? (float) $data['height'] : null,
            'depth' => ! empty($data['depth']) ? (float) $data['depth'] : null,
            'size_unit' => $data['size_unit'] ?? 'mm',
            'cost_price' => (float) ($data['cost_price'] ?? 0.0),
            'selling_price' => (float) ($data['selling_price'] ?? 0.0),
            'tax_rate' => (float) ($data['tax_rate'] ?? 18.0),
            'discount_eligible' => isset($data['discount_eligible']) ? (int) (bool) $data['discount_eligible'] : 1,
            'warranty_months' => (int) ($data['warranty_months'] ?? 12),
            'attributes_json' => ! empty($data['attributes']) ? json_encode($data['attributes'], JSON_UNESCAPED_UNICODE) : null,
            'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'updated_at' => Crm::now(),
        ];

        if ($id) {
            $dup = $db->table('crm_product_variants')->where('sku', $sku)->where('id !=', $id)->countAllResults();
            if ($dup > 0) {
                throw new CrmException("Variant SKU '{$sku}' already exists.");
            }
            $db->table('crm_product_variants')->where('id', $id)->update($payload);
            return $id;
        }

        $dup = $db->table('crm_product_variants')->where('sku', $sku)->countAllResults();
        if ($dup > 0) {
            throw new CrmException("Variant SKU '{$sku}' already exists.");
        }
        $payload['created_at'] = Crm::now();
        $db->table('crm_product_variants')->insert($payload);
        return (int) $db->insertID();
    }

    /** Calculate area and dimensions for custom-sized architectural openings */
    public static function calculateDimensions(float $width, float $height, string $unit = 'mm'): array
    {
        // Convert to millimeters first
        $wMm = match (strtolower($unit)) {
            'inch', 'in' => $width * 25.4,
            'ft', 'feet' => $width * 304.8,
            'm', 'meter' => $width * 1000.0,
            default => $width,
        };
        $hMm = match (strtolower($unit)) {
            'inch', 'in' => $height * 25.4,
            'ft', 'feet' => $height * 304.8,
            'm', 'meter' => $height * 1000.0,
            default => $height,
        };

        $sqM = ($wMm * $hMm) / 1000000.0;
        $sqFt = $sqM * 10.7639;
        $rft = (2 * ($wMm + $hMm)) / 304.8; // Perimeter in running feet

        return [
            'width_mm' => round($wMm, 2),
            'height_mm' => round($hMm, 2),
            'sq_meters' => round($sqM, 3),
            'sq_feet' => round($sqFt, 2),
            'perimeter_rft' => round($rft, 2),
        ];
    }
}
