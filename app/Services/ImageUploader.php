<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Validates and stores uploaded images securely:
 *  - whitelisted MIME types (jpeg/png/webp), verified by content not extension
 *  - max size enforced
 *  - re-encoded + resized via GD (strips any embedded payload / EXIF)
 *  - random, non-guessable filename
 *  - stored outside the public web root (served via a gated controller)
 */
final class ImageUploader
{
    private const MAX_BYTES = 3 * 1024 * 1024; // 3 MB
    private const MAX_DIM = 1200;

    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string,mixed> $file A single $_FILES entry.
     * @param string $subdir e.g. 'members' or 'projects'
     * @return string relative path stored (e.g. members/ab12cd.jpg)
     * @throws \RuntimeException on validation failure
     */
    public function store(array $file, string $subdir): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('The file failed to upload.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('The image must be 3 MB or smaller.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Invalid upload.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Only JPEG, PNG or WebP images are allowed.');
        }

        $image = $this->createImage($file['tmp_name'], $mime);
        if ($image === null) {
            throw new \RuntimeException('The image could not be processed.');
        }
        $image = $this->resize($image);

        $ext = self::ALLOWED[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = $this->baseDir() . '/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Upload directory is not writable.');
        }
        $target = $dir . '/' . $name;

        $ok = match ($ext) {
            'jpg'  => imagejpeg($image, $target, 85),
            'png'  => imagepng($image, $target, 6),
            'webp' => imagewebp($image, $target, 85),
            default => false,
        };
        imagedestroy($image);

        if (!$ok) {
            throw new \RuntimeException('Failed to save the image.');
        }
        @chmod($target, 0644);

        return $subdir . '/' . $name;
    }

    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $path = $this->baseDir() . '/' . ltrim($relativePath, '/');
        $real = realpath($path);
        if ($real !== false && str_starts_with($real, $this->baseDir())) {
            @unlink($real);
        }
    }

    public function baseDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads';
    }

    private function createImage(string $path, string $mime): ?\GdImage
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => false,
        };
        return $img instanceof \GdImage ? $img : null;
    }

    private function resize(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $max = self::MAX_DIM;
        if ($w <= $max && $h <= $max) {
            return $src;
        }
        $ratio = min($max / $w, $max / $h);
        $nw = (int) max(1, floor($w * $ratio));
        $nh = (int) max(1, floor($h * $ratio));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        return $dst;
    }
}
