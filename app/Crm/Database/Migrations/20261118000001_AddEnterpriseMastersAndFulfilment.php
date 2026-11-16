<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 20261118000001: Enterprise Master Data Management, Projects, Payments,
 * Deliveries, Installations, After-Sales Service, and Extended Sales Lifecycle.
 */
class AddEnterpriseMastersAndFulfilment extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        // 1. Master Data Tables
        // 1.1 Master Types Registry
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'group_name' => ['type' => 'VARCHAR', 'constraint' => 64], // organisation, customer, product, sales, system
            'code' => ['type' => 'VARCHAR', 'constraint' => 64],
            'name' => ['type' => 'VARCHAR', 'constraint' => 128],
            'description' => ['type' => 'TEXT', 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('crm_master_types', true);

        // 1.2 Master Records (Universal, flexible entity store for all categories)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type_code' => ['type' => 'VARCHAR', 'constraint' => 64],
            'code' => ['type' => 'VARCHAR', 'constraint' => 64],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'metadata_json' => ['type' => 'LONGTEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['type_code', 'is_active']);
        $this->forge->addUniqueKey(['type_code', 'code']);
        $this->forge->createTable('crm_master_records', true);

        // 1.3 Product Brands
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'website' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('crm_product_brands', true);

        // 1.4 Product Subcategories
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('crm_product_subcategories', true);

        // 1.5 Product Types
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'subcategory_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'brand_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('crm_product_types', true);

        // 1.6 Product Variants
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'INT', 'unsigned' => true],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 64],
            'variant_name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'brand_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'finish' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'colour' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'glass_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tracks' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'width' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'height' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'depth' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'size_unit' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'mm'],
            'cost_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => '0.00'],
            'selling_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => '0.00'],
            'tax_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '18.00'],
            'discount_eligible' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'warranty_months' => ['type' => 'INT', 'default' => 12],
            'attributes_json' => ['type' => 'LONGTEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addUniqueKey('sku');
        $this->forge->createTable('crm_product_variants', true);

        // 1.7 Customer Additional Addresses
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'address_type' => ['type' => 'ENUM', 'constraint' => ['site', 'billing', 'branch', 'warehouse', 'other'], 'default' => 'site'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 100],
            'address_line' => ['type' => 'TEXT'],
            'city' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'Pune'],
            'state' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'Maharashtra'],
            'pin_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'contact_person' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'contact_phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->createTable('crm_customer_addresses', true);

        // 2. Commercial Transactions & Project Workflows
        // 2.1 Customer Projects
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'project_no' => ['type' => 'VARCHAR', 'constraint' => 32],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'site_location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'Pune'],
            'state' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'Maharashtra'],
            'pin_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'], 'default' => 'planning'],
            'budget' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'completion_date' => ['type' => 'DATE', 'null' => true],
            'assigned_to' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'is_demo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->addUniqueKey('project_no');
        $this->forge->createTable('crm_projects', true);

        // 2.2 Payment Transactions
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'payment_no' => ['type' => 'VARCHAR', 'constraint' => 32],
            'order_id' => ['type' => 'INT', 'unsigned' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'payment_type' => ['type' => 'ENUM', 'constraint' => ['advance', 'partial', 'full', 'balance', 'refund', 'adjustment'], 'default' => 'advance'],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'payment_mode' => ['type' => 'ENUM', 'constraint' => ['cash', 'upi', 'bank_transfer', 'cheque', 'card', 'other'], 'default' => 'bank_transfer'],
            'transaction_reference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'payment_date' => ['type' => 'DATE'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'initiated', 'partially_paid', 'paid', 'failed', 'overdue', 'refunded', 'cancelled'], 'default' => 'paid'],
            'receipt_no' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'recorded_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_demo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey('customer_id');
        $this->forge->addUniqueKey('payment_no');
        $this->forge->createTable('crm_payments', true);

        // 2.3 Delivery Tracking & Challans
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'delivery_no' => ['type' => 'VARCHAR', 'constraint' => 32],
            'challan_no' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'order_id' => ['type' => 'INT', 'unsigned' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'scheduled', 'ready', 'dispatched', 'partially_delivered', 'delivered', 'delivery_failed', 'rescheduled', 'cancelled'], 'default' => 'pending'],
            'scheduled_date' => ['type' => 'DATE', 'null' => true],
            'dispatched_date' => ['type' => 'DATETIME', 'null' => true],
            'delivered_date' => ['type' => 'DATETIME', 'null' => true],
            'vehicle_details' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'driver_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'driver_phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'tracking_no' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'delivery_address' => ['type' => 'TEXT', 'null' => true],
            'recipient_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'recipient_phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'recorded_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_demo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey('customer_id');
        $this->forge->addUniqueKey('delivery_no');
        $this->forge->createTable('crm_deliveries', true);

        // 2.4 Installation Management
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'installation_no' => ['type' => 'VARCHAR', 'constraint' => 32],
            'order_id' => ['type' => 'INT', 'unsigned' => true],
            'delivery_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'scheduled', 'assigned', 'in_progress', 'completed', 'partially_completed', 'failed', 'revisit_required', 'cancelled'], 'default' => 'pending'],
            'scheduled_date' => ['type' => 'DATE', 'null' => true],
            'started_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'technician_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'technician_phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'site_address' => ['type' => 'TEXT', 'null' => true],
            'customer_signoff_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'signoff_date' => ['type' => 'DATE', 'null' => true],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'recorded_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_demo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey('customer_id');
        $this->forge->addUniqueKey('installation_no');
        $this->forge->createTable('crm_installations', true);

        // 2.5 After-Sales Service & Complaints
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ticket_no' => ['type' => 'VARCHAR', 'constraint' => 32],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'request_type' => ['type' => 'ENUM', 'constraint' => ['complaint', 'service_request', 'warranty_claim', 'repair_request', 'installation_complaint', 'maintenance_visit'], 'default' => 'service_request'],
            'priority' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium'],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'assigned', 'acknowledged', 'in_progress', 'waiting_for_customer', 'waiting_for_parts', 'resolved', 'closed', 'reopened', 'cancelled'], 'default' => 'open'],
            'issue_description' => ['type' => 'TEXT'],
            'assigned_to' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resolution_notes' => ['type' => 'TEXT', 'null' => true],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
            'customer_feedback' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_demo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->addKey('order_id');
        $this->forge->addUniqueKey('ticket_no');
        $this->forge->createTable('crm_service_requests', true);

        // 3. Safe Schema Updates to existing tables
        // 3.1 Alter crm_enquiries to add lead management fields & project linkage
        if ($db->tableExists('crm_enquiries')) {
            $fields = $db->getFieldNames('crm_enquiries');
            $newEnquiryFields = [];
            if (! in_array('is_lead', $fields, true)) {
                $newEnquiryFields['is_lead'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if (! in_array('project_id', $fields, true)) {
                $newEnquiryFields['project_id'] = ['type' => 'INT', 'unsigned' => true, 'null' => true];
            }
            if (! in_array('lead_priority', $fields, true)) {
                $newEnquiryFields['lead_priority'] = ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'medium'];
            }
            if (! in_array('expected_purchase_date', $fields, true)) {
                $newEnquiryFields['expected_purchase_date'] = ['type' => 'DATE', 'null' => true];
            }
            if (! empty($newEnquiryFields)) {
                $this->forge->addColumn('crm_enquiries', $newEnquiryFields);
            }
        }

        // 3.2 Alter crm_customers to add customer_type & credit_terms
        if ($db->tableExists('crm_customers')) {
            $custFields = $db->getFieldNames('crm_customers');
            $newCustFields = [];
            if (! in_array('customer_type', $custFields, true)) {
                $newCustFields['customer_type'] = ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'individual'];
            }
            if (! in_array('credit_terms', $custFields, true)) {
                $newCustFields['credit_terms'] = ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true];
            }
            if (! in_array('alternate_phone', $custFields, true)) {
                $newCustFields['alternate_phone'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true];
            }
            if (! empty($newCustFields)) {
                $this->forge->addColumn('crm_customers', $newCustFields);
            }
        }

        // 3.3 Ensure crm_sales_orders status column supports all 17 workflow stages
        if ($db->tableExists('crm_sales_orders')) {
            $orderFields = $db->getFieldData('crm_sales_orders');
            foreach ($orderFields as $f) {
                if ($f->name === 'status' && $f->type === 'enum') {
                    // Widen status to VARCHAR(40) to support all 17 lifecycle statuses smoothly
                    $db->query("ALTER TABLE crm_sales_orders MODIFY COLUMN status VARCHAR(40) NOT NULL DEFAULT 'draft'");
                    break;
                }
            }
        }

        // 3.4 Seed initial sequence counters for new modules
        $sequences = [
            ['seq_key' => 'project', 'prefix' => 'PRJ-', 'pad_length' => 4, 'next_value' => 16],
            ['seq_key' => 'payment', 'prefix' => 'PAY-', 'pad_length' => 4, 'next_value' => 21],
            ['seq_key' => 'receipt', 'prefix' => 'RCP-', 'pad_length' => 4, 'next_value' => 21],
            ['seq_key' => 'delivery', 'prefix' => 'DEL-', 'pad_length' => 4, 'next_value' => 11],
            ['seq_key' => 'challan', 'prefix' => 'DC-', 'pad_length' => 4, 'next_value' => 11],
            ['seq_key' => 'installation', 'prefix' => 'INS-', 'pad_length' => 4, 'next_value' => 11],
            ['seq_key' => 'service', 'prefix' => 'SRV-', 'pad_length' => 4, 'next_value' => 11],
            ['seq_key' => 'lead', 'prefix' => 'LEAD-', 'pad_length' => 4, 'next_value' => 36],
        ];

        foreach ($sequences as $seq) {
            $exists = $db->table('crm_number_sequences')->where('seq_key', $seq['seq_key'])->countAllResults();
            if ($exists === 0) {
                $db->table('crm_number_sequences')->insert($seq);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('crm_service_requests', true);
        $this->forge->dropTable('crm_installations', true);
        $this->forge->dropTable('crm_deliveries', true);
        $this->forge->dropTable('crm_payments', true);
        $this->forge->dropTable('crm_projects', true);
        $this->forge->dropTable('crm_customer_addresses', true);
        $this->forge->dropTable('crm_product_variants', true);
        $this->forge->dropTable('crm_product_types', true);
        $this->forge->dropTable('crm_product_subcategories', true);
        $this->forge->dropTable('crm_product_brands', true);
        $this->forge->dropTable('crm_master_records', true);
        $this->forge->dropTable('crm_master_types', true);
    }
}
