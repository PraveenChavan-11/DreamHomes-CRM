<?php

namespace App\Controllers\Traits;

use App\Services\ProductService;

/**
 * Shared by the public ProductsController and the admin draft preview so both render the identical template.
 * Requires BaseController::renderView().
 */
trait RendersProductPage
{
    /** Resolve a stored relative path (uploads/..., assets/...) to an absolute URL, or null. */
    protected function productAsset(?string $path): ?string
    {
        if (!$path || !preg_match('#^(uploads/products/\d+/[a-f0-9]{32}\.(jpg|png|webp)|assets/img/products/[A-Za-z0-9._-]+)$#', $path)) {
            return null;
        }
        return base_url($path);
    }

    protected function renderProductPage(array $product, bool $preview = false): string
    {
        $slug    = (string) $product['slug'];
        $url     = base_url('products/' . $slug);
        $ogImage = $this->productAsset($product['og_image_path'] ?? null)
            ?? $this->productAsset($product['hero_image_path'] ?? null)
            ?? $this->productAsset($product['logo_path'] ?? null);

        $title = trim((string) ($product['seo_title'] ?? '')) ?: ($product['name'] . (!empty($product['tagline']) ? ' | ' . $product['tagline'] : '')) . ' | TalentHub';
        $desc  = trim((string) ($product['seo_description'] ?? '')) ?: trim((string) ($product['short_description'] ?? ''));
        $desc  = mb_substr($desc, 0, 320);

        $screens  = [];
        $videoUrl = null;
        foreach ($product['media'] as $m) {
            if ($m['kind'] === 'video') {
                $videoUrl = $videoUrl ?? ProductService::videoEmbedUrl((string) $m['path_or_url']);
            } elseif ($u = $this->productAsset($m['path_or_url'])) {
                $screens[] = ['url' => $u, 'caption' => $m['caption'], 'alt' => $m['alt_text'] ?: ($m['caption'] ?: $product['name'] . ' screenshot')];
            }
        }
        if (!empty($product['hero_video_url'])) {
            $videoUrl = ProductService::videoEmbedUrl((string) $product['hero_video_url']) ?? $videoUrl;
        }

        $ld = [[
            '@context'            => 'https://schema.org',
            '@type'               => 'SoftwareApplication',
            'name'                => $product['name'],
            'description'         => $desc,
            'url'                 => $url,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem'     => 'Web',
            'publisher'           => ['@type' => 'Organization', 'name' => 'TalentHub Consultant', 'url' => base_url()],
        ]];
        if ($ogImage) {
            $ld[0]['image'] = $ogImage;
        }
        if (!empty($product['faqs'])) {
            $ld[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array_map(static fn ($f) => [
                    '@type' => 'Question', 'name' => $f['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
                ], $product['faqs']),
            ];
        }

        $signup = $product['signup_url'] ?: ($product['demo_url'] ?: null);
        if (($product['is_flagship'] ?? 0) && !$signup) {
            $signup = $this->siteSettings['worksphere_signup_url'] ?? null;
        }

        return $this->renderView('public/products/show', [
            'product'          => $product,
            'preview'          => $preview,
            'meta_title'       => $title,
            'meta_description' => $desc,
            'canonical_url'    => !empty($product['canonical_url']) ? $product['canonical_url'] : $url,
            'og_image'         => $ogImage,
            'og_type'          => 'website',
            'noindex'          => $preview || !empty($product['no_index']) || ($product['status'] ?? '') !== 'published',
            'json_ld'          => $ld,
            'screens'          => $screens,
            'videoEmbed'       => $videoUrl,
            'signupUrl'        => ($signup && ProductService::validateUrl($signup)) ? $signup : null,
            'logoUrl'          => $this->productAsset($product['logo_path'] ?? null),
            'heroUrl'          => $this->productAsset($product['hero_image_path'] ?? null),
            'formProtection'   => (new \App\Services\FormSecurityService())->renderProtectionFields('project_enquiry'),
            'enquiryAction'    => $preview ? '#' : base_url('products/' . $slug . '/enquiry'),
        ]);
    }
}
