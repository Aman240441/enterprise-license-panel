<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $params;
    private array $body;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pathOnly = parse_url($uri, PHP_URL_PATH);
        
        // Strip base subfolder path if running in subdirectory
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && str_starts_with($pathOnly, $scriptName)) {
            $pathOnly = substr($pathOnly, strlen($scriptName));
        }
        
        $this->path = '/' . trim($pathOnly, '/');
        $this->params = $_GET;

        // Parse JSON or POST payload
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $jsonInput = file_get_contents('php://input');
            $this->body = json_decode($jsonInput, true) ?? [];
        } else {
            $this->body = $_POST;
        }

        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->params[$key] ?? $default;
    }

    public function getHeader(string $key, mixed $default = null): mixed
    {
        $normalizedKey = strtolower($key);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $normalizedKey) {
                return $v;
            }
        }
        return $default;
    }
}
