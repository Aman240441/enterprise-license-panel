<?php

namespace App\Services\Storage;

class R2StorageDriver implements StorageDriverInterface
{
    public function upload(string $fileName, string $fileContent, string $mimeType = 'application/octet-stream'): string
    {
        // Cloudflare R2 Driver contract implementation
        return (new LocalStorageDriver())->upload($fileName, $fileContent, $mimeType);
    }

    public function delete(string $filePath): bool
    {
        return (new LocalStorageDriver())->delete($filePath);
    }

    public function getUrl(string $filePath): string
    {
        return (new LocalStorageDriver())->getUrl($filePath);
    }

    public function exists(string $filePath): bool
    {
        return (new LocalStorageDriver())->exists($filePath);
    }
}
