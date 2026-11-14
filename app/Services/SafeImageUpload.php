<?php

namespace App\Services;

/**
 * Content-based validation for admin image uploads (CMS media library).
 *
 * The decision is made on the file's BYTES, never on its name or the client-sent type:
 *   - detected MIME (finfo) must be JPEG, PNG, WebP or GIF, and the file must decode as an image of that type;
 *   - the stored extension is derived from the detected type, so "evil.php.jpg" or "x.svg" cannot choose its own;
 *   - SVG is refused outright (it can contain script that runs when opened directly from /uploads/);
 *   - script/executable names and double extensions are refused; size and pixel dimensions are capped.
 */
final class SafeImageUpload
{
    public const MAX_BYTES     = 5 * 1024 * 1024;
    public const MAX_DIMENSION = 8000;
    private const MIME_TO_EXT  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    private const IMAGETYPE_MIME = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp', IMAGETYPE_GIF => 'image/gif'];

    /** @return array{ok:bool,ext:string,mime:string,error:string} */
    public static function inspect(string $tmpPath, string $clientName, int $size): array
    {
        $fail = static fn (string $m) => ['ok' => false, 'ext' => '', 'mime' => '', 'error' => $m];

        if (!is_file($tmpPath) || $size <= 0) {
            return $fail('The file is empty or could not be read.');
        }
        if ($size > self::MAX_BYTES) {
            return $fail('File is too large. Maximum size is 5 MB.');
        }
        if (preg_match('/\.(php\d?|phtml|phar|svg|html?|js|exe|sh|bat|cgi|pl|py)(\.|$)/i', $clientName)) {
            return $fail('This file name is not permitted.');
        }
        $head     = @file_get_contents($tmpPath, false, null, 0, 8192);
        $detected = $head === false ? '' : (string) ((new \finfo(FILEINFO_MIME_TYPE))->buffer($head) ?: '');
        $info     = @getimagesize($tmpPath);
        if (!isset(self::MIME_TO_EXT[$detected]) || $info === false || (self::IMAGETYPE_MIME[$info[2]] ?? null) !== $detected) {
            return $fail('Only JPG, PNG, WEBP and GIF images are accepted.');
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            return $fail('The image dimensions are not acceptable.');
        }
        return ['ok' => true, 'ext' => self::MIME_TO_EXT[$detected], 'mime' => $detected, 'error' => ''];
    }
}
