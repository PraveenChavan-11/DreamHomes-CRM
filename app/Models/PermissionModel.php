<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code',
        'name',
        'category',
    ];

    protected $useTimestamps = false;

    /**
     * Get all permissions grouped by category
     */
    public function getAllGroupedByCategory(): array
    {
        $all = $this->orderBy('category', 'ASC')->orderBy('name', 'ASC')->findAll();
        $grouped = [];

        foreach ($all as $p) {
            $cat = ucfirst(strtolower($p['category'] ?: 'General'));
            $grouped[$cat][] = $p;
        }

        return $grouped;
    }
}

