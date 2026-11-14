<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqModel extends Model
{
    protected $table            = 'faqs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category',
        'question',
        'answer',
        'order_num',
        'is_published',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPublishedFaqs(?string $category = null): array
    {
        $builder = $this->where('is_published', 1);
        if ($category !== null && $category !== '') {
            $builder->where('category', $category);
        }
        return $builder->orderBy('order_num', 'ASC')
                       ->orderBy('id', 'ASC')
                       ->findAll();
    }
}

