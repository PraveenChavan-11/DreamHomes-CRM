<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;

/**
 * Enterprise Social Media Library Service
 *
 * Manages media uploads, secure storage, automatic thumbnail generation,
 * metadata extraction (dimensions, duration, size, MIME), asset tagging,
 * and post attachment associations.
 */
class SocialMediaLibraryService
{
    private BaseConnection $db;
    private SocialSecurityService $security;
    private string $uploadPath;
    private string $uploadBaseUrl;

    public function __construct(?BaseConnection $db = null, ?SocialSecurityService $security = null)
    {
        $this->db            = $db ?? Database::connect();
        $this->security      = $security ?? new SocialSecurityService($this->db);
        $this->uploadPath    = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'social' . DIRECTORY_SEPARATOR;
        $this->uploadBaseUrl = base_url('uploads/social/');

        if (!is_dir($this->uploadPath)) {
            @mkdir($this->uploadPath, 0755, true);
        }

        // Security: deny script execution inside uploads directory
        $htaccessPath = $this->uploadPath . '.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents($htaccessPath, "<FilesMatch \"\.(php|php5|phtml|phar|sh|pl|cgi)$\">\n    Deny from all\n</FilesMatch>\nOptions -ExecCGI -Indexes\n");
        }
    }

    /**
     * Store an uploaded media file.
     */
    public function storeUploadedFile(UploadedFile $file, string $category = 'corporate_branding', ?string $tags = null, ?int $uploadedBy = null): array
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('Uploaded file error: ' . $file->getErrorString());
        }

        $tempPath = $file->getTempName();
        $origName = $file->getClientName();

        // Validate via Security Service
        $validation = $this->security->validateMediaFile($tempPath, $origName);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }

        $ext = $validation['extension'];
        $safeFilename = 'social_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath   = $this->uploadPath . $safeFilename;

        if (!$file->move($this->uploadPath, $safeFilename)) {
            throw new \RuntimeException('Failed to move uploaded media to storage directory.');
        }

        $fileUrl = $this->uploadBaseUrl . $safeFilename;
        $thumbnailUrl = null;

        // Generate thumbnail for image files
        if ($validation['media_type'] === 'image') {
            $thumbnailUrl = $this->generateThumbnail($targetPath, $safeFilename);
        }

        $record = [
            'title'            => pathinfo($origName, PATHINFO_FILENAME),
            'filename'         => $safeFilename,
            'original_name'    => $origName,
            'file_path'        => $targetPath,
            'file_url'         => $fileUrl,
            'thumbnail_url'    => $thumbnailUrl ?? $fileUrl,
            'mime_type'        => $validation['mime_type'],
            'file_size'        => $validation['file_size'],
            'media_type'       => $validation['media_type'],
            'width'            => $validation['width'],
            'height'           => $validation['height'],
            'duration_seconds' => null,
            'category'         => $category,
            'tags'             => $tags,
            'uploaded_by'      => $uploadedBy,
            'usage_count'      => 0,
            'is_approved'      => 1,
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        $this->db->table('social_media_library')->insert($record);
        $record['id'] = (int) $this->db->insertID();

        return $record;
    }

    /**
     * Store raw file from path (useful for testing or automated imports).
     */
    public function storeFromPath(string $sourcePath, string $origName, string $category = 'corporate_branding', ?int $uploadedBy = null): array
    {
        $validation = $this->security->validateMediaFile($sourcePath, $origName);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }

        $ext = $validation['extension'];
        $safeFilename = 'social_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath   = $this->uploadPath . $safeFilename;

        if (!copy($sourcePath, $targetPath)) {
            throw new \RuntimeException('Failed to copy file to social media directory.');
        }

        $fileUrl = $this->uploadBaseUrl . $safeFilename;
        $thumbnailUrl = null;
        if ($validation['media_type'] === 'image') {
            $thumbnailUrl = $this->generateThumbnail($targetPath, $safeFilename);
        }

        $record = [
            'title'         => pathinfo($origName, PATHINFO_FILENAME),
            'filename'      => $safeFilename,
            'original_name' => $origName,
            'file_path'     => $targetPath,
            'file_url'      => $fileUrl,
            'thumbnail_url' => $thumbnailUrl ?? $fileUrl,
            'mime_type'     => $validation['mime_type'],
            'file_size'     => $validation['file_size'],
            'media_type'    => $validation['media_type'],
            'width'         => $validation['width'],
            'height'        => $validation['height'],
            'category'      => $category,
            'uploaded_by'   => $uploadedBy,
            'usage_count'   => 0,
            'is_approved'   => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->table('social_media_library')->insert($record);
        $record['id'] = (int) $this->db->insertID();

        return $record;
    }

    /**
     * Generate an optimized thumbnail for images.
     */
    private function generateThumbnail(string $filePath, string $filename): ?string
    {
        try {
            $thumbDir = $this->uploadPath . 'thumbs' . DIRECTORY_SEPARATOR;
            if (!is_dir($thumbDir)) {
                @mkdir($thumbDir, 0755, true);
            }

            $thumbFilename = 'thumb_' . $filename;
            $thumbPath     = $thumbDir . $thumbFilename;

            $image = \Config\Services::image();
            $image->withFile($filePath)
                ->resize(320, 320, true, 'height')
                ->save($thumbPath, 85);

            return $this->uploadBaseUrl . 'thumbs/' . $thumbFilename;
        } catch (\Throwable $e) {
            log_message('notice', 'Thumbnail generation failed, falling back to full image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Associate media library items with a post.
     */
    public function attachMediaToPost(int $postId, array $mediaLibraryIds, string $platform = 'all'): void
    {
        $order = 0;
        foreach ($mediaLibraryIds as $mid) {
            $mid = (int) $mid;
            $media = $this->db->table('social_media_library')->where('id', $mid)->get()->getRowArray();
            if ($media) {
                $this->db->table('social_post_media')->insert([
                    'post_id'          => $postId,
                    'media_library_id' => $mid,
                    'platform'         => $platform,
                    'sort_order'       => $order++,
                    'media_type'       => $media['media_type'],
                    'file_path'        => $media['file_path'],
                    'file_url'         => $media['file_url'],
                    'mime_type'        => $media['mime_type'],
                    'file_size'        => $media['file_size'],
                    'width'            => $media['width'],
                    'height'           => $media['height'],
                    'alt_text'         => $media['title'],
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);

                // Increment usage count
                $this->db->table('social_media_library')
                    ->where('id', $mid)
                    ->set('usage_count', 'usage_count + 1', false)
                    ->update();
            }
        }
    }

    /**
     * Query media library items with filtering and pagination.
     */
    public function getMediaList(array $filters = [], int $limit = 24, int $offset = 0): array
    {
        $builder = $this->db->table('social_media_library');

        if (!empty($filters['media_type'])) {
            $builder->where('media_type', $filters['media_type']);
        }
        if (!empty($filters['category'])) {
            $builder->where('category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('tags', $search)
                ->orLike('original_name', $search)
            ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $items = $builder->orderBy('id', 'DESC')->limit($limit, $offset)->get()->getResultArray();

        return [
            'total'  => $total,
            'items'  => $items,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Delete media item from library.
     */
    public function deleteMedia(int $id): bool
    {
        $item = $this->db->table('social_media_library')->where('id', $id)->get()->getRowArray();
        if (!$item) {
            return false;
        }

        // Delete physical files
        if (file_exists($item['file_path'])) {
            @unlink($item['file_path']);
        }

        $thumbPath = $this->uploadPath . 'thumbs' . DIRECTORY_SEPARATOR . 'thumb_' . $item['filename'];
        if (file_exists($thumbPath)) {
            @unlink($thumbPath);
        }

        $this->db->table('social_media_library')->where('id', $id)->delete();
        $this->db->table('social_post_media')->where('media_library_id', $id)->delete();

        return true;
    }
}

