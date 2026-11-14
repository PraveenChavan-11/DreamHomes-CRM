<?php

namespace App\Services;

use App\Models\CandidateDocumentModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use ZipArchive;

class DocumentVaultService
{
    private string $vaultPath;
    private const MAX_BYTES = 5242880; // 5 MB (5 * 1024 * 1024)
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    private const ALLOWED_MIMES = [
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
    ];

    public function __construct()
    {
        $this->vaultPath = WRITEPATH . 'vault' . DIRECTORY_SEPARATOR . 'resumes' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->vaultPath)) {
            mkdir($this->vaultPath, 0750, true);
        }

        // Defense-in-depth: Ensure directory access is blocked via .htaccess
        $htaccessPath = WRITEPATH . 'vault' . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\nOptions -ExecCGI -Indexes\n");
        }
    }

    /**
     * Validate file metadata, extension, size, and filename properties.
     */
    public function validateFileProperties(string $filename, string $mime, int $sizeBytes): array
    {
        // 1. Filename traversal & illegal characters
        if (str_contains($filename, "\0") || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return [
                'valid'   => false,
                'code'    => 'INVALID_FILENAME_CHARACTERS',
                'message' => 'Filename contains invalid path traversal or control characters.',
            ];
        }

        // 2. Double extension detection
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $dangerousExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'vbs', 'js', 'py', 'pl', 'cgi'];
            for ($i = 0; $i < count($parts) - 1; $i++) {
                if (in_array(strtolower($parts[$i]), $dangerousExtensions, true)) {
                    return [
                        'valid'   => false,
                        'code'    => 'DOUBLE_EXTENSION_DETECTED',
                        'message' => 'Prohibited dangerous double extension pattern detected.',
                    ];
                }
            }
        }

        // 3. Extension allowlist
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'valid'   => false,
                'code'    => 'DISALLOWED_FILE_EXTENSION',
                'message' => 'Only PDF, DOC, and DOCX documents are permitted.',
            ];
        }

        // 4. File size limits
        if ($sizeBytes <= 0 || $sizeBytes > self::MAX_BYTES) {
            return [
                'valid'   => false,
                'code'    => 'FILE_EXCEEDS_MAX_SIZE',
                'message' => 'Document exceeds the maximum permitted size of 5 MB.',
            ];
        }

        return [
            'valid'   => true,
            'code'    => 'VALID',
            'message' => 'File properties validated.',
        ];
    }

    /**
     * Inspect deep content structure and magic bytes
     */
    public function inspectFileContent(string $filePath, string $ext): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'valid'   => false,
                'code'    => 'FILE_NOT_FOUND',
                'message' => 'Document file cannot be opened for structural inspection.',
            ];
        }

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return [
                'valid'   => false,
                'code'    => 'FILE_READ_ERROR',
                'message' => 'Unable to read document header.',
            ];
        }
        $header = fread($handle, 16);
        fclose($handle);

        $ext = strtolower($ext);

        if ($ext === 'pdf') {
            if (!str_starts_with($header, "%PDF-")) {
                return [
                    'valid'   => false,
                    'code'    => 'INVALID_PDF_HEADER',
                    'message' => 'Malformed or fake PDF document. Header signature verification failed.',
                ];
            }
        } elseif ($ext === 'doc') {
            $oleMagic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
            if (!str_starts_with($header, $oleMagic)) {
                return [
                    'valid'   => false,
                    'code'    => 'INVALID_DOC_HEADER',
                    'message' => 'Malformed Microsoft Word (.doc) document. Binary header verification failed.',
                ];
            }
        } elseif ($ext === 'docx') {
            if (!str_starts_with($header, "PK\x03\x04")) {
                return [
                    'valid'   => false,
                    'code'    => 'INVALID_DOCX_HEADER',
                    'message' => 'Malformed DOCX document. ZIP package signature verification failed.',
                ];
            }

            try {
                $this->validateDocxArchive($filePath);
            } catch (\Throwable $e) {
                return [
                    'valid'   => false,
                    'code'    => 'INVALID_DOCX_ARCHIVE',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'valid'   => true,
            'code'    => 'VALID',
            'message' => 'Content structure validated.',
        ];
    }

    /**
     * Validate and securely store an uploaded candidate resume.
     *
     * @param UploadedFile $file
     * @param int $candidateId
     * @return array [ 'document_id', 'original_name', 'file_size', 'mime_type', 'file_hash' ]
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function storeResume(UploadedFile $file, int $candidateId): array
    {
        // 1. Upload errors check
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Upload failed: ' . $file->getErrorString() . ' (Code: ' . $file->getError() . ')');
        }

        // 2. Strict file size bound: Advertised 5MB limit
        $fileSize = $file->getSize();
        if ($fileSize <= 0) {
            throw new \InvalidArgumentException('Uploaded resume file is empty.');
        }
        if ($fileSize > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Resume file exceeds the 5MB size limit. Please upload a smaller document.');
        }

        // 3. Filename security checks (Double extensions, null bytes, traversal)
        $clientName = $file->getClientName();
        if (str_contains($clientName, "\0") || str_contains($clientName, '..') || str_contains($clientName, '/') || str_contains($clientName, '\\')) {
            throw new \InvalidArgumentException('Invalid or dangerous filename detected.');
        }

        // Disallow dangerous intermediate extensions (e.g. resume.php.pdf, cv.exe.docx)
        $parts = explode('.', $clientName);
        if (count($parts) > 2) {
            $dangerousExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'vbs', 'js', 'py', 'pl', 'cgi'];
            for ($i = 0; $i < count($parts) - 1; $i++) {
                if (in_array(strtolower($parts[$i]), $dangerousExtensions, true)) {
                    throw new \InvalidArgumentException('Prohibited double extension pattern detected in filename.');
                }
            }
        }

        // 4. Strict extension allowlist
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Unsupported file format (. ' . esc($ext) . '). Only PDF (.pdf), Word 97-2003 (.doc), and Word 2007+ (.docx) are permitted.');
        }

        $tempPath = $file->getTempName();
        if (!file_exists($tempPath) || !is_readable($tempPath)) {
            throw new \RuntimeException('Temporary upload storage is inaccessible.');
        }

        // 5. Detected MIME type check (using finfo magic bytes, not client-supplied header)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tempPath);
        finfo_close($finfo);

        $allowedForExt = self::ALLOWED_MIMES[$ext] ?? [];
        if (!in_array($detectedMime, $allowedForExt, true)) {
            // Special check: some systems detect .doc as application/octet-stream or application/vnd.ms-office
            if ($ext === 'doc' && ($detectedMime === 'application/octet-stream' || $detectedMime === 'application/vnd.ms-office')) {
                // Will be checked by magic bytes below
            } elseif ($ext === 'docx' && ($detectedMime === 'application/octet-stream' || $detectedMime === 'application/zip')) {
                // Will be verified by bounded ZipArchive inspection below
            } else {
                log_message('warning', "DocumentVaultService: MIME mismatch for {$clientName}: expected for {$ext}, got {$detectedMime}");
                throw new \InvalidArgumentException('File content does not match the expected ' . strtoupper($ext) . ' document structure.');
            }
        }

        // 6. Deep Content Structure & Magic Bytes Inspection
        $handle = fopen($tempPath, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Unable to inspect document structure.');
        }
        $header = fread($handle, 16);
        fclose($handle);

        if ($ext === 'pdf') {
            // PDF Magic Bytes: %PDF- (\x25\x50\x44\x46\x2D)
            if (!str_starts_with($header, "%PDF-")) {
                throw new \InvalidArgumentException('Malformed or fake PDF document. Header signature verification failed.');
            }
        } elseif ($ext === 'doc') {
            // OLE CFBF Magic Bytes: \xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1
            $oleMagic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
            if (!str_starts_with($header, $oleMagic)) {
                throw new \InvalidArgumentException('Malformed Microsoft Word (.doc) document. Binary header verification failed.');
            }
        } elseif ($ext === 'docx') {
            // DOCX is a ZIP package starting with PK\x03\x04 (\x50\x4B\x03\x04)
            if (!str_starts_with($header, "PK\x03\x04")) {
                throw new \InvalidArgumentException('Malformed DOCX document. ZIP package signature verification failed.');
            }

            // Bounded Archive Inspection for DOCX (Anti-Zip Bomb & Content Validation)
            $this->validateDocxArchive($tempPath);
        }

        // 7. Unguessable storage filename & safe sanitized metadata
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', basename($clientName));
        $randomStorageName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
        $destinationPath = $this->vaultPath . $randomStorageName;

        // 8. Move file into private vault
        $file->move($this->vaultPath, $randomStorageName);

        if (!file_exists($destinationPath)) {
            throw new \RuntimeException('Failed to store document in secure vault.');
        }

        // Calculate SHA-256 integrity hash
        $fileHash = hash_file('sha256', $destinationPath);

        // 9. Persist metadata in database with orphan protection
        try {
            $docModel = new CandidateDocumentModel();
            $docId = $docModel->insert([
                'candidate_id'     => $candidateId,
                'document_type'    => 'RESUME',
                'original_name'    => substr($safeOriginalName, 0, 190),
                'storage_filename' => $randomStorageName,
                'file_path'        => $destinationPath,
                'file_size'        => $fileSize,
                'mime_type'        => $detectedMime,
                'file_hash'        => $fileHash,
                'uploaded_at'      => date('Y-m-d H:i:s'),
            ]);

            return [
                'document_id'      => $docId,
                'original_name'    => $safeOriginalName,
                'storage_filename' => $randomStorageName,
                'file_path'        => $destinationPath,
                'file_size'        => $fileSize,
                'mime_type'        => $detectedMime,
                'file_hash'        => $fileHash,
            ];
        } catch (\Throwable $e) {
            // Clean up orphan file if database transaction or insertion fails
            if (file_exists($destinationPath)) {
                unlink($destinationPath);
                log_message('info', "DocumentVaultService: Cleaned up orphan file {$randomStorageName} after DB error");
            }
            throw $e;
        }
    }

    /**
     * Bounded archive inspection for DOCX files (Zip Bomb defense and structural integrity)
     */
    private function validateDocxArchive(string $zipPath): void
    {
        $zip = new ZipArchive();
        $res = $zip->open($zipPath, ZipArchive::RDONLY);
        if ($res !== true) {
            throw new \InvalidArgumentException('Unable to open DOCX archive for structural verification.');
        }

        $numFiles = $zip->numFiles;
        // Bound entry count
        if ($numFiles > 500) {
            $zip->close();
            throw new \InvalidArgumentException('DOCX document rejected: excessive internal entry count (' . $numFiles . ').');
        }

        $totalUncompressedSize = 0;
        $maxUncompressedLimit = 52428800; // 50 MB
        $hasDocXml = false;
        $hasContentTypes = false;

        $prohibitedExtensions = ['exe', 'bat', 'cmd', 'vbs', 'js', 'sh', 'php', 'phtml', 'dll'];

        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) {
                continue;
            }

            $entryName = $stat['name'];
            $entrySize = $stat['size'];

            $totalUncompressedSize += $entrySize;
            if ($totalUncompressedSize > $maxUncompressedLimit) {
                $zip->close();
                throw new \InvalidArgumentException('DOCX document rejected: uncompressed payload exceeds safe threshold (possible decompression bomb).');
            }

            // Check for required Office Open XML components
            if ($entryName === '[Content_Types].xml') {
                $hasContentTypes = true;
            }
            if ($entryName === 'word/document.xml') {
                $hasDocXml = true;
            }

            // Reject executable files embedded inside the DOCX ZIP archive
            $entryExt = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
            if (in_array($entryExt, $prohibitedExtensions, true)) {
                $zip->close();
                throw new \InvalidArgumentException('DOCX document rejected: dangerous executable asset (' . esc($entryName) . ') found inside document archive.');
            }
        }

        $zip->close();

        if (!$hasContentTypes && !$hasDocXml) {
            throw new \InvalidArgumentException('Malformed DOCX document: missing required OpenXML document definitions.');
        }
    }

    /**
     * Retrieve document and verify file exists on disk
     */
    public function getDocument(int $documentId): ?array
    {
        $docModel = new CandidateDocumentModel();
        $doc = $docModel->find($documentId);
        if (!$doc) {
            return null;
        }

        $fullPath = $this->vaultPath . $doc['storage_filename'];
        if (!file_exists($fullPath)) {
            return null;
        }

        $doc['full_path'] = $fullPath;
        return $doc;
    }

    /**
     * Lightweight CV Keyword & Skills Parser (GAP-03: Automated Resume Parsing)
     * Extracts text from PDF, DOC, or DOCX and matches against a curated dictionary.
     */
    public function extractResumeKeywords(string $filePath, string $ext): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $rawText = '';
        $ext = strtolower($ext);

        if ($ext === 'docx') {
            $zip = new ZipArchive();
            if ($zip->open($filePath, ZipArchive::RDONLY) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml !== false) {
                    $rawText = strip_tags(str_replace(['<w:p>', '</w:p>', '<w:tab/>', '<w:br/>'], [' ', "\n", "\t", "\n"], $xml));
                }
            }
        } elseif ($ext === 'pdf') {
            $content = @file_get_contents($filePath);
            if ($content !== false) {
                if (preg_match_all('/BT[\s\S]*?ET/i', $content, $matches)) {
                    foreach ($matches[0] as $block) {
                        if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $block, $tMatches)) {
                            $rawText .= ' ' . implode(' ', $tMatches[1]);
                        }
                    }
                }
                $rawText .= ' ' . preg_replace('/[^a-zA-Z0-9\+\#\.\s]/', ' ', $content);
            }
        } elseif ($ext === 'doc') {
            $content = @file_get_contents($filePath);
            if ($content !== false) {
                $rawText = preg_replace('/[^a-zA-Z0-9\+\#\.\s]/', ' ', $content);
            }
        }

        if (empty($rawText)) {
            return [];
        }

        $skillDictionary = [
            'Java', 'Spring Boot', 'Python', 'Django', 'Flask', 'PHP', 'Laravel', 'CodeIgniter',
            'React', 'Node.js', 'Angular', 'Vue.js', 'JavaScript', 'TypeScript', 'HTML5', 'CSS3', 'Tailwind',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Oracle', 'SQL Server',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'DevOps', 'CI/CD', 'Git', 'Linux',
            'REST API', 'GraphQL', 'Microservices',
            'AutoCAD', 'SolidWorks', 'CATIA', 'PLC', 'SCADA', 'CNC', 'Six Sigma', 'Lean Manufacturing',
            'Quality Assurance', 'Supply Chain', 'Procurement', 'Production Planning', 'Maintenance',
            'Human Resources', 'Recruitment', 'Talent Acquisition', 'Payroll', 'Employee Relations',
            'Accounting', 'GST', 'TDS', 'Financial Analysis', 'Auditing', 'Compliance', 'Banking',
            'Business Development', 'B2B Sales', 'Key Account Management', 'Marketing', 'SEO', 'SEM',
            'Project Management', 'Agile', 'Scrum', 'Product Management', 'Customer Support',
        ];

        $detected = [];
        foreach ($skillDictionary as $skill) {
            $pattern = '/\b' . preg_quote($skill, '/') . '\b/i';
            if (preg_match($pattern, $rawText)) {
                $detected[] = $skill;
            }
        }

        return array_values(array_unique($detected));
    }
}
