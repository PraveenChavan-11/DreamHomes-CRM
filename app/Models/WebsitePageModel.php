<?php

namespace App\Models;

use CodeIgniter\Model;

class WebsitePageModel extends Model
{
    protected $table            = 'website_pages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'og_image',
        'status',
        'content_json',
        'draft_json',
        'version',
        'last_published_at',
        'last_updated_by',
        'meta_keywords',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get published page content decoded as array
     */
    public function getPublishedBySlug(string $slug): ?array
    {
        $page = $this->where('slug', $slug)
                     ->where('status', 'PUBLISHED')
                     ->first();

        if (!$page) {
            return null;
        }

        $page['content'] = json_decode($page['content_json'] ?? '{}', true) ?: [];
        return $page;
    }

    /**
     * Save draft JSON without publishing to public
     */
    public function saveDraft(string $slug, array $draftData, ?int $userId = null): bool
    {
        $page = $this->where('slug', $slug)->first();
        if (!$page) {
            return false;
        }

        return $this->update($page['id'], [
            'draft_json'      => json_encode($draftData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'last_updated_by' => $userId,
        ]);
    }

    /**
     * Publish draft to live content_json, increment version, and record history
     */
    public function publish(string $slug, ?int $userId = null): bool
    {
        $page = $this->where('slug', $slug)->first();
        if (!$page) {
            return false;
        }

        $newContentJson = !empty($page['draft_json']) ? $page['draft_json'] : $page['content_json'];
        $newVersion = ((int) ($page['version'] ?? 1)) + 1;

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Record in history for rollback capability
        $historyModel = new WebsitePageHistoryModel();
        $historyModel->insert([
            'page_id'      => $page['id'],
            'version'      => (int) ($page['version'] ?? 1),
            'content_json' => $page['content_json'],
            'created_by'   => $userId,
        ]);

        // 2. Update page to live
        $this->update($page['id'], [
            'content_json'      => $newContentJson,
            'draft_json'        => null,
            'version'           => $newVersion,
            'status'            => 'PUBLISHED',
            'last_published_at' => date('Y-m-d H:i:s'),
            'last_updated_by'   => $userId,
        ]);

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Rollback page content to a specific previous version
     */
    public function rollback(string $slug, int $targetVersion, ?int $userId = null): bool
    {
        $page = $this->where('slug', $slug)->first();
        if (!$page) {
            return false;
        }

        $historyModel = new WebsitePageHistoryModel();
        $history = $historyModel->where('page_id', $page['id'])
                               ->where('version', $targetVersion)
                               ->first();

        if (!$history) {
            return false;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Save current into history before rolling back
        $historyModel->insert([
            'page_id'      => $page['id'],
            'version'      => (int) ($page['version'] ?? 1),
            'content_json' => $page['content_json'],
            'created_by'   => $userId,
        ]);

        $newVersion = ((int) ($page['version'] ?? 1)) + 1;

        $this->update($page['id'], [
            'content_json'      => $history['content_json'],
            'draft_json'        => null,
            'version'           => $newVersion,
            'last_published_at' => date('Y-m-d H:i:s'),
            'last_updated_by'   => $userId,
        ]);

        $db->transComplete();
        return $db->transStatus();
    }
}

