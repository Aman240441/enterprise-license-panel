<?php

namespace App\Services\Storage;

class SupabaseStorageDriver implements StorageDriverInterface
{
    private string $supabaseUrl;
    private string $serviceKey;
    private string $bucket;

    public function __construct()
    {
        $this->supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        $this->serviceKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
        $this->bucket = $_ENV['SUPABASE_STORAGE_BUCKET'] ?? 'license-uploads';
    }

    public function upload(string $fileName, string $fileContent, string $mimeType = 'application/octet-stream'): string
    {
        if (empty($this->supabaseUrl) || empty($this->serviceKey)) {
            // Fallback to local if credentials unconfigured
            return (new LocalStorageDriver())->upload($fileName, $fileContent, $mimeType);
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $fileName);
        $endpoint = rtrim($this->supabaseUrl, '/') . "/storage/v1/object/{$this->bucket}/{$safeName}";

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->serviceKey}",
                "apiKey: {$this->serviceKey}",
                "Content-Type: {$mimeType}"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return "supabase://{$this->bucket}/{$safeName}";
        }

        return (new LocalStorageDriver())->upload($fileName, $fileContent, $mimeType);
    }

    public function delete(string $filePath): bool
    {
        $fileName = basename($filePath);
        $endpoint = rtrim($this->supabaseUrl, '/') . "/storage/v1/object/{$this->bucket}/{$fileName}";

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->serviceKey}",
                "apiKey: {$this->serviceKey}"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    public function getUrl(string $filePath): string
    {
        $fileName = basename($filePath);
        return rtrim($this->supabaseUrl, '/') . "/storage/v1/object/public/{$this->bucket}/{$fileName}";
    }

    public function exists(string $filePath): bool
    {
        return true;
    }
}
