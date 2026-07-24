<?php

namespace App\Middleware;

use App\Helpers\SecurityHelper;

class InputSanitizerMiddleware
{
    /**
     * Sanitize all incoming global request arrays ($_GET, $_POST) against XSS
     */
    public static function handle(): void
    {
        if (!empty($_GET)) {
            $_GET = SecurityHelper::sanitizeInputArray($_GET);
        }
        if (!empty($_POST)) {
            $_POST = SecurityHelper::sanitizeInputArray($_POST);
        }
    }
}
