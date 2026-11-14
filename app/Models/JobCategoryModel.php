<?php

namespace App\Models;

use CodeIgniter\Model;

class JobCategoryModel extends Model
{
    protected $table            = 'job_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'description',
        'order_num',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveCategories(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('order_num', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }
}

