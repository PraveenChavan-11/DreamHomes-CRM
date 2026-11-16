<?php

namespace Crm\Services;

/**
 * Deterministic FICTIONAL demonstration organisation for Dream Homes Interior Solutions (DHIS).
 * All people, phone numbers, projects and prices are invented; e-mail addresses use the reserved ".invalid" TLD.
 * Brand names (Vitrum, Saint-Gobain, Grohe, American Standard, Mr. Kitchen, Hafele) are taken from the public dhisgroup.in site;
 * model names, SKUs and every price are demonstration values only — NOT a DHIS price list.
 * Business flow is driven through the real services (numbering, workflow, audit, calculator), with a frozen business clock for history.
 */
final class DemoSeeder
{
    public const PASSWORD = 'Demo@DHIS#2026';   // demo-only; the seeder refuses to run outside a designated demo environment

    private const FIRST = ['Aarav', 'Vihaan', 'Aditya', 'Rohan', 'Sahil', 'Ishaan', 'Kunal', 'Tanmay', 'Omkar', 'Yash', 'Anaya', 'Diya', 'Kavya', 'Riya', 'Neha', 'Shruti', 'Pallavi', 'Madhuri', 'Sakshi', 'Tejas'];
    private const LAST = ['Kulkarni', 'Deshmukh', 'Patil', 'Joshi', 'Bhosale', 'Gaikwad', 'Mane', 'Kadam', 'Phadke', 'Sawant', 'Chavan', 'Jagtap', 'Wagh', 'Thorat', 'Gokhale'];
    private const AREAS = ['Wakad', 'Hinjewadi', 'Baner', 'Aundh', 'Kharadi', 'Viman Nagar', 'Pimple Saudagar', 'Balewadi', 'Kothrud', 'Hadapsar', 'Pashan', 'Ravet', 'Pimpri', 'Chinchwad', 'Undri'];
    private const PIN = ['Wakad' => '411057', 'Hinjewadi' => '411057', 'Baner' => '411045', 'Aundh' => '411007', 'Kharadi' => '411014', 'Viman Nagar' => '411014', 'Pimple Saudagar' => '411027', 'Balewadi' => '411045', 'Kothrud' => '411038', 'Hadapsar' => '411028', 'Pashan' => '411021', 'Ravet' => '412101', 'Pimpri' => '411018', 'Chinchwad' => '411033', 'Undri' => '411060'];
    private const LOST = ['Price too high', 'Chose a competitor', 'Project postponed or cancelled', 'No response from customer'];
    private const OUTCOMES = ['Customer interested, wants options', 'Asked for a quotation', 'Will discuss with family', 'Requested a site visit', 'Comparing with other brands', 'Needs budget approval'];

    /** category => [[sku, name, brand, unit, pricing, std, spec]] */
    private const CATALOGUE = [
        'Windows' => [
            ['WIN-001', 'Slimline® Aluminium Sliding Window - 2 Track', 'Vitrum', 'sqft', 'sqft', 1450, 'Aluminium Slimline profile, 2-track sliding, toughened glass'],
            ['WIN-002', 'Slimline® Aluminium Sliding Window - 3 Track', 'Vitrum', 'sqft', 'sqft', 1580, 'Aluminium Slimline profile, 3-track sliding'],
            ['WIN-003', 'Slimline® Aluminium Casement Window', 'Vitrum', 'sqft', 'sqft', 1620, 'Side-hung casement with multi-point lock'],
            ['WIN-004', 'Slimline® Aluminium Fixed Glazing', 'Vitrum', 'sqft', 'sqft', 1150, 'Fixed panel, toughened glass'],
            ['WIN-005', 'Slimline® Aluminium Sliding Door', 'Vitrum', 'sqft', 'sqft', 1720, 'Large-format sliding door'],
            ['WIN-006', 'Slimline® Aluminium Bi-fold Door', 'Vitrum', 'sqft', 'sqft', 2150, 'Bi-fold door system'],
            ['WIN-007', 'UPVC Sliding Window - 2 Track', 'Saint-Gobain', 'sqft', 'sqft', 980, 'UPVC multi-chamber profile, 2-track sliding'],
            ['WIN-008', 'UPVC Casement Window', 'Saint-Gobain', 'sqft', 'sqft', 1120, 'UPVC casement, reinforced profile'],
            ['WIN-009', 'UPVC Tilt & Turn Window', 'Saint-Gobain', 'sqft', 'sqft', 1380, 'Tilt and turn hardware'],
            ['WIN-010', 'UPVC Fixed Window', 'Saint-Gobain', 'sqft', 'sqft', 760, 'Fixed UPVC panel'],
            ['WIN-011', 'UPVC French Door', 'Saint-Gobain', 'sqft', 'sqft', 1290, 'Double-leaf French door'],
            ['WIN-012', 'Mosquito Mesh Add-on', 'Saint-Gobain', 'sqft', 'sqft', 120, 'Retractable/fixed mesh add-on'],
        ],
        'Bathrooms' => [
            ['BTH-001', 'Overhead Rain Shower Set', 'Grohe', 'nos', 'unit', 18500, 'Overhead shower with arm, chrome'],
            ['BTH-002', 'Single-lever Basin Mixer', 'Grohe', 'nos', 'unit', 9800, 'Basin mixer, chrome'],
            ['BTH-003', 'Wall-hung WC with Soft-close Seat', 'Grohe', 'nos', 'unit', 24500, 'Rimless wall-hung WC'],
            ['BTH-004', 'Concealed Cistern with Flush Plate', 'Grohe', 'nos', 'unit', 14500, 'In-wall cistern, dual flush'],
            ['BTH-005', 'Thermostatic Shower Mixer', 'Grohe', 'nos', 'unit', 21500, 'Wall-mounted thermostatic mixer'],
            ['BTH-006', 'Floor-mounted WC', 'American Standard', 'nos', 'unit', 12800, 'Floor-standing WC, water-saving'],
            ['BTH-007', 'Counter-top Wash Basin', 'American Standard', 'nos', 'unit', 7600, 'Vitreous china basin'],
            ['BTH-008', 'Freestanding Bathtub', 'American Standard', 'nos', 'unit', 46000, 'Acrylic freestanding bathtub'],
            ['BTH-009', 'Frameless Shower Enclosure', 'American Standard', 'nos', 'unit', 38500, 'Toughened glass enclosure'],
            ['BTH-010', 'Health Faucet Set', 'American Standard', 'nos', 'unit', 1950, 'Health faucet with hose'],
            ['BTH-011', 'Heated Towel Rail', 'American Standard', 'nos', 'unit', 8200, 'Wall-mounted towel rail'],
            ['BTH-012', 'Bath Accessories Set (5 pc)', 'American Standard', 'set', 'package', 6400, 'Accessories package'],
        ],
        'Modular Kitchens' => [
            ['KIT-001', 'Base Cabinet Module', 'Mr. Kitchen', 'rft', 'rft', 6800, 'Base cabinet, per running foot'],
            ['KIT-002', 'Wall Cabinet Module', 'Mr. Kitchen', 'rft', 'rft', 5200, 'Wall cabinet, per running foot'],
            ['KIT-003', 'Tall Unit', 'Mr. Kitchen', 'nos', 'unit', 28500, 'Full-height tall unit'],
            ['KIT-004', 'Soft-close Drawer Module', 'Mr. Kitchen', 'nos', 'unit', 12500, 'Three-drawer soft-close module'],
            ['KIT-005', 'Countertop (per rft)', 'Mr. Kitchen', 'rft', 'rft', 3400, 'Countertop surface'],
            ['KIT-006', 'Built-in Hob 4-burner', 'Hafele', 'nos', 'unit', 26500, 'Built-in gas hob'],
            ['KIT-007', 'Wall Chimney', 'Hafele', 'nos', 'unit', 32500, 'Wall-mounted chimney'],
            ['KIT-008', 'Built-in Oven', 'Hafele', 'nos', 'unit', 48500, 'Built-in electric oven'],
            ['KIT-009', 'Built-in Dishwasher', 'Hafele', 'nos', 'unit', 62500, 'Built-in dishwasher'],
        ],
        'Other Products & Services' => [
            ['SRV-001', 'Site Measurement & Survey', 'DHIS', 'nos', 'package', 1500, 'On-site survey and measurement'],
            ['SRV-002', 'Extended Installation Service', 'DHIS', 'nos', 'package', 7500, 'Extended installation package'],
        ],
    ];

    private int $seq = 0;
    private array $execs = [];
    private array $mgrOf = [];
    private array $cust = [];
    private array $prodByCat = [];
    private array $admin;

    /** @throws CrmException when not in a designated demo environment */
    public static function assertDemoEnvironment(): void
    {
        $flag = strtolower((string) (env('DHIS_DEMO_ENV') ?: getenv('DHIS_DEMO_ENV')));
        if ($flag !== 'true' && $flag !== '1') {
            throw new CrmException('Refusing: this is not a designated demo environment (set DHIS_DEMO_ENV=true on the demo server only).');
        }
        if (Settings::get('demo.enabled') !== '1') {
            throw new CrmException('Refusing: demo mode is not enabled (setting demo.enabled).');
        }
    }

    private function clock(int $daysAgo, string $hm = '10:00'): void
    {
        Crm::setClock(date('Y-m-d', strtotime("-{$daysAgo} days")) . " {$hm}:00");
    }

    private function stamp(string $table, int $id, string $col = 'created_at'): void
    {
        Crm::db()->table($table)->where('id', $id)->update([$col => Crm::now()]);
    }

    private function reg(string $table, int $id): void
    {
        Crm::db()->query('INSERT IGNORE INTO crm_demo_registry (table_name,row_id) VALUES (?,?)', [$table, $id]);
    }

    private function actor(int $uid): array
    {
        return AuthService::sessionUser($uid);
    }

    /** @return array<string,int> summary counts */
    public function run(bool $skipGuard = false, bool $purgeFirst = true): array
    {
        if (! $skipGuard) {
            self::assertDemoEnvironment();
        }
        if ($purgeFirst) {
            DemoService::purgeDemoRows();
        }
        MasterService::seedDefaultMasters();
        mt_srand(20261113);
        Crm::setActor(null, 'seed');
        $db = Crm::db();
        $db->transBegin();
        try {
            $this->people();
            $this->products();
            $this->customers();
            $this->pipeline();
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            Crm::setClock(null);
            throw $e;
        }
        Crm::setClock(null);
        Crm::resetContext();
        $out = [];
        foreach (['admin_users' => 'users', 'crm_customers' => 'customers', 'crm_enquiries' => 'enquiries', 'crm_followups' => 'followups', 'crm_activities' => 'activities', 'crm_site_visits' => 'site_visits',
            'crm_products' => 'products', 'crm_quotations' => 'quotations', 'crm_quotation_versions' => 'quotation_versions', 'crm_negotiations' => 'negotiations', 'crm_discount_approvals' => 'discount_approvals',
            'crm_sales_orders' => 'sales_orders', 'crm_email_log' => 'emails', 'crm_notifications' => 'notifications', 'crm_workflow_events' => 'workflow_events'] as $t => $k) {
            $out[$k] = $t === 'admin_users'
                ? (int) $db->table('crm_demo_registry')->where('table_name', 'admin_users')->countAllResults()
                : (int) $db->table($t)->countAllResults();
        }

        return $out;
    }

    private function people(): void
    {
        $db = Crm::db();
        $rows = [
            ['demo.superadmin', 'Kiran', 'Rao', 'super_admin'], ['demo.admin1', 'Meera', 'Joshi', 'admin'], ['demo.admin2', 'Sandeep', 'Patil', 'admin'],
            ['demo.manager1', 'Rahul', 'Deshpande', 'sales_manager'], ['demo.manager2', 'Priya', 'Naik', 'sales_manager'],
            ['demo.exec1', 'Amit', 'Pawar', 'sales_executive'], ['demo.exec2', 'Sneha', 'Kale', 'sales_executive'], ['demo.exec3', 'Vikram', 'Shinde', 'sales_executive'],
            ['demo.exec4', 'Pooja', 'Jadhav', 'sales_executive'], ['demo.exec5', 'Nikhil', 'More', 'sales_executive'], ['demo.exec6', 'Anjali', 'Bhosale', 'sales_executive'],
            ['demo.management', 'Suresh', 'Iyer', 'management'],
        ];
        $ids = [];
        $this->clock(160, '09:00');
        foreach ($rows as $i => [$u, $f, $l, $role]) {
            $existing = $db->table('admin_users')->where('username', $u)->get()->getRowArray();
            if ($existing) {
                $id = (int) $existing['id'];
                $db->table('admin_users')->where('id', $id)->update([
                    'password_hash' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                    'first_name' => $f,
                    'last_name' => $l,
                    'email' => "{$u}@dhis-demo.invalid",
                    'role' => $role,
                    'is_active' => 1,
                    'updated_at' => Crm::now(),
                ]);
            } else {
                $id = UserService::create(['username' => $u, 'email' => "{$u}@dhis-demo.invalid", 'first_name' => $f, 'last_name' => $l, 'role' => $role, 'password' => self::PASSWORD,
                    'phone' => '+91 90000 1' . sprintf('%04d', 2100 + $i * 7), 'employee_code' => 'DEMO' . sprintf('%03d', $i + 1)]);
            }
            $this->reg('admin_users', $id);
            $ids[$u] = $id;
        }

        $t1Row = $db->table('crm_teams')->where('name', 'Demo Team - Windows')->get()->getRowArray();
        $t1 = $t1Row ? (int) $t1Row['id'] : UserService::createTeam('Demo Team - Windows', $ids['demo.manager1']);
        $t2Row = $db->table('crm_teams')->where('name', 'Demo Team - Bathrooms & Kitchens')->get()->getRowArray();
        $t2 = $t2Row ? (int) $t2Row['id'] : UserService::createTeam('Demo Team - Bathrooms & Kitchens', $ids['demo.manager2']);

        $db->table('crm_team_members')->whereIn('team_id', [$t1, $t2])->delete();
        foreach (['demo.exec1', 'demo.exec2', 'demo.exec3'] as $u) { UserService::addToTeam($t1, $ids[$u]); $this->mgrOf[$ids[$u]] = $ids['demo.manager1']; }
        foreach (['demo.exec4', 'demo.exec5', 'demo.exec6'] as $u) { UserService::addToTeam($t2, $ids[$u]); $this->mgrOf[$ids[$u]] = $ids['demo.manager2']; }
        $this->execs = [$ids['demo.exec1'], $ids['demo.exec2'], $ids['demo.exec3'], $ids['demo.exec4'], $ids['demo.exec5'], $ids['demo.exec6']];
        $this->admin = $this->actor($ids['demo.admin1']);
        // demo teams must be the only assignment pool: restrict the default rule to round-robin across ALL active executives (deterministic by id)
        $db->table('crm_assignment_rules')->update(['is_active' => 0]);
        $db->table('crm_assignment_rules')->insert(['priority' => 1, 'mode' => 'round_robin']);
        $db->query('DELETE FROM crm_assignment_state');

        // Tags
        $tags = [
            ['hni-client', 'HNI Client'],
            ['repeat-buyer', 'Repeat Buyer'],
            ['architect-referral', 'Architect Referral'],
            ['commercial-project', 'Commercial High-Rise'],
            ['urgent-delivery', 'Urgent Dispatch'],
            ['luxury-villa', 'Luxury Villa Interior'],
        ];
        foreach ($tags as [$slug, $lbl]) {
            $db->query('INSERT INTO crm_tags (slug, label, active) VALUES (?,?,1) ON DUPLICATE KEY UPDATE label=VALUES(label)', [$slug, $lbl]);
        }

        // Territories
        $territories = [
            ['Pune West (Baner, Wakad, Aundh)', 'Pune', '41104', $t1],
            ['Pune East (Kharadi, Viman Nagar, Hadapsar)', 'Pune', '41101', $t1],
            ['PCMC & Industrial (Pimpri, Chinchwad, Ravet)', 'Pune', '41101', $t2],
            ['Pune South & Central (Kothrud, Undri, Deccan)', 'Pune', '41103', $t2],
        ];
        foreach ($territories as [$name, $city, $pin, $tId]) {
            $db->query('INSERT INTO crm_territories (name, city, pin_prefix, team_id, is_active) VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE city=VALUES(city)', [$name, $city, $pin, $tId]);
        }
    }

    private function products(): void
    {
        $db  = Crm::db();
        $tax = (int) $db->table('crm_tax_rates')->where('is_default', 1)->get()->getRow()->id;

        // 1. Seed Brands
        $brands = [
            ['name' => 'Vitrum', 'code' => 'VITRUM', 'website' => 'https://dhisgroup.in/vitrum', 'desc' => 'Precision Engineered Aluminium Windows & Doors'],
            ['name' => 'Saint-Gobain', 'code' => 'SGOB', 'website' => 'https://saint-gobain.com', 'desc' => 'High-Performance Glass & UPVC Profiles'],
            ['name' => 'Grohe', 'code' => 'GROHE', 'website' => 'https://grohe.co.in', 'desc' => 'German Luxury Bathroom Fittings & Showers'],
            ['name' => 'American Standard', 'code' => 'AMSTD', 'website' => 'https://americanstandard.in', 'desc' => 'Premium Sanitaryware & Ceramics'],
            ['name' => 'Mr. Kitchen', 'code' => 'MRKIT', 'website' => 'https://dhisgroup.in/mrkitchen', 'desc' => 'Custom European Modular Kitchens'],
            ['name' => 'Häfele', 'code' => 'HAFELE', 'website' => 'https://hafeleindia.com', 'desc' => 'German Architectural Hardware & Built-in Appliances'],
            ['name' => 'DHIS Group', 'code' => 'DHIS', 'website' => 'https://dhisgroup.in', 'desc' => 'Dream Homes Interior Solutions Custom Fabrication'],
        ];
        $brandIds = [];
        foreach ($brands as $b) {
            $db->query('INSERT INTO crm_product_brands (name, code, website, description, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)',
                [$b['name'], $b['code'], $b['website'], $b['desc'], Crm::now(), Crm::now()]);
            $brandIds[$b['name']] = (int) $db->table('crm_product_brands')->where('code', $b['code'])->get()->getRow()->id;
        }

        // 1.1 Seed Product Subcategories & Types
        $winCat = (int) ($db->table('crm_product_categories')->where('name', 'Windows')->get()->getRow()->id ?? 1);
        $bthCat = (int) ($db->table('crm_product_categories')->where('name', 'Bathrooms')->get()->getRow()->id ?? 2);
        $kitCat = (int) ($db->table('crm_product_categories')->where('name', 'Modular Kitchens')->get()->getRow()->id ?? 3);

        $subcats = [
            [$winCat, 'Aluminium Sliding Systems', 'SUBCAT-WIN-SLD', 'Heavy-gauge thermal and non-thermal aluminium sliding window profiles'],
            [$winCat, 'Aluminium Casement Systems', 'SUBCAT-WIN-CSM', 'Architectural openable casement windows and tilt-turn systems'],
            [$winCat, 'UPVC Engineered Profiles', 'SUBCAT-WIN-UPVC', 'Multi-chamber acoustic and weather-resistant UPVC windows'],
            [$bthCat, 'Luxury Showers & Thermostats', 'SUBCAT-BTH-SHW', 'German concealed rain showers and temperature-regulated mixers'],
            [$bthCat, 'Sanitaryware & Closets', 'SUBCAT-BTH-SAN', 'Rimless wall-hung water closets and vitreous china basins'],
            [$kitCat, 'European Base Modules', 'SUBCAT-KIT-BAS', 'Ergonomic marine-ply base cabinets with soft-close mechanisms'],
            [$kitCat, 'Hafele Built-in Appliances', 'SUBCAT-KIT-APP', 'German built-in hobs, ovens, chimneys, and dishwashers'],
        ];
        foreach ($subcats as [$cId, $name, $code, $desc]) {
            $db->query('INSERT INTO crm_product_subcategories (category_id, name, code, description, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)',
                [$cId, $name, $code, $desc, Crm::now(), Crm::now()]);
        }

        $prodTypes = [
            [$winCat, 'Heavy-duty 3-Track Sliding Systems', 'TYPE-WIN-3TRK', 'Multi-slide heavy architectural fenestration for panoramic views'],
            [$winCat, 'Acoustic Soundproof DGU Windows', 'TYPE-WIN-DGU', 'Double-glazed hermetically sealed sound reduction windows'],
            [$bthCat, 'Thermostatic Concealed Rain Showers', 'TYPE-BTH-THERM', 'Luxury German thermostatic multi-jet shower systems'],
            [$bthCat, 'Rimless Intelligent Water Closets', 'TYPE-BTH-RIML', 'Water-saving rimless ceramic wall-hung closets'],
            [$kitCat, 'Built-in Island Chimneys & Gas Hobs', 'TYPE-KIT-ISLND', 'High-suction baffle filter island chimneys and cast-iron hobs'],
        ];
        foreach ($prodTypes as [$cId, $name, $code, $desc]) {
            $db->query('INSERT INTO crm_product_types (category_id, name, code, description, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)',
                [$cId, $name, $code, $desc, Crm::now(), Crm::now()]);
        }

        // 2. Seed Master Products
        foreach (self::CATALOGUE as $cat => $items) {
            $cid = (int) $db->table('crm_product_categories')->where('name', $cat)->get()->getRow()->id;
            foreach ($items as [$sku, $name, $brand, $unit, $model, $std, $spec]) {
                $skuCode = 'DEMO-' . $sku;
                $existing = $db->table('crm_products')->where('sku', $skuCode)->get()->getRowArray();
                $prodData = [
                    'sku' => $skuCode, 'name' => $name, 'brand' => $brand, 'category_id' => $cid, 'unit' => $unit, 'pricing_model' => $model, 'tax_rate_id' => $tax,
                    'standard_price' => $std, 'base_price' => (int) round($std * 0.68), 'min_price' => (int) round($std * 0.88), 'specifications' => $spec,
                    'image' => self::productImageFor($sku),
                    'description' => 'DEMONSTRATION ITEM - price and specification are illustrative, not a DHIS price list.',
                ];
                $pid = $existing
                    ? ProductService::save((int) $existing['id'], $prodData, $this->admin, 1)
                    : ProductService::save(null, $prodData, $this->admin, 1);
                $this->prodByCat[$cat][] = $pid;

                // 3. Seed 2 Variants per Product
                $vBrandId = $brandIds[$brand] ?? null;
                $db->query("INSERT INTO crm_product_variants (product_id, sku, variant_name, brand_id, finish, colour, glass_type, cost_price, selling_price, tax_rate, is_active, created_at, updated_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,1,?,?)
                    ON DUPLICATE KEY UPDATE selling_price=VALUES(selling_price), updated_at=VALUES(updated_at)",
                    [$pid, $skuCode . '-V1', $name . ' (Matte Black Finish)', $vBrandId, 'Anodized', 'Matte Black', 'Toughened Clear Glass', round($std * 0.70), $std, 18.00, Crm::now(), Crm::now()]
                );
                $db->query("INSERT INTO crm_product_variants (product_id, sku, variant_name, brand_id, finish, colour, glass_type, cost_price, selling_price, tax_rate, is_active, created_at, updated_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,1,?,?)
                    ON DUPLICATE KEY UPDATE selling_price=VALUES(selling_price), updated_at=VALUES(updated_at)",
                    [$pid, $skuCode . '-V2', $name . ' (Champagne Bronze Finish)', $vBrandId, 'Powder Coated', 'Champagne Bronze', 'Double Glazed DGU Glass', round($std * 0.78), round($std * 1.15), 18.00, Crm::now(), Crm::now()]
                );
            }
        }
    }

    public static function productImageFor(string $sku): string
    {
        $clean = preg_replace('/^DEMO-/', '', $sku);
        $map = [
            'WIN-001' => 'uploads/crm-products/vitrum-sliding-window.svg',
            'WIN-002' => 'uploads/crm-products/vitrum-sliding-window.svg',
            'WIN-003' => 'uploads/crm-products/vitrum-casement-window.svg',
            'WIN-004' => 'uploads/crm-products/saintgobain-upvc-window.svg',
            'WIN-005' => 'uploads/crm-products/vitrum-sliding-door.svg',
            'WIN-006' => 'uploads/crm-products/vitrum-bifold-door.svg',
            'WIN-007' => 'uploads/crm-products/saintgobain-upvc-window.svg',
            'WIN-008' => 'uploads/crm-products/saintgobain-upvc-window.svg',
            'WIN-009' => 'uploads/crm-products/saintgobain-upvc-window.svg',
            'WIN-010' => 'uploads/crm-products/saintgobain-upvc-window.svg',
            'WIN-011' => 'uploads/crm-products/vitrum-sliding-door.svg',
            'WIN-012' => 'uploads/crm-products/saintgobain-upvc-window.svg',
            'BTH-001' => 'uploads/crm-products/grohe-rainshower-system.svg',
            'BTH-002' => 'uploads/crm-products/grohe-basin-mixer.svg',
            'BTH-003' => 'uploads/crm-products/amstd-wallhung-wc.svg',
            'BTH-004' => 'uploads/crm-products/amstd-wallhung-wc.svg',
            'BTH-005' => 'uploads/crm-products/grohe-rainshower-system.svg',
            'BTH-006' => 'uploads/crm-products/amstd-wallhung-wc.svg',
            'BTH-007' => 'uploads/crm-products/grohe-basin-mixer.svg',
            'BTH-008' => 'uploads/crm-products/amstd-freestanding-bathtub.svg',
            'BTH-009' => 'uploads/crm-products/amstd-freestanding-bathtub.svg',
            'BTH-010' => 'uploads/crm-products/grohe-basin-mixer.svg',
            'BTH-011' => 'uploads/crm-products/grohe-rainshower-system.svg',
            'BTH-012' => 'uploads/crm-products/grohe-basin-mixer.svg',
            'KIT-001' => 'uploads/crm-products/mrkitchen-base-cabinet.svg',
            'KIT-002' => 'uploads/crm-products/mrkitchen-base-cabinet.svg',
            'KIT-003' => 'uploads/crm-products/mrkitchen-tall-pantry.svg',
            'KIT-004' => 'uploads/crm-products/mrkitchen-base-cabinet.svg',
            'KIT-005' => 'uploads/crm-products/mrkitchen-base-cabinet.svg',
            'KIT-006' => 'uploads/crm-products/hafele-builtin-hob.svg',
            'KIT-007' => 'uploads/crm-products/hafele-island-chimney.svg',
            'KIT-008' => 'uploads/crm-products/hafele-builtin-oven.svg',
            'KIT-009' => 'uploads/crm-products/hafele-builtin-dishwasher.svg',
            'SRV-001' => 'uploads/crm-products/vitrum-sliding-window.svg',
            'SRV-002' => 'uploads/crm-products/vitrum-sliding-window.svg',
        ];

        return $map[$clean] ?? 'uploads/crm-products/vitrum-sliding-window.svg';
    }

    private function customers(): void
    {
        $this->clock(158, '10:00');
        $db = Crm::db();
        $src = array_column($db->table('crm_lead_sources')->get()->getResultArray(), 'id');
        for ($i = 0; $i < 40; $i++) {
            $f = self::FIRST[$i % 20];
            $l = self::LAST[($i * 7) % 15];
            $a = self::AREAS[($i * 4) % 15];
            $type = $i % 13 === 0 ? 'architect' : ($i % 11 === 0 ? 'builder' : ($i % 9 === 0 ? 'company' : 'individual'));
            $email = strtolower("{$f}.{$l}{$i}") . '@demo.invalid';
            $mobile = '90000' . sprintf('%05d', 11000 + $i * 37);

            $custRow = $db->table('crm_customers')->where('email', $email)->orWhere('mobile', $mobile)->get()->getRowArray();
            if ($custRow) {
                $id = (int) $custRow['id'];
            } else {
                $id = CustomerService::create([
                    'name' => "{$f} {$l}", 'customer_type' => $type, 'company_name' => $type === 'individual' ? null : "{$l} " . ['architect' => 'Design Studio', 'builder' => 'Developers', 'company' => 'Enterprises'][$type],
                    'mobile' => $mobile, 'email' => $email,
                    'site_address' => 'Flat ' . (101 + $i) . ", {$a}", 'billing_address' => 'Flat ' . (101 + $i) . ", {$a}", 'city' => 'Pune', 'state' => 'Maharashtra', 'pin_code' => self::PIN[$a],
                    'source_id' => $src[$i % count($src)], 'remarks' => 'Fictional demo customer',
                ], $this->admin['id'], true, 1);
            }
            $this->cust[] = ['id' => $id, 'area' => $a, 'surname' => $l];

            $db->query("INSERT INTO crm_customer_addresses (customer_id, address_type, title, address_line, city, state, pin_code, contact_person, contact_phone, is_default, is_active, created_at, updated_at)
                VALUES (?, 'site', 'Primary Execution Site', ?, 'Pune', 'Maharashtra', ?, ?, ?, 1, 1, ?, ?)",
                [$id, 'Plot ' . (101 + $i) . ", {$a}, Pune", self::PIN[$a], "{$f} {$l}", $mobile, Crm::now(), Crm::now()]);

            if ($i % 2 === 0) {
                $db->query("INSERT INTO crm_customer_contacts (customer_id, name, role_title, mobile, email, is_primary)
                    VALUES (?, ?, ?, ?, ?, 0)",
                    [$id, $l . ' Architectural Consultant', 'Site Architect / PM', '98220' . sprintf('%05d', 50000 + $i * 19), strtolower("architect.{$l}{$i}") . '@demo.invalid']);
            }
        }
    }

    private function dayAgo(int $i): int
    {
        return match (true) {
            $i < 10 => 1 + ($i % 4),
            $i < 25 => 6 + ($i % 9),
            $i < 40 => 12 + ($i % 15),
            default => 18 + (($i * 7) % 120),
        };
    }

    private function lines(string $cat, int $i): array
    {
        $pool = $this->prodByCat[$cat];
        $n    = 2 + ($i % 3);
        $out  = [];
        for ($k = 0; $k < $n; $k++) {
            $pid = $pool[($i + $k * 3) % count($pool)];
            $qty = match ($cat) { 'Windows' => 18 + (($i * 5 + $k * 11) % 90), 'Bathrooms' => 1 + (($i + $k) % 4), default => 2 + (($i * 3 + $k) % 10) };
            $out[] = ['product_id' => $pid, 'quantity' => (string) $qty, 'discount_pct' => (string) (($i + $k) % 5)];
        }

        return $out;
    }

    private function pipeline(): void
    {
        $db   = Crm::db();
        $cats = ['Windows', 'Bathrooms', 'Modular Kitchens'];
        $srcs = array_column($db->table('crm_lead_sources')->get()->getResultArray(), 'id', 'code');
        $chan = array_keys(ReferenceData::LEAD_SOURCES);
        $lost = array_column($db->table('crm_lost_reasons')->get()->getResultArray(), 'id');
        $pcts = [6, 8, 10, 12, 14, 9, 11, 13, 7, 15, 18, 22, 16, 20, 25, 30, 8, 10, 12, 17];

        // Seed 15 Architectural Customer Projects
        $projects = [];
        for ($p = 0; $p < 15; $p++) {
            $c = $this->cust[$p];
            $projTypes = ['Luxury Villa', 'Modern Apartment', 'Penthouse Duplex', 'Residential Bungalow', 'Commercial Studio'];
            $pNo = 'PRJ-' . sprintf('%04d', $p + 1);
            $existPrj = $db->table('crm_projects')->where('project_no', $pNo)->get()->getRowArray();
            if ($existPrj) {
                $projects[$p] = (int) $existPrj['id'];
            } else {
                $db->table('crm_projects')->insert([
                    'project_no' => $pNo,
                    'customer_id' => $c['id'],
                    'name' => "{$c['surname']} " . $projTypes[$p % 5] . " - {$c['area']}",
                    'site_location' => 'Plot ' . (10 + $p) . ', Road 4, ' . $c['area'],
                    'city' => 'Pune',
                    'state' => 'Maharashtra',
                    'pin_code' => self::PIN[$c['area']],
                    'status' => ['planning', 'in_progress', 'completed'][$p % 3],
                    'budget' => 500000 + ($p * 150000),
                    'assigned_to' => $this->execs[$p % 6],
                    'remarks' => 'Architectural execution project covering Vitrum windows, Grohe bathrooms, and Hafele modular kitchen.',
                    'is_demo' => 1,
                    'created_at' => Crm::now(),
                    'updated_at' => Crm::now(),
                ]);
                $projects[$p] = (int) $db->insertID();
            }
            $this->reg('crm_projects', $projects[$p]);
        }

        for ($i = 0; $i < 100; $i++) {
            $d0   = $this->dayAgo($i);
            $c    = $this->cust[$i % 40];
            $cat  = $cats[$i % 3];
            $catId = (int) $db->table('crm_product_categories')->where('name', $cat)->get()->getRow()->id;
            $this->clock($d0, '09:30');
            $enq = EnquiryService::create([
                'customer_id' => $c['id'], 'project_id' => $projects[$i % 15], 'is_lead' => ($i < 20 ? 1 : 0),
                'project_name' => "{$c['surname']} " . ['Residence', 'Villa', 'Apartment', 'Bungalow', 'Office'][$i % 5] . " #{$i} - {$c['area']}", 'product_category_id' => $catId,
                'requirements' => "{$cat} for new project (demo)", 'est_budget' => (string) (150000 + (($i * 53) % 12) * 85000), 'expected_purchase_date' => date('Y-m-d', strtotime('+' . (10 + $i % 60) . ' days')),
                'source_id' => $srcs[$chan[$i % count($chan)]], 'source_channel' => $chan[$i % count($chan)], 'priority' => ['low', 'medium', 'high', 'urgent'][$i % 4],
                'description' => 'Fictional demo enquiry',
            ], $this->admin, true, true, 1, Crm::now());
            $eid = $enq['id'];
            $this->reg('crm_enquiries', $eid);
            EmailService::queue("placeholder{$i}@demo.invalid", ['template' => 'enquiry_ack', 'customer_id' => $c['id'], 'enquiry_id' => $eid, 'is_demo' => 1, 'automation' => 'enquiry_ack', 'user_id' => $this->admin['id'],
                'vars' => ['customer_name' => 'Customer', 'enquiry_no' => $enq['enquiry_no'], 'salesperson' => '-', 'company_name' => Settings::get('company.name')]]);
            if ($i % 17 === 0 && $i >= 10) {
                $to = $this->execs[($i + 2) % 6];
                if ($to !== $enq['owner_id']) {
                    $this->clock($d0, '16:00');
                    EnquiryService::reassign($eid, $to, $this->actor($this->mgrOf[$enq['owner_id']] ?? $this->admin['id']), 'Rebalancing team workload');
                    $enq['owner_id'] = $to;
                }
            }
            if ($i < 10) {
                continue;
            }
            $owner = $enq['owner_id'];
            $X     = $this->actor($owner);
            $M     = $this->actor($this->mgrOf[$owner]);

            $this->clock($d0 - 1, '11:00');
            ActivityService::recordContact($eid, $X, ['call', 'whatsapp', 'meeting'][$i % 3], 'Initial discussion with customer', self::OUTCOMES[$i % 6]);
            $this->clock($d0 - 2, '10:30');
            $fu = ActivityService::scheduleFollowup($eid, $X, date('Y-m-d H:i:s', strtotime('-' . ($d0 - 2) . ' days 15:00')), 'call', 'Share options and brochures');
            $this->stamp('crm_followups', $fu);
            $this->clock($d0 - 2, '15:10');
            ActivityService::completeFollowup($fu, $X, self::OUTCOMES[($i + 1) % 6], 'Spoke for 12 minutes', null, null, Crm::now());

            if ($i >= 15 && $i < 20) {
                $this->clock($d0 - 3, '12:00');
                WorkflowService::transition($eid, 'qualified', $X, 'Budget and timeline confirmed');
            }
            if ($i >= 10 && $i < 20) {   // overdue follow-ups (relative to real today)
                Crm::setClock(null);
                $f = ActivityService::scheduleFollowup($eid, $X, date('Y-m-d H:i:s', strtotime('-' . (1 + $i % 5) . ' days 11:00')), 'call', 'Call back customer');
                $this->reg('crm_enquiries', $eid);
            }
            if ($i >= 20 && $i < 23) { $this->clock($d0 - 4, '10:00'); OrderService::markLost($eid, $X, $lost[3], 'Customer unreachable after three attempts'); }
            if ($i >= 23 && $i < 25) { $this->clock($d0 - 4, '10:00'); OrderService::putOnHold($eid, $X, 'Customer postponing project', date('Y-m-d', strtotime('+20 days'))); }
            if ($i >= 25 && $i < 30) {   // upcoming visit
                Crm::setClock(null);
                $vid = ActivityService::scheduleVisit($eid, $X, date('Y-m-d H:i:s', strtotime('+' . (1 + $i % 4) . ' days 11:00')));
                continue;
            }
            $needsVisit = ($i >= 30 && $i < 40) || ($i >= 40 && $i < 60);
            if ($needsVisit) {
                $this->clock($d0 - 3, '10:00');
                $vid = ActivityService::scheduleVisit($eid, $X, date('Y-m-d H:i:s', strtotime('-' . ($d0 - 5) . ' days 11:00')));
                $this->stamp('crm_site_visits', $vid);
                $this->clock($d0 - 5, '13:00');
                ActivityService::completeVisit($vid, $X, ['Opening 1' => (1200 + $i * 3) . ' x 1500 mm', 'Opening 2' => '900 x 1200 mm'], 'Walls plumb; no obstruction', "{$cat} requirement confirmed", 'Prepare quotation', Crm::now());
            }
            if ($i < 40) {
                continue;
            }

            // ───── quotations ─────
            $j = $i - 40;
            $this->clock($d0 - 6, '12:00');
            $q = QuotationService::create($eid, $X, $this->lines($cat, $i), [['name' => 'Installation charges', 'amount' => (string) (3000 + ($i % 8) * 2500)], ['name' => 'Transportation charges', 'amount' => '1500']], [], 1);
            $this->stamp('crm_quotations', $q['id']);
            $qid = $q['id'];
            if ($j < 5) {
                continue;   // drafts
            }
            QuotationService::send($qid, $X);
            $mkRev = function (int $pct, int $nid, int $day) use ($qid, $X, $cat, $i) {
                $lines = array_map(static fn ($l) => ['product_id' => $l['product_id'], 'quantity' => $l['quantity'], 'discount_pct' => (string) $pct], $this->lines($cat, $i));
                $this->clock($day, '16:00');

                return QuotationService::revise($qid, $X, $lines, [['name' => 'Installation charges', 'amount' => (string) (3000 + ($i % 8) * 2500)], ['name' => 'Transportation charges', 'amount' => '1500']], [], $nid);
            };

            if ($j < 15) {
                continue;   // sent, awaiting customer
            }
            if ($j < 35) {   // negotiation group
                $k   = $j - 15;
                $pct = $pcts[$k];
                $this->clock($d0 - 8, '11:00');
                $n = NegotiationService::request($qid, $X, (string) $pct, 'Customer asked for a better price after comparing quotes', null, null, 1);
                $this->stamp('crm_negotiations', $n['id']);
                $approver = $n['required_level'] === 'admin' ? $this->admin : $M;
                $this->clock($d0 - 9, '10:00');
                if ($k < 12) {
                    NegotiationService::decide($n['id'], $approver, 'approve', 'Approved for this deal');
                    $mkRev($pct, $n['id'], $d0 - 9);
                    QuotationService::send($qid, $X);
                    if ($k < 3) {   // second round -> V3
                        $this->clock($d0 - 10, '11:00');
                        $p2 = $pct + 2;
                        $n2 = NegotiationService::request($qid, $X, (string) $p2, 'Customer wants a little more off', null, null, 1);
                        $ap2 = $n2['required_level'] === 'admin' ? $this->admin : $M;
                        NegotiationService::decide($n2['id'], $ap2, 'approve', 'One-time final concession');
                        $mkRev($p2, $n2['id'], $d0 - 10);
                        QuotationService::send($qid, $X);
                    }
                    if ($k < 8) {
                        $this->clock($d0 - 11, '15:00');
                        QuotationService::recordResponse($qid, $X, 'accepted', 'Accepted on call', Crm::now());
                        $this->makeOrder($qid, $eid, $M, $d0 - 12, $k);
                    }
                } elseif ($k < 16) {
                    NegotiationService::decide($n['id'], $approver, 'reject', 'Margin too thin to approve');
                } elseif ($k < 18) {
                    // pending decision: leave as is
                } else {
                    NegotiationService::decide($n['id'], $approver, 'return', 'Ask customer for a lower figure');
                }
                continue;
            }
            if ($j < 50) {   // direct acceptance vs lost
                $k = $j - 35;
                if ($k < 7) {
                    $this->clock($d0 - 11, '15:00');
                    QuotationService::recordResponse($qid, $X, 'accepted', 'Customer accepted the quotation', Crm::now());
                    $this->makeOrder($qid, $eid, $M, $d0 - 12, 8 + $k);
                } else {
                    $this->clock($d0 - 12, '15:00');
                    OrderService::markLost($eid, $X, $lost[$k % 3], 'Customer decided against proceeding', $k % 2 ? 'Local fabricator' : null, Crm::now());
                }
                continue;
            }
            // 50..59 sent then lost
            $this->clock($d0 - 14, '15:00');
            OrderService::markLost($eid, $X, $lost[$j % 3], 'Went with another vendor', 'Other brand dealer', Crm::now());
        }
        // today / tomorrow follow-ups for live demo
        Crm::setClock(null);
        $live = $db->query("SELECT id, current_owner_id FROM crm_enquiries WHERE is_demo = 1 AND status IN ('quotation_sent','negotiation') ORDER BY id LIMIT 12")->getResultArray();
        foreach ($live as $n => $e) {
            $X = $this->actor((int) $e['current_owner_id']);
            ActivityService::scheduleFollowup((int) $e['id'], $X, date('Y-m-d H:i:s', strtotime(($n % 2 ? 'today 17:00' : 'tomorrow 11:30'))), 'call', 'Follow up on quotation');
        }
    }

    private function makeOrder(int $qid, int $eid, array $M, int $day, int $k): void
    {
        $this->clock(max($day, 0), '11:30');
        $o = OrderService::createFromQuotation($qid, $M, ['advance_amount' => (string) (10000 + $k * 2500), 'advance_reference' => 'DEMO-ADV-' . ($k + 1), 'confirm' => $k % 4 !== 3, 'expected_delivery' => date('Y-m-d', strtotime('+30 days')), 'at' => Crm::now()]);
        $this->stamp('crm_sales_orders', $o['id']);
        OrderService::markWon($eid, $M, null, Crm::now());
        EmailService::queue('placeholder-order@demo.invalid', ['template' => 'order_confirmation', 'customer_id' => (int) Crm::db()->table('crm_enquiries')->where('id', $eid)->get()->getRow()->customer_id, 'enquiry_id' => $eid, 'is_demo' => 1,
            'automation' => 'order_confirmation', 'vars' => ['customer_name' => 'Customer', 'order_no' => $o['order_no'], 'grand_total' => 'INR ' . Money::fmt($o['grand_total']), 'company_name' => Settings::get('company.name')]]);

        $db = Crm::db();
        $cid = (int) $db->table('crm_enquiries')->where('id', $eid)->get()->getRow()->customer_id;
        $orderTotal = (float) $o['grand_total'];
        $advPaid = round($orderTotal * 0.40, 2);

        // 1. Seed Advance Payment Record
        $db->query('INSERT INTO crm_payments (payment_no, receipt_no, order_id, customer_id, payment_type, amount, payment_mode, transaction_reference, payment_date, status, notes, recorded_by, is_demo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)',
            ['PAY-' . sprintf('%04d', $k + 1), 'RCP-' . sprintf('%04d', $k + 1), $o['id'], $cid, 'advance', $advPaid, ['bank_transfer', 'upi', 'cheque'][$k % 3], 'UTR-DEMO-' . sprintf('%06d', $k * 91 + 1045), date('Y-m-d', strtotime('-' . max(1, $day) . ' days')), 'paid', 'Initial 40% manufacturing advance', $this->admin['id'], Crm::now(), Crm::now()]);

        // 2. Seed Progress Payments & Fulfilment
        if ($k < 8) {
            $partPaid = round($orderTotal * 0.45, 2);
            $db->query('INSERT INTO crm_payments (payment_no, receipt_no, order_id, customer_id, payment_type, amount, payment_mode, transaction_reference, payment_date, status, notes, recorded_by, is_demo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)',
                ['PAY-' . sprintf('%04d', $k + 16), 'RCP-' . sprintf('%04d', $k + 16), $o['id'], $cid, 'partial', $partPaid, ['bank_transfer', 'upi'][$k % 2], 'UTR-DEMO-' . sprintf('%06d', $k * 87 + 2099), date('Y-m-d', strtotime('-' . max(1, $day - 10) . ' days')), 'paid', 'Pre-dispatch inspection milestone payment', $this->admin['id'], Crm::now(), Crm::now()]);

            // Seed Delivery Challan
            $delStatus = $k < 6 ? 'delivered' : 'dispatched';
            $db->query('INSERT INTO crm_deliveries (delivery_no, challan_no, order_id, customer_id, status, scheduled_date, delivered_date, vehicle_details, driver_name, driver_phone, recipient_name, notes, is_demo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)',
                ['DEL-' . sprintf('%04d', $k + 1), 'DC-' . sprintf('%04d', $k + 1), $o['id'], $cid, $delStatus, date('Y-m-d', strtotime('-' . max(1, $day - 12) . ' days')), ($k < 6 ? Crm::now() : null), 'MH 12 AB ' . sprintf('%04d', 2000 + $k * 13), 'Santosh Shinde', '98220 54321', 'Site Supervisor', 'Delivered at site in sound condition with transit protection', Crm::now(), Crm::now()]);

            // Seed Installation Handover
            if ($k < 6) {
                $db->query('INSERT INTO crm_installations (installation_no, order_id, customer_id, status, scheduled_date, started_at, completed_at, technician_name, technician_phone, customer_signoff_name, signoff_date, remarks, is_demo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)',
                    ['INS-' . sprintf('%04d', $k + 1), $o['id'], $cid, 'completed', date('Y-m-d', strtotime('-' . max(1, $day - 16) . ' days')), Crm::now(), Crm::now(), 'Ganesh Kadam', '98900 12345', 'Client Signoff Verified', date('Y-m-d'), 'All windows and fittings installed, sealed, and aligned successfully', Crm::now(), Crm::now()]);
            }
        }

        // 3. Seed After-Sales Service Tickets
        if ($k < 5) {
            $db->query('INSERT INTO crm_service_requests (ticket_no, customer_id, order_id, request_type, priority, status, issue_description, resolution_notes, resolved_at, is_demo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,1,?,?)',
                ['SRV-' . sprintf('%04d', $k + 1), $cid, $o['id'], ['service_request', 'warranty_claim', 'maintenance_visit'][$k % 3], 'medium', ($k < 3 ? 'resolved' : 'in_progress'), 'Post-installation routine alignment check and gasket sealing inspection for balcony sliding window', ($k < 3 ? 'Technician inspected on site, lubricated rollers, and verified smooth sliding.' : null), ($k < 3 ? Crm::now() : null), Crm::now(), Crm::now()]);
        }

        if ($k === 9) {
            OrderService::setStatus($o['id'], 'on_hold', $M, 'Customer site not ready');
        }
        if ($k === 10) {
            OrderService::setStatus($o['id'], 'closed', $M);
        }
    }
}
