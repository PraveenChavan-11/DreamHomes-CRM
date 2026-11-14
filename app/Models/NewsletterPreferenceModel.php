<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsletterPreferenceModel extends Model
{
    protected $table            = 'newsletter_preferences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subscriber_id',
        'category',
        'is_enabled',
        'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get all preferences for a subscriber keyed by category
     */
    public function getPreferencesBySubscriberId(int $subscriberId): array
    {
        $rows = $this->where('subscriber_id', $subscriberId)->findAll();
        $prefs = [];
        foreach ($rows as $row) {
            $prefs[$row['category']] = (bool) $row['is_enabled'];
        }
        return $prefs;
    }

    /**
     * Save / update a list of categories for a subscriber
     */
    public function setPreferences(int $subscriberId, array $categories): void
    {
        foreach ($categories as $cat => $enabled) {
            $existing = $this->where('subscriber_id', $subscriberId)
                             ->where('category', $cat)
                             ->first();

            if ($existing) {
                $this->update($existing['id'], [
                    'is_enabled' => $enabled ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->insert([
                    'subscriber_id' => $subscriberId,
                    'category'      => $cat,
                    'is_enabled'    => $enabled ? 1 : 0,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
