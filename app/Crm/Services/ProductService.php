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
}
