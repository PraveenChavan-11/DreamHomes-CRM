<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailManagementTables extends Migration
{
    public function up()
    {
        // 1. mailbox_connections
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'mailbox_resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'ACTIVE',
            ],
            'quota_used_bytes' => [
                'type'       => 'BIGINT',
                'default'    => 0,
            ],
            'quota_total_bytes' => [
                'type'       => 'BIGINT',
                'default'    => 0,
            ],
            'is_default' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'assigned_roles' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => 'all',
            ],
            'last_synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'sync_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'IDLE',
            ],
            'sync_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->addKey('status');
        $this->forge->createTable('mailbox_connections', true);

        // 2. mail_message_index
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'mailbox_resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'message_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'folder' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'INBOX',
            ],
            'thread_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'internet_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'in_reply_to' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'references_header' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'from_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'from_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'to_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'cc_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'bcc_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reply_to' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'snippet' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'body_html' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'body_text' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'has_attachments' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'attachments_meta' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_read' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_flagged' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_draft' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'general',
            ],
            'recruitment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'recruitment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'internal_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'assigned_to' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'message_date' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['mailbox_resource_id', 'folder', 'message_uid']);
        $this->forge->addKey('folder');
        $this->forge->addKey('message_date');
        $this->forge->addKey('from_email');
        $this->forge->addKey('is_read');
        $this->forge->addKey('category');
        $this->forge->addKey(['recruitment_type', 'recruitment_id']);
        $this->forge->createTable('mail_message_index', true);

        // 3. mail_local_drafts
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admin_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'mailbox_resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'to_recipients' => [
                'type' => 'TEXT',
            ],
            'cc_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'bcc_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'body_html' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'body_text' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'attachments_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'in_reply_to' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'thread_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'general',
            ],
            'recruitment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'recruitment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('admin_user_id');
        $this->forge->addKey('mailbox_resource_id');
        $this->forge->createTable('mail_local_drafts', true);

        // 4. mail_recruitment_links
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'mailbox_resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'message_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'folder' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'INBOX',
            ],
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['application', 'candidate', 'resume_submission', 'employer_enquiry', 'contact_enquiry'],
            ],
            'entity_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey(['mailbox_resource_id', 'message_uid']);
        $this->forge->createTable('mail_recruitment_links', true);

        // 5. mail_webhook_events
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'event_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'mailbox_resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'payload_json' => [
                'type' => 'MEDIUMTEXT',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['PENDING', 'PROCESSED', 'FAILED', 'IGNORED'],
                'default'    => 'PENDING',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'processed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('mail_webhook_events', true);

        // 6. mail_activity_logs
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admin_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'mailbox_resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'message_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'details_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['admin_user_id', 'action']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('mail_activity_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('mail_activity_logs', true);
        $this->forge->dropTable('mail_webhook_events', true);
        $this->forge->dropTable('mail_recruitment_links', true);
        $this->forge->dropTable('mail_local_drafts', true);
        $this->forge->dropTable('mail_message_index', true);
        $this->forge->dropTable('mailbox_connections', true);
    }
}

