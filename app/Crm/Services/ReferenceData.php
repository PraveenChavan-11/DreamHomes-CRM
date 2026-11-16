<?php

namespace Crm\Services;

/** Idempotent seeding of roles, permissions, transitions, lookups and templates. Contains NO user accounts and NO secrets. */
final class ReferenceData
{
    /** code => [label, category] */
    public const PERMISSIONS = [
        'dashboard.view'            => ['View dashboard', 'dashboard'],
        'customers.view'            => ['View customers', 'customers'],
        'customers.create'          => ['Create customers', 'customers'],
        'customers.edit'            => ['Edit customers', 'customers'],
        'customers.deactivate'      => ['Deactivate customers', 'customers'],
        'customers.import'          => ['Import customers/enquiries', 'customers'],
        'customers.export'          => ['Export customers', 'customers'],
        'enquiries.view'            => ['View enquiries', 'enquiries'],
        'enquiries.create'          => ['Create enquiries', 'enquiries'],
        'enquiries.edit'            => ['Edit enquiries / change status', 'enquiries'],
        'enquiries.assign'          => ['Assign enquiries', 'enquiries'],
        'enquiries.reassign'        => ['Reassign enquiries', 'enquiries'],
        'enquiries.close'           => ['Mark won / lost / hold', 'enquiries'],
        'enquiries.reopen'          => ['Reopen closed enquiries', 'enquiries'],
        'followups.manage'          => ['Manage follow-ups & activities', 'activity'],
        'visits.manage'             => ['Manage site visits & measurements', 'activity'],
        'products.view'             => ['View products', 'catalogue'],
        'products.manage'           => ['Manage products & pricing', 'catalogue'],
        'quotations.view'           => ['View quotations', 'quotations'],
        'quotations.create'         => ['Create / revise quotations', 'quotations'],
        'quotations.send'           => ['Send quotations', 'quotations'],
        'negotiations.create'       => ['Create negotiation / discount requests', 'negotiations'],
        'discounts.approve_manager' => ['Approve discounts (manager level)', 'negotiations'],
        'discounts.approve_admin'   => ['Approve discounts (admin level)', 'negotiations'],
        'orders.view'               => ['View sales orders', 'orders'],
        'orders.manage'             => ['Create / manage sales orders', 'orders'],
        'history.view'              => ['View sales history', 'history'],
        'reports.view'              => ['View reports', 'reports'],
        'reports.export'            => ['Export reports', 'reports'],
        'users.manage'              => ['Manage users', 'admin'],
        'teams.manage'              => ['Manage teams', 'admin'],
        'settings.manage'           => ['Manage CRM configuration', 'admin'],
        'audit.view'                => ['View audit history', 'admin'],
        'emails.view'               => ['View email log', 'communication'],
        'emails.send'               => ['Send manual emails', 'communication'],
        'leads.view'                => ['View leads pipeline', 'leads'],
        'leads.manage'              => ['Manage leads & conversion', 'leads'],
        'projects.view'             => ['View customer projects', 'projects'],
        'projects.manage'           => ['Manage customer projects', 'projects'],
        'payments.view'             => ['View payments & receipts', 'finance'],
        'payments.manage'           => ['Manage payments & receipts', 'finance'],
        'deliveries.view'           => ['View delivery dispatches & challans', 'fulfilment'],
        'deliveries.manage'         => ['Manage deliveries & dispatch', 'fulfilment'],
        'installations.view'        => ['View installation schedules', 'fulfilment'],
        'installations.manage'      => ['Manage installations & sign-offs', 'fulfilment'],
        'service.view'              => ['View after-sales service tickets', 'service'],
        'service.manage'            => ['Manage after-sales & complaints', 'service'],
        'masters.view'              => ['View master data catalogue', 'masters'],
        'masters.manage'            => ['Manage CRM master data', 'masters'],
        'demo.reset'                => ['Reset demo data', 'admin'],
    ];

    /** role slug => [name, description, data scope, permission codes|'*'] */
    public const ROLES = [
        'super_admin'     => ['Super Admin', 'Complete access', 'all', '*'],
        'admin'           => ['Admin', 'Manages configuration, customers, users, products, sales and reports', 'all', '*'],
        'sales_manager'   => ['Sales Manager', 'Runs team pipeline, approves discounts within limit', 'team', [
            'dashboard.view', 'customers.view', 'customers.create', 'customers.edit', 'customers.export', 'customers.import',
            'leads.view', 'leads.manage', 'projects.view', 'projects.manage',
            'enquiries.view', 'enquiries.create', 'enquiries.edit', 'enquiries.assign', 'enquiries.reassign', 'enquiries.close', 'enquiries.reopen',
            'followups.manage', 'visits.manage', 'products.view', 'quotations.view', 'quotations.create', 'quotations.send',
            'negotiations.create', 'discounts.approve_manager', 'orders.view', 'orders.manage',
            'payments.view', 'payments.manage', 'deliveries.view', 'deliveries.manage', 'installations.view', 'installations.manage',
            'service.view', 'service.manage', 'masters.view', 'history.view', 'reports.view', 'reports.export', 'emails.view', 'emails.send',
        ]],
        'sales_executive' => ['Sales Executive', 'Works own enquiries, follow-ups, visits and quotations', 'own', [
            'dashboard.view', 'customers.view', 'customers.create', 'customers.edit',
            'leads.view', 'leads.manage', 'projects.view',
            'enquiries.view', 'enquiries.create', 'enquiries.edit', 'enquiries.close',
            'followups.manage', 'visits.manage', 'products.view', 'quotations.view', 'quotations.create', 'quotations.send',
            'negotiations.create', 'orders.view', 'payments.view', 'deliveries.view', 'installations.view', 'service.view',
            'history.view', 'emails.view', 'emails.send',
        ]],
        'management'      => ['Management (Read-only)', 'Consolidated read-only visibility', 'all', [
            'dashboard.view', 'customers.view', 'customers.export', 'leads.view', 'projects.view',
            'enquiries.view', 'products.view', 'quotations.view', 'orders.view', 'payments.view',
            'deliveries.view', 'installations.view', 'service.view', 'masters.view',
            'history.view', 'reports.view', 'reports.export', 'audit.view', 'emails.view',
        ]],
    ];

    /** @var list<array{string,string,?string,int}> from,to,permission,requires_reason */
    public const ENQUIRY_TRANSITIONS = [
        ['new', 'assigned', 'enquiries.assign', 0],
        ['assigned', 'contacted', 'enquiries.edit', 0],
        ['contacted', 'qualified', 'enquiries.edit', 0],
        ['contacted', 'site_visit', 'enquiries.edit', 0],
        ['contacted', 'quotation_preparation', 'enquiries.edit', 0],
        ['qualified', 'site_visit', 'enquiries.edit', 0],
        ['qualified', 'quotation_preparation', 'enquiries.edit', 0],
        ['site_visit', 'quotation_preparation', 'enquiries.edit', 0],
        ['quotation_preparation', 'quotation_sent', 'enquiries.edit', 0],
        ['quotation_sent', 'negotiation', 'enquiries.edit', 0],
        ['quotation_sent', 'quotation_preparation', 'enquiries.edit', 0],
        ['negotiation', 'quotation_preparation', 'enquiries.edit', 0],
        ['negotiation', 'quotation_sent', 'enquiries.edit', 0],
        ['quotation_preparation', 'won', 'enquiries.close', 0],
        ['quotation_sent', 'won', 'enquiries.close', 0],
        ['negotiation', 'won', 'enquiries.close', 0],
        ['new', 'lost', 'enquiries.close', 1], ['assigned', 'lost', 'enquiries.close', 1], ['contacted', 'lost', 'enquiries.close', 1],
        ['qualified', 'lost', 'enquiries.close', 1], ['site_visit', 'lost', 'enquiries.close', 1], ['quotation_preparation', 'lost', 'enquiries.close', 1],
        ['quotation_sent', 'lost', 'enquiries.close', 1], ['negotiation', 'lost', 'enquiries.close', 1],
        ['new', 'on_hold', 'enquiries.close', 1], ['assigned', 'on_hold', 'enquiries.close', 1], ['contacted', 'on_hold', 'enquiries.close', 1],
        ['qualified', 'on_hold', 'enquiries.close', 1], ['site_visit', 'on_hold', 'enquiries.close', 1], ['quotation_preparation', 'on_hold', 'enquiries.close', 1],
        ['quotation_sent', 'on_hold', 'enquiries.close', 1], ['negotiation', 'on_hold', 'enquiries.close', 1],
        ['on_hold', 'contacted', 'enquiries.edit', 0],
        ['lost', 'contacted', 'enquiries.reopen', 1],
        ['won', 'negotiation', 'enquiries.reopen', 1],
    ];

    public const LEAD_SOURCES = [
        'manual' => 'Manual', 'website' => 'Website', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'walk_in' => 'Walk-in', 'reference' => 'Reference',
        'architect' => 'Architect', 'builder' => 'Builder', 'advertisement' => 'Advertisement', 'campaign' => 'Campaign', 'excel_import' => 'Excel Import',
    ];

    public const LOST_REASONS = ['Price too high', 'Chose a competitor', 'Project postponed or cancelled', 'No response from customer', 'Requirement not serviceable', 'Other'];

    public static function seedAll(): void
    {
        Crm::tx(static function ($db) {
            self::seedRbac($db);
            self::seedTransitions($db);
            foreach (self::LEAD_SOURCES as $code => $name) {
                $db->query('INSERT IGNORE INTO crm_lead_sources (code, name) VALUES (?,?)', [$code, $name]);
            }
            foreach (self::LOST_REASONS as $r) {
                $db->query('INSERT IGNORE INTO crm_lost_reasons (name) VALUES (?)', [$r]);
            }
            foreach ([['GST 18%', 18, 1], ['GST 12%', 12, 0], ['GST 5%', 5, 0], ['No tax', 0, 0]] as [$n, $r, $d]) {
                $db->query('INSERT IGNORE INTO crm_tax_rates (name, rate, is_default) VALUES (?,?,?)', [$n, $r, $d]);
            }
            foreach (['Windows', 'Bathrooms', 'Modular Kitchens', 'Other Products & Services'] as $i => $c) {
                $db->query('INSERT IGNORE INTO crm_product_categories (name, sort_order) VALUES (?,?)', [$c, $i + 1]);
            }
            foreach (NumberSequenceService::DEFAULTS as $k => [$p, $pad]) {
                $db->query('INSERT IGNORE INTO crm_number_sequences (seq_key, prefix, next_value, pad_length) VALUES (?,?,1,?)', [$k, $p, $pad]);
            }
            foreach ([['Installation charges', 'fixed', 0], ['Transportation charges', 'fixed', 0]] as [$n, $t, $v]) {
                $db->query('INSERT IGNORE INTO crm_additional_charges (name, calc_type, value) VALUES (?,?,?)', [$n, $t, $v]);
            }
            foreach ([['priority', 'eq', 'urgent', 20], ['priority', 'eq', 'high', 10], ['est_budget', 'gte', '500000', 15], ['est_budget', 'gte', '200000', 8], ['source_channel', 'eq', 'reference', 10], ['source_channel', 'eq', 'architect', 10]] as [$f, $o, $m, $p]) {
                if (! $db->table('crm_scoring_rules')->where(['field_name' => $f, 'operator' => $o, 'match_value' => $m])->countAllResults()) {
                    $db->table('crm_scoring_rules')->insert(['field_name' => $f, 'operator' => $o, 'match_value' => $m, 'points' => $p]);
                }
            }
            if (! $db->table('crm_assignment_rules')->countAllResults()) {
                $db->table('crm_assignment_rules')->insert(['priority' => 100, 'mode' => 'round_robin']);
            }
            Settings::seedDefaults();
            self::seedEmailTemplates($db);
            MasterService::seedDefaultMasters();
        });
    }

    private static function seedRbac($db): void
    {
        foreach (self::PERMISSIONS as $code => [$name, $cat]) {
            $db->query('INSERT INTO permissions (code, name, category) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name), category = VALUES(category)', [$code, $name, $cat]);
        }
        $permIds = array_column($db->table('permissions')->select('id, code')->get()->getResultArray(), 'id', 'code');
        foreach (self::ROLES as $slug => [$name, $desc, , $perms]) {
            $db->query('INSERT INTO roles (slug, name, description) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)', [$slug, $name, $desc]);
            $roleId = (int) $db->table('roles')->where('slug', $slug)->get()->getRow()->id;
            $codes  = $perms === '*' ? array_keys(self::PERMISSIONS) : $perms;
            foreach ($codes as $c) {
                $db->query('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)', [$roleId, $permIds[$c]]);
            }
        }
    }

    private static function seedTransitions($db): void
    {
        foreach (self::ENQUIRY_TRANSITIONS as [$f, $t, $p, $r]) {
            $db->query('INSERT IGNORE INTO crm_status_transitions (entity, from_status, to_status, required_permission, requires_reason) VALUES (?,?,?,?,?)', ['enquiry', $f, $t, $p, $r]);
        }
        $active = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation'];
        foreach ($active as $f) {
            foreach ($active as $t) {
                if ($f !== $t) {
                    $db->query('INSERT IGNORE INTO crm_status_transitions (entity, from_status, to_status, required_permission, requires_reason) VALUES (?,?,?,?,?)', ['enquiry', $f, $t, 'enquiries.edit', 0]);
                }
            }
        }
    }

    private static function seedEmailTemplates($db): void
    {
        $t = [
            ['enquiry_ack', 'Enquiry acknowledgement', 'We received your enquiry {{enquiry_no}}', '<p>Dear {{customer_name}},</p><p>Thank you for contacting {{company_name}}. Your enquiry <b>{{enquiry_no}}</b> has been registered and {{salesperson}} will be in touch shortly.</p>'],
            ['quotation_sent', 'Quotation email', 'Quotation {{quotation_no}} from {{company_name}}', '<p>Dear {{customer_name}},</p><p>Please find attached quotation <b>{{quotation_no}}</b> (version {{version_no}}) for {{grand_total}}, valid until {{valid_until}}.</p><p>Regards,<br>{{salesperson}}<br>{{company_name}}</p>'],
            ['followup', 'Follow-up', 'Following up on {{enquiry_no}}', '<p>Dear {{customer_name}},</p><p>{{message}}</p><p>Regards,<br>{{salesperson}}</p>'],
            ['negotiation_update', 'Negotiation update', 'Update on quotation {{quotation_no}}', '<p>Dear {{customer_name}},</p><p>We have an update regarding your request on quotation {{quotation_no}}: {{message}}</p>'],
            ['order_confirmation', 'Order confirmation', 'Order {{order_no}} confirmed', '<p>Dear {{customer_name}},</p><p>Your order <b>{{order_no}}</b> worth {{grand_total}} has been confirmed.</p><p>Regards,<br>{{company_name}}</p>'],
        ];
        foreach ($t as [$code, $name, $subj, $body]) {
            $db->query('INSERT IGNORE INTO crm_email_templates (code, name, subject, body_html, variables) VALUES (?,?,?,?,?)', [$code, $name, $subj, $body, 'customer_name,enquiry_no,quotation_no,order_no,salesperson,company_name,grand_total,valid_until,version_no,message']);
        }
    }
}
