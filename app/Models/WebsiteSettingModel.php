<?php

namespace App\Models;

use CodeIgniter\Model;

class WebsiteSettingModel extends Model
{
    protected $table            = 'website_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'setting_key',
        'setting_value',
        'setting_group',
        'is_public',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = '';
    protected $updatedField  = 'updated_at';

    private static array $cache = [];

    /**
     * Retrieve all settings indexed by key
     */
    public function getAllKeyed(): array
    {
        if (!empty(self::$cache['all'])) {
            return self::$cache['all'];
        }

        $records = $this->findAll();
        $settings = [];
        foreach ($records as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        self::$cache['all'] = $settings;
        return $settings;
    }

    /**
     * Retrieve only public settings (for frontend / public views & APIs)
     */
    public function getPublicSettings(): array
    {
        if (!empty(self::$cache['public'])) {
            return self::$cache['public'];
        }

        $records = $this->where('is_public', 1)->findAll();
        $settings = [];
        foreach ($records as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        self::$cache['public'] = $settings;
        return $settings;
    }

    /**
     * Get a single setting by key
     */
    public function getSetting(string $key, ?string $default = null): ?string
    {
        $all = $this->getAllKeyed();
        return $all[$key] ?? $default;
    }

    /**
     * Update or insert a setting by key
     */
    public function setSetting(string $key, ?string $value, string $group = 'general', int $isPublic = 1): bool
    {
        $existing = $this->where('setting_key', $key)->first();
        self::$cache = []; // invalidate static cache

        if ($existing) {
            return $this->update($existing['id'], [
                'setting_value' => $value,
                'setting_group' => $group,
                'is_public'     => $isPublic,
            ]);
        }

        return (bool) $this->insert([
            'setting_key'   => $key,
            'setting_value' => $value,
            'setting_group' => $group,
            'is_public'     => $isPublic,
        ]);
    }
}

