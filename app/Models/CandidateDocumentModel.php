<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateDocumentModel extends Model
{
    protected $table            = 'candidate_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'candidate_id',
        'document_type',
        'original_name',
        'storage_filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_hash',
        'uploaded_at',
    ];

    protected $useTimestamps = false;
}

