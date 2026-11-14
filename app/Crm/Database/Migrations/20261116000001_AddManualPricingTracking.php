<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Manual line-total override + negotiated grand-total target, with the catalogue price kept on each line for audit. Additive and idempotent. */
class AddManualPricingTracking extends Migration
{
    public function up(): void
    {
        $this->addCols('crm_quotation_items', [
            'override_total' => 'DECIMAL(14,2) NULL',
            'std_price'      => 'DECIMAL(14,2) NULL',
        ]);
        $this->addCols('crm_quotation_versions', [
            'target_total'      => 'DECIMAL(14,2) NULL',
            'adjustment_amount' => 'DECIMAL(14,2) NOT NULL DEFAULT 0',
        ]);
    }

    public function down(): void
    {
        // additive columns are intentionally left in place (non-destructive rollback)
    }

    private function addCols(string $table, array $cols): void
    {
        $have = array_column($this->db->query("SHOW COLUMNS FROM `{$table}`")->getResultArray(), 'Field');
        foreach ($cols as $c => $def) {
            if (! in_array($c, $have, true)) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$c}` {$def}");
            }
        }
    }
}
