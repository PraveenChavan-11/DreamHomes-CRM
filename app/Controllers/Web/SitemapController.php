<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\EnvironmentSafetyService;
use App\Services\SitemapService;
use CodeIgniter\HTTP\ResponseInterface;

class SitemapController extends BaseController
{
    /**
     * Staging SEO Guard: Strictly disallow public sitemap rendering in staging
     */
    protected function blockStagingSitemap(): ?ResponseInterface
    {
        if (EnvironmentSafetyService::isStaging()) {
            return $this->response
                ->setStatusCode(404)
                ->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
                ->setContentType('text/plain', 'utf-8')
                ->setBody("Sitemaps are disabled on the staging environment.\n");
        }
        return null;
    }

    public function index(): ResponseInterface
    {
        if ($blocked = $this->blockStagingSitemap()) {
            return $blocked;
        }

        $service = new SitemapService();
        $xml = $service->generateIndexXml();

        return $this->response
            ->setContentType('application/xml', 'utf-8')
            ->setBody($xml);
    }

    public function pages(): ResponseInterface
    {
        if ($blocked = $this->blockStagingSitemap()) {
            return $blocked;
        }

        $service = new SitemapService();
        $xml = $service->generatePagesXml();

        return $this->response
            ->setContentType('application/xml', 'utf-8')
            ->setBody($xml);
    }

    public function jobs(): ResponseInterface
    {
        if ($blocked = $this->blockStagingSitemap()) {
            return $blocked;
        }

        $service = new SitemapService();
        $xml = $service->generateJobsXml();

        return $this->response
            ->setContentType('application/xml', 'utf-8')
            ->setBody($xml);
    }

    public function insights(): ResponseInterface
    {
        if ($blocked = $this->blockStagingSitemap()) {
            return $blocked;
        }

        $service = new SitemapService();
        $xml = $service->generateInsightsXml();

        return $this->response
            ->setContentType('application/xml', 'utf-8')
            ->setBody($xml);
    }

    public function news(): ResponseInterface
    {
        if ($blocked = $this->blockStagingSitemap()) {
            return $blocked;
        }

        $service = new SitemapService();
        $xml = $service->generateNewsXml();

        return $this->response
            ->setContentType('application/xml', 'utf-8')
            ->setBody($xml);
    }
}
