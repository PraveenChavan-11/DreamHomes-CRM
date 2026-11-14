<?php

namespace App\Models;

use CodeIgniter\Model;

class SitemapLogModel extends Model
{
    protected $table            = 'sitemap_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'trigger_source',
        'total_urls',
        'pages_count',
        'jobs_count',
        'insights_count',
        'duration_ms',
        'status',
        'error_message',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}

