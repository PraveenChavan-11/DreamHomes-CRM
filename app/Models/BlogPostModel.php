<?php

namespace App\Models;

use CodeIgniter\Model;

class BlogPostModel extends Model
{
    protected $table            = 'blog_posts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'featured_image',
        'meta_title',
        'meta_description',
        'is_published',
        'published_at',
        'author_id',
        'meta_keywords',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPublishedPosts(int $limit = 9, int $offset = 0): array
    {
        return $this->select('blog_posts.*, blog_categories.name as category_name, blog_categories.slug as category_slug, admin_users.first_name, admin_users.last_name')
                    ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left')
                    ->join('admin_users', 'admin_users.id = blog_posts.author_id', 'left')
                    ->where('blog_posts.is_published', 1)
                    ->orderBy('blog_posts.published_at', 'DESC')
                    ->findAll($limit, $offset);
    }

    public function getPublishedBySlug(string $slug): ?array
    {
        return $this->select('blog_posts.*, blog_categories.name as category_name, blog_categories.slug as category_slug, admin_users.first_name, admin_users.last_name')
                    ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left')
                    ->join('admin_users', 'admin_users.id = blog_posts.author_id', 'left')
                    ->where('blog_posts.slug', $slug)
                    ->where('blog_posts.is_published', 1)
                    ->first();
    }
}

