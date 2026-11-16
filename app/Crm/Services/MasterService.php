<?php

namespace Crm\Services;

/**
 * Enterprise Master Data Management Service
 * Manages all 5 Master groups (Organisation, Customer, Product, Sales, System)
 * with unified CRUD, duplicate detection, dependency checks, audit logging, and export.
 */
final class MasterService
{
    /** Definition of all master catalog categories and their backing engines */
    public const MASTER_GROUPS = [
        'organisation' => [
            'label' => 'Organisation Masters',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'items' => [
                'company_profile'   => ['name' => 'Company Profile', 'mode' => 'settings', 'desc' => 'Core company entity details, branding and GSTIN'],
                'branches'          => ['name' => 'Branches', 'mode' => 'records', 'desc' => 'Office, showroom and warehouse locations'],
                'departments'       => ['name' => 'Departments', 'mode' => 'records', 'desc' => 'Functional business units (Sales, Design, Projects)'],
                'teams'             => ['name' => 'Teams', 'mode' => 'native', 'table' => 'crm_teams', 'desc' => 'Sales and execution teams'],
                'employees'         => ['name' => 'Employees', 'mode' => 'native', 'table' => 'admin_users', 'desc' => 'Team members, roles and access profiles'],
                'sales_territories' => ['name' => 'Sales Territories', 'mode' => 'native', 'table' => 'crm_territories', 'desc' => 'Geographic territories and PIN code mappings'],
                'sales_channels'    => ['name' => 'Sales Channels', 'mode' => 'records', 'desc' => 'Direct, Architect, Builder, Referral, Dealer channels'],
                'lead_sources'      => ['name' => 'Lead Sources', 'mode' => 'native', 'table' => 'crm_lead_sources', 'desc' => 'Inbound acquisition channels and campaigns'],
            ],
        ],
        'customer' => [
            'label' => 'Customer Masters',
            'icon' => 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zM2 20v-2a4 4 0 014-4h6a4 4 0 014 4v2',
            'items' => [
                'customer_types'        => ['name' => 'Customer Types', 'mode' => 'records', 'desc' => 'Individual, Corporate, Builder, Architect, Contractor, Dealer'],
                'customer_categories'   => ['name' => 'Customer Categories', 'mode' => 'native', 'table' => 'crm_categories', 'desc' => 'Residential, Commercial, Hospitality classifications'],
                'customer_segments'     => ['name' => 'Customer Segments', 'mode' => 'records', 'desc' => 'Luxury, Premium, Standard, Volume Builder segments'],
                'customer_groups'       => ['name' => 'Customer Groups', 'mode' => 'records', 'desc' => 'Strategic accounts and umbrella business entities'],
                'customer_tags'         => ['name' => 'Customer Tags', 'mode' => 'native', 'table' => 'crm_tags', 'desc' => 'Quick labels for customer prioritization'],
                'customer_contacts'     => ['name' => 'Customer Contacts', 'mode' => 'native', 'table' => 'crm_customer_contacts', 'desc' => 'Multiple stakeholder contacts per customer'],
                'customer_addresses'    => ['name' => 'Customer Addresses', 'mode' => 'native', 'table' => 'crm_customer_addresses', 'desc' => 'Site, billing and delivery addresses'],
                'customer_credit_terms' => ['name' => 'Customer Credit Terms', 'mode' => 'records', 'desc' => 'Payment terms (Immediate, Net 15, Net 30, Milestone-based)'],
            ],
        ],
        'product' => [
            'label' => 'Product Masters',
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'items' => [
                'product_categories'    => ['name' => 'Product Categories', 'mode' => 'native', 'table' => 'crm_product_categories', 'desc' => 'Top-level lines: Windows, Bathrooms, Modular Kitchens, Appliances'],
                'product_subcategories' => ['name' => 'Product Subcategories', 'mode' => 'native', 'table' => 'crm_product_subcategories', 'desc' => 'Specialized lines: Sliding, Casement, Basins, Faucets, Cabinets, Hobs'],
                'product_brands'        => ['name' => 'Product Brands', 'mode' => 'native', 'table' => 'crm_product_brands', 'desc' => 'Vitrum, Saint-Gobain, Grohe, American Standard, Mr. Kitchen, Häfele'],
                'product_types'         => ['name' => 'Product Types', 'mode' => 'native', 'table' => 'crm_product_types', 'desc' => 'Aluminium Sliding, Slimline, UPVC, Built-in Ovens, Wall Hung Toilets'],
                'products'              => ['name' => 'Products / SKUs', 'mode' => 'native', 'table' => 'crm_products', 'desc' => 'Master products with base prices, HSN, and specifications'],
                'product_variants'      => ['name' => 'Product Variants', 'mode' => 'native', 'table' => 'crm_product_variants', 'desc' => 'Track/panel, glass type, finishes, dimensions, and variant SKUs'],
                'product_attributes'    => ['name' => 'Product Attributes', 'mode' => 'records', 'desc' => 'Configurable attributes: Frame material, Profile series, Glass type'],
                'product_specifications'=> ['name' => 'Product Specifications', 'mode' => 'records', 'desc' => 'Technical specifications and testing certifications'],
                'product_finishes'      => ['name' => 'Product Finishes', 'mode' => 'records', 'desc' => 'Anodized, Powder Coated, Wood Grain, Chrome, Matte Black, Satin'],
                'product_colours'       => ['name' => 'Product Colours', 'mode' => 'records', 'desc' => 'RAL White, Anthracite Grey, Champagne, Walnut, Teak'],
                'product_dimensions'    => ['name' => 'Product Dimensions', 'mode' => 'records', 'desc' => 'Standard modular sizes and dimension rules'],
                'units_of_measurement'  => ['name' => 'Units of Measurement', 'mode' => 'records', 'desc' => 'Sq.Ft, Sq.M, RFT, Nos, Sets, Modules'],
                'price_lists'           => ['name' => 'Price Lists', 'mode' => 'records', 'desc' => 'Retail MRP, Architect Special, Builder Project, Dealer Net'],
                'tax_rates'             => ['name' => 'Tax Rates', 'mode' => 'native', 'table' => 'crm_tax_rates', 'desc' => 'GST brackets: 18%, 12%, 5%, 0%'],
                'discounts'             => ['name' => 'Discounts & Limits', 'mode' => 'records', 'desc' => 'Salesperson, Manager and Director authorized discount thresholds'],
            ],
        ],
        'sales' => [
            'label' => 'Sales Masters',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'items' => [
                'lead_sources'          => ['name' => 'Lead Sources', 'mode' => 'native', 'table' => 'crm_lead_sources', 'desc' => 'Inbound enquiry channels'],
                'lead_types'            => ['name' => 'Lead Types', 'mode' => 'records', 'desc' => 'Hot, Warm, Cold, Enterprise, HNI, Tender'],
                'enquiry_types'         => ['name' => 'Enquiry Types', 'mode' => 'records', 'desc' => 'New Villa, Renovation, Commercial Project, Multi-unit Apartment'],
                'sales_stages'          => ['name' => 'Sales Stages', 'mode' => 'records', 'desc' => '13 pipeline stages from New to Site Visit, Quotation, Order, Won'],
                'followup_types'        => ['name' => 'Follow-up Types', 'mode' => 'records', 'desc' => 'Phone Call, Site Meeting, Showroom Visit, WhatsApp, Email'],
                'lost_reasons'          => ['name' => 'Lost Reasons', 'mode' => 'native', 'table' => 'crm_lost_reasons', 'desc' => 'Price, Competitor, Timeline, Postponed'],
                'qualification_criteria'=> ['name' => 'Lead Qualification Criteria', 'mode' => 'records', 'desc' => 'BANT: Budget, Authority, Need, Timeline validation checks'],
                'payment_terms'         => ['name' => 'Payment Terms', 'mode' => 'records', 'desc' => '50% Advance + 40% on Dispatch + 10% on Installation'],
                'delivery_terms'        => ['name' => 'Delivery Terms', 'mode' => 'records', 'desc' => 'Ex-factory, Site Delivered Unloaded, 3-4 Weeks from Measurement'],
                'installation_types'    => ['name' => 'Installation Types', 'mode' => 'records', 'desc' => 'Standard Installation, High-rise Sub-frame, Wet Glazing'],
                'warranty_types'        => ['name' => 'Warranty Types', 'mode' => 'records', 'desc' => '10-Year Profile Warranty, 2-Year Hardware, Comprehensive'],
                'cancellation_reasons'  => ['name' => 'Cancellation Reasons', 'mode' => 'records', 'desc' => 'Client Budget Cut, Site Redesign, Project Scrapped'],
            ],
        ],
        'system' => [
            'label' => 'System Masters',
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'items' => [
                'document_numbering'    => ['name' => 'Document Numbering', 'mode' => 'native', 'table' => 'crm_number_sequences', 'desc' => 'Auto-increment prefixes and zero-padding (CUS, ENQ, QT, SO, PAY)'],
                'notification_templates'=> ['name' => 'Notification Templates', 'mode' => 'records', 'desc' => 'In-app alert templates and escalation alerts'],
                'email_templates'       => ['name' => 'Email Templates', 'mode' => 'native', 'table' => 'crm_email_templates', 'desc' => 'Branded transaction email templates with dynamic tokens'],
                'pdf_templates'         => ['name' => 'PDF Templates', 'mode' => 'records', 'desc' => 'Commercial A4 Quotation and Delivery Challan layouts'],
                'user_roles'            => ['name' => 'User Roles', 'mode' => 'native', 'table' => 'roles', 'desc' => 'RBAC Roles: Super Admin, Admin, Manager, Executive, Management'],
                'permissions'           => ['name' => 'Permissions', 'mode' => 'native', 'table' => 'permissions', 'desc' => 'Granular permission keys and security gates'],
                'workflow_config'       => ['name' => 'Workflow Configuration', 'mode' => 'native', 'table' => 'crm_status_transitions', 'desc' => 'Controlled state transitions and approval chains'],
            ],
        ],
    ];

    /** Find metadata definition for a master key */
    public static function getMasterMeta(string $typeKey): ?array
    {
        foreach (self::MASTER_GROUPS as $groupKey => $group) {
            if (isset($group['items'][$typeKey])) {
                $item = $group['items'][$typeKey];
                $item['group_key'] = $groupKey;
                $item['group_label'] = $group['label'];
                $item['type_key'] = $typeKey;
                return $item;
            }
        }
        return null;
    }

    /** Query master items with pagination, search, filter and sorting */
    public static function listItems(string $typeKey, array $params = []): array
    {
        $meta = self::getMasterMeta($typeKey) ?? throw new CrmException("Unknown master type: {$typeKey}");
        $db = Crm::db();
        $q = trim((string) ($params['q'] ?? ''));
        $status = $params['status'] ?? 'all';
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        if ($meta['mode'] === 'records') {
            $builder = $db->table('crm_master_records')->where('type_code', $typeKey);
            if ($status === 'active') {
                $builder->where('is_active', 1);
            } elseif ($status === 'inactive') {
                $builder->where('is_active', 0);
            }
            if ($q !== '') {
                $builder->groupStart()
                    ->like('name', $q)
                    ->orLike('code', $q)
                    ->orLike('description', $q)
                    ->groupEnd();
            }
            $total = $builder->countAllResults(false);
            $rows = $builder->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get($limit, $offset)->getResultArray();
            return ['meta' => $meta, 'rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit];
        }

        if ($meta['mode'] === 'native') {
            $table = $meta['table'];
            $builder = $db->table($table);
            $fields = $db->getFieldNames($table);

            if (in_array('is_active', $fields, true)) {
                if ($status === 'active') { $builder->where('is_active', 1); }
                elseif ($status === 'inactive') { $builder->where('is_active', 0); }
            }

            if ($q !== '') {
                $builder->groupStart();
                $first = true;
                foreach (['name', 'code', 'slug', 'first_name', 'last_name', 'email', 'title', 'seq_key'] as $f) {
                    if (in_array($f, $fields, true)) {
                        if ($first) { $builder->like($f, $q); $first = false; }
                        else { $builder->orLike($f, $q); }
                    }
                }
                $builder->groupEnd();
            }

            $total = $builder->countAllResults(false);
            $orderCol = in_array('sort_order', $fields, true) ? 'sort_order' : (in_array('name', $fields, true) ? 'name' : $fields[0]);
            $rows = $builder->orderBy($orderCol, 'ASC')->get($limit, $offset)->getResultArray();
            return ['meta' => $meta, 'rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit];
        }

        // Settings mode (Company Profile)
        $keys = ['company.name', 'company.address', 'company.phone', 'company.email', 'company.gstin', 'company.state', 'company.website'];
        $rows = [];
        foreach ($keys as $k) {
            $rows[] = [
                'code' => $k,
                'name' => ucwords(str_replace(['company.', '_'], ['', ' '], $k)),
                'value' => Settings::get($k, ''),
                'description' => 'System branding and invoicing parameter',
                'is_active' => 1,
            ];
        }
        return ['meta' => $meta, 'rows' => $rows, 'total' => count($rows), 'page' => 1, 'limit' => 50];
    }

    /** Save or update a master record with duplicate check and audit */
    public static function saveRecord(string $typeKey, array $data, ?int $id = null, ?array $actor = null): array
    {
        $meta = self::getMasterMeta($typeKey) ?? throw new CrmException("Unknown master type: {$typeKey}");
        $db = Crm::db();
        $code = trim((string) ($data['code'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new CrmException('Master name is required.');
        }
        if ($code === '') {
            $code = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '_', substr($name, 0, 30)));
        }

        if ($meta['mode'] === 'records') {
            // Check duplicates
            $dup = $db->table('crm_master_records')
                ->where('type_code', $typeKey)
                ->where('code', $code)
                ->where('id !=', $id ?? 0)
                ->countAllResults();
            if ($dup > 0) {
                throw new CrmException("A record with code '{$code}' already exists in {$meta['name']}.");
            }

            $payload = [
                'type_code' => $typeKey,
                'code' => $code,
                'name' => $name,
                'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
                'description' => trim((string) ($data['description'] ?? '')),
                'metadata_json' => ! empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
                'updated_at' => Crm::now(),
            ];

            if ($id === null) {
                $payload['created_by'] = $actor['id'] ?? null;
                $payload['created_at'] = Crm::now();
                $db->table('crm_master_records')->insert($payload);
                $id = $db->insertID();
                AuditService::audit('master_created', 'crm_master_records', $id, [], $payload);
            } else {
                $old = $db->table('crm_master_records')->where('id', $id)->get()->getRowArray() ?? [];
                $db->table('crm_master_records')->where('id', $id)->update($payload);
                AuditService::audit('master_updated', 'crm_master_records', $id, $old, $payload);
            }
            return self::getRecord($typeKey, $id);
        }

        // Native table handler
        if ($meta['mode'] === 'native') {
            $table = $meta['table'];
            $fields = $db->getFieldNames($table);
            $payload = [];
            foreach (['name', 'code', 'description', 'sort_order', 'is_active', 'rate', 'website'] as $f) {
                if (in_array($f, $fields, true) && array_key_exists($f, $data)) {
                    $payload[$f] = $data[$f];
                }
            }
            if (in_array('updated_at', $fields, true)) {
                $payload['updated_at'] = Crm::now();
            }

            if ($id === null) {
                if (in_array('created_at', $fields, true)) {
                    $payload['created_at'] = Crm::now();
                }
                $db->table($table)->insert($payload);
                $id = $db->insertID();
                AuditService::audit('master_created', $table, $id, [], $payload);
            } else {
                $old = $db->table($table)->where('id', $id)->get()->getRowArray() ?? [];
                $db->table($table)->where('id', $id)->update($payload);
                AuditService::audit('master_updated', $table, $id, $old, $payload);
            }
            return (array) $db->table($table)->where('id', $id)->get()->getRowArray();
        }

        throw new CrmException("Direct editing for master mode '{$meta['mode']}' is handled through dedicated screen.");
    }

    /** Retrieve single record */
    public static function getRecord(string $typeKey, int $id): array
    {
        $meta = self::getMasterMeta($typeKey) ?? throw new CrmException("Unknown master type: {$typeKey}");
        $db = Crm::db();
        if ($meta['mode'] === 'records') {
            return (array) $db->table('crm_master_records')->where('id', $id)->where('type_code', $typeKey)->get()->getRowArray();
        }
        if ($meta['mode'] === 'native') {
            return (array) $db->table($meta['table'])->where('id', $id)->get()->getRowArray();
        }
        return [];
    }

    /** Toggle active/inactive status */
    public static function toggleActive(string $typeKey, int $id, ?array $actor = null): bool
    {
        $meta = self::getMasterMeta($typeKey) ?? throw new CrmException("Unknown master type: {$typeKey}");
        $db = Crm::db();
        $table = ($meta['mode'] === 'records') ? 'crm_master_records' : ($meta['table'] ?? null);
        if (! $table) {
            throw new CrmException('Active toggle not supported for this master type.');
        }

        $row = $db->table($table)->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Record not found.');
        $newActive = empty($row['is_active']) ? 1 : 0;

        // Dependency check if deactivating
        if ($newActive === 0) {
            self::checkDependencies($typeKey, $id, $row);
        }

        $db->table($table)->where('id', $id)->update(['is_active' => $newActive, 'updated_at' => Crm::now()]);
        AuditService::audit('master_status_toggled', $table, $id, ['is_active' => $row['is_active'] ?? 1], ['is_active' => $newActive]);
        return (bool) $newActive;
    }

    /** Validate whether record has active commercial dependencies before deactivation/deletion */
    public static function checkDependencies(string $typeKey, int $id, array $row): void
    {
        $db = Crm::db();
        // Check for product category dependencies
        if ($typeKey === 'product_categories') {
            $activeProducts = $db->table('crm_products')->where('category_id', $id)->where('is_active', 1)->countAllResults();
            if ($activeProducts > 0) {
                throw new CrmException("Cannot deactivate category: {$activeProducts} active products depend on it.");
            }
        }
        // Check for tax rate dependencies
        if ($typeKey === 'tax_rates') {
            $activeProducts = $db->table('crm_products')->where('tax_rate_id', $id)->where('is_active', 1)->countAllResults();
            if ($activeProducts > 0) {
                throw new CrmException("Cannot deactivate tax rate: {$activeProducts} active products are linked to it.");
            }
        }
        // Check for lead source dependencies
        if ($typeKey === 'lead_sources') {
            $activeEnquiries = $db->table('crm_enquiries')->where('source_id', $id)->whereNotIn('status', ['won', 'lost', 'cancelled'])->countAllResults();
            if ($activeEnquiries > 0) {
                throw new CrmException("Cannot deactivate lead source: {$activeEnquiries} open enquiries are associated with it.");
            }
        }
    }

    /** Export master items to CSV */
    public static function exportCsv(string $typeKey): string
    {
        $list = self::listItems($typeKey, ['limit' => 1000]);
        $rows = $list['rows'];
        $out = fopen('php://temp', 'r+');
        if (! empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    /** Idempotently seed default master records into crm_master_records and master types */
    public static function seedDefaultMasters(): int
    {
        $db = Crm::db();

        // 1. Seed Master Types
        if ($db->tableExists('crm_master_types')) {
            $order = 1;
            foreach (self::MASTER_GROUPS as $groupKey => $group) {
                foreach ($group['items'] as $itemCode => $item) {
                    $exists = $db->table('crm_master_types')->where('code', $itemCode)->countAllResults();
                    if (! $exists) {
                        $db->table('crm_master_types')->insert([
                            'group_name'  => $group['label'],
                            'code'        => $itemCode,
                            'name'        => $item['name'],
                            'description' => $item['desc'] ?? null,
                            'icon'        => $group['icon'] ?? null,
                            'sort_order'  => $order++,
                            'is_active'   => 1,
                            'created_at'  => date('Y-m-d H:i:s'),
                            'updated_at'  => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        $catalog = [
            'branches' => [
                ['BR-PUN-01', 'Pune Main Experience Centre & Flagship', 'Kothrud, Pune - Luxury fenestration & sanitaryware gallery'],
                ['BR-HIN-02', 'Hinjewadi Corporate Sales Branch', 'Hinjewadi Phase 1 - Commercial builder & architect liaison'],
                ['BR-WAK-03', 'Wakad Studio & Modular Display', 'Wakad Datta Mandir Road - Kitchens & wardrobes studio'],
                ['BR-CHK-04', 'Chakan Central Fabrication & Logistics Hub', 'MIDC Chakan - Window manufacturing & buffer storage'],
            ],
            'departments' => [
                ['DEP-SAL', 'Architectural Sales & Estimation', 'Direct client quoting, showroom walkthroughs and lead closing'],
                ['DEP-FEN', 'Fenestration & Facade Division', 'Vitrum & Saint-Gobain aluminium and UPVC engineered systems'],
                ['DEP-SAN', 'Luxury Bathrooms & Wellness', 'Grohe & American Standard premium bathroom fittings'],
                ['DEP-KIT', 'Modular Kitchens & Appliances', 'Mr. Kitchen modules and Häfele built-in appliances'],
                ['DEP-OPS', 'Site Operations & Logistics', 'Deliveries, sub-frame verification and site installations'],
                ['DEP-SRV', 'After-Sales Service & Warranty', 'Preventive checkups, repairs and spare parts management'],
            ],
            'sales_channels' => [
                ['CH-DIR', 'Direct Walk-in & Showroom Client', 'Direct residential homeowners visiting DHIS showrooms'],
                ['CH-ARC', 'Architect & Interior Designer Network', 'Commissioned luxury residential and penthouse projects'],
                ['CH-BLD', 'Builder & Developer Key Accounts', 'Bulk apartment windows and commercial facade contracts'],
                ['CH-CON', 'Civil Contractor & Fabricator Channel', 'Site referrals and subcontractor installations'],
                ['CH-WEB', 'Digital Marketing & Inbound Website', 'Leads originating from dhisgroup.in and social media'],
            ],
            'customer_types' => [
                ['CT-IND', 'Individual Homeowner', 'Bungalow, villa or apartment owner with custom requirements'],
                ['CT-CORP', 'Corporate & Commercial Client', 'Office fit-outs, tech parks and commercial establishments'],
                ['CT-BLD', 'Builder & Real Estate Developer', 'Multi-unit residential schemes and township projects'],
                ['CT-ARC', 'Architect & Interior Designer', 'Design consultancies specifying premium architectural materials'],
                ['CT-CON', 'General Contractor', 'Civil and MEP contractors handling site execution'],
                ['CT-DLR', 'Authorised Trade Dealer', 'B2B distributor for DHIS brand products'],
            ],
            'customer_segments' => [
                ['SEG-LUX', 'Luxury Residential (Villas & Penthouses)', 'Ultra high-ticket architectural fenestration and wellness'],
                ['SEG-PREM', 'Premium High-Rise Apartments', 'Quality-conscious 3BHK and 4BHK homeowners'],
                ['SEG-COMM', 'Commercial & Hospitality Spaces', 'Hotels, resorts, premium corporate offices and retail spaces'],
                ['SEG-BULK', 'Bulk Township & Builder Contracts', 'High volume standardised sliding window and door packages'],
            ],
            'customer_groups' => [
                ['GRP-PANCH', 'Panchshil Realty Projects', 'Luxury residential and commercial developments'],
                ['GRP-KOLTE', 'Kolte-Patil Developers', 'Integrated township and residential schemes'],
                ['GRP-ROHAN', 'Rohan Builders Group', 'Eco-friendly and sustainable architecture projects'],
                ['GRP-ARCH-ASSOC', 'Pune Architects Association', 'Affiliated certified interior and architectural consultants'],
                ['GRP-RETAIL-HNI', 'Direct HNI Homeowners', 'Independent luxury bungalow and villa owners'],
            ],
            'customer_credit_terms' => [
                ['CR-ADV', '100% Pre-Dispatch / Advance', 'Standard retail terms: advance before fabrication and delivery'],
                ['CR-MILE', 'Milestone Linked (50-40-10)', '50% with order, 40% before dispatch, 10% upon installation signoff'],
                ['CR-NET15', 'Net 15 Days from Invoice', 'Approved interior designer and contractor credit account'],
                ['CR-NET30', 'Net 30 Days from Invoice', 'Corporate and builder institutional accounts subject to PO'],
            ],
            'product_attributes' => [
                ['ATTR-MAT', 'Frame Material', 'High-tensile architectural aluminium alloy 6063-T6 or lead-free UPVC'],
                ['ATTR-SERIES', 'Profile Series', 'Vitrum Slimline 45mm, Heavy Duty 65mm or Thermal Break 75mm'],
                ['ATTR-GLASS', 'Glass Specification', 'Toughened Clear, Double Glazed DGU, Low-E Solar Acoustic, Frosted'],
                ['ATTR-TRACK', 'Track & Panel Configuration', '2-Track 2-Panel, 3-Track 3-Panel, 3-Track with Stainless Steel Mesh'],
                ['ATTR-HARDWARE', 'Hardware & Locks', 'European multi-point locking mechanism with flush pop-up handles'],
            ],
            'product_specifications' => [
                ['SPEC-WIND', 'Wind Load Resistance (Class 4)', 'Tested to withstand coastal and high-rise wind loads up to 2400 Pa'],
                ['SPEC-ACOUSTIC', 'Acoustic Sound Insulation (Rw 38 dB)', 'Acoustic glazing reducing ambient traffic noise by up to 38 decibels'],
                ['SPEC-WATER', 'Water Tightness (Class 9A)', 'No water infiltration up to 600 Pa driving rain test standards'],
                ['SPEC-CYCLES', 'Hardware Endurance (100,000 Cycles)', 'Heavy-duty rollers and friction stays cycle-tested for 25-year lifespan'],
            ],
            'product_finishes' => [
                ['FIN-ANOD-SILVER', 'Anodized Natural Silver', '25-micron natural satin anodized corrosion-resistant finish'],
                ['FIN-POWDER-MATTE', 'Architectural Powder Coated Matte', 'AkzoNobel Qualicoat Class 2 architectural powder finish'],
                ['FIN-PVDF-GREY', 'PVDF Matt Charcoal Grey', 'Fluoropolymer finish for harsh weather and UV resistance'],
                ['FIN-WOOD-SUBL', 'Royal Wood Grain Sublimation', 'Natural Italian timber aesthetic with aluminium structural durability'],
                ['FIN-CHROME-MIRROR', 'Mirror Chrome Plated', 'Multi-layer electroplated chrome for luxury Grohe bathroom brassware'],
                ['FIN-MATTE-BLACK', 'Velvet Matte Black', 'Electrophoretic scratch-resistant tactile matte black coating'],
            ],
            'product_colours' => [
                ['COL-RAL9010', 'Pure White (RAL 9010)', 'Crisp architectural brilliant white for contemporary spaces'],
                ['COL-RAL7016', 'Anthracite Grey (RAL 7016)', 'Deep architectural grey, top choice for modern luxury villas'],
                ['COL-RAL9005', 'Jet Black (RAL 9005)', 'High-contrast minimalist industrial black'],
                ['COL-CHAMPAGNE', 'Champagne Bronze', 'Warm metallic tone for bespoke facade and door accents'],
                ['COL-WALNUT', 'Royal Walnut Timber', 'Dark rich walnut wood grain finish for traditional homes'],
            ],
            'product_dimensions' => [
                ['DIM-STD-W1', 'Standard Window 4ft x 4ft (1200 x 1200 mm)', 'Bedroom and study 2-track sliding window format'],
                ['DIM-BALCONY-W2', 'Balcony Slider 7ft x 8ft (2100 x 2400 mm)', 'Living room floor-to-ceiling balcony slider with mosquito mesh'],
                ['DIM-FRENCH-D1', 'French Door 8ft x 9ft (2400 x 2700 mm)', 'Grand terrace 3-track sliding French door with toughened glass'],
                ['DIM-KIT-BASE', 'Kitchen Base Module (600 x 720 x 560 mm)', 'Standard European ergonomics base cabinet unit with Blum soft-close'],
            ],
            'units_of_measurement' => [
                ['UOM-SQFT', 'Square Feet (Sq.Ft)', 'Primary dimensional unit for architectural windows and facade glass'],
                ['UOM-SQM', 'Square Metres (Sq.M)', 'Metric unit for institutional and export architectural projects'],
                ['UOM-RFT', 'Running Feet (RFT)', 'Linear measurement for countertops, pelmets, and modular plinths'],
                ['UOM-NOS', 'Units / Numbers (Nos)', 'Item count for sanitary fixtures, sinks, faucets, and built-in appliances'],
                ['UOM-SET', 'Sets (Set)', 'Pre-packaged hardware assemblies, shower sets, and accessory kits'],
            ],
            'price_lists' => [
                ['PL-RETAIL', 'Retail Showroom MRP', 'Standard retail price for direct homeowners and walk-in clients'],
                ['PL-ARCH', 'Architect & Designer Preferred', '15% professional courtesy rebate for registered designers'],
                ['PL-BUILDER', 'Builder Project Volume Tier', 'Tiered volume pricing for multi-unit apartment developments'],
                ['PL-DEALER', 'Authorized Dealer Net', 'Dealer wholesale pricing at standard cost plus 12% margin'],
            ],
            'discounts' => [
                ['DISC-EXEC', 'Sales Executive Authority (0 - 5%)', 'Discretionary discount allowable without higher authorization'],
                ['DISC-MGR', 'Sales Manager Authority (5.01 - 15%)', 'Negotiation threshold requiring Sales Manager approval in CRM'],
                ['DISC-MGMT', 'Director / Management Authority (> 15%)', 'Strategic enterprise discount requiring Management signoff'],
            ],
            'lead_types' => [
                ['LT-HOT', 'Hot / Immediate Priority', 'Customer has immediate requirement, site ready, budget approved'],
                ['LT-WARM', 'Warm / Active Consideration', 'Civil work underway, material selection within 30 to 60 days'],
                ['LT-COLD', 'Cold / Early Concept Stage', 'Architectural drawings in progress, purchase expected in 6+ months'],
                ['LT-TENDER', 'Institutional Tender / RFP', 'Formal corporate or government tender with detailed BOQ specifications'],
            ],
            'enquiry_types' => [
                ['ET-VILLA', 'Full Villa Architectural Fenestration', 'Complete turnkey windows, doors, bathrooms, and kitchen for a villa'],
                ['ET-BATH-RENO', 'Luxury Bathroom Modernisation', 'Replacement and upgrade of existing sanitaryware and wellness fittings'],
                ['ET-KIT-COMPLETE', 'Modular Kitchen & Appliance Package', 'End-to-end design, cabinetry, quartz countertop, and built-in appliances'],
                ['ET-COMM', 'Commercial Glazing & Curtain Wall', 'Structural facade, shopfronts, and acoustic office partition glass'],
            ],
            'sales_stages' => [
                ['STG-NEW', 'New Inbound Lead', 'Uncontacted lead requiring initial salesperson engagement within 2 hours'],
                ['STG-QUALIFIED', 'Qualified Requirement', 'Customer scope, budget, and project location verified'],
                ['STG-VISIT', 'Site Measurement Scheduled', 'Technical site engineer assigned for millimeter-accurate dimensions'],
                ['STG-QUOTED', 'Commercial Quotation Issued', 'Formal A4 proposal sent to customer with brand specifications and pricing'],
                ['STG-NEGOTIATING', 'Price Negotiation & Discount Review', 'Commercial discount requested and processed through approval chain'],
                ['STG-ORDER', 'Sales Order Confirmed', 'Manufacturing advance received, production drawings released'],
                ['STG-DELIVERED', 'Site Delivered & Verified', 'Material dispatched with Delivery Challan and inspected at site'],
                ['STG-INSTALLED', 'Installation Completed & Signed', 'Final testing, silicone sealing, and customer sign-off achieved'],
            ],
            'followup_types' => [
                ['FU-CALL', 'Telephonic Discussion', 'Review proposal details, timeline, and commercial terms over phone'],
                ['FU-VISIT', 'Site Inspection & Measurement', 'Physical on-site consultation with customer and civil contractor'],
                ['FU-SHOWROOM', 'Showroom Experience Walkthrough', 'Customer hands-on demo of Vitrum sliding doors and Grohe showers'],
                ['FU-WHATSAPP', 'WhatsApp Quotation & Video Share', 'Sharing product videos, finish swatches, and PDF proposals'],
            ],
            'qualification_criteria' => [
                ['QC-BUDGET', 'Budget Alignment', 'Client budget aligns with premium architectural material category'],
                ['QC-DECISION', 'Decision Maker Identified', 'Direct access to homeowner, architect or authorized purchaser'],
                ['QC-TIMELINE', 'Timeline Feasibility', 'Project handover timeline within 30 to 90 fabrication window'],
                ['QC-SITE-READY', 'Site Readiness for Measurement', 'Plastering or opening framing completed for accurate laser measurement'],
            ],
            'payment_terms' => [
                ['PT-STD-504010', 'Standard Residential (50% - 40% - 10%)', '50% advance with order, 40% prior to dispatch, 10% upon installation'],
                ['PT-COMM-30402505', 'Commercial Project (30-40-25-5)', '30% advance, 40% material at site, 25% installation, 5% retention 90d'],
                ['PT-APPLIANCE', 'Appliances & Sanitaryware (100% Pre-Dispatch)', '100% payment before warehouse release for Häfele and Grohe products'],
            ],
            'delivery_terms' => [
                ['DT-PUNE-METRO', 'Site Delivery with Transit Protection (Pune Metro)', 'Includes protective foam wrapping, crate packaging, and insured transit'],
                ['DT-EX-WAREHOUSE', 'Ex-Warehouse Chakan Hub', 'Customer arranges dedicated transport from DHIS Chakan logistics facility'],
                ['DT-OUTSTATION', 'Outstation Freight Paid to Transporter Godown', 'Dispatched to nearest regional logistics hub with third-party freight'],
            ],
            'installation_types' => [
                ['INS-SUBFRAME', 'Galvanised Steel Sub-frame Installation', 'Pre-installed steel sub-frames ensuring millimeter-accurate opening geometry'],
                ['INS-DIRECT', 'Direct Masonry Anchor Fixing', 'Heavy-duty stainless steel anchor bolts fixed directly into RCC lintels'],
                ['INS-HIGHRISE', 'High-Rise Structural Facade Anchoring', 'Engineered brackets with seismic and wind-resistant structural anchoring'],
                ['INS-SEALANT', 'Silicone Weatherproofing & Wet Glazing', 'Dow Corning structural neutral silicone weather sealant application'],
            ],
            'warranty_types' => [
                ['WAR-10YR-PROFILE', '10-Year Profile Structural Warranty', 'Guarantees structural integrity against warping, cracking or oxidation'],
                ['WAR-5YR-HARDWARE', '5-Year European Mechanism Warranty', 'Covers rollers, multi-point locks, friction stays, and soft-close hinges'],
                ['WAR-10YR-COAT', '10-Year Surface Coating Guarantee', 'Guarantees against fading, peeling, or chalking under direct sunlight'],
                ['WAR-2YR-SERVICE', '2-Year Complimentary Preventive Maintenance', 'Includes bi-annual technician inspection, realignment, and lubrication'],
            ],
            'cancellation_reasons' => [
                ['CAN-BUDGET', 'Client Budget Constraints', 'Customer deferred project or reduced scope due to budget limitations'],
                ['CAN-COMPETITOR', 'Lost to Competitor', 'Order placed with competing local fabricator or retail dealer'],
                ['CAN-TIMELINE', 'Timeline Mismatch', 'Customer required urgent delivery faster than custom fabrication cycle'],
                ['CAN-REDESIGN', 'Site Architectural Redesign', 'Openings altered to civil walls or alternate architectural elements'],
            ],
            'notification_templates' => [
                ['NT-LEAD-ASSIGN', 'New Enquiry Assigned', 'Notification sent to sales executive immediately upon round-robin assignment'],
                ['NT-VISIT-REMIND', 'Site Visit Reminder (T-24h)', 'Automated reminder sent to sales engineer and customer 24 hours before visit'],
                ['NT-DISC-REQ', 'Discount Approval Escalation', 'Alert delivered to Sales Manager when quote discount exceeds executive limit'],
                ['NT-PAY-REC', 'Payment Receipt Confirmed', 'Instant confirmation to accounts and customer upon payment recording'],
            ],
            'pdf_templates' => [
                ['PDF-QT-A4', 'Official DHIS Quotation (A4 Portrait)', 'Executive multi-brand proposal with itemized specs, GST split, and terms'],
                ['PDF-DC-A4', 'Delivery Challan & Dispatch Gate Pass', 'Official transit documentation detailing dispatch quantities and vehicle'],
                ['PDF-INS-A4', 'Installation & Quality Handover Certificate', 'Technical inspection sign-off sheet with customer acceptance signature'],
                ['PDF-RCP-A4', 'Official Payment & Milestone Receipt', 'Computerized GST-compliant receipt with INR words and balance tracking'],
            ],
        ];

        $inserted = 0;
        $order = 10;
        foreach ($catalog as $typeCode => $records) {
            foreach ($records as [$code, $name, $desc]) {
                $exists = $db->table('crm_master_records')->where('type_code', $typeCode)->where('code', $code)->countAllResults();
                if (! $exists) {
                    $db->table('crm_master_records')->insert([
                        'type_code' => $typeCode,
                        'code' => $code,
                        'name' => $name,
                        'description' => $desc,
                        'sort_order' => $order,
                        'is_active' => 1,
                        'created_at' => Crm::now(),
                        'updated_at' => Crm::now(),
                    ]);
                    $inserted++;
                    $order += 10;
                }
            }
        }
        return $inserted;
    }
}
