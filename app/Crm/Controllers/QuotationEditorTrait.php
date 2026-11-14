<?php

namespace Crm\Controllers;

use Crm\Services\Crm;
use Crm\Services\Settings;

/** Data the shared quotation editor partial needs (used by the quotation pages and the negotiation page). */
trait QuotationEditorTrait
{
    /** Catalogue for the editor: products grouped by category + chargeable extras. */
    protected function catalogue(): array
    {
        $db   = Crm::db();
        $rows = $db->query('SELECT p.id, p.sku, p.name, p.unit, p.pricing_model, p.standard_price, p.min_price, p.specifications, t.rate AS tax_rate, c.name AS category
                            FROM crm_products p JOIN crm_product_categories c ON c.id = p.category_id JOIN crm_tax_rates t ON t.id = p.tax_rate_id WHERE p.is_active = 1 ORDER BY c.sort_order, c.name, p.name')->getResultArray();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['category']][] = $r;
        }

        return ['products' => $grouped, 'charges' => $db->table('crm_additional_charges')->where('is_active', 1)->orderBy('name')->get()->getResultArray()];
    }

    /** Version items → editor line inputs (unit price carried over; override cleared so the saved price is the base of the next edit). */
    protected function itemsToLines(array $items, ?string $discountPct = null): array
    {
        return array_map(static fn ($i) => [
            'product_id' => $i['product_id'], 'description' => $i['description'], 'variant' => $i['variant'], 'width' => $i['width'] !== null ? (float) $i['width'] : '', 'height' => $i['height'] !== null ? (float) $i['height'] : '',
            'size_unit' => $i['size_unit'], 'nos' => $i['nos'] !== null ? (float) $i['nos'] : '', 'quantity' => (float) $i['quantity'], 'unit' => $i['unit'], 'unit_price' => $i['unit_price'], 'line_total' => '',
            'discount_pct' => $discountPct ?? ((float) $i['discount_pct'] > 0 ? (float) $i['discount_pct'] : ''), 'tax_rate' => (float) $i['tax_rate'], 'specifications' => $i['specifications'], 'price_source' => $i['price_source'], 'std_price' => $i['std_price'] ?? null,
        ], $items);
    }

    protected function editorBase(array $data): array
    {
        $dt = Crm::db()->table('crm_tax_rates')->where('is_default', 1)->get()->getRow();

        return $data + $this->catalogue() + ['user' => $this->user(), 'limit' => (string) ($this->user()['max_discount_pct'] ?? '0'), 'approvedPct' => null, 'defaultTax' => $dt ? (string) (float) $dt->rate : Settings::get('tax.default_rate')];
    }
}
