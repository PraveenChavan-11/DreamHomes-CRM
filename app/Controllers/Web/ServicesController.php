<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Libraries\ServiceJourneys;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Public services: exactly two primary services (Recruitment, IT Services).
 * Legacy product and capability URLs 301-redirect into this structure.
 */
class ServicesController extends BaseController
{
    private const PRIMARY   = ['recruitment', 'it-services'];
    private const LEGACY_IT = ['erp-hrms', 'qa-testing', 'digital-transformation', 'digital-marketing'];

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    /** Returns $default if the catalogue tables are unavailable (keeps the site up). */
    private function safe(callable $fn, $default = [])
    {
        try {
            static $ensured = false;
            if (!$ensured) {
                if (!$this->db()->tableExists('services')) {
                    require_once APPPATH . 'Database/Migrations/20261105000001_create_service_catalogue.php';
                    \App\Database\Migrations\CreateServiceCatalogue::apply($this->db());
                }
                require_once APPPATH . 'Database/Migrations/20261106000001_two_service_structure.php';
                if (\App\Database\Migrations\TwoServiceStructure::needed($this->db())) {
                    \App\Database\Migrations\TwoServiceStructure::apply($this->db());
                }
                $ensured = true;
            }
            return $fn($this->db());
        } catch (\Throwable $e) {
            log_message('error', 'ServicesController: ' . $e->getMessage());
            return $default;
        }
    }

    private function redirectTo(string $path)
    {
        return redirect()->to(base_url($path))->setStatusCode(301);
    }

    /** Fixed brand positioning for the two services. */
    private function primary(): array
    {
        return [
            'recruitment' => [
                'slug' => 'recruitment',
                'name' => 'Recruitment & Talent Acquisition',
                'tagline' => 'The right talent. The right opportunity. The right connection.',
                'summary' => 'TalentHub helps organisations identify, attract and acquire the right talent through structured recruitment solutions across India. We support startups, SMEs and enterprises with permanent, contract, specialist and high-volume hiring.',
            ],
            'it-services' => [
                'slug' => 'it-services',
                'name' => 'IT Services & Software Development',
                'tagline' => 'Turning business requirements into powerful digital solutions.',
                'summary' => 'TalentHub delivers custom websites, web applications, enterprise portals, APIs and business software designed around real operational requirements. We help organisations build new digital solutions, improve existing systems and streamline business processes.',
            ],
        ];
    }

    private function capabilities(string $slug): array
    {
        $rows = $this->safe(fn ($db) => $db->table('services s')->select('s.name')
            ->join('service_categories c', 'c.id = s.category_id')
            ->where('c.slug', $slug)->where('s.status', 'published')->where('s.deleted_at IS NULL')
            ->orderBy('s.sort_order')->get()->getResultArray());
        return $rows ? array_column($rows, 'name') : ServiceJourneys::fallbackCapabilities($slug);
    }

    public function index()
    {
        $cats = $this->primary();
        foreach ($cats as $slug => &$c) {
            $c['capabilities'] = $this->capabilities($slug);
        }
        unset($c);
        return $this->renderView('public/services/index', [
            'meta_title'       => 'Services — Recruitment & IT Software Development | TalentHub',
            'meta_description' => 'Recruitment & talent acquisition and IT services & software development from one consulting partner, for startups, SMEs and enterprises across India.',
            'categories'       => $cats,
        ]);
    }

    public function category(string $slug)
    {
        if (in_array($slug, self::LEGACY_IT, true)) {
            return $this->redirectTo('services/it-services');
        }
        if (!in_array($slug, self::PRIMARY, true)) {
            throw PageNotFoundException::forPageNotFound('Service not found.');
        }
        $isRecruit = $slug === 'recruitment';
        return $this->renderView('public/services/category', [
            'meta_title'       => $isRecruit ? 'Recruitment & Talent Acquisition Services in India | TalentHub' : 'IT Services & Software Development in India | TalentHub',
            'meta_description' => $isRecruit
                ? 'Structured permanent, contract, specialist and high-volume recruitment across India for startups, SMEs and enterprises.'
                : 'Custom software, websites, web and mobile applications, APIs and enterprise portals built around your operational requirements.',
            'category'         => $this->primary()[$slug],
            'capabilities'     => $this->capabilities($slug),
            'journey'          => $isRecruit ? ServiceJourneys::recruitment() : ServiceJourneys::it(),
            'isRecruit'        => $isRecruit,
        ]);
    }

    /** /services/{category}/{capability} -> 301 to the parent service page. */
    public function legacyService(string $catSlug, string $slug)
    {
        if ($catSlug === 'recruitment') {
            return $this->redirectTo('services/recruitment');
        }
        if ($catSlug === 'it-services' || in_array($catSlug, self::LEGACY_IT, true)) {
            return $this->redirectTo('services/it-services');
        }
        throw PageNotFoundException::forPageNotFound('Service not found.');
    }

    /** /products and /products/* (retired catalogue) -> 301. */
    public function legacyProducts(?string $slug = null)
    {
        return $this->redirectTo($slug === null ? 'services' : 'services/it-services');
    }
}
