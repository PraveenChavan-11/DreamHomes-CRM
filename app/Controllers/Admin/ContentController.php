<?php

namespace App\Controllers\Admin;

use App\Models\BlogPostModel;
use App\Models\FaqModel;
use App\Models\TestimonialModel;
use App\Models\WebsitePageModel;
use App\Services\EnquiryAutomationService;

class ContentController extends AdminBaseController
{
    public function pages()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $pageModel = new WebsitePageModel();
        $pages = $pageModel->orderBy('slug', 'ASC')->findAll();

        return $this->renderAdminView('admin/content/pages', [
            'pages' => $pages,
            'title' => 'Website Pages & Content Management — TalentHub Admin',
        ], 'pages');
    }

    public function editPage($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $pageModel = new WebsitePageModel();
        $page = $pageModel->find($id);

        if (!$page) {
            session()->setFlashdata('error', 'Page not found.');
            return redirect()->to(base_url('admin/content/pages'));
        }

        return $this->renderAdminView('admin/content/page_form', [
            'page'  => $page,
            'title' => "Edit Page: {$page['title']} — TalentHub Admin",
        ], 'pages');
    }

    public function updatePage($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $pageModel = new WebsitePageModel();
        $page = $pageModel->find($id);

        if (!$page) {
            session()->setFlashdata('error', 'Page not found.');
            return redirect()->to(base_url('admin/content/pages'));
        }

        $title = trim($this->request->getPost('title') ?? '');
        $data = [
            'title'            => $title,
            'meta_title'       => trim($this->request->getPost('meta_title') ?? ''),
            'meta_description' => trim($this->request->getPost('meta_description') ?? ''),
            'meta_keywords'    => trim($this->request->getPost('meta_keywords') ?? ''),
            'og_image'         => trim($this->request->getPost('og_image') ?? ''),
        ];

        try {
            $pageModel->update($id, $data);
            session()->setFlashdata('success', "Page '{$title}' updated successfully.");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to update page: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/content/pages'));
    }

    public function testimonials()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $testimonialModel = new TestimonialModel();
        $testimonials = $testimonialModel->orderBy('created_at', 'DESC')->findAll();

        return $this->renderAdminView('admin/content/testimonials', [
            'testimonials' => $testimonials,
            'title'        => 'Testimonials Management — TalentHub Admin',
        ], 'testimonials');
    }

    public function storeTestimonial()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $testimonialModel = new TestimonialModel();
        $name        = trim((string)($this->request->getPost('name') ?? $this->request->getPost('client_name') ?? ''));
        $designation = trim((string)($this->request->getPost('designation') ?? $this->request->getPost('client_role') ?? ''));
        $company     = trim((string)($this->request->getPost('company') ?? $this->request->getPost('company_name') ?? ''));
        $content     = trim((string)($this->request->getPost('content') ?? $this->request->getPost('quote') ?? ''));
        $rating      = (int)($this->request->getPost('rating') ?? 5);

        $data = [
            'name'         => $name,
            'designation'  => $designation,
            'company'      => $company,
            'content'      => $content,
            'rating'       => $rating,
            'is_published' => 1,
            'order_num'    => 0,
        ];

        $testimonialModel->insert($data);
        session()->setFlashdata('success', 'Testimonial created successfully.');
        return redirect()->to(base_url('admin/content/testimonials'));
    }

    public function deleteTestimonial($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $testimonialModel = new TestimonialModel();
        $testimonialModel->delete($id);
        session()->setFlashdata('success', 'Testimonial removed.');
        return redirect()->to(base_url('admin/content/testimonials'));
    }

    public function faqs()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $faqModel = new FaqModel();
        try {
            $faqs = $faqModel->orderBy('order_num', 'ASC')->findAll();
        } catch (\Throwable $e) {
            try {
                $faqs = $faqModel->orderBy('id', 'ASC')->findAll();
            } catch (\Throwable $e2) {
                $faqs = [];
            }
        }

        return $this->renderAdminView('admin/content/faqs', [
            'faqs'  => $faqs,
            'title' => 'FAQ Management — TalentHub Admin',
        ], 'faqs');
    }

    public function storeFaq()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $faqModel = new FaqModel();
        $data = [
            'question'     => trim((string)($this->request->getPost('question') ?? '')),
            'answer'       => trim((string)($this->request->getPost('answer') ?? '')),
            'category'     => trim((string)($this->request->getPost('category') ?? 'General')),
            'order_num'    => (int)($this->request->getPost('order_num') ?? $this->request->getPost('display_order') ?? 0),
            'is_published' => 1,
        ];

        $faqModel->insert($data);
        session()->setFlashdata('success', 'FAQ question added.');
        return redirect()->to(base_url('admin/content/faqs'));
    }

    public function deleteFaq($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $faqModel = new FaqModel();
        $faqModel->delete($id);
        session()->setFlashdata('success', 'FAQ question removed.');
        return redirect()->to(base_url('admin/content/faqs'));
    }

    /**
     * Articles & Insights Management
     */
    public function articles()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $blogModel = new BlogPostModel();
        $articles = $blogModel->select('blog_posts.*, blog_categories.name as category_name')
            ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left')
            ->orderBy('blog_posts.id', 'DESC')
            ->findAll();

        return $this->renderAdminView('admin/content/articles', [
            'articles' => $articles,
            'title'    => 'Articles & Insights CMS — TalentHub Admin',
        ], 'articles');
    }

    public function newArticle()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $db = \Config\Database::connect();
        $categories = $db->table('blog_categories')->orderBy('name', 'ASC')->get()->getResultArray();

        return $this->renderAdminView('admin/content/article_form', [
            'article'    => null,
            'categories' => $categories,
            'title'      => 'New Article Publication — TalentHub Admin',
        ], 'articles');
    }

    public function storeArticle()
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug = url_title(trim((string) ($this->request->getPost('slug') ?: $title)), '-', true);
        $isPublished = $this->request->getPost('is_published') ? 1 : 0;

        $data = [
            'category_id'      => (int) $this->request->getPost('category_id'),
            'title'            => $title,
            'slug'             => $slug,
            'summary'          => (string) $this->request->getPost('summary'),
            'content'          => (string) $this->request->getPost('content'),
            'featured_image'   => trim((string) $this->request->getPost('featured_image')),
            'meta_title'       => trim((string) ($this->request->getPost('meta_title') ?: $title)),
            'meta_description' => trim((string) ($this->request->getPost('meta_description') ?: $this->request->getPost('summary'))),
            'meta_keywords'    => trim((string) $this->request->getPost('meta_keywords')),
            'is_published'     => $isPublished,
            'published_at'     => $isPublished ? date('Y-m-d H:i:s') : null,
            'author_id'        => $this->adminUser['id'] ?? 1,
        ];

        $blogModel = new BlogPostModel();
        try {
            $insertId = $blogModel->insert($data);

            $notifyMsg = '';
            if ($isPublished && $this->request->getPost('notify_subscribers')) {
                $categoryName = 'Market Intelligence';
                $db = \Config\Database::connect();
                $cat = $db->table('blog_categories')->where('id', $data['category_id'])->get()->getRowArray();
                if ($cat && !empty($cat['name'])) {
                    $categoryName = $cat['name'];
                }

                $broadcast = (new EnquiryAutomationService())->broadcastNewArticleAlert(array_merge($data, [
                    'id'            => $insertId,
                    'category_name' => $categoryName,
                ]));
                if (!empty($broadcast['queued'])) {
                    $notifyMsg = " Newsletter broadcast queued for {$broadcast['queued']} members.";
                }
            }

            session()->setFlashdata('success', "Article '{$title}' created successfully.{$notifyMsg}");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to create article: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/content/articles'));
    }

    public function editArticle($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $blogModel = new BlogPostModel();
        $article = $blogModel->find($id);
        if (!$article) {
            session()->setFlashdata('error', 'Article not found.');
            return redirect()->to(base_url('admin/content/articles'));
        }

        $db = \Config\Database::connect();
        $categories = $db->table('blog_categories')->orderBy('name', 'ASC')->get()->getResultArray();

        return $this->renderAdminView('admin/content/article_form', [
            'article'    => $article,
            'categories' => $categories,
            'title'      => "Edit Article: {$article['title']} — TalentHub Admin",
        ], 'articles');
    }

    public function updateArticle($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $blogModel = new BlogPostModel();
        $article = $blogModel->find($id);
        if (!$article) {
            session()->setFlashdata('error', 'Article not found.');
            return redirect()->to(base_url('admin/content/articles'));
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug = url_title(trim((string) ($this->request->getPost('slug') ?: $title)), '-', true);
        $isPublished = $this->request->getPost('is_published') ? 1 : 0;

        $data = [
            'category_id'      => (int) $this->request->getPost('category_id'),
            'title'            => $title,
            'slug'             => $slug,
            'summary'          => (string) $this->request->getPost('summary'),
            'content'          => (string) $this->request->getPost('content'),
            'featured_image'   => trim((string) $this->request->getPost('featured_image')),
            'meta_title'       => trim((string) ($this->request->getPost('meta_title') ?: $title)),
            'meta_description' => trim((string) ($this->request->getPost('meta_description') ?: $this->request->getPost('summary'))),
            'meta_keywords'    => trim((string) $this->request->getPost('meta_keywords')),
            'is_published'     => $isPublished,
        ];

        if ($isPublished && empty($article['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        try {
            $blogModel->update($id, $data);

            $notifyMsg = '';
            if ($isPublished && $this->request->getPost('notify_subscribers')) {
                $categoryName = 'Market Intelligence';
                $db = \Config\Database::connect();
                $cat = $db->table('blog_categories')->where('id', $data['category_id'])->get()->getRowArray();
                if ($cat && !empty($cat['name'])) {
                    $categoryName = $cat['name'];
                }

                $broadcast = (new EnquiryAutomationService())->broadcastNewArticleAlert(array_merge($article, $data, [
                    'id'            => (int) $id,
                    'category_name' => $categoryName,
                ]));
                if (!empty($broadcast['queued'])) {
                    $notifyMsg = " Newsletter broadcast queued for {$broadcast['queued']} members.";
                }
            }

            session()->setFlashdata('success', "Article '{$title}' updated successfully.{$notifyMsg}");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to update article: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/content/articles'));
    }

    public function toggleArticleStatus($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $blogModel = new BlogPostModel();
        $article = $blogModel->find($id);
        if (!$article) {
            session()->setFlashdata('error', 'Article not found.');
            return redirect()->to(base_url('admin/content/articles'));
        }

        $newPublished = $article['is_published'] ? 0 : 1;
        $blogModel->update($id, [
            'is_published' => $newPublished,
            'published_at' => ($newPublished && empty($article['published_at'])) ? date('Y-m-d H:i:s') : $article['published_at'],
        ]);

        $statusText = $newPublished ? 'published' : 'unshared/draft';
        session()->setFlashdata('success', "Article '{$article['title']}' is now {$statusText}.");
        return redirect()->to(base_url('admin/content/articles'));
    }

    public function deleteArticle($id)
    {
        if ($redirect = $this->requirePermission('content.manage')) {
            return $redirect;
        }

        $blogModel = new BlogPostModel();
        $blogModel->delete($id);
        session()->setFlashdata('success', 'Article deleted successfully.');
        return redirect()->to(base_url('admin/content/articles'));
    }
}

