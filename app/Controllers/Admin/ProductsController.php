<?php

namespace App\Controllers\Admin;

use App\Controllers\Traits\RendersProductPage;
use App\Models\AuditLogModel;
use App\Services\ProductService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Product catalogue administration.
 * View / preview = products.view; every change (create, edit, publish, feature, reorder, delete) = products.manage.
 * All POSTs are CSRF protected by the global filter and every mutation is written to the audit log.
 */
class ProductsController extends AdminBaseController
{
    use RendersProductPage;

    private ProductService $svc;

    public function __construct()
    {
        $this->svc = new ProductService();
    }

    private function adminId(): int
    {
        return (int) ($this->adminUser['id'] ?? 0);
    }

    private function canManage(): bool
    {
        return $this->isSuperAdmin() || in_array('products.manage', $this->adminUser['permissions'] ?? [], true);
    }

    private function audit(string $action, int $id, ?array $old = null, ?array $new = null): void
    {
        try {
            (new AuditLogModel())->log($action, 'product', $id, $old, $new, $this->adminId());
        } catch (\Throwable $e) {
            log_message('error', 'Product audit failed: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------ read

    public function index()
    {
        if ($r = $this->requirePermission('products.view')) {
            return $r;
        }
        $status = (string) $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('search'));
        $page   = max(1, (int) $this->request->getGet('page'));
        $per    = 15;
        $res    = $this->svc->adminList($status, $search, $page, $per);

        return $this->renderAdminView('admin/products/index', [
            'title'      => 'Products — TalentHub Admin',
            'products'   => $res['rows'],
            'total'      => $res['total'],
            'page'       => $page,
            'totalPages' => max(1, (int) ceil($res['total'] / $per)),
            'status'     => isset(ProductService::STATUSES[$status]) ? $status : '',
            'search'     => $search,
            'counts'     => $this->getAdminCounts(),
            'statusCounts' => $this->svc->counts(),
            'canManage'  => $this->canManage(),
        ], 'products');
    }

    public function new()
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        return $this->form(null);
    }

    public function edit(int $id)
    {
        if ($r = $this->requirePermission('products.view')) {
            return $r;
        }
        $p = $this->svc->find($id);
        if (!$p) {
            session()->setFlashdata('error', 'Product not found.');
            return redirect()->to(base_url('admin/products'));
        }
        return $this->form($this->svc->withChildren($p));
    }

    private function form(?array $product)
    {
        $old = session()->getFlashdata('_ci_old_input');
        return $this->renderAdminView('admin/products/form', [
            'title'     => ($product ? 'Edit ' . $product['name'] : 'New product') . ' — TalentHub Admin',
            'product'   => $product,
            'canManage' => $this->canManage(),
            'formErrors' => session()->getFlashdata('product_errors') ?: [],
            'ctaTypes'  => ProductService::CTA_TYPES,
            'statuses'  => ProductService::STATUSES,
            'counts'    => $this->getAdminCounts(),
            'assetUrl'  => fn (?string $p) => $this->productAsset($p),
        ], 'products');
    }

    public function preview(int $id)
    {
        if ($r = $this->requirePermission('products.view')) {
            return $r;
        }
        $p = $this->svc->find($id);
        if (!$p) {
            throw PageNotFoundException::forPageNotFound('Product not found.');
        }
        return $this->renderProductPage($this->svc->withChildren($p), true);
    }

    // ------------------------------------------------------------------ write

    public function create()
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        return $this->persist(null);
    }

    public function update(int $id)
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        if (!$this->svc->find($id)) {
            session()->setFlashdata('error', 'Product not found.');
            return redirect()->to(base_url('admin/products'));
        }
        return $this->persist($id);
    }

    private function persist(?int $id)
    {
        $post = (array) $this->request->getPost();
        $res  = $this->svc->sanitize($post, $id);
        $data = $res['data'];
        $errors = $res['errors'];
        if ($data['status'] === 'published' && empty($data['short_description'])) {
            $errors['short_description'] = 'A short description is required before a product can be published.';
        }
        if ($errors) {
            session()->setFlashdata('product_errors', $errors);
            return redirect()->back()->withInput()->with('error', 'Please fix the highlighted fields: ' . implode(' ', $errors));
        }

        $before = $id ? $this->svc->find($id) : null;
        $children = [];
        foreach (array_keys(ProductService::CHILDREN) as $k) {
            $children[$k] = $this->svc->sanitizeChildren($k, $post[$k] ?? []);
        }

        try {
            $isNew = $id === null;
            if ($isNew) {
                $id = $this->svc->save(null, $data, [], $this->adminId());
            }
            $warnings = [];

            // Single images (logo 3 MB, hero/og 5 MB). A checkbox removes the current image.
            foreach (['logo' => ['logo_path', 'logo'], 'hero' => ['hero_image_path', 'image'], 'og' => ['og_image_path', 'image']] as $field => [$col, $kind]) {
                $file = $this->request->getFile($field . '_file');
                if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                    $up = $this->svc->storeUpload($file, $id, $kind);
                    if (isset($up['path'])) {
                        $this->svc->setImage($id, $col, $up['path'], $this->adminId());
                    } else {
                        $warnings[] = ucfirst($field) . ' image: ' . $up['error'];
                    }
                } elseif (!empty($post['remove_' . $field])) {
                    $this->svc->setImage($id, $col, null, $this->adminId());
                }
            }

            // Media: keep the submitted existing rows, then append new uploads.
            $media = $this->svc->sanitizeMedia($post['media'] ?? [], $id);
            $newFiles = $this->request->getFileMultiple('media_new') ?: [];
            $newCaps  = (array) ($post['media_new_caption'] ?? []);
            $newAlts  = (array) ($post['media_new_alt'] ?? []);
            foreach ($newFiles as $i => $file) {
                if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $up = $this->svc->storeUpload($file, $id, 'image');
                if (isset($up['path'])) {
                    $media[] = ['kind' => 'screenshot', 'path_or_url' => $up['path'],
                        'caption' => mb_substr(trim((string) ($newCaps[$i] ?? '')), 0, 255) ?: null,
                        'alt_text' => mb_substr(trim((string) ($newAlts[$i] ?? '')), 0, 255) ?: null];
                } else {
                    $warnings[] = 'Screenshot ' . ($i + 1) . ': ' . $up['error'];
                }
            }
            $children['media'] = $media;

            $this->svc->save($id, $data, $children, $this->adminId());
        } catch (\Throwable $e) {
            log_message('error', 'Product save failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'The product could not be saved. Please try again.');
        }

        $this->audit($isNew ? 'PRODUCT_CREATE' : 'PRODUCT_UPDATE', $id, $before ? ['name' => $before['name'], 'status' => $before['status'], 'slug' => $before['slug']] : null,
            ['name' => $data['name'], 'status' => $data['status'], 'slug' => $data['slug']]);

        $msg = $isNew ? 'Product created.' : 'Product saved.';
        if ($warnings) {
            $msg .= ' Some images were not saved: ' . implode(' ', $warnings);
            session()->setFlashdata('error', $msg);
        } else {
            session()->setFlashdata('success', $msg);
        }
        return redirect()->to(base_url('admin/products/' . $id . '/edit'));
    }

    public function status(int $id)
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        $to  = (string) $this->request->getPost('status');
        $cur = $this->svc->find($id);
        if ($cur && $to === 'published' && trim((string) $cur['short_description']) === '') {
            session()->setFlashdata('error', 'Add a short description before publishing.');
            return redirect()->back();
        }
        $ok = $cur && $this->svc->setStatus($id, $to, $this->adminId());
        if ($ok) {
            $this->audit('PRODUCT_STATUS', $id, ['status' => $cur['status']], ['status' => $to]);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? 'Status changed to ' . ProductService::STATUSES[$to] . '.' : 'Could not change status.');
        return redirect()->back();
    }

    public function feature(int $id)
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        $on = (bool) $this->request->getPost('featured');
        $ok = $this->svc->setFeatured($id, $on, $this->adminId());
        if ($ok) {
            $this->audit('PRODUCT_FEATURE', $id, null, ['is_featured' => $on]);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? ($on ? 'Marked as featured.' : 'Removed from featured.') : 'Product not found.');
        return redirect()->back();
    }

    public function move(int $id)
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        $dir = $this->request->getPost('direction') === 'up' ? 'up' : 'down';
        $ok  = $this->svc->move($id, $dir);
        if ($ok) {
            $this->audit('PRODUCT_REORDER', $id, null, ['direction' => $dir]);
        }
        return redirect()->back();
    }

    public function delete(int $id)
    {
        if ($r = $this->requirePermission('products.manage')) {
            return $r;
        }
        $cur = $this->svc->find($id);
        $ok  = $cur && $this->svc->softDelete($id, $this->adminId());
        if ($ok) {
            $this->audit('PRODUCT_DELETE', $id, ['name' => $cur['name'], 'slug' => $cur['slug'], 'status' => $cur['status']], null);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? 'Product deleted. It is hidden everywhere and retained in the database.' : 'Product not found.');
        return redirect()->to(base_url('admin/products'));
    }
}
