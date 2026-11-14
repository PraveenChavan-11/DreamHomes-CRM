<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\BlogPostModel;
use App\Models\WebsitePageModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class InsightsController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $pageRecord = $pageModel->getPublishedBySlug('insights');

        $blogModel = new BlogPostModel();

        $page = (int) ($this->request->getGet('page') ?? 1);
        $page = max(1, $page);
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $posts = $blogModel->getPublishedPosts($limit, $offset);
        $totalPosts = $blogModel->where('is_published', 1)->countAllResults();
        $totalPages = (int) ceil($totalPosts / $limit);

        $metaTitle = !empty($pageRecord['meta_title']) ? $pageRecord['meta_title'] : 'Pune Hiring Market Trends & Salary Insights | TalentHub';
        $metaDesc = !empty($pageRecord['meta_description']) ? $pageRecord['meta_description'] : 'Expert market intelligence, IT hiring benchmarks, engineering salaries, and recruitment insights for Pune enterprises and career seekers.';

        if ($this->wantsMarkdown()) {
            $md = "# Pune Hiring Trends & Career Insights | TalentHub Consultant\n\n"
                . "> Expert market intelligence, IT hiring benchmarks, engineering salaries, and recruitment insights for Pune enterprises and career seekers.\n\n";

            foreach ($posts as $post) {
                $md .= "### [" . esc($post['title']) . "](" . base_url('insights/' . $post['slug']) . ")\n"
                    . esc($post['summary'] ?? '') . "\n"
                    . "- **Published**: " . date('M d, Y', strtotime($post['published_at'] ?? 'now')) . "\n"
                    . "- **Read Article**: " . base_url('insights/' . $post['slug']) . "\n\n";
            }

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/insights/index', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $pageRecord,
            'posts'            => $posts,
            'totalPosts'       => $totalPosts,
            'currentPage'      => $page,
            'totalPages'       => $totalPages,
        ]);
    }

    public function show(string $slug): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $blogModel = new BlogPostModel();
        $post = $blogModel->getPublishedBySlug($slug);

        if (!$post) {
            throw PageNotFoundException::forPageNotFound("Insights article '{$slug}' not found.");
        }

        // Recent 3 other posts
        $relatedPosts = $blogModel->where('is_published', 1)
                                  ->where('id !=', $post['id'])
                                  ->orderBy('published_at', 'DESC')
                                  ->findAll(3);

        if (!empty($post['meta_title'])) {
            $title = str_contains($post['meta_title'], 'TalentHub') ? $post['meta_title'] : ($post['meta_title'] . ' | TalentHub Insights');
        } else {
            $title = $post['title'] . ' | TalentHub Insights';
        }
        $desc = $post['meta_description'] ?: $post['summary'];

        if ($this->wantsMarkdown()) {
            $md = "# {$post['title']}\n\n"
                . "> " . ($post['summary'] ?? '') . "\n\n"
                . "- **Published**: " . date('F j, Y', strtotime($post['published_at'] ?? 'now')) . "\n"
                . "- **Author**: " . esc($post['author'] ?? 'TalentHub Research Team') . "\n\n"
                . strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n\n", $post['content'] ?? '')) . "\n\n"
                . "## Related Articles\n";

            foreach ($relatedPosts as $r) {
                $md .= "- [" . esc($r['title']) . "](" . base_url('insights/' . $r['slug']) . ")\n";
            }

            return $this->respondMarkdown($md);
        }

        $keywords = !empty($post['meta_keywords']) ? $post['meta_keywords'] : ($post['title'] . ', Pune recruitment intelligence, IT hiring benchmarks, career insights Pune');
        $featuredImg = !empty($post['featured_image']) ? base_url($post['featured_image']) : null;

        return $this->renderView('public/insights/show', [
            'meta_title'       => $title,
            'meta_description' => $desc,
            'meta_keywords'    => $keywords,
            'og_type'          => 'article',
            'og_image'         => $featuredImg,
            'post'             => $post,
            'relatedPosts'     => $relatedPosts,
        ]);
    }
}

