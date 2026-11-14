<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\WebsitePageModel;
use CodeIgniter\HTTP\ResponseInterface;

class LegalController extends BaseController
{
    public function robots(): ResponseInterface
    {
        // Staging SEO Protection: Universal Disallow
        if (\App\Services\EnvironmentSafetyService::isStaging()) {
            return $this->response
                ->setContentType('text/plain', 'utf-8')
                ->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
                ->setBody("User-agent: *\nDisallow: /\n");
        }

        $baseUrl = rtrim(base_url(), '/');
        $content = "Content-Signal: search=yes, ai-train=no\n\n"
                 . "User-agent: *\n"
                 . "Allow: /\n"
                 . "Disallow: /admin/\n"
                 . "Disallow: /api/\n"
                 . "\n"
                 . "Sitemap: {$baseUrl}/sitemap.xml\n"
                 . "Sitemap: {$baseUrl}/sitemap-jobs.xml\n"
                 . "Sitemap: {$baseUrl}/sitemap-insights.xml\n"
                 . "Sitemap: {$baseUrl}/sitemap-news.xml\n";

        return $this->response
            ->setContentType('text/plain', 'utf-8')
            ->setBody($content);
    }

    public function llmsTxt(): ResponseInterface
    {
        $file = FCPATH . 'llms.txt';
        $content = file_exists($file) ? file_get_contents($file) : '';
        return $this->response
            ->setContentType('text/plain', 'utf-8')
            ->setBody($content);
    }

    public function llmsFullTxt(): ResponseInterface
    {
        $file = FCPATH . 'llms-full.txt';
        $content = file_exists($file) ? file_get_contents($file) : '';
        return $this->response
            ->setContentType('text/plain', 'utf-8')
            ->setBody($content);
    }

    public function mcpServerCard(): ResponseInterface
    {
        $file = FCPATH . '.well-known' . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'server-card.json';
        $content = file_exists($file) ? file_get_contents($file) : '{}';
        return $this->response
            ->setContentType('application/json', 'utf-8')
            ->setBody($content);
    }

    public function privacy(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('privacy');

        $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : 'Privacy Policy & India DPDP Act Compliance | TalentHub';
        $metaDesc = !empty($page['meta_description']) ? $page['meta_description'] : 'TalentHub Consultant privacy policy, DPDP Act 2023 compliance, candidate resume protection, and enterprise hiring confidentiality frameworks.';

        if ($this->wantsMarkdown()) {
            $md = "# Privacy Policy | TalentHub Consultant Pune\n\n"
                . "> How TalentHub Consultant handles, stores, and protects candidate resumes and corporate records.\n\n"
                . strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n\n", $page['content'] ?? "TalentHub Consultant respects your privacy. Candidate resumes are maintained securely and are never shared without briefing and explicit consent.\n\nContact: hr@talenthubconsultant.com\n"));
            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/privacy', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
        ]);
    }

    public function terms(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('terms');

        $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : 'Terms of Service & Advisory Conditions | TalentHub Pune';
        $metaDesc = !empty($page['meta_description']) ? $page['meta_description'] : 'Terms and conditions governing recruitment consultancy, candidate representation, and corporate hiring partnerships with TalentHub Pune.';

        if ($this->wantsMarkdown()) {
            $md = "# Terms & Conditions | TalentHub Consultant Pune\n\n"
                . "> Terms governing corporate recruitment partnerships and candidate placement services.\n\n"
                . strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n\n", $page['content'] ?? "TalentHub Consultant operates under standard contingency recruitment and executive search agreements.\n\nCandidate placement is 100% free of charge.\n\nContact: hr@talenthubconsultant.com\n"));
            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/terms', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
        ]);
    }
}

