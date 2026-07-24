<?php

namespace App\Services\Storage;

class LocalStorageDriver implements StorageDriverInterface
{
    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../../storage/uploads/';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }
    }

    public function upload(string $fileName, string $fileContent, string $mimeType = 'application/octet-stream'): string
    {
        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $fileName);
        $fullPath = $this->uploadDir . $safeName;
        file_put_contents($fullPath, $fileContent);

        return 'uploads/' . $safeName;
    }

    public function delete(string $filePath): bool
    {
        $fullPath = __DIR__ . '/../../../storage/' . ltrim($filePath, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function getUrl(string $filePath): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return rtrim($baseUrl, '/') . '/storage/' . ltrim($filePath, '/');
    }

    public function exists(string $filePath): bool
    {
        $fullPath = __DIR__ . '/../../../storage/' . ltrim($filePath, '/');
        return file_exists($fullPath);
    }
}
