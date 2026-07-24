<?php

namespace App\Config;

class Env
{
    private static array $variables = [];
    private static bool $loaded = false;

    /**
     * Load environment variables from a .env file
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Strip quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                // Convert booleans & null
                $lowerVal = strtolower($value);
                if ($lowerVal === 'true') {
                    $typedVal = true;
                } elseif ($lowerVal === 'false') {
                    $typedVal = false;
                } elseif ($lowerVal === 'null') {
                    $typedVal = null;
                } else {
                    $typedVal = $value;
                }

                self::$variables[$name] = $typedVal;
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable value with default fallback
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return $envVal;
        }

        return $default;
    }
}
