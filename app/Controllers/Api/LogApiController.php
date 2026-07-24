<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Middleware\AuthMiddleware;
use App\Middleware\RBACMiddleware;

class LogApiController
{
    /**
     * GET /api/v1/logs/activity
     */
    public function activityLogs(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('logs.view');

        $params = $request->getParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 25);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $queryParams = [];

        if (!empty($params['search'])) {
            $term = '%' . trim($params['search']) . '%';
            $where[] = "(al.action LIKE ? OR al.description LIKE ? OR al.ip_address LIKE ? OR u.full_name LIKE ?)";
            array_push($queryParams, $term, $term, $term, $term);
        }

        $whereSql = implode(" AND ", $where);

        $countRow = DatabaseConnection::fetchOne("
            SELECT COUNT(*) as total 
            FROM `activity_logs` al 
            LEFT JOIN `users` u ON al.user_id = u.id 
            WHERE {$whereSql}
        ", $queryParams);
        $total = (int) ($countRow['total'] ?? 0);

        $logs = DatabaseConnection::fetchAll("
            SELECT al.*, u.full_name, u.email 
            FROM `activity_logs` al 
            LEFT JOIN `users` u ON al.user_id = u.id 
            WHERE {$whereSql}
            ORDER BY al.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $queryParams);

        ResponseHelper::success($logs, "Activity logs retrieved", 200, [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
    }
}
