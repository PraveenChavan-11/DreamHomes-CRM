<?php

namespace App\Commands;

use App\Services\SitemapService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RegenerateSitemap extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'sitemap:generate';
    protected $description = 'Regenerate and validate dynamic XML sitemaps';

    public function run(array $params)
    {
        CLI::write('Regenerating dynamic XML sitemaps...', 'green');
        $start = microtime(true);
        $service = new SitemapService();

        $service->generateIndexXml();
        $service->generatePagesXml();
        $jobsXml = $service->generateJobsXml();
        $insightsXml = $service->generateInsightsXml();
        $newsXml = $service->generateNewsXml();

        $duration = (int) round((microtime(true) - $start) * 1000);
        $totalUrls = substr_count($jobsXml, '<url>') + substr_count($insightsXml, '<url>') + substr_count($newsXml, '<url>') + 13;

        $service->recordGenerationLog('CRON', $totalUrls, $duration, 'SUCCESS');
        CLI::write("Sitemaps generated: {$totalUrls} URLs in {$duration}ms.", 'yellow');
    }
}

