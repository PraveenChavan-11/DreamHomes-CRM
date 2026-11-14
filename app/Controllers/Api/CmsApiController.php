<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\BlogPostModel;
use App\Models\FaqModel;
use App\Models\MediaAssetModel;
use App\Models\TestimonialModel;
use App\Models\WebsitePageModel;
use CodeIgniter\HTTP\ResponseInterface;

class CmsApiController extends BaseController
{
    // ─── BLOGS ────────────────────────────────────────────────────────────────
    public function blogs(): ResponseInterface
    {
        $blogModel = new BlogPostModel();
        $isAdmin = str_contains($this->request->getUri()->getPath(), 'admin');

        $builder = $blogModel->builder();
        $builder->select('blog_posts.*, blog_categories.name as category_name')
                ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left');

        if (!$isAdmin) {
            $builder->where('blog_posts.is_published', 1);
        }

        $posts = $builder->orderBy('blog_posts.published_at', 'DESC')->get()->getResultArray();
        return $this->respondJson($posts);
    }

    public function blog($slugOrId): ResponseInterface
    {
        $blogModel = new BlogPostModel();
        if (is_numeric($slugOrId)) {
            $post = $blogModel->find($slugOrId);
        } else {
            $post = $blogModel->getPublishedBySlug((string) $slugOrId);
        }

        if (!$post) {
            return $this->respondError('Article not found', 404);
        }

        return $this->respondJson($post);
    }

    public function storeBlog(): ResponseInterface
    {
        $blogModel = new BlogPostModel();
        $rules = [
            'title'   => 'required|min_length[3]|max_length[200]',
            'summary' => 'required|min_length[10]',
            'content' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validation failed', 422, 'VALIDATION_FAILED', $this->validator->getErrors());
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug = url_title($title, '-', true) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $data = [
            'category_id'      => $this->request->getPost('category_id') ?: 1,
            'title'            => $title,
            'slug'             => $slug,
            'summary'          => $this->request->getPost('summary'),
            'content'          => $this->request->getPost('content'),
            'featured_image'   => $this->request->getPost('featured_image'),
            'meta_title'       => $this->request->getPost('meta_title') ?: $title,
            'meta_description' => $this->request->getPost('meta_description') ?: substr($this->request->getPost('summary'), 0, 160),
            'is_published'     => $this->request->getPost('is_published') ? 1 : 0,
            'published_at'     => $this->request->getPost('is_published') ? date('Y-m-d H:i:s') : null,
            'author_id'        => session()->get('admin_user')['id'] ?? 1,
        ];

        $id = $blogModel->insert($data);
        $data['id'] = $id;

        return $this->respondJson($data, 'Blog post published', 201);
    }

    public function updateBlog($id): ResponseInterface
    {
        $blogModel = new BlogPostModel();
        $post = $blogModel->find($id);
        if (!$post) {
            return $this->respondError('Blog post not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $allowed = ['title', 'category_id', 'summary', 'content', 'featured_image', 'meta_title', 'meta_description', 'is_published'];
        $updates = [];

        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $updates[$f] = $input[$f];
            }
        }

        if (isset($updates['is_published']) && $updates['is_published'] && empty($post['published_at'])) {
            $updates['published_at'] = date('Y-m-d H:i:s');
        }

        $blogModel->update($id, $updates);
        return $this->respondJson($blogModel->find($id), 'Blog post updated');
    }

    public function destroyBlog($id): ResponseInterface
    {
        $blogModel = new BlogPostModel();
        $post = $blogModel->find($id);
        if (!$post) {
            return $this->respondError('Post not found', 404);
        }

        $blogModel->delete($id);
        return $this->respondJson([], 'Post deleted');
    }

    // ─── FAQS ─────────────────────────────────────────────────────────────────
    public function faqs(): ResponseInterface
    {
        $faqModel = new FaqModel();
        $isAdmin = str_contains($this->request->getUri()->getPath(), 'admin');

        $faqs = $isAdmin ? $faqModel->orderBy('order_num', 'ASC')->findAll() : $faqModel->getPublishedFaqs();
        return $this->respondJson($faqs);
    }

    public function storeFaq(): ResponseInterface
    {
        $faqModel = new FaqModel();
        $rules = [
            'question' => 'required|min_length[5]',
            'answer'   => 'required|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validation failed', 422, 'VALIDATION_FAILED', $this->validator->getErrors());
        }

        $data = [
            'category'     => $this->request->getPost('category') ?: 'General',
            'question'     => trim((string) $this->request->getPost('question')),
            'answer'       => trim((string) $this->request->getPost('answer')),
            'order_num'    => (int) ($this->request->getPost('order_num') ?: 10),
            'is_published' => $this->request->getPost('is_published') ? 1 : 1,
        ];

        $id = $faqModel->insert($data);
        $data['id'] = $id;

        return $this->respondJson($data, 'FAQ added', 201);
    }

    public function updateFaq($id): ResponseInterface
    {
        $faqModel = new FaqModel();
        $faq = $faqModel->find($id);
        if (!$faq) {
            return $this->respondError('FAQ not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $updates = [];
        foreach (['category', 'question', 'answer', 'order_num', 'is_published'] as $f) {
            if (isset($input[$f])) {
                $updates[$f] = $input[$f];
            }
        }

        $faqModel->update($id, $updates);
        return $this->respondJson($faqModel->find($id), 'FAQ updated');
    }

    public function destroyFaq($id): ResponseInterface
    {
        $faqModel = new FaqModel();
        if (!$faqModel->find($id)) {
            return $this->respondError('FAQ not found', 404);
        }
        $faqModel->delete($id);
        return $this->respondJson([], 'FAQ deleted');
    }

    // ─── TESTIMONIALS ─────────────────────────────────────────────────────────
    public function testimonials(): ResponseInterface
    {
        $testModel = new TestimonialModel();
        $isAdmin = str_contains($this->request->getUri()->getPath(), 'admin');

        $tests = $isAdmin ? $testModel->orderBy('order_num', 'ASC')->findAll() : $testModel->getPublishedTestimonials();
        return $this->respondJson($tests);
    }

    public function storeTestimonial(): ResponseInterface
    {
        $testModel = new TestimonialModel();
        $data = [
            'name'         => trim((string) $this->request->getPost('name')),
            'designation'  => trim((string) $this->request->getPost('designation') ?? 'Client'),
            'company'      => trim((string) $this->request->getPost('company') ?? ''),
            'content'      => trim((string) $this->request->getPost('content')),
            'rating'       => (int) ($this->request->getPost('rating') ?: 5),
            'order_num'    => (int) ($this->request->getPost('order_num') ?: 10),
            'is_published' => 1,
        ];

        $id = $testModel->insert($data);
        $data['id'] = $id;
        return $this->respondJson($data, 'Testimonial added', 201);
    }

    public function updateTestimonial($id): ResponseInterface
    {
        $testModel = new TestimonialModel();
        if (!$testModel->find($id)) {
            return $this->respondError('Testimonial not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $updates = [];
        foreach (['name', 'designation', 'company', 'content', 'rating', 'order_num', 'is_published'] as $f) {
            if (isset($input[$f])) {
                $updates[$f] = $input[$f];
            }
        }

        $testModel->update($id, $updates);
        return $this->respondJson($testModel->find($id), 'Testimonial updated');
    }

    public function destroyTestimonial($id): ResponseInterface
    {
        $testModel = new TestimonialModel();
        if (!$testModel->find($id)) {
            return $this->respondError('Testimonial not found', 404);
        }
        $testModel->delete($id);
        return $this->respondJson([], 'Testimonial deleted');
    }

    // ─── DYNAMIC CMS PAGES & ROLLBACK ─────────────────────────────────────────
    public function pages(): ResponseInterface
    {
        $pageModel = new WebsitePageModel();
        $pages = $pageModel->orderBy('id', 'ASC')->findAll();
        foreach ($pages as &$p) {
            $p['content'] = json_decode($p['content_json'] ?? '{}', true) ?: [];
            $p['draft'] = !empty($p['draft_json']) ? json_decode($p['draft_json'], true) : null;
        }
        return $this->respondJson($pages);
    }

    public function page(string $slug): ResponseInterface
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->where('slug', $slug)->first();
        if (!$page) {
            return $this->respondError('Page not found', 404);
        }

        $page['content'] = json_decode($page['content_json'] ?? '{}', true) ?: [];
        $page['draft'] = !empty($page['draft_json']) ? json_decode($page['draft_json'], true) : null;

        // Fetch version history for rollback
        $db = \Config\Database::connect();
        $page['history'] = $db->table('website_page_history')
                              ->where('page_id', $page['id'])
                              ->orderBy('version', 'DESC')
                              ->get(10)
                              ->getResultArray();

        return $this->respondJson($page);
    }

    public function savePageDraft(string $slug): ResponseInterface
    {
        $pageModel = new WebsitePageModel();
        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $content = $input['content'] ?? $input['draft'] ?? $input;

        $userId = session()->get('admin_user')['id'] ?? 1;
        $success = $pageModel->saveDraft($slug, (array) $content, $userId);

        if (!$success) {
            return $this->respondError('Failed to save page draft', 400);
        }

        return $this->respondJson([], 'Draft saved successfully');
    }

    public function publishPage(string $slug): ResponseInterface
    {
        $pageModel = new WebsitePageModel();
        $userId = session()->get('admin_user')['id'] ?? 1;
        $success = $pageModel->publish($slug, $userId);

        if (!$success) {
            return $this->respondError('Failed to publish page', 400);
        }

        $audit = new AuditLogModel();
        $audit->log('PAGE_PUBLISHED', 'website_pages', null, null, ['slug' => $slug], $userId);

        return $this->respondJson([], 'Page published to live site successfully');
    }

    public function rollbackPage(string $slug, int $version): ResponseInterface
    {
        $pageModel = new WebsitePageModel();
        $userId = session()->get('admin_user')['id'] ?? 1;
        $success = $pageModel->rollback($slug, $version, $userId);

        if (!$success) {
            return $this->respondError("Failed to rollback page '{$slug}' to version {$version}", 400);
        }

        $audit = new AuditLogModel();
        $audit->log('PAGE_ROLLBACK', 'website_pages', null, null, ['slug' => $slug, 'target_version' => $version], $userId);

        return $this->respondJson([], "Page rolled back to version {$version}");
    }

    // ─── MEDIA ASSETS ─────────────────────────────────────────────────────────
    public function media(): ResponseInterface
    {
        $mediaModel = new MediaAssetModel();
        $assets = $mediaModel->orderBy('created_at', 'DESC')->findAll(100);
        return $this->respondJson($assets);
    }

    public function uploadMedia(): ResponseInterface
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->respondError('No valid file uploaded', 422);
        }

        // Validate the CONTENT, not the name (see SafeImageUpload): no SVG, real image decode, extension from the detected type.
        $check = \App\Services\SafeImageUpload::inspect($file->getTempName(), $file->getClientName(), (int) $file->getSize());
        if (!$check['ok']) {
            return $this->respondError($check['error'], 422);
        }
        $ext      = $check['ext'];
        $detected = $check['mime'];

        $uploadDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $randomName = 'media_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($uploadDir, $randomName);

        $savedPath = $uploadDir . $randomName;
        $webPath = base_url('uploads/media/' . $randomName);

        $width = null;
        $height = null;
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $imgInfo = @getimagesize($savedPath);
            if ($imgInfo) {
                $width = $imgInfo[0];
                $height = $imgInfo[1];
            }
        }

        $mediaModel = new MediaAssetModel();
        $id = $mediaModel->insert([
            'title'           => pathinfo($file->getClientName(), PATHINFO_FILENAME),
            'filename'        => $randomName,
            'file_path'       => $webPath,
            'mime_type'       => $detected,
            'file_size_bytes' => filesize($savedPath),
            'alt_text'        => pathinfo($file->getClientName(), PATHINFO_FILENAME),
            'width'           => $width,
            'height'          => $height,
            'uploaded_by'     => session()->get('admin_user')['id'] ?? 1,
        ]);

        return $this->respondJson([
            'id'        => $id,
            'url'       => $webPath,
            'file_path' => $webPath,
            'filename'  => $randomName,
        ], 'Media uploaded successfully', 201);
    }

    public function deleteMedia($id): ResponseInterface
    {
        $mediaModel = new MediaAssetModel();
        $asset = $mediaModel->find($id);
        if (!$asset) {
            return $this->respondError('Media asset not found', 404);
        }

        $localFile = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $asset['filename'];
        if (file_exists($localFile)) {
            @unlink($localFile);
        }

        $mediaModel->delete($id);
        return $this->respondJson([], 'Media asset deleted');
    }
}

