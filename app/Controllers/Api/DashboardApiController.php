<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Middleware\AuthMiddleware;

class DashboardApiController
{
    /**
     * GET /api/v1/dashboard
     * Executive Dashboard Counters & Chart Datasets
     */
    public function getDashboard(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();

        // 1. Executive Metric Cards
        $totalLicenses = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `licenses`")['cnt'] ?? 0);
        $activeLicenses = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `licenses` WHERE status = 'active'")['cnt'] ?? 0);
        $expiredLicenses = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `licenses` WHERE status = 'expired'")['cnt'] ?? 0);
        $revokedLicenses = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `licenses` WHERE status = 'revoked'")['cnt'] ?? 0);
        $suspendedLicenses = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `licenses` WHERE status = 'suspended'")['cnt'] ?? 0);

        $todayActivations = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `devices` WHERE DATE(activation_date) = CURDATE()")['cnt'] ?? 0);
        $totalProducts = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `products` WHERE status = 'active'")['cnt'] ?? 0);
        $totalEmployees = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `users` u JOIN `roles` r ON u.role_id = r.id WHERE r.slug = 'employee' AND u.deleted_at IS NULL")['cnt'] ?? 0);
        $totalResellers = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `resellers` WHERE status = 'active'")['cnt'] ?? 0);
        $totalCustomers = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `customers`")['cnt'] ?? 0);
        $totalUploads = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `uploads`")['cnt'] ?? 0);

        $storageSum = DatabaseConnection::fetchOne("SELECT SUM(file_size) as total_bytes FROM `uploads`")['total_bytes'] ?? 0;
        $storageMb = round($storageSum / (1024 * 1024), 2);

        // 2. Monthly License Generation Chart Dataset (Last 12 Months)
        $monthlyGrowth = DatabaseConnection::fetchAll("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month_label, COUNT(*) as count 
            FROM `licenses` 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month_label 
            ORDER BY month_label ASC
        ");

        // 3. Status Breakdown Pie Dataset
        $statusBreakdown = [
            'active' => $activeLicenses,
            'expired' => $expiredLicenses,
            'revoked' => $revokedLicenses,
            'suspended' => $suspendedLicenses
        ];

        // 4. Country Distribution Dataset (Top 8)
        $countryDistribution = DatabaseConnection::fetchAll("
            SELECT country, COUNT(*) as count 
            FROM `devices` 
            WHERE country IS NOT NULL AND country != '' 
            GROUP BY country 
            ORDER BY count DESC 
            LIMIT 8
        ");

        // 5. Device OS Breakdown Dataset
        $osBreakdown = DatabaseConnection::fetchAll("
            SELECT IFNULL(os, 'Unknown OS') as os_name, COUNT(*) as count 
            FROM `devices` 
            GROUP BY os_name 
            ORDER BY count DESC 
            LIMIT 5
        ");

        // 6. Recent Activity Logs (Last 8)
        $recentLogs = DatabaseConnection::fetchAll("
            SELECT al.*, u.full_name, u.email 
            FROM `activity_logs` al 
            LEFT JOIN `users` u ON al.user_id = u.id 
            ORDER BY al.id DESC 
            LIMIT 8
        ");

        // 7. Recent Activations (Last 8)
        $recentActivations = DatabaseConnection::fetchAll("
            SELECT d.*, l.license_key, p.name as product_name, c.name as customer_name
            FROM `devices` d
            JOIN `licenses` l ON d.license_id = l.id
            JOIN `products` p ON l.product_id = p.id
            LEFT JOIN `customers` c ON l.customer_id = c.id
            ORDER BY d.id DESC
            LIMIT 8
        ");

        ResponseHelper::success([
            'metrics' => [
                'total_licenses'      => $totalLicenses,
                'active_licenses'     => $activeLicenses,
                'expired_licenses'    => $expiredLicenses,
                'revoked_licenses'    => $revokedLicenses,
                'suspended_licenses'  => $suspendedLicenses,
                'today_activations'   => $todayActivations,
                'total_products'      => $totalProducts,
                'total_employees'     => $totalEmployees,
                'total_resellers'     => $totalResellers,
                'total_customers'     => $totalCustomers,
                'total_uploads'       => $totalUploads,
                'storage_usage_mb'    => $storageMb
            ],
            'charts' => [
                'monthly_growth'      => $monthlyGrowth,
                'status_breakdown'    => $statusBreakdown,
                'country_distribution'=> $countryDistribution,
                'os_breakdown'        => $osBreakdown
            ],
            'recent_logs'            => $recentLogs,
            'recent_activations'     => $recentActivations
        ], "Dashboard metrics retrieved successfully");
    }
}
