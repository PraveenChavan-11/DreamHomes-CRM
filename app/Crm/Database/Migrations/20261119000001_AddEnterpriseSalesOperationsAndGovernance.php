<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 20261119000001: Commercial Governance, Organisation-Centric Structure,
 * Dedicated Sales Activities, Sales Targets & Performance, Approval Matrix Engine,
 * Communication Centre & Central Notification Infrastructure.
 */
class AddEnterpriseSalesOperationsAndGovernance extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        // 1. CRM Organisations (Primary Business Account Master)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'org_no' => ['type' => 'VARCHAR', 'constraint' => 40],
            'name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'org_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'corporate'],
            'industry' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'business_category' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'website' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gstin' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'pan' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'primary_email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'primary_phone' => ['type' => 'VARCHAR', 'constraint' => 25, 'null' => true],
            'alternate_phone' => ['type' => 'VARCHAR', 'constraint' => 25, 'null' => true],
            'registered_address' => ['type' => 'TEXT', 'null' => true],
            'billing_address' => ['type' => 'TEXT', 'null' => true],
            'default_site_address' => ['type' => 'TEXT', 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'state' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pincode' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'country' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'India'],
            'account_owner_id' => ['type' => 'INT', 'null' => true],
            'sales_territory_id' => ['type' => 'INT', 'null' => true],
            'lead_source_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'account_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'account_rating' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'warm'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('org_no');
        $this->forge->addKey('account_owner_id');
        $this->forge->addKey('account_status');
        $this->forge->addKey('city');
        $this->forge->addKey('org_type');
        $this->forge->createTable('crm_organisations', true);

        // 2. CRM Contacts (Multiple Contacts under one Organisation)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'contact_no' => ['type' => 'VARCHAR', 'constraint' => 40],
            'organisation_id' => ['type' => 'INT', 'unsigned' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'designation' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'department' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'mobile' => ['type' => 'VARCHAR', 'constraint' => 25],
            'alternate_number' => ['type' => 'VARCHAR', 'constraint' => 25, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'whatsapp' => ['type' => 'VARCHAR', 'constraint' => 25, 'null' => true],
            'contact_role' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'owner'],
            'contact_types' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'primary'],
            'is_decision_maker' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'preferred_channel' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'call'],
            'preferred_language' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'english'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('contact_no');
        $this->forge->addKey('organisation_id');
        $this->forge->addKey('mobile');
        $this->forge->addKey('email');
        $this->forge->addKey('is_primary');
        $this->forge->addKey('contact_role');
        $this->forge->createTable('crm_contacts', true);

        // 3. CRM Sales Activities (Dedicated Module Separated from Leads)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'activity_no' => ['type' => 'VARCHAR', 'constraint' => 40],
            'activity_type' => ['type' => 'VARCHAR', 'constraint' => 60],
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'project_id' => ['type' => 'INT', 'null' => true],
            'lead_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'assigned_user_id' => ['type' => 'INT', 'null' => true],
            'created_by' => ['type' => 'INT', 'null' => true],
            'activity_date' => ['type' => 'DATE'],
            'start_time' => ['type' => 'TIME', 'null' => true],
            'end_time' => ['type' => 'TIME', 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'discussion_summary' => ['type' => 'TEXT'],
            'requirement_identified' => ['type' => 'TEXT', 'null' => true],
            'product_interest' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'est_business_value' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'next_action' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'next_followup_date' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'scheduled'],
            'outcome' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'followup_required'],
            'converted_to_lead_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'converted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('activity_no');
        $this->forge->addKey('organisation_id');
        $this->forge->addKey('contact_id');
        $this->forge->addKey('assigned_user_id');
        $this->forge->addKey('activity_date');
        $this->forge->addKey('status');
        $this->forge->addKey('converted_to_lead_id');
        $this->forge->createTable('crm_sales_activities', true);

        // 4. CRM Approval Rules (Settings > Sales Governance Matrix)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rule_code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'rule_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'rule_description' => ['type' => 'TEXT', 'null' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'brand_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'salesperson_role' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'salesperson_id' => ['type' => 'INT', 'null' => true],
            'min_discount_pct' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0],
            'max_discount_pct' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'min_discount_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'min_quotation_value' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'requires_min_price_check' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'requires_margin_check' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'margin_floor_pct' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 15.00],
            'required_role' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'sales_manager'],
            'approval_level' => ['type' => 'INT', 'default' => 1],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'priority' => ['type' => 'INT', 'default' => 10],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('rule_code');
        $this->forge->addKey('approval_level');
        $this->forge->addKey('is_active');
        $this->forge->createTable('crm_approval_rules', true);

        // 5. CRM Quotation Approvals (Traceable Commercial Approvals)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'approval_no' => ['type' => 'VARCHAR', 'constraint' => 40],
            'quotation_id' => ['type' => 'INT', 'unsigned' => true],
            'quotation_version_id' => ['type' => 'INT', 'unsigned' => true],
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'salesperson_id' => ['type' => 'INT'],
            'approval_level' => ['type' => 'INT', 'default' => 1],
            'max_approval_level' => ['type' => 'INT', 'default' => 1],
            'required_role' => ['type' => 'VARCHAR', 'constraint' => 60],
            'assigned_approver_id' => ['type' => 'INT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pending'],
            'proposed_selling_price' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'standard_selling_price' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'cost_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'requested_discount_pct' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0],
            'requested_discount_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'net_taxable_value' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'grand_total' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'projected_margin_pct' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'salesperson_justification' => ['type' => 'TEXT'],
            'action_by_user_id' => ['type' => 'INT', 'null' => true],
            'action_at' => ['type' => 'DATETIME', 'null' => true],
            'manager_remarks' => ['type' => 'TEXT', 'null' => true],
            'rejection_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sla_due_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('approval_no');
        $this->forge->addKey('quotation_id');
        $this->forge->addKey('quotation_version_id');
        $this->forge->addKey('salesperson_id');
        $this->forge->addKey('status');
        $this->forge->addKey('assigned_approver_id');
        $this->forge->createTable('crm_quotation_approvals', true);

        // 6. CRM Approval Clarifications (Discussion Thread)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'approval_id' => ['type' => 'INT', 'unsigned' => true],
            'sender_user_id' => ['type' => 'INT'],
            'sender_role' => ['type' => 'VARCHAR', 'constraint' => 60],
            'message' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('approval_id');
        $this->forge->createTable('crm_approval_clarifications', true);

        // 7. CRM Sales Targets (Daily, Weekly, Monthly, Quarterly, Yearly)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'target_no' => ['type' => 'VARCHAR', 'constraint' => 40],
            'user_id' => ['type' => 'INT', 'null' => true],
            'team_id' => ['type' => 'INT', 'null' => true],
            'frequency' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'monthly'],
            'period_start' => ['type' => 'DATE'],
            'period_end' => ['type' => 'DATE'],
            'period_label' => ['type' => 'VARCHAR', 'constraint' => 60],
            'revenue_target' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'confirmed_orders_target' => ['type' => 'INT', 'default' => 0],
            'organisations_target' => ['type' => 'INT', 'default' => 0],
            'contacts_target' => ['type' => 'INT', 'default' => 0],
            'leads_target' => ['type' => 'INT', 'default' => 0],
            'qualified_leads_target' => ['type' => 'INT', 'default' => 0],
            'site_visits_target' => ['type' => 'INT', 'default' => 0],
            'meetings_target' => ['type' => 'INT', 'default' => 0],
            'calls_target' => ['type' => 'INT', 'default' => 0],
            'followups_target' => ['type' => 'INT', 'default' => 0],
            'quotations_target' => ['type' => 'INT', 'default' => 0],
            'quotations_approved_target' => ['type' => 'INT', 'default' => 0],
            'quotations_accepted_target' => ['type' => 'INT', 'default' => 0],
            'conversion_rate_target' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'collections_target' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'assigned_by_user_id' => ['type' => 'INT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'revision_notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('target_no');
        $this->forge->addKey('user_id');
        $this->forge->addKey('team_id');
        $this->forge->addKey('period_start');
        $this->forge->addKey('period_end');
        $this->forge->addKey('frequency');
        $this->forge->createTable('crm_sales_targets', true);

        // 8. CRM Communication Logs (Full Relationship Timeline)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'organisation_id' => ['type' => 'INT', 'unsigned' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'communication_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'outgoing'],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 255],
            'message_body' => ['type' => 'TEXT'],
            'outcome' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'next_action' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'followup_date' => ['type' => 'DATETIME', 'null' => true],
            'related_entity_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'related_entity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'attachment_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'logged_by_user_id' => ['type' => 'INT'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('organisation_id');
        $this->forge->addKey('contact_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('crm_communication_logs', true);

        // 9. Safe Schema Alterations to Existing Tables
        $this->alterTableSafely('crm_customers', [
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'primary_contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->alterTableSafely('crm_enquiries', [
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'converted_from_activity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->alterTableSafely('crm_quotations', [
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approval_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'none'],
            'is_commercially_approved' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'current_approval_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->alterTableSafely('crm_quotation_versions', [
            'approval_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'none'],
            'approval_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->alterTableSafely('crm_sales_orders', [
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->alterTableSafely('crm_products', [
            'cost_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'target_margin_pct' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 25.00],
            'approved_discount_limit' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 5.00],
        ]);

        $this->alterTableSafely('crm_email_log', [
            'organisation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
    }

    private function alterTableSafely(string $tableName, array $fields): void
    {
        $db = $this->db;
        if (! $db->tableExists($tableName)) {
            return;
        }
        $existingCols = [];
        try {
            $rows = $db->query("SHOW COLUMNS FROM `{$tableName}`")->getResultArray();
            foreach ($rows as $r) {
                $existingCols[] = strtolower($r['Field'] ?? $r['field'] ?? '');
            }
        } catch (\Throwable $e) {
            $existingCols = array_map('strtolower', $db->getFieldNames($tableName));
        }

        $toAdd = [];
        foreach ($fields as $colName => $def) {
            if (! in_array(strtolower($colName), $existingCols, true)) {
                $toAdd[$colName] = $def;
            }
        }
        if (! empty($toAdd)) {
            $this->forge->addColumn($tableName, $toAdd);
        }
    }

    public function down(): void
    {
        // Safe rollback: preserve tables
    }
}
