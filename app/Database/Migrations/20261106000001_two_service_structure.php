<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Additive/reversible: reduces the PUBLIC service structure to two services and retires the
 * product catalogue from public view. Nothing is deleted - rows are archived (status='archived')
 * so history and any admin references stay intact. down() restores 'published'.
 */
class TwoServiceStructure extends Migration
{
    private const RETIRED_CATEGORIES = ['erp-hrms', 'qa-testing', 'digital-transformation', 'digital-marketing'];

    /** Extra capabilities folded into IT Services (previously separate categories). */
    private const IT_EXTRA = [
        ['erp-hrms-workflows', 'ERP and HRMS Workflows', 'HR, payroll, inventory and approval workflows tailored to your processes.'],
        ['business-process-automation', 'Business Process Automation', 'Automate repetitive, approval-driven and reporting workflows.'],
        ['qa-software-testing', 'Quality Assurance and Software Testing', 'Functional, API, regression and UAT testing from requirements to sign-off.'],
        ['technical-seo-optimisation', 'Technical SEO and Website Optimisation', 'Site structure, performance and metadata. We do not guarantee rankings.'],
    ];

    public function up()
    {
        self::apply($this->db);
    }

    public static function needed(\CodeIgniter\Database\BaseConnection $db): bool
    {
        return (bool) $db->query("SELECT 1 FROM service_categories WHERE slug IN ('erp-hrms','qa-testing','digital-transformation','digital-marketing') AND status <> 'archived' LIMIT 1")->getRow()
            || (bool) $db->query("SELECT 1 FROM products WHERE status <> 'archived' AND slug IN ('societyhub','peoplehub') LIMIT 1")->getRow()
            || !$db->query("SELECT 1 FROM services WHERE slug='qa-software-testing' LIMIT 1")->getRow();
    }

    public static function apply(\CodeIgniter\Database\BaseConnection $db): void
    {
        $now = date('Y-m-d H:i:s');
        $in  = "'" . implode("','", self::RETIRED_CATEGORIES) . "'";
        $db->query("UPDATE service_categories SET status='archived', updated_at=? WHERE slug IN ($in) AND status <> 'archived'", [$now]);

        $it = $db->query("SELECT id FROM service_categories WHERE slug='it-services'")->getRow();
        if ($it) {
            $max = (int) ($db->query('SELECT COALESCE(MAX(sort_order),0) m FROM services WHERE category_id=?', [$it->id])->getRow()->m ?? 0);
            foreach (self::IT_EXTRA as $i => $s) {
                $db->query("INSERT IGNORE INTO services (category_id,slug,name,summary,sort_order,status,created_at,updated_at) VALUES (?,?,?,?,?, 'published',?,?)",
                    [$it->id, $s[0], $s[1], $s[2], $max + $i + 1, $now, $now]);
            }
        }
        // Retire the legacy capability-level services of archived categories (kept, not deleted).
        $db->query("UPDATE services s JOIN service_categories c ON c.id=s.category_id SET s.status='archived', s.updated_at=? WHERE c.slug IN ($in) AND s.status <> 'archived'", [$now]);

        // Product catalogue is no longer public; archive, never delete.
        $db->query("UPDATE products SET status='archived', updated_at=? WHERE status <> 'archived' AND slug IN ('societyhub','peoplehub')", [$now]);
    }

    public function down()
    {
        $in = "'" . implode("','", self::RETIRED_CATEGORIES) . "'";
        $this->db->query("UPDATE service_categories SET status='published' WHERE slug IN ($in)");
        $this->db->query("UPDATE services s JOIN service_categories c ON c.id=s.category_id SET s.status='published' WHERE c.slug IN ($in)");
        $this->db->query("UPDATE products SET status='published' WHERE slug IN ('societyhub','peoplehub')");
        $this->db->query("DELETE FROM services WHERE slug IN ('erp-hrms-workflows','business-process-automation','qa-software-testing','technical-seo-optimisation')");
    }
}
