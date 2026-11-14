<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEngagementCentreTables extends Migration
{
    public function up()
    {
        // 1. newsletter_subscribers
        if (!$this->db->tableExists('newsletter_subscribers')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'unique'     => true,
                ],
                'first_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['unverified', 'subscribed', 'unsubscribed', 'bounced'],
                    'default'    => 'unverified',
                ],
                'verification_token_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'verification_expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'unsubscribe_token_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'consent_given' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'consent_timestamp' => [
                    'type' => 'DATETIME',
                ],
                'consent_source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'web_form',
                ],
                'verified_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'unsubscribed_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('status');
            $this->forge->addKey('verification_token_hash');
            $this->forge->addKey('unsubscribe_token_hash');
            $this->forge->createTable('newsletter_subscribers', true);
        }

        // 2. newsletter_preferences
        if (!$this->db->tableExists('newsletter_preferences')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'subscriber_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'is_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['subscriber_id', 'category']);
            $this->forge->addForeignKey('subscriber_id', 'newsletter_subscribers', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('newsletter_preferences', true);
        }

        // 3. newsletter_activity_logs
        if (!$this->db->tableExists('newsletter_activity_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'subscriber_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'activity_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'activity_timestamp' => [
                    'type' => 'DATETIME',
                ],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'metadata' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('subscriber_id');
            $this->forge->addKey('activity_type');
            $this->forge->addForeignKey('subscriber_id', 'newsletter_subscribers', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('newsletter_activity_logs', true);
        }

        // 4. announcements
        if (!$this->db->tableExists('announcements')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'internal_description' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                ],
                'public_heading' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'public_content' => [
                    'type' => 'TEXT',
                ],
                'image_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                ],
                'cta_text' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'cta_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                ],
                'announcement_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'company_announcement',
                ],
                'display_format' => [
                    'type'       => 'ENUM',
                    'constraint' => ['center_modal', 'top_banner', 'bottom_banner', 'inline_homepage', 'newsletter_popup'],
                    'default'    => 'center_modal',
                ],
                'start_date' => [
                    'type' => 'DATE',
                ],
                'end_date' => [
                    'type' => 'DATE',
                ],
                'start_time' => [
                    'type'    => 'TIME',
                    'default' => '00:00:00',
                ],
                'end_time' => [
                    'type'    => 'TIME',
                    'default' => '23:59:59',
                ],
                'timezone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'Asia/Kolkata',
                ],
                'audience' => [
                    'type'       => 'ENUM',
                    'constraint' => ['everyone', 'candidates', 'employers', 'homepage', 'insights', 'selected_pages', 'new_visitors', 'returning_visitors'],
                    'default'    => 'everyone',
                ],
                'target_pages' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'frequency' => [
                    'type'       => 'ENUM',
                    'constraint' => ['every_visit', 'once_per_session', 'once_per_day', 'once_7_days', 'once_30_days', 'until_dismissed'],
                    'default'    => 'once_per_session',
                ],
                'priority' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['DRAFT', 'SCHEDULED', 'ACTIVE', 'PAUSED', 'EXPIRED', 'ARCHIVED'],
                    'default'    => 'DRAFT',
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
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
            $this->forge->addKey('status');
            $this->forge->addKey(['start_date', 'end_date']);
            $this->forge->addKey('priority');
            $this->forge->createTable('announcements', true);
        }

        // 5. announcement_events
        if (!$this->db->tableExists('announcement_events')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'announcement_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'event_type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['impression', 'click', 'dismissal', 'conversion'],
                ],
                'ip_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'user_agent_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'session_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('announcement_id');
            $this->forge->addKey('event_type');
            $this->forge->addKey('created_at');
            $this->forge->addForeignKey('announcement_id', 'announcements', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('announcement_events', true);
        }

        // 6. email_campaigns
        if (!$this->db->tableExists('email_campaigns')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'campaign_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'newsletter',
                ],
                'template_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'subject' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'preview_text' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'body_html' => [
                    'type' => 'MEDIUMTEXT',
                ],
                'body_text' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'audience_filter' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'all',
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['DRAFT', 'SCHEDULED', 'SENDING', 'SENT', 'PAUSED', 'CANCELLED'],
                    'default'    => 'DRAFT',
                ],
                'scheduled_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'total_recipients' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'sent_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'delivered_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'failed_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
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
            $this->forge->addKey('status');
            $this->forge->createTable('email_campaigns', true);
        }

        // 7. email_delivery_logs
        if (!$this->db->tableExists('email_delivery_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'campaign_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'subscriber_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'recipient_email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'subject' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'provider' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'smtp',
                ],
                'provider_message_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['QUEUED', 'SENT', 'DELIVERED', 'FAILED', 'BOUNCED'],
                    'default'    => 'QUEUED',
                ],
                'attempt_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'last_attempt' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('campaign_id');
            $this->forge->addKey('subscriber_id');
            $this->forge->addKey('recipient_email');
            $this->forge->addKey('status');
            $this->forge->createTable('email_delivery_logs', true);
        }

        // 8. job_alert_subscriptions
        if (!$this->db->tableExists('job_alert_subscriptions')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'job_category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'category_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'preferred_location' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'experience_level' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'employment_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'frequency' => [
                    'type'       => 'ENUM',
                    'constraint' => ['immediate', 'daily', 'weekly'],
                    'default'    => 'weekly',
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['active', 'unsubscribed'],
                    'default'    => 'active',
                ],
                'token_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'last_alert_sent_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('token_hash');
            $this->forge->createTable('job_alert_subscriptions', true);
        }

        // 9. cookie_consent_logs
        if (!$this->db->tableExists('cookie_consent_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'consent_uuid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'necessary' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'analytics' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'marketing' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'ip_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'user_agent_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('consent_uuid');
            $this->forge->addKey('created_at');
            $this->forge->createTable('cookie_consent_logs', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('cookie_consent_logs', true);
        $this->forge->dropTable('job_alert_subscriptions', true);
        $this->forge->dropTable('email_delivery_logs', true);
        $this->forge->dropTable('email_campaigns', true);
        $this->forge->dropTable('announcement_events', true);
        $this->forge->dropTable('announcements', true);
        $this->forge->dropTable('newsletter_activity_logs', true);
        $this->forge->dropTable('newsletter_preferences', true);
        $this->forge->dropTable('newsletter_subscribers', true);
    }
}
