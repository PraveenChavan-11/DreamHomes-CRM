<?php
$path = 'app/Services/DocumentVaultService.php';
$content = file_get_contents($path);

// 1. Add 'storage_filename' and 'file_path' to storeResume return array
$oldReturn = "            return [
                'document_id'   => \$docId,
                'original_name' => \$safeOriginalName,
                'file_size'     => \$fileSize,
                'mime_type'     => \$detectedMime,
                'file_hash'     => \$fileHash,
            ];";

$newReturn = "            return [
                'document_id'      => \$docId,
                'original_name'    => \$safeOriginalName,
                'storage_filename' => \$randomStorageName,
                'file_path'        => \$destinationPath,
                'file_size'        => \$fileSize,
                'mime_type'        => \$detectedMime,
                'file_hash'        => \$fileHash,
            ];";

// 2. Add extractResumeKeywords method at the end of DocumentVaultService before closing brace
$targetEnd = "        \$doc['full_path'] = \$fullPath;
        return \$doc;
    }
}";

$newEnd = "        \$doc['full_path'] = \$fullPath;
        return \$doc;
    }

    /**
     * Lightweight CV Keyword & Skills Parser (GAP-03: Automated Resume Parsing)
     * Extracts text from PDF, DOC, or DOCX and matches against a curated dictionary.
     */
    public function extractResumeKeywords(string \$filePath, string \$ext): array
    {
        if (!file_exists(\$filePath) || !is_readable(\$filePath)) {
            return [];
        }

        \$rawText = '';
        \$ext = strtolower(\$ext);

        if (\$ext === 'docx') {
            \$zip = new ZipArchive();
            if (\$zip->open(\$filePath, ZipArchive::RDONLY) === true) {
                \$xml = \$zip->getFromName('word/document.xml');
                \$zip->close();
                if (\$xml !== false) {
                    \$rawText = strip_tags(str_replace(['<w:p>', '</w:p>', '<w:tab/>', '<w:br/>'], [' ', \"\\n\", \"\\t\", \"\\n\"], \$xml));
                }
            }
        } elseif (\$ext === 'pdf') {
            \$content = @file_get_contents(\$filePath);
            if (\$content !== false) {
                if (preg_match_all('/BT[\s\S]*?ET/i', \$content, \$matches)) {
                    foreach (\$matches[0] as \$block) {
                        if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', \$block, \$tMatches)) {
                            \$rawText .= ' ' . implode(' ', \$tMatches[1]);
                        }
                    }
                }
                \$rawText .= ' ' . preg_replace('/[^a-zA-Z0-9\+\#\.\s]/', ' ', \$content);
            }
        } elseif (\$ext === 'doc') {
            \$content = @file_get_contents(\$filePath);
            if (\$content !== false) {
                \$rawText = preg_replace('/[^a-zA-Z0-9\+\#\.\s]/', ' ', \$content);
            }
        }

        if (empty(\$rawText)) {
            return [];
        }

        \$skillDictionary = [
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

        \$detected = [];
        foreach (\$skillDictionary as \$skill) {
            \$pattern = '/\b' . preg_quote(\$skill, '/') . '\b/i';
            if (preg_match(\$pattern, \$rawText)) {
                \$detected[] = \$skill;
            }
        }

        return array_values(array_unique(\$detected));
    }
}";

$content_norm = str_replace("\r\n", "\n", $content);
$oldReturn_norm = str_replace("\r\n", "\n", $oldReturn);
$newReturn_norm = str_replace("\r\n", "\n", $newReturn);
$targetEnd_norm = str_replace("\r\n", "\n", $targetEnd);
$newEnd_norm = str_replace("\r\n", "\n", $newEnd);

if (strpos($content_norm, $oldReturn_norm) !== false && strpos($content_norm, $targetEnd_norm) !== false) {
    $content_norm = str_replace($oldReturn_norm, $newReturn_norm, $content_norm);
    $content_norm = str_replace($targetEnd_norm, $newEnd_norm, $content_norm);
    file_put_contents($path, $content_norm);
    echo "DocumentVaultService updated with keyword extractor.\n";
} else {
    echo "FAILED: markers not found in DocumentVaultService.\n";
}

