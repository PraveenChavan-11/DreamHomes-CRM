<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Product catalogue domain logic: CRUD, slugs, publishing, ordering, URL/video validation and image uploads.
 *
 * Security notes:
 *  - URLs are only validated syntactically (http/https). Nothing is ever fetched server-side, so there is no SSRF surface.
 *  - Video is accepted by URL only, from YouTube/Vimeo hosts, and converted to an embed URL we construct ourselves.
 *  - Uploaded images are verified with getimagesize(), stored under random names with an extension derived from the real
 *    image type, and only paths we generated (uploads/products/{id}/...) are ever deleted.
 *  - All text is stored raw and escaped on output.
 */
class ProductService
{
    public const STATUSES  = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
    public const CTA_TYPES = ['enquiry' => 'Open enquiry form', 'demo' => 'Start demo / sign-up link', 'external' => 'Custom link'];

    public const MAX_LOGO_BYTES  = 3 * 1024 * 1024;
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    public const MAX_DIMENSION   = 6000;
    public const MIN_DIMENSION   = 16;
    public const UPLOAD_DIR      = 'uploads/products';

    private const IMAGE_TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    private const ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /** Child collections: table => editable columns (besides product_id/sort). */
    public const CHILDREN = [
        'features'  => ['table' => 'product_features', 'cols' => ['title' => 160, 'description' => 600, 'icon' => 40]],
        'modules'   => ['table' => 'product_modules', 'cols' => ['title' => 160, 'description' => 600]],
        'benefits'  => ['table' => 'product_benefits', 'cols' => ['title' => 160, 'description' => 600, 'audience' => 80]],
        'audiences' => ['table' => 'product_audiences', 'cols' => ['title' => 160, 'description' => 600]],
        'faqs'      => ['table' => 'product_faqs', 'cols' => ['question' => 255, 'answer' => 4000]],
        'sections'  => ['table' => 'product_sections', 'cols' => ['section_key' => 60, 'title' => 160, 'body' => 4000]],
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        static $ensured = [];
        $key = spl_object_id($this->db);
        if (empty($ensured[$key])) {
            if (!$this->db->tableExists('product_features') || !$this->db->fieldExists('is_flagship', 'products')) {
                require_once APPPATH . 'Database/Migrations/20261108000001_create_product_catalogue.php';
                \App\Database\Migrations\CreateProductCatalogue::apply($this->db);
            }
            try {                       // one-time WorkSphere content update (marker-guarded); must never break the site
                require_once APPPATH . 'Database/Migrations/20261108000001_create_product_catalogue.php';
                \App\Database\Migrations\CreateProductCatalogue::upgradeWorkSphereCommercial($this->db);
            } catch (\Throwable $e) {
                log_message('error', 'WorkSphere commercial content update failed: ' . $e->getMessage());
            }
            $ensured[$key] = true;
        }
    }

    // ------------------------------------------------------------------ validation helpers

    /** http/https only, host required, no credentials, no control characters. Relative "/path" allowed on request. */
    public static function validateUrl(string $url, bool $allowRelative = false): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 500 || preg_match('/[\x00-\x20\x7F"<>\\\\^`{|}]/', $url)) {
            return false;
        }
        if ($allowRelative && preg_match('#^/(?!/)#', $url)) {
            return true;
        }
        $p = parse_url($url);
        if ($p === false || empty($p['scheme']) || empty($p['host'])) {
            return false;
        }
        if (!in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
            return false;
        }
        if (isset($p['user']) || isset($p['pass'])) {
            return false;
        }
        return (bool) preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i', $p['host']);
    }

    /** Returns a privacy-friendly embed URL for YouTube/Vimeo links, or null for anything else. */
    public static function videoEmbedUrl(string $url): ?string
    {
        $url = trim($url);
        if (!self::validateUrl($url)) {
            return null;
        }
        $p    = parse_url($url);
        $host = strtolower(preg_replace('/^www\./i', '', $p['host']));
        $path = $p['path'] ?? '';
        $id   = null;

        if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str($p['query'] ?? '', $q);
                $id = is_string($q['v'] ?? null) ? $q['v'] : null;
            } elseif (preg_match('#^/(?:embed|shorts|live)/([^/?]+)#', $path, $m)) {
                $id = $m[1];
            }
            return ($id !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? 'https://www.youtube-nocookie.com/embed/' . $id : null;
        }
        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');
            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? 'https://www.youtube-nocookie.com/embed/' . $id : null;
        }
        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            if (preg_match('#^/(?:video/)?(\d{5,12})/?$#', $path, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1];
            }
        }
        return null;
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        return substr($text !== '' ? $text : 'product', 0, 80);
    }

    /** Slugs that would collide with static routes under /products/. */
    private const RESERVED_SLUGS = ['new', 'create', 'preview', 'enquiry', 'index'];

    /** Unique slug among ALL rows (including soft-deleted, because the DB index is unique). */
    public function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = self::slugify($base);
        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-product';
        }
        $slug = $base;
        $n    = 2;
        while ($this->slugTaken($slug, $ignoreId)) {
            $slug = substr($base, 0, 75) . '-' . $n++;
        }
        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        $b = $this->db->table('products')->select('id')->where('slug', $slug);
        if ($ignoreId) {
            $b->where('id !=', $ignoreId);
        }
        return (bool) $b->get(1)->getRowArray();
    }

    private static function clean(?string $v, int $max): string
    {
        $v = preg_replace('/[^\P{C}\n\t]/u', '', (string) $v) ?? '';
        return mb_substr(trim(str_replace("\r\n", "\n", $v)), 0, $max);
    }

    /**
     * Validate and normalise the product form. Returns ['data' => [...columns], 'errors' => [field => message]].
     * Image columns are NOT taken from input (set only by the upload handler).
     */
    public function sanitize(array $in, ?int $id = null): array
    {
        $errors = [];
        $d      = [];
        $d['name'] = self::clean($in['name'] ?? '', 150);
        if (mb_strlen($d['name']) < 2) {
            $errors['name'] = 'Product name is required (at least 2 characters).';
        }
        $slugIn = self::clean($in['slug'] ?? '', 100);
        $d['slug'] = $this->uniqueSlug($slugIn !== '' ? $slugIn : $d['name'], $id);

        foreach (['tagline' => 255, 'short_description' => 600, 'long_description' => 8000, 'category' => 80, 'pricing_summary' => 255,
                  'cta_label' => 80, 'seo_title' => 255, 'seo_description' => 320] as $f => $max) {
            $d[$f] = self::clean($in[$f] ?? '', $max) ?: null;
        }
        if (!empty($d['seo_description']) && mb_strlen($d['seo_description']) > 320) {
            $d['seo_description'] = mb_substr($d['seo_description'], 0, 320);
        }

        foreach (['demo_url' => 'Demo URL', 'signup_url' => 'Sign-up URL', 'docs_url' => 'Documentation URL', 'canonical_url' => 'Canonical URL'] as $f => $label) {
            $v = trim((string) ($in[$f] ?? ''));
            if ($v === '') {
                $d[$f] = null;
            } elseif (!self::validateUrl($v)) {
                $errors[$f] = "$label must be a full http:// or https:// address.";
            } else {
                $d[$f] = $v;
            }
        }
        $cta = trim((string) ($in['cta_url'] ?? ''));
        if ($cta === '') {
            $d['cta_url'] = null;
        } elseif (!self::validateUrl($cta, true)) {
            $errors['cta_url'] = 'Button link must be an http(s) address or a site path starting with a single "/".';
        } else {
            $d['cta_url'] = $cta;
        }

        $video = trim((string) ($in['hero_video_url'] ?? ''));
        if ($video === '') {
            $d['hero_video_url'] = null;
        } elseif (self::videoEmbedUrl($video) === null) {
            $errors['hero_video_url'] = 'Video must be a YouTube or Vimeo link.';
        } else {
            $d['hero_video_url'] = $video;
        }

        $cat = (string) ($in['primary_cta_type'] ?? 'enquiry');
        $d['primary_cta_type'] = isset(self::CTA_TYPES[$cat]) ? $cat : 'enquiry';
        if ($d['primary_cta_type'] === 'external' && empty($d['cta_url'])) {
            $errors['cta_url'] = 'A custom button needs a link.';
        }
        if ($d['primary_cta_type'] === 'demo' && empty($d['signup_url']) && empty($d['demo_url'])) {
            $errors['signup_url'] = 'Add a sign-up or demo URL for a demo button.';
        }

        $st = (string) ($in['status'] ?? 'draft');
        $d['status']        = isset(self::STATUSES[$st]) ? $st : 'draft';
        $d['is_featured']   = !empty($in['is_featured']) ? 1 : 0;
        $d['is_flagship']   = !empty($in['is_flagship']) ? 1 : 0;
        $d['no_index']      = !empty($in['no_index']) ? 1 : 0;
        $d['display_order'] = max(0, min(9999, (int) ($in['display_order'] ?? 0)));
        return ['data' => $d, 'errors' => $errors];
    }

    /** Normalise a repeater input (list of arrays) for one child collection; blank rows are dropped. */
    public function sanitizeChildren(string $key, $rows): array
    {
        $cols = self::CHILDREN[$key]['cols'];
        $out  = [];
        foreach ((array) $rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $row = [];
            foreach ($cols as $c => $max) {
                $row[$c] = self::clean($r[$c] ?? '', $max);
            }
            $first = array_key_first($cols);
            if ($key === 'sections') {
                $row['section_key'] = self::slugify($row['section_key'] !== '' ? $row['section_key'] : ($row['title'] ?? ''));
            }
            if ($row[$first] === '' && ($key !== 'sections' || ($row['title'] === '' && $row['body'] === ''))) {
                continue;
            }
            foreach ($row as $c => $v) {
                $row[$c] = $v === '' ? null : $v;
            }
            $out[] = $row;
            if (count($out) >= 40) {
                break;
            }
        }
        return $out;
    }

    /** Media rows: screenshots/gallery use stored upload paths of THIS product; video uses a validated YouTube/Vimeo URL. */
    public function sanitizeMedia($rows, int $productId): array
    {
        $out = [];
        foreach ((array) $rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $kind = in_array($r['kind'] ?? '', ['screenshot', 'video', 'gallery'], true) ? $r['kind'] : 'screenshot';
            $val  = trim((string) ($r['path_or_url'] ?? ''));
            if ($kind === 'video') {
                if (self::videoEmbedUrl($val) === null) {
                    continue;
                }
            } elseif (!$this->ownsPath($val, $productId)) {
                continue;
            }
            $out[] = [
                'kind' => $kind, 'path_or_url' => $val,
                'caption'  => self::clean($r['caption'] ?? '', 255) ?: null,
                'alt_text' => self::clean($r['alt_text'] ?? '', 255) ?: null,
            ];
            if (count($out) >= 30) {
                break;
            }
        }
        return $out;
    }

    private function ownsPath(string $path, int $productId): bool
    {
        return (bool) preg_match('#^' . preg_quote(self::UPLOAD_DIR, '#') . '/' . $productId . '/[a-f0-9]{32}\.(jpg|png|webp)$#', $path);
    }

    // ------------------------------------------------------------------ uploads

    /**
     * Validate a candidate image file on disk. Returns ['ok'=>bool, 'ext'=>string, 'error'=>string].
     * The extension is derived from the REAL image type, never from the client file name.
     */
    public static function validateImageFile(string $tmpPath, string $clientName, int $size, string $kind = 'image'): array
    {
        $max = $kind === 'logo' ? self::MAX_LOGO_BYTES : self::MAX_IMAGE_BYTES;
        $fail = static fn (string $m) => ['ok' => false, 'ext' => '', 'error' => $m];

        if (!is_file($tmpPath) || $size <= 0) {
            return $fail('The file is empty or could not be read.');
        }
        if ($size > $max) {
            return $fail('The image is too large (maximum ' . ($max / 1048576) . ' MB).');
        }
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return $fail('Only JPG, PNG or WebP images are allowed.');
        }
        // A double extension such as shell.php.jpg is refused outright.
        if (preg_match('/\.(php\d?|phtml|phar|svg|html?|js|exe|sh|bat|cgi|pl|py)(\.|$)/i', $clientName)) {
            return $fail('This file name is not allowed.');
        }
        $head = @file_get_contents($tmpPath, false, null, 0, 8192);
        $mime = $head === false ? '' : ((new \finfo(FILEINFO_MIME_TYPE))->buffer($head) ?: '');
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return $fail('The file is not a valid JPG, PNG or WebP image.');
        }
        $info = @getimagesize($tmpPath);
        if ($info === false || !isset(self::IMAGE_TYPES[$info[2]])) {
            return $fail('The file is not a valid JPG, PNG or WebP image.');
        }
        if ($info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            return $fail('The image dimensions are too large (maximum ' . self::MAX_DIMENSION . ' px per side).');
        }
        if ($info[0] < self::MIN_DIMENSION || $info[1] < self::MIN_DIMENSION) {
            return $fail('The image is too small.');
        }
        return ['ok' => true, 'ext' => self::IMAGE_TYPES[$info[2]], 'error' => ''];
    }

    /** Validate and store an uploaded image for a product. Returns ['path'=>string] or ['error'=>string]. */
    public function storeUpload(?UploadedFile $file, int $productId, string $kind = 'image'): array
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['error' => 'No file selected.'];
        }
        if (!$file->isValid()) {
            return ['error' => 'The upload failed. Please try again with a smaller file.'];
        }
        $v = self::validateImageFile($file->getTempName(), $file->getClientName(), (int) $file->getSize(), $kind);
        if (!$v['ok']) {
            return ['error' => $v['error']];
        }
        $dir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $productId;
        if (!$this->prepareUploadDir($dir)) {
            return ['error' => 'The upload folder is not writable.'];
        }
        $name = bin2hex(random_bytes(16)) . '.' . $v['ext'];
        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            log_message('error', 'Product upload move failed: ' . $e->getMessage());
            return ['error' => 'The file could not be saved.'];
        }
        return ['path' => self::UPLOAD_DIR . '/' . $productId . '/' . $name];
    }

    private function prepareUploadDir(string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        // Defence in depth on Apache hosts: never execute anything from the uploads tree.
        $root = dirname($dir) . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($root)) {
            @file_put_contents($root, "Options -Indexes -ExecCGI\n<FilesMatch \"(?i)\\.(php\\d?|phtml|phar|pl|py|cgi|sh|html?|svg)$\">\n  Require all denied\n</FilesMatch>\n");
        }
        return is_writable($dir);
    }

    /** Delete a file only if it is one we generated (uploads/products/{id}/{32 hex}.ext). */
    public static function deleteStoredFile(?string $path): void
    {
        if (!$path || !preg_match('#^' . preg_quote(self::UPLOAD_DIR, '#') . '/\d+/[a-f0-9]{32}\.(jpg|png|webp)$#', $path)) {
            return;
        }
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    // ------------------------------------------------------------------ queries

    private function base(): \CodeIgniter\Database\BaseBuilder
    {
        return $this->db->table('products')->where('deleted_at IS NULL');
    }

    /** Admin list: filters status, search; returns ['rows','total']. */
    public function adminList(string $status, string $search, int $page, int $perPage = 15): array
    {
        $b = $this->base();
        if (isset(self::STATUSES[$status])) {
            $b->where('status', $status);
        }
        if ($search !== '') {
            $b->groupStart()->like('name', $search)->orLike('slug', $search)->orLike('tagline', $search)->orLike('category', $search)->groupEnd();
        }
        $total = (clone $b)->countAllResults();
        $rows  = $b->orderBy('display_order', 'ASC')->orderBy('id', 'ASC')->limit($perPage, max(0, ($page - 1) * $perPage))->get()->getResultArray();
        return ['rows' => $rows, 'total' => $total];
    }

    public function counts(): array
    {
        $out = ['all' => 0, 'draft' => 0, 'published' => 0, 'archived' => 0];
        foreach ($this->base()->select('status, COUNT(*) c')->groupBy('status')->get()->getResultArray() as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['all'] += (int) $r['c'];
        }
        return $out;
    }

    public function find(int $id): ?array
    {
        return $this->base()->where('id', $id)->get()->getRowArray() ?: null;
    }

    /** Published, non-deleted product by slug (public). */
    public function findPublished(string $slug): ?array
    {
        return $this->base()->where('slug', $slug)->where('status', 'published')->get()->getRowArray() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->base()->where('slug', $slug)->get()->getRowArray() ?: null;
    }

    /** Attach child collections and media (media split into screenshots/gallery/video). */
    public function withChildren(array $p): array
    {
        $id = (int) $p['id'];
        foreach (self::CHILDREN as $key => $def) {
            $p[$key] = $this->db->table($def['table'])->where('product_id', $id)->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        }
        $p['media'] = $this->db->table('product_media')->where('product_id', $id)->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        return $p;
    }

    /** Public listing: published only; flagship, then featured, then manual order. */
    public function publicList(string $category = ''): array
    {
        $b = $this->base()->where('status', 'published');
        if ($category !== '') {
            $b->where('category', $category);
        }
        return $b->orderBy('is_flagship', 'DESC')->orderBy('is_featured', 'DESC')->orderBy('display_order', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function publicCategories(): array
    {
        return array_column($this->base()->select('category')->distinct()->where('status', 'published')->where("category IS NOT NULL AND category <> ''")->orderBy('category')->get()->getResultArray(), 'category');
    }

    /** Rows for the sitemap: published, indexable. */
    public function sitemapRows(): array
    {
        return $this->base()->select('slug, updated_at, published_at, canonical_url')->where('status', 'published')->where('no_index', 0)->orderBy('display_order')->get()->getResultArray();
    }

    // ------------------------------------------------------------------ mutations

    /**
     * Create/update a product with its child collections in one transaction.
     * @param array $children ['features'=>rows,...,'media'=>rows]
     * @return int product id
     */
    public function save(?int $id, array $data, array $children, ?int $adminId): int
    {
        $now = date('Y-m-d H:i:s');
        $oldMedia = [];
        $this->db->transStart();
        if ($id === null) {
            $data += ['created_by' => $adminId, 'updated_by' => $adminId, 'created_at' => $now, 'updated_at' => $now];
            if ($data['status'] === 'published') {
                $data['published_at'] = $now;
            }
            $this->db->table('products')->insert($data);
            $id = (int) $this->db->insertID();
        } else {
            $cur = $this->find($id);
            if (!$cur) {
                $this->db->transComplete();
                throw new \RuntimeException('Product not found');
            }
            $data['updated_by'] = $adminId;
            $data['updated_at'] = $now;
            if ($data['status'] === 'published' && empty($cur['published_at'])) {
                $data['published_at'] = $now;
            }
            $this->db->table('products')->where('id', $id)->update($data);
            $oldMedia = array_column($this->db->table('product_media')->where('product_id', $id)->get()->getResultArray(), 'path_or_url');
        }

        foreach (self::CHILDREN as $key => $def) {
            if (!array_key_exists($key, $children)) {
                continue;
            }
            $this->db->table($def['table'])->where('product_id', $id)->delete();
            foreach (array_values($children[$key]) as $i => $row) {
                $this->db->table($def['table'])->insert($row + ['product_id' => $id, 'sort' => $i + 1]);
            }
        }
        $keptMedia = [];
        if (array_key_exists('media', $children)) {
            $this->db->table('product_media')->where('product_id', $id)->delete();
            foreach (array_values($children['media']) as $i => $row) {
                $this->db->table('product_media')->insert($row + ['product_id' => $id, 'sort' => $i + 1]);
                $keptMedia[] = $row['path_or_url'];
            }
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Could not save product');
        }
        if (array_key_exists('media', $children)) {
            foreach (array_diff($oldMedia, $keptMedia) as $gone) {
                self::deleteStoredFile($gone);
            }
        }
        return $id;
    }

    /** Set a single image column (logo_path, hero_image_path, og_image_path), deleting the replaced file. */
    public function setImage(int $id, string $column, ?string $path, ?int $adminId): void
    {
        if (!in_array($column, ['logo_path', 'hero_image_path', 'og_image_path'], true)) {
            throw new \InvalidArgumentException('Bad column');
        }
        $cur = $this->find($id);
        if (!$cur) {
            return;
        }
        $this->db->table('products')->where('id', $id)->update([$column => $path, 'updated_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s')]);
        if (!empty($cur[$column]) && $cur[$column] !== $path) {
            self::deleteStoredFile($cur[$column]);
        }
    }

    public function setStatus(int $id, string $status, ?int $adminId): bool
    {
        if (!isset(self::STATUSES[$status]) || !$this->find($id)) {
            return false;
        }
        $set = ['status' => $status, 'updated_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'published') {
            $cur = $this->find($id);
            if (empty($cur['published_at'])) {
                $set['published_at'] = $set['updated_at'];
            }
        }
        $this->db->table('products')->where('id', $id)->update($set);
        return true;
    }

    public function setFeatured(int $id, bool $featured, ?int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $this->db->table('products')->where('id', $id)->update(['is_featured' => $featured ? 1 : 0, 'updated_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /** Soft delete: the row and its uploads are retained so it can be restored by a developer if needed. */
    public function softDelete(int $id, ?int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->where('id', $id)->update(['deleted_at' => $now, 'status' => 'archived', 'updated_by' => $adminId, 'updated_at' => $now]);
        return true;
    }

    /** Move a product up/down by renumbering display_order 1..n. */
    public function move(int $id, string $direction): bool
    {
        $ids = array_map('intval', array_column($this->base()->select('id')->orderBy('display_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray(), 'id'));
        $pos = array_search($id, $ids, true);
        if ($pos === false) {
            return false;
        }
        $swap = $direction === 'up' ? $pos - 1 : $pos + 1;
        if ($swap >= 0 && $swap < count($ids)) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
        }
        return $this->reorder($ids);
    }

    /** Persist an explicit order (list of product ids). */
    public function reorder(array $ids): bool
    {
        $this->db->transStart();
        foreach (array_values($ids) as $i => $pid) {
            $this->db->table('products')->where('id', (int) $pid)->update(['display_order' => $i + 1]);
        }
        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
