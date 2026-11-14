<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Controllers\Traits\RendersProductPage;
use App\Services\FormSecurityService;
use App\Services\ItLeadService;
use App\Services\ProductService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Public product catalogue.
 * GET  /products                      published products only
 * GET  /products/{slug}               published product (draft/archived/deleted -> 404; drafts are previewed in admin)
 * POST /products/{slug}/enquiry       creates an IT Services CRM lead (CSRF + honeypot/captcha + rate limit)
 */
class ProductsController extends BaseController
{
    use RendersProductPage;

    /** Slugs of the retired placeholder products: keep their old URLs working. */
    private const LEGACY_SLUGS = ['societyhub', 'peoplehub'];

    /** What a flagship-product enquiry is about (whitelist: anything else is ignored). Shown on the form and stamped on the lead. */
    public const ENGAGEMENTS = [
        ''             => 'Not sure yet',
        'subscription' => 'A subscription for my organisation',
        'licence'      => 'A licence with source code (IT / software company)',
        'partner'      => 'Reselling, white-labelling or implementing for clients',
    ];

    public function index()
    {
        $svc      = new ProductService();
        $category = trim((string) $this->request->getGet('category'));
        $cats     = $svc->publicCategories();
        if (!in_array($category, $cats, true)) {
            $category = '';
        }
        $products = $svc->publicList($category);
        foreach ($products as &$p) {
            $p['logo_url'] = $this->productAsset($p['logo_path'] ?? null);
            $p['hero_url'] = $this->productAsset($p['hero_image_path'] ?? null);
        }
        unset($p);

        return $this->renderView('public/products/index', [
            'meta_title'        => 'Products | TalentHub',
            'meta_description'  => 'Enterprise software products from TalentHub, including WorkSphere HRMS and upcoming platforms.',
            'products'          => $products,
            'categories'        => $cats,
            'category'          => $category,
            'coming_soon_count' => ($category === '') ? 2 : 0,
        ]);
    }

    public function show(string $slug)
    {
        $svc = new ProductService();
        $p   = $svc->findPublished($slug);
        if (!$p) {
            if (in_array($slug, self::LEGACY_SLUGS, true)) {
                return redirect()->to(base_url('services/it-services'))->setStatusCode(301);
            }
            throw PageNotFoundException::forPageNotFound('Product not found.');
        }
        return $this->renderProductPage($svc->withChildren($p));
    }

    public function enquiry(string $slug)
    {
        $svc = new ProductService();
        $p   = $svc->findPublished($slug);
        if (!$p) {
            throw PageNotFoundException::forPageNotFound('Product not found.');
        }
        $back = base_url('products/' . $slug) . '#enquiry';

        $post      = $this->request->getPost() ?: [];
        $wantsJson = $this->request->isAJAX();
        $fail = function (string $msg, int $status, string $code, array $errors = []) use ($wantsJson, $back) {
            if ($wantsJson) {
                return $this->respondError($msg, $status, $code, $errors);
            }
            return redirect()->to($back)->withInput()->with('product_enquiry_error', $msg);
        };

        $sec = (new FormSecurityService())->validateSubmission($post, 'project_enquiry', $this->request->getIPAddress());
        if (!$sec['valid']) {
            return $fail($sec['message'], 422, $sec['code']);
        }
        if (empty($post['consent'])) {
            return $fail('Please consent to being contacted under the Privacy Policy.', 422, 'CONSENT_REQUIRED');
        }

        $name   = trim((string) ($post['client_name'] ?? ''));
        $email  = strtolower(trim((string) ($post['email'] ?? '')));
        $msg    = trim((string) ($post['project_description'] ?? ''));
        $phone  = normalize_phone_number((string) ($post['phone'] ?? ''));
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors['client_name'] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!$phone) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        if (mb_strlen($msg) < 10 || mb_strlen($msg) > 3900) {
            $errors['project_description'] = 'Please tell us a little about your requirement (10-3900 characters).';
        }
        if ($errors) {
            return $fail('Please correct the highlighted fields: ' . implode(' ', $errors), 422, 'VALIDATION_FAILED', $errors);
        }

        $utm  = [];
        $sess = (array) (session()->get('landing_utm_params') ?? []);
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
            $v       = trim((string) ($sess[$k] ?? ''));
            $utm[$k] = $v !== '' ? mb_substr(preg_replace('/[^\w\-\.\s\+%]/u', '', $v), 0, 160) : null;
        }

        try {
            $res = (new ItLeadService())->create([
                'client_name'         => $name,
                'company'             => mb_substr(trim((string) ($post['company'] ?? '')), 0, 150),
                'email'               => $email,
                'phone'               => $phone,
                'location'            => '',
                'service_category'    => 'other',
                'service_slug'        => $p['slug'],
                'project_description' => '[Product enquiry: ' . $p['name'] . "]\n"
                    . (($eng = (string) ($post['engagement'] ?? '')) !== '' && isset(self::ENGAGEMENTS[$eng]) && !empty($p['is_flagship'])
                        ? '[Interested in: ' . self::ENGAGEMENTS[$eng] . "]\n" : '')
                    . $msg,
                'budget_range'        => '',
                'timeline'            => '',
                'preferred_contact'   => '',
                'source_page'         => 'products/' . $p['slug'],
                'ip_address'          => $this->request->getIPAddress(),
                'source_system'       => 'website',
                'source'              => 'product_enquiry',
                'product_slug'        => $p['slug'],
            ] + $utm);
        } catch (\Throwable $e) {
            log_message('error', 'Product enquiry save failed: ' . $e->getMessage());
            return $fail('We could not save your enquiry right now. Please try again shortly or contact us directly.', 500, 'SAVE_FAILED');
        }
        if (empty($res['id'])) {
            return $fail('We could not save your enquiry right now. Please try again shortly or contact us directly.', 500, 'SAVE_FAILED');
        }

        if (empty($res['idempotent'])) {
            try {
                (new \App\Services\CrmNotificationService())->notify('product_enquiry', (int) $res['id']);
            } catch (\Throwable $e) {
                log_message('error', 'Product enquiry notification failed: ' . $e->getMessage());
            }
        }

        $ok = 'Thank you. Your enquiry about ' . $p['name'] . ' has been received and our team will contact you.';
        if ($wantsJson) {
            return $this->respondJson(['id' => $res['id']], $ok, 201);
        }
        return redirect()->to(base_url('thank-you?type=enquiry&ref=' . $res['id']))->with('success', $ok);
    }
}
