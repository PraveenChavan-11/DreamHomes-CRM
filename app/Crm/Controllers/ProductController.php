<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ProductService;

/** Product & price master. Pricing rules (min price, decimal maths, price history) live in ProductService; this class only maps HTTP. */
class ProductController extends CrmController
{
    private const PER = 25;
    private const IMG_EXT = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function index(): string
    {
        $db = Crm::db();
        $f  = ['q' => trim((string) $this->request->getGet('q')), 'category' => (int) $this->request->getGet('category'), 'active' => (string) $this->request->getGet('active')];
        $b  = $db->table('crm_products p')->join('crm_product_categories c', 'c.id = p.category_id', 'left')->join('crm_tax_rates t', 't.id = p.tax_rate_id', 'left');
        if ($f['q'] !== '') {
            $b->groupStart()->like('p.name', $f['q'])->orLike('p.sku', $f['q'])->orLike('p.brand', $f['q'])->groupEnd();
        }
        if ($f['category']) {
            $b->where('p.category_id', $f['category']);
        }
        if ($f['active'] === '1' || $f['active'] === '0') {
            $b->where('p.is_active', (int) $f['active']);
        }
        $total = (int) (clone $b)->countAllResults(false);
        $pages = max(1, (int) ceil($total / self::PER));
        $page  = min($this->page(), $pages);
        $rows  = $b->select('p.*, c.name AS category, t.rate AS tax_rate')->orderBy('c.sort_order')->orderBy('p.sku')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();

        return $this->render('products/index', [
            'rows' => $rows, 'f' => $f, 'total' => $total, 'page' => $page, 'pages' => $pages,
            'categories' => $db->table('crm_product_categories')->orderBy('sort_order')->get()->getResultArray(),
        ], 'Products & Pricing', 'products');
    }

    public function show(int $id): string
    {
        $db = Crm::db();
        $p  = $db->table('crm_products p')->select('p.*, c.name AS category, t.name AS tax_name, t.rate AS tax_rate')->join('crm_product_categories c', 'c.id = p.category_id', 'left')->join('crm_tax_rates t', 't.id = p.tax_rate_id', 'left')->where('p.id', $id)->get()->getRowArray()
            ?? throw new PageNotFoundException();
        $hist = $db->table('crm_product_price_history h')->select("h.*, CONCAT(u.first_name,' ',u.last_name) AS by_name", false)->join('admin_users u', 'u.id = h.changed_by', 'left')->where('h.product_id', $id)->orderBy('h.id', 'DESC')->get()->getResultArray();
        $usage = (int) $db->table('crm_quotation_items')->where('product_id', $id)->countAllResults();

        return $this->render('products/show', ['p' => $p, 'history' => $hist, 'usage' => $usage], $p['name'], 'products');
    }

    public function new(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        return $this->form($id);
    }

    public function save(?int $id = null)
    {
        $post = $this->request->getPost();
        $d    = $this->pick($post, ['sku', 'name', 'brand', 'category_id', 'description', 'specifications', 'unit', 'pricing_model', 'base_price', 'standard_price', 'min_price', 'tax_rate_id']);
        $d['is_active'] = ! empty($post['is_active']) ? 1 : 0;
        $user = $this->user();

        return $this->act(function () use ($id, $d, $user) {
            $e  = [];
            $db = Crm::db();
            if (! $db->table('crm_product_categories')->where('id', (int) ($d['category_id'] ?? 0))->countAllResults()) {
                $e['category_id'] = 'Choose a category.';
            }
            if (! $db->table('crm_tax_rates')->where('id', (int) ($d['tax_rate_id'] ?? 0))->countAllResults()) {
                $e['tax_rate_id'] = 'Choose a tax rate.';
            }
            if (trim((string) ($d['unit'] ?? '')) === '') {
                $d['unit'] = 'nos';
            }
            $img = $this->handleImage($e);
            if ($e) {
                throw CrmException::validation($e);
            }
            if ($img !== null) {
                $d['image'] = $img;
            }
            // minimum-price / pricing-order rules are enforced inside ProductService::validate (server side)
            $pid = ProductService::save($id, $d, $user);

            return 'crm/products/' . $pid;
        }, $id ? 'Product updated. Any price change was recorded in the price history.' : 'Product created.');
    }

    private function form(?int $id): string
    {
        $db = Crm::db();
        $p  = $id ? ($db->table('crm_products')->where('id', $id)->get()->getRowArray() ?? throw new PageNotFoundException()) : ['pricing_model' => 'unit', 'unit' => 'nos', 'is_active' => 1];

        return $this->render('products/form', [
            'p' => $p, 'id' => $id, 'models' => ProductService::PRICING,
            'categories' => $db->table('crm_product_categories')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray(),
            'taxes' => $db->table('crm_tax_rates')->where('is_active', 1)->get()->getResultArray(),
        ], $id ? 'Edit product' : 'New product', 'products');
    }

    /** @param array<string,string> $errors by-ref validation collector. @return string|null stored relative path */
    private function handleImage(array &$errors): ?string
    {
        $file = $this->request->getFile('image_file');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $file->isValid()) {
            $errors['image_file'] = 'Upload failed: ' . $file->getErrorString();

            return null;
        }
        $ext = strtolower($file->getExtension());
        if (! isset(self::IMG_EXT[$ext])) {
            $errors['image_file'] = 'Only JPG, PNG or WebP images are allowed.';

            return null;
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            $errors['image_file'] = 'Image must be 2 MB or smaller.';

            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if ($mime !== self::IMG_EXT[$ext] || @getimagesize($file->getRealPath()) === false) {
            $errors['image_file'] = 'The file content is not a valid image of that type.';

            return null;
        }
        $dir = FCPATH . 'uploads/crm-products';
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $errors['image_file'] = 'Image folder is not writable.';

            return null;
        }
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $file->move($dir, $name);

        return 'uploads/crm-products/' . $name;
    }
}
