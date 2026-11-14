<?php

namespace App\Services;

use App\Models\BlogPostModel;
use App\Models\JobModel;
use App\Models\SitemapLogModel;
use App\Models\WebsitePageModel;

class SitemapService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(base_url(), '/');
    }

    /**
     * Generate the complete master XML sitemap index
     */
    public function generateIndexXml(): string
    {
        $now = date('c');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= "  <sitemap>\n    <loc>{$this->baseUrl}/sitemap-pages.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
        $xml .= "  <sitemap>\n    <loc>{$this->baseUrl}/sitemap-jobs.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
        $xml .= "  <sitemap>\n    <loc>{$this->baseUrl}/sitemap-insights.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
        $xml .= "  <sitemap>\n    <loc>{$this->baseUrl}/sitemap-news.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Generate sitemap for static & CMS pages
     */
    public function generatePagesXml(): string
    {
        $corePages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/jobs', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => '/employers', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/candidates', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/career-enquiry', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/solutions', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/services', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/services/recruitment', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => '/services/it-services', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => '/about', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/insights', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        // Product catalogue (published, indexable products only)
        try {
            $corePages[] = ['loc' => '/products', 'priority' => '0.8', 'changefreq' => 'weekly'];
            foreach ((new ProductService())->sitemapRows() as $p) {
                $corePages[] = [
                    'loc'        => '/products/' . $p['slug'],
                    'lastmod'    => date('c', strtotime($p['updated_at'] ?? $p['published_at'] ?? 'now')),
                    'changefreq' => 'monthly',
                    'priority'   => '0.8',
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Sitemap products failed: ' . $e->getMessage());
        }

        // Case studies (published only; the section is omitted entirely while none exist)
        try {
            $csRows = (new CaseStudyService())->sitemapRows();
            if ($csRows) {
                $corePages[] = ['loc' => '/case-studies', 'priority' => '0.7', 'changefreq' => 'weekly'];
                foreach ($csRows as $c) {
                    $corePages[] = [
                        'loc'        => '/case-studies/' . $c['slug'],
                        'lastmod'    => date('c', strtotime($c['updated_at'] ?? $c['published_at'] ?? 'now')),
                        'changefreq' => 'monthly',
                        'priority'   => '0.6',
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Sitemap case studies failed: ' . $e->getMessage());
        }

        return $this->buildUrlset($corePages);
    }

    /**
     * Generate sitemap for active jobs
     */
    public function generateJobsXml(): string
    {
        $jobModel = new JobModel();
        $jobs = $jobModel->whereIn('status', ['PUBLISHED', 'active'])
                         ->where('deleted_at IS NULL')
                         ->orderBy('published_at', 'DESC')
                         ->findAll(500);

        $urls = [];
        foreach ($jobs as $job) {
            $urls[] = [
                'loc'        => '/jobs/' . $job['slug'],
                'lastmod'    => date('c', strtotime($job['updated_at'] ?? $job['published_at'] ?? 'now')),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        return $this->buildUrlset($urls);
    }

    /**
     * Generate sitemap for published insights (blogs)
     */
    public function generateInsightsXml(): string
    {
        $blogModel = new BlogPostModel();
        $posts = $blogModel->where('is_published', 1)
                           ->orderBy('published_at', 'DESC')
                           ->findAll(500);

        $urls = [];
        foreach ($posts as $post) {
            $urls[] = [
                'loc'        => '/insights/' . $post['slug'],
                'lastmod'    => date('c', strtotime($post['updated_at'] ?? $post['published_at'] ?? 'now')),
                'changefreq' => 'monthly',
                'priority'   => '0.7',
            ];
        }

        return $this->buildUrlset($urls);
    }

    /**
     * Generate dynamic Google News XML sitemap compliant with Google News schema
     */
    public function generateNewsXml(): string
    {
        $blogModel = new BlogPostModel();
        $posts = $blogModel->where('is_published', 1)
                           ->orderBy('published_at', 'DESC')
                           ->findAll(250);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

        foreach ($posts as $post) {
            $loc = htmlspecialchars($this->baseUrl . '/insights/' . $post['slug'], ENT_XML1, 'UTF-8');
            $rawDate = !empty($post['published_at']) ? $post['published_at'] : (!empty($post['created_at']) ? $post['created_at'] : 'now');
            $pubDate = date('Y-m-d\TH:i:sP', strtotime($rawDate));
            $title = htmlspecialchars(strip_tags($post['title'] ?? ''), ENT_XML1, 'UTF-8');

            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <news:news>\n";
            $xml .= "      <news:publication>\n";
            $xml .= "        <news:name>TalentHub Consultant Insights</news:name>\n";
            $xml .= "        <news:language>en</news:language>\n";
            $xml .= "      </news:publication>\n";
            $xml .= "      <news:publication_date>{$pubDate}</news:publication_date>\n";
            $xml .= "      <news:title>{$title}</news:title>\n";
            $xml .= "    </news:news>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Helper to build valid XML urlset
     */
    private function buildUrlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $loc = htmlspecialchars($this->baseUrl . $u['loc'], ENT_XML1, 'UTF-8');
            $lastmod = !empty($u['lastmod']) ? $u['lastmod'] : date('c');
            $changefreq = $u['changefreq'] ?? 'weekly';
            $priority = $u['priority'] ?? '0.5';

            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Log sitemap generation telemetry
     */
    public function recordGenerationLog(string $source, int $totalUrls, int $durationMs, string $status = 'SUCCESS', ?string $error = null): void
    {
        $logModel = new SitemapLogModel();
        $logModel->insert([
            'trigger_source' => $source,
            'total_urls'     => $totalUrls,
            'duration_ms'    => $durationMs,
            'status'         => $status,
            'error_message'  => $error,
        ]);
    }
}

