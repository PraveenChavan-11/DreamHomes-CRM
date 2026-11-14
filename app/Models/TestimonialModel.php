<?php

namespace App\Models;

use CodeIgniter\Model;

class TestimonialModel extends Model
{
    protected $table            = 'testimonials';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'designation',
        'company',
        'content',
        'rating',
        'photo_path',
        'order_num',
        'is_published',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPublishedTestimonials(): array
    {
        return $this->where('is_published', 1)
                    ->orderBy('order_num', 'ASC')
                    ->findAll();
    }
}

