<?php

namespace App\Controllers;

use App\Models\WebsiteSettingModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation.
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'text', 'html', 'phone'];

    /**
     * Public website settings cached for the request
     */
    protected array $siteSettings = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Load public settings once
        try {
            $settingModel = new WebsiteSettingModel();
            $this->siteSettings = $settingModel->getPublicSettings();
        } catch (\Throwable $e) {
            $this->siteSettings = [];
        }

        // Capture UTM Campaign Attribution Parameters (GAP-08)
        try {
            $utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
            $capturedUtms = [];
            foreach ($utmKeys as $key) {
                $val = $this->request->getGet($key);
                if (!empty($val)) {
                    $capturedUtms[$key] = substr(strip_tags((string) $val), 0, 100);
                }
            }
            if (!empty($capturedUtms)) {
                $session = session();
                $existingUtms = (array) ($session->get('landing_utm_params') ?? []);
                $session->set('landing_utm_params', array_merge($existingUtms, $capturedUtms));
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Render a public page view wrapped with the unified layout
     */
    protected function renderView(string $viewPath, array $data = []): string
    {
        $data['settings'] = $this->siteSettings;
        $data['currentUri'] = $this->request->getUri()->getPath();

        $rawPath = trim(uri_string(), '/');
        if (empty($rawPath)) {
            $rawPath = trim($this->request->getUri()->getPath(), '/');
            $rawPath = preg_replace('#^index\.php/?#', '', $rawPath);
        }
        $slug = empty($rawPath) ? 'home' : $rawPath;

        // Canonical route aliases mapping to website_pages slugs
        $slugAliases = [
            ''                            => 'home',
            'home'                        => 'home',
            'about'                       => 'about',
            'about-us'                    => 'about',
            'solutions'                   => 'solutions',
            'employers'                   => 'employers',
            'candidates'                  => 'candidates',
            'jobs'                        => 'jobs',
            'career'                      => 'career-enquiry',
            'careers'                     => 'career-enquiry',
            'career-enquiry'              => 'career-enquiry',
            'insights'                    => 'insights',
            'blog'                        => 'insights',
            'interviews'                  => 'interviews',
            'interview-guides'            => 'interviews',
            'contact'                     => 'contact',
            'contact-us'                  => 'contact',
            'faq'                         => 'faq',
            'faqs'                        => 'faq',
            'privacy'                     => 'privacy',
            'privacy-policy'              => 'privacy',
            'terms'                       => 'terms',
            'terms-of-service'            => 'terms',
            'terms-and-conditions'        => 'terms',
            'application/tracker'         => 'application-tracker',
            'application-tracker'         => 'application-tracker',
            'privacy/request-cv-deletion' => 'request-cv-deletion',
            'request-cv-deletion'         => 'request-cv-deletion',
        ];

        $targetSlug = $slugAliases[$slug] ?? $slug;

        // Determine if this is an individual item detail view (job or blog post detail)
        $isItemDetail = !empty($data['job']) 
                     || !empty($data['post']) 
                     || str_contains($viewPath, 'submission_status');

        // Dynamic Page SEO lookup from website_pages CMS
        try {
            $pageModel = new \App\Models\WebsitePageModel();
            $pageRecord = $pageModel->where('slug', $targetSlug)->first();
            if (!$pageRecord && str_contains($targetSlug, '/')) {
                $primarySegment = explode('/', $targetSlug)[0];
                $pageRecord = $pageModel->where('slug', $primarySegment)->first();
            }

            if ($pageRecord) {
                if (!$isItemDetail) {
                    // For standard CMS pages, database website_pages is the authoritative source of truth!
                    if (!empty($pageRecord['meta_title'])) {
                        $data['meta_title'] = $pageRecord['meta_title'];
                    }
                    if (!empty($pageRecord['meta_description'])) {
                        $data['meta_description'] = $pageRecord['meta_description'];
                    }
                    if (!empty($pageRecord['meta_keywords'])) {
                        $data['meta_keywords'] = $pageRecord['meta_keywords'];
                    }
                    if (!empty($pageRecord['og_image'])) {
                        $data['og_image'] = base_url($pageRecord['og_image']);
                    }
                } else {
                    // For detail views (e.g. /jobs/:slug, /insights/:slug), only fallback if item title is empty
                    if (empty($data['meta_title']) && !empty($pageRecord['meta_title'])) {
                        $data['meta_title'] = $pageRecord['meta_title'];
                    }
                    if (empty($data['meta_description']) && !empty($pageRecord['meta_description'])) {
                        $data['meta_description'] = $pageRecord['meta_description'];
                    }
                    if (empty($data['meta_keywords']) && !empty($pageRecord['meta_keywords'])) {
                        $data['meta_keywords'] = $pageRecord['meta_keywords'];
                    }
                }

                if (!isset($data['page'])) {
                    $data['page'] = $pageRecord;
                }
            }
        } catch (\Throwable $e) {}

        // Default SEO meta fallback
        if (!isset($data['meta_title'])) {
            $data['meta_title'] = $this->siteSettings['site_name'] ?? 'TalentHub Consultant';
            if (!empty($this->siteSettings['site_tagline'])) {
                $data['meta_title'] .= ' — ' . $this->siteSettings['site_tagline'];
            }
        }
        if (!isset($data['meta_description'])) {
            $data['meta_description'] = $this->siteSettings['site_description'] ?? 'TalentHub Consultant connects top engineering, tech, and leadership talent with Pune\'s leading enterprises.';
        }
        if (!isset($data['meta_keywords'])) {
            $data['meta_keywords'] = 'recruitment agency Pune, placement consultancy Pune, IT jobs Hinjawadi, staffing agency Maharashtra, engineering jobs Chakan, executive search Pune';
        }

        $data['viewContent'] = view($viewPath, $data, ['saveData' => false]);
        return view('public/layouts/main', $data, ['saveData' => false]);
    }

    /**
     * Determine if current request expects Markdown representation (Autonomous Agent requests)
     */
    protected function wantsMarkdown(): bool
    {
        $accept = strtolower((string) ($this->request->getHeaderLine('Accept') ?? ''));
        $format = strtolower((string) ($this->request->getGet('format') ?? ''));

        return str_contains($accept, 'text/markdown')
            || str_contains($accept, 'text/x-markdown')
            || in_array($format, ['md', 'markdown'], true);
    }

    /**
     * Respond with a standardized Markdown document for AI agents
     */
    protected function respondMarkdown(string $markdown, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setContentType('text/markdown', 'utf-8')
            ->setHeader('Vary', 'Accept')
            ->setBody($markdown);
    }

    /**
     * Determine if current request expects JSON or targets an API endpoint
     */
    protected function isApiRequest(): bool
    {
        $uri = ltrim($this->request->getUri()->getPath(), '/');
        $uri = preg_replace('#^index\.php/#', '', $uri);
        return str_starts_with($uri, 'api/')
            || stripos($this->request->getHeaderLine('Accept'), 'application/json') !== false
            || stripos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false
            || $this->request->isAJAX();
    }

    /**
     * Standard JSON success response
     */
    protected function respondJson($data = [], string $message = 'Success', int $status = 200, array $meta = []): ResponseInterface
    {
        $payload = [
            'success'    => true,
            'status'     => $status,
            'message'    => $message,
            'data'       => $data,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return $this->response
            ->setStatusCode($status)
            ->setContentType('application/json')
            ->setJSON($payload);
    }

    /**
     * Safely parse request body JSON without throwing HTTPException on form-urlencoded posts
     */
    protected function getSafeJson(): array
    {
        $contentType = $this->request->header('Content-Type') ? $this->request->header('Content-Type')->getValue() : '';
        if (is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
                return is_array($json) ? $json : [];
            } catch (\Throwable $e) {
                return [];
            }
        }
        return [];
    }

    /**
     * Safely resolve input from JSON, PUT/PATCH raw input, or POST data
     */
    protected function getSafeInput(): array
    {
        $json = $this->getSafeJson();
        if (!empty($json)) {
            return $json;
        }

        try {
            $raw = $this->request->getRawInput();
            if (!empty($raw) && is_array($raw)) {
                return $raw;
            }
        } catch (\Throwable $e) {}

        return (array) ($this->request->getPost() ?: []);
    }

    /**
     * Standard JSON error response
     */
    protected function respondError(string $message = 'An error occurred', int $status = 400, ?string $code = null, $details = null): ResponseInterface
    {
        $payload = [
            'success'    => false,
            'status'     => $status,
            'code'       => $code ?? 'ERROR_' . $status,
            'message'    => $message,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        return $this->response
            ->setStatusCode($status)
            ->setContentType('application/json')
            ->setJSON($payload);
    }
}
