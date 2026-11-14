<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Case studies / portfolio: CRUD, slugs, publishing, safe image uploads.
 *
 * Nothing here is seeded: with no published rows the public site shows an honest empty state (or hides the links).
 * Uploads reuse ProductService::validateImageFile() (real MIME + getimagesize, extension derived from the actual image
 * type, size and dimension limits). Only paths we generated are ever served or deleted.
 */
class CaseStudyService
{
    public const STATUSES   = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
    public const UPLOAD_DIR = 'uploads/case-studies';
    public const MAX_GALLERY = 12;

    private BaseConnection $db;
    private static array $publishedCache = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        static $ensured = [];
        $key = spl_object_id($this->db);
        if (empty($ensured[$key])) {
            if (!$this->db->tableExists('case_study_images')) {
                require_once APPPATH . 'Database/Migrations/20261109000001_create_case_studies.php';
                \App\Database\Migrations\CreateCaseStudies::apply($this->db);
            }
            $ensured[$key] = true;
        }
    }

    /** Safe for use on every public page: never throws, memoised per request. */
    public static function hasPublished(): bool
    {
        if (!isset(self::$publishedCache['has'])) {
            try {
                self::$publishedCache['has'] = (new self())->countPublished() > 0;
            } catch (\Throwable $e) {
                log_message('error', 'CaseStudyService::hasPublished failed: ' . $e->getMessage());
                self::$publishedCache['has'] = false;
            }
        }
        return self::$publishedCache['has'];
    }

    public static function resetCache(): void
    {
        self::$publishedCache = [];
    }

    // ------------------------------------------------------------------ helpers

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = trim(preg_replace('/[^a-z0-9]+/', '-', $text) ?? '', '-');
        return substr($text !== '' ? $text : 'case-study', 0, 80);
    }

    private const RESERVED_SLUGS = ['new', 'create', 'index', 'category'];

    public function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = self::slugify($base);
        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-case-study';
        }
        $slug = $base;
        $n = 2;
        while ($this->slugTaken($slug, $ignoreId)) {
            $slug = substr($base, 0, 74) . '-' . $n++;
        }
        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        $b = $this->db->table('case_studies')->where('slug', $slug);
        if ($ignoreId) {
            $b->where('id !=', $ignoreId);
        }
        return $b->countAllResults() > 0;
    }

    private static function clean(?string $v, int $max): string
    {
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $v) ?? '');
        return mb_substr($v, 0, $max);
    }

    /** @return array{data: array, errors: array<string,string>} */
    public function sanitize(array $in, ?int $id = null): array
    {
        $errors = [];
        $title = self::clean($in['title'] ?? '', 180);
        if ($title === '') {
            $errors['title'] = 'A title is required.';
        }
        $status = (string) ($in['status'] ?? 'draft');
        if (!isset(self::STATUSES[$status])) {
            $status = 'draft';
        }
        $url = self::clean($in['project_url'] ?? '', 500);
        if ($url !== '' && !ProductService::validateUrl($url)) {
            $errors['project_url'] = 'The project link must start with http:// or https://.';
            $url = '';
        }
        $slugIn = self::clean($in['slug'] ?? '', 100);
        $slug = $this->uniqueSlug($slugIn !== '' ? $slugIn : ($title !== '' ? $title : 'case-study'), $id);
        $techs = array_values(array_unique(array_filter(array_map(
            static fn ($t) => self::clean($t, 40),
            explode(',', (string) ($in['technologies'] ?? ''))
        ))));

        $data = [
            'slug'            => $slug,
            'title'           => $title,
            'client_name'     => self::clean($in['client_name'] ?? '', 150) ?: null,
            'category'        => self::clean($in['category'] ?? '', 80) ?: null,
            'summary'         => self::clean($in['summary'] ?? '', 600) ?: null,
            'challenge'       => self::clean($in['challenge'] ?? '', 5000) ?: null,
            'solution'        => self::clean($in['solution'] ?? '', 5000) ?: null,
            'outcome'         => self::clean($in['outcome'] ?? '', 5000) ?: null,
            'technologies'    => mb_substr(implode(', ', $techs), 0, 500) ?: null,
            'project_url'     => $url ?: null,
            'seo_title'       => self::clean($in['seo_title'] ?? '', 200) ?: null,
            'seo_description' => self::clean($in['seo_description'] ?? '', 320) ?: null,
            'status'          => $status,
            'is_featured'     => empty($in['is_featured']) ? 0 : 1,
            'display_order'   => (int) ($in['display_order'] ?? 0),
        ];
        return ['data' => $data, 'errors' => $errors];
    }

    // ------------------------------------------------------------------ uploads

    public function storeUpload(?UploadedFile $file, int $caseId): array
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['error' => 'No file selected.'];
        }
        if (!$file->isValid()) {
            return ['error' => 'The upload failed. Please try again with a smaller file.'];
        }
        $v = ProductService::validateImageFile($file->getTempName(), $file->getClientName(), (int) $file->getSize(), 'image');
        if (!$v['ok']) {
            return ['error' => $v['error']];
        }
        $dir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'case-studies' . DIRECTORY_SEPARATOR . $caseId;
        if (!$this->prepareUploadDir($dir)) {
            return ['error' => 'The upload folder is not writable.'];
        }
        $name = bin2hex(random_bytes(16)) . '.' . $v['ext'];
        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            log_message('error', 'Case study upload move failed: ' . $e->getMessage());
            return ['error' => 'The file could not be saved.'];
        }
        return ['path' => self::UPLOAD_DIR . '/' . $caseId . '/' . $name];
    }

    private function prepareUploadDir(string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $root = dirname($dir) . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($root)) {
            @file_put_contents($root, "Options -Indexes -ExecCGI\n<FilesMatch \"(?i)\\.(php\\d?|phtml|phar|pl|py|cgi|sh|html?|svg)$\">\n  Require all denied\n</FilesMatch>\n");
        }
        return is_writable($dir);
    }

    public static function isOwnPath(?string $path): bool
    {
        return (bool) ($path && preg_match('#^' . preg_quote(self::UPLOAD_DIR, '#') . '/\d+/[a-f0-9]{32}\.(jpg|png|webp)$#', $path));
    }

    public static function deleteStoredFile(?string $path): void
    {
        if (!self::isOwnPath($path)) {
            return;
        }
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /** Absolute URL for a stored path we generated, else null. */
    public static function assetUrl(?string $path): ?string
    {
        return self::isOwnPath($path) ? base_url($path) : null;
    }

    // ------------------------------------------------------------------ queries

    private function base(): \CodeIgniter\Database\BaseBuilder
    {
        return $this->db->table('case_studies')->where('deleted_at IS NULL');
    }

    public function adminList(string $status, string $search, int $page, int $perPage = 15): array
    {
        $b = $this->base();
        if (isset(self::STATUSES[$status])) {
            $b->where('status', $status);
        }
        if ($search !== '') {
            $b->groupStart()->like('title', $search)->orLike('client_name', $search)->orLike('category', $search)->orLike('slug', $search)->groupEnd();
        }
        $total = (clone $b)->countAllResults();
        $rows = $b->orderBy('display_order', 'ASC')->orderBy('id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['rows' => $rows, 'total' => $total];
    }

    public function counts(): array
    {
        $out = ['all' => 0, 'draft' => 0, 'published' => 0, 'archived' => 0];
        foreach ($this->base()->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $r) {
            $out[$r['status']] = (int) $r['n'];
            $out['all'] += (int) $r['n'];
        }
        return $out;
    }

    public function find(int $id): ?array
    {
        return $this->base()->where('id', $id)->get()->getRowArray() ?: null;
    }

    public function findPublished(string $slug): ?array
    {
        return $this->base()->where('slug', $slug)->where('status', 'published')->get()->getRowArray() ?: null;
    }

    public function countPublished(): int
    {
        return (int) $this->base()->where('status', 'published')->countAllResults();
    }

    public function images(int $caseId): array
    {
        return $this->db->table('case_study_images')->where('case_study_id', $caseId)->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
    }

    public function publicList(string $category = '', int $limit = 60): array
    {
        $b = $this->base()->where('status', 'published');
        if ($category !== '') {
            $b->where('category', $category);
        }
        return $b->orderBy('is_featured', 'DESC')->orderBy('display_order', 'ASC')->orderBy('published_at', 'DESC')->limit($limit)->get()->getResultArray();
    }

    public function publicCategories(): array
    {
        return array_column($this->base()->select('category')->distinct()->where('status', 'published')->where("category IS NOT NULL AND category <> ''")->orderBy('category')->get()->getResultArray(), 'category');
    }

    public function sitemapRows(): array
    {
        return $this->base()->select('slug, updated_at, published_at')->where('status', 'published')->orderBy('display_order')->get()->getResultArray();
    }

    // ------------------------------------------------------------------ mutations

    public function save(?int $id, array $data, ?int $adminId): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        if ($id === null) {
            $data['created_by'] = $adminId;
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $data['published_at'] = $data['status'] === 'published' ? $now : null;
            $this->db->table('case_studies')->insert($data);
            $id = (int) $this->db->insertID();
        } else {
            $cur = $this->find($id);
            $data['updated_at'] = $now;
            if ($data['status'] === 'published' && empty($cur['published_at'])) {
                $data['published_at'] = $now;
            }
            $this->db->table('case_studies')->where('id', $id)->update($data);
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Case study could not be saved.');
        }
        self::resetCache();
        return $id;
    }

    public function setCover(int $id, ?string $path): void
    {
        $cur = $this->find($id);
        if ($cur && $cur['cover_path'] && $cur['cover_path'] !== $path) {
            self::deleteStoredFile($cur['cover_path']);
        }
        $this->db->table('case_studies')->where('id', $id)->update(['cover_path' => $path, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function addImage(int $caseId, string $path, ?string $caption, ?string $alt): void
    {
        $max = (int) ($this->db->table('case_study_images')->selectMax('sort')->where('case_study_id', $caseId)->get()->getRow()->sort ?? 0);
        $this->db->table('case_study_images')->insert([
            'case_study_id' => $caseId, 'path' => $path,
            'caption' => $caption !== null ? mb_substr($caption, 0, 255) : null,
            'alt_text' => $alt !== null ? mb_substr($alt, 0, 255) : null,
            'sort' => $max + 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function imageCount(int $caseId): int
    {
        return (int) $this->db->table('case_study_images')->where('case_study_id', $caseId)->countAllResults();
    }

    public function removeImage(int $caseId, int $imageId): bool
    {
        $row = $this->db->table('case_study_images')->where('id', $imageId)->where('case_study_id', $caseId)->get()->getRowArray();
        if (!$row) {
            return false;
        }
        $this->db->table('case_study_images')->where('id', $imageId)->delete();
        self::deleteStoredFile($row['path']);
        return true;
    }

    /** Update caption / alt text for existing gallery rows of this case study only. */
    public function updateImageMeta(int $caseId, array $rows): void
    {
        foreach ($rows as $imageId => $r) {
            $this->db->table('case_study_images')->where('id', (int) $imageId)->where('case_study_id', $caseId)->update([
                'caption'  => self::clean($r['caption'] ?? '', 255) ?: null,
                'alt_text' => self::clean($r['alt_text'] ?? '', 255) ?: null,
            ]);
        }
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!isset(self::STATUSES[$status]) || !$this->find($id)) {
            return false;
        }
        $upd = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'published') {
            $cur = $this->find($id);
            if (empty($cur['published_at'])) {
                $upd['published_at'] = $upd['updated_at'];
            }
        }
        $this->db->table('case_studies')->where('id', $id)->update($upd);
        self::resetCache();
        return true;
    }

    public function softDelete(int $id): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        // Slug is renamed so the unique index frees the address; files stay on disk for audit/recovery.
        $this->db->table('case_studies')->where('id', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'), 'status' => 'archived', 'slug' => 'deleted-' . $id . '-' . bin2hex(random_bytes(3)),
        ]);
        self::resetCache();
        return true;
    }
}
