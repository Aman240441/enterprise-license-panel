<?php

namespace App\Services\Storage;

interface StorageDriverInterface
{
    /**
     * Store file content and return relative storage path or public URL
     */
    public function upload(string $fileName, string $fileContent, string $mimeType = 'application/octet-stream'): string;

    /**
     * Delete file from storage
     */
    public function delete(string $filePath): bool;

    /**
     * Get temporary or direct download URL
     */
    public function getUrl(string $filePath): string;

    /**
     * Check if file exists
     */
    public function exists(string $filePath): bool;
}
