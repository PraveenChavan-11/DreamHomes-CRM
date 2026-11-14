<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\CaseStudyService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Public portfolio. Published entries only; with none published the listing shows an honest empty state and the
 * navigation hides the link. Everything is escaped in the views (no HTML is ever stored).
 */
class CaseStudiesController extends BaseController
{
    public function index()
    {
        $svc = new CaseStudyService();
        $cat = trim((string) $this->request->getGet('category'));
        $cats = $svc->publicCategories();
        if (!in_array($cat, $cats, true)) {
            $cat = '';
        }
        $rows = $svc->publicList($cat);
        foreach ($rows as &$r) {
            $r['cover_url'] = CaseStudyService::assetUrl($r['cover_path'] ?? null);
        }
        unset($r);

        return $this->renderView('public/case_studies/index', [
            'meta_title'       => 'Case Studies | TalentHub',
            'meta_description' => 'Selected recruitment and technology projects delivered by TalentHub.',
            'rows'             => $rows,
            'categories'       => $cats,
            'category'         => $cat,
            // A filtered or empty listing should not compete with the main page in search.
            'noindex'          => $cat !== '' || $rows === [],
        ]);
    }

    public function show(string $slug)
    {
        $svc = new CaseStudyService();
        $cs  = $svc->findPublished($slug);
        if (!$cs) {
            throw PageNotFoundException::forPageNotFound('Case study not found.');
        }
        $cover = CaseStudyService::assetUrl($cs['cover_path'] ?? null);
        $images = [];
        foreach ($svc->images((int) $cs['id']) as $im) {
            if ($u = CaseStudyService::assetUrl($im['path'])) {
                $images[] = ['url' => $u, 'caption' => (string) $im['caption'], 'alt' => (string) ($im['alt_text'] ?: ($im['caption'] ?: $cs['title'] . ' project image'))];
            }
        }
        $url  = base_url('case-studies/' . $cs['slug']);
        $desc = mb_substr(trim((string) ($cs['seo_description'] ?: $cs['summary'])), 0, 320);
        $ld = [[
            '@context'      => 'https://schema.org',
            '@type'         => 'CreativeWork',
            'name'          => $cs['title'],
            'description'   => $desc,
            'url'           => $url,
            'datePublished' => $cs['published_at'] ? date('c', strtotime($cs['published_at'])) : null,
            'publisher'     => ['@type' => 'Organization', 'name' => 'TalentHub Consultant', 'url' => base_url()],
        ]];
        if ($cover) {
            $ld[0]['image'] = $cover;
        }
        $ld[0] = array_filter($ld[0], static fn ($v) => $v !== null);

        return $this->renderView('public/case_studies/show', [
            'meta_title'       => ($cs['seo_title'] ?: $cs['title']) . ' | TalentHub Case Study',
            'meta_description' => $desc,
            'og_image'         => $cover,
            'og_type'          => 'article',
            'json_ld'          => $ld,
            'cs'               => $cs,
            'cover'            => $cover,
            'images'           => $images,
        ]);
    }
}
