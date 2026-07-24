<?php

namespace App\Database;

use App\Helpers\SecurityHelper;

class Seeder
{
    public static function run(): void
    {
        $existing = DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM products WHERE slug = 'chrome-extension'");
        if (!empty($existing['cnt']) && (int)$existing['cnt'] > 0) {
            return; // Already seeded, skip to preserve entity IDs
        }

        $isPgsql = DatabaseConnection::isPgsql();

        if (!$isPgsql) {
            DatabaseConnection::query("SET FOREIGN_KEY_CHECKS = 0");
        }

        try {
            self::seedRoles();
            self::seedPermissions();
            self::seedRolePermissions();
            self::seedProducts();
            self::seedPlans();
            self::seedSettings();
            self::seedSuperAdmin();
        } finally {
            if (!$isPgsql) {
                DatabaseConnection::query("SET FOREIGN_KEY_CHECKS = 1");
            }
        }
    }

    private static function seedRoles(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full System Access'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Can manage licenses, plans, products, and customers'],
            ['name' => 'Reseller', 'slug' => 'reseller', 'description' => 'Can generate licenses for assigned products and track commissions'],
            ['name' => 'Employee', 'slug' => 'employee', 'description' => 'Can generate licenses within assigned quotas'],
            ['name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Read-only access to platform metrics and licenses']
        ];

        foreach ($roles as $r) {
            $check = DatabaseConnection::fetchOne("SELECT id FROM roles WHERE slug = ?", [$r['slug']]);
            if (!$check) {
                DatabaseConnection::query(
                    "INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)",
                    [$r['name'], $r['slug'], $r['description']]
                );
            }
        }
    }

    private static function seedPermissions(): void
    {
        $permissions = [
            ['name' => 'Manage Platform Settings', 'slug' => 'settings.manage', 'category' => 'system', 'description' => 'Modify system configurations'],
            ['name' => 'Manage Employees & Users', 'slug' => 'users.manage', 'category' => 'system', 'description' => 'Create, edit, and delete system accounts'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'category' => 'products', 'description' => 'Create and edit software products'],
            ['name' => 'Manage Resellers', 'slug' => 'resellers.manage', 'category' => 'resellers', 'description' => 'Manage reseller accounts and commissions'],
            ['name' => 'Manage License Plans', 'slug' => 'plans.manage', 'category' => 'plans', 'description' => 'Create and edit license plan tiers'],
            ['name' => 'Generate License', 'slug' => 'licenses.generate', 'category' => 'licenses', 'description' => 'Generate single license keys'],
            ['name' => 'Bulk Generate Licenses', 'slug' => 'licenses.bulk_generate', 'category' => 'licenses', 'description' => 'Bulk generate license keys'],
            ['name' => 'Revoke License', 'slug' => 'licenses.revoke', 'category' => 'licenses', 'description' => 'Revoke active licenses'],
            ['name' => 'Suspend License', 'slug' => 'licenses.suspend', 'category' => 'licenses', 'description' => 'Suspend active licenses'],
            ['name' => 'Delete License', 'slug' => 'licenses.delete', 'category' => 'licenses', 'description' => 'Delete licenses'],
            ['name' => 'View All Licenses', 'slug' => 'licenses.view_all', 'category' => 'licenses', 'description' => 'View all system licenses'],
            ['name' => 'Manage Devices', 'slug' => 'devices.manage', 'category' => 'devices', 'description' => 'Deactivate or reset device activations'],
            ['name' => 'View Activity Logs', 'slug' => 'logs.view', 'category' => 'analytics', 'description' => 'View activity and login logs'],
            ['name' => 'Export Data', 'slug' => 'data.export', 'category' => 'analytics', 'description' => 'Export data to CSV/Excel/PDF']
        ];

        foreach ($permissions as $p) {
            $check = DatabaseConnection::fetchOne("SELECT id FROM permissions WHERE slug = ?", [$p['slug']]);
            if (!$check) {
                DatabaseConnection::query(
                    "INSERT INTO permissions (name, slug, category, description) VALUES (?, ?, ?, ?)",
                    [$p['name'], $p['slug'], $p['category'], $p['description']]
                );
            }
        }
    }

    private static function seedRolePermissions(): void
    {
        // Super Admin permissions (All)
        $superAdmin = DatabaseConnection::fetchOne("SELECT id FROM roles WHERE slug = 'super_admin'");
        if ($superAdmin) {
            $allPerms = DatabaseConnection::fetchAll("SELECT id FROM permissions");
            foreach ($allPerms as $perm) {
                $check = DatabaseConnection::fetchOne("SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ?", [$superAdmin['id'], $perm['id']]);
                if (!$check) {
                    DatabaseConnection::query("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$superAdmin['id'], $perm['id']]);
                }
            }
        }

        // Admin permissions
        $admin = DatabaseConnection::fetchOne("SELECT id FROM roles WHERE slug = 'admin'");
        if ($admin) {
            $adminPerms = DatabaseConnection::fetchAll("SELECT id FROM permissions WHERE slug IN ('products.manage', 'resellers.manage', 'plans.manage', 'licenses.generate', 'licenses.bulk_generate', 'licenses.revoke', 'licenses.suspend', 'licenses.view_all', 'devices.manage', 'logs.view', 'data.export')");
            foreach ($adminPerms as $perm) {
                $check = DatabaseConnection::fetchOne("SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ?", [$admin['id'], $perm['id']]);
                if (!$check) {
                    DatabaseConnection::query("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$admin['id'], $perm['id']]);
                }
            }
        }

        // Employee permissions
        $emp = DatabaseConnection::fetchOne("SELECT id FROM roles WHERE slug = 'employee'");
        if ($emp) {
            $empPerms = DatabaseConnection::fetchAll("SELECT id FROM permissions WHERE slug IN ('licenses.generate', 'licenses.bulk_generate', 'licenses.view_all', 'devices.manage')");
            foreach ($empPerms as $perm) {
                $check = DatabaseConnection::fetchOne("SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ?", [$emp['id'], $perm['id']]);
                if (!$check) {
                    DatabaseConnection::query("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$emp['id'], $perm['id']]);
                }
            }
        }

        // Viewer permissions
        $viewer = DatabaseConnection::fetchOne("SELECT id FROM roles WHERE slug = 'viewer'");
        if ($viewer) {
            $viewerPerms = DatabaseConnection::fetchAll("SELECT id FROM permissions WHERE slug IN ('licenses.view_all', 'logs.view', 'data.export')");
            foreach ($viewerPerms as $perm) {
                $check = DatabaseConnection::fetchOne("SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ?", [$viewer['id'], $perm['id']]);
                if (!$check) {
                    DatabaseConnection::query("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$viewer['id'], $perm['id']]);
                }
            }
        }
    }

    private static function seedProducts(): void
    {
        $prod = ['name' => 'Chrome Extension', 'slug' => 'chrome-extension', 'product_type' => 'chrome_extension', 'description' => 'Chrome Extension Software License'];
        $check = DatabaseConnection::fetchOne("SELECT id FROM products WHERE slug = ?", [$prod['slug']]);
        if (!$check) {
            $uuid = SecurityHelper::generateUuid();
            $secretKey = hash('sha256', $prod['slug'] . '_secret_key_' . bin2hex(random_bytes(8)));
            DatabaseConnection::query(
                "INSERT INTO products (uuid, name, slug, product_type, secret_key, description) VALUES (?, ?, ?, ?, ?, ?)",
                [$uuid, $prod['name'], $prod['slug'], $prod['product_type'], $secretKey, $prod['description']]
            );
        }
    }

    private static function seedPlans(): void
    {
        $product = DatabaseConnection::fetchOne("SELECT id FROM products WHERE slug = 'chrome-extension' LIMIT 1");
        $productId = $product['id'] ?? 1;

        $checkPlan = DatabaseConnection::fetchOne("SELECT id FROM license_plans WHERE product_id = ?", [$productId]);
        if ($checkPlan) {
            return;
        }

        $plans = [
            // STANDARD PLANS
            ['category' => 'Standard', 'name' => 'Free Trial (7 Days)', 'slug' => 'free_trial', 'duration_type' => 'trial', 'duration_days' => 7, 'max_devices' => 1, 'price' => 0.00, 'description' => '7-day full feature trial period'],
            ['category' => 'Standard', 'name' => '1 Month', 'slug' => 'monthly', 'duration_type' => '1_month', 'duration_days' => 30, 'max_devices' => 1, 'price' => 9.99, 'description' => 'Standard 30 days monthly license'],
            ['category' => 'Standard', 'name' => '2 Months', 'slug' => '2_months', 'duration_type' => '2_months', 'duration_days' => 60, 'max_devices' => 1, 'price' => 18.99, 'description' => 'Bi-monthly 60 days license'],
            ['category' => 'Standard', 'name' => '3 Months', 'slug' => '3_months', 'duration_type' => '3_months', 'duration_days' => 90, 'max_devices' => 1, 'price' => 26.99, 'description' => 'Quarterly 90 days license'],
            ['category' => 'Standard', 'name' => '4 Months', 'slug' => '4_months', 'duration_type' => '4_months', 'duration_days' => 120, 'max_devices' => 1, 'price' => 34.99, 'description' => '4-month 120 days license'],
            ['category' => 'Standard', 'name' => '6 Months', 'slug' => '6_months', 'duration_type' => '6_months', 'duration_days' => 180, 'max_devices' => 1, 'price' => 49.99, 'description' => 'Semi-annual 180 days license'],
            ['category' => 'Standard', 'name' => '7 Months', 'slug' => '7_months', 'duration_type' => '7_months', 'duration_days' => 210, 'max_devices' => 1, 'price' => 57.99, 'description' => '7-month 210 days license'],
            ['category' => 'Standard', 'name' => '8 Months', 'slug' => '8_months', 'duration_type' => '8_months', 'duration_days' => 240, 'max_devices' => 1, 'price' => 64.99, 'description' => '8-month 240 days license'],
            ['category' => 'Standard', 'name' => '9 Months', 'slug' => '9_months', 'duration_type' => '9_months', 'duration_days' => 270, 'max_devices' => 1, 'price' => 71.99, 'description' => '9-month 270 days license'],
            ['category' => 'Standard', 'name' => '12 Months (Yearly)', 'slug' => '12_months', 'duration_type' => '12_months', 'duration_days' => 365, 'max_devices' => 1, 'price' => 89.99, 'description' => 'Annual 365 days license'],
            ['category' => 'Standard', 'name' => 'Lifetime', 'slug' => 'lifetime', 'duration_type' => 'lifetime', 'duration_days' => 0, 'max_devices' => 1, 'price' => 199.99, 'description' => 'Permanent non-expiring lifetime license'],

            // BUSINESS PLANS
            ['category' => 'Business', 'name' => 'Starter', 'slug' => 'starter', 'duration_type' => 'starter', 'duration_days' => 30, 'max_devices' => 2, 'price' => 29.99, 'description' => 'Starter plan for small businesses (30 days)'],
            ['category' => 'Business', 'name' => 'Professional', 'slug' => 'professional', 'duration_type' => 'professional', 'duration_days' => 90, 'max_devices' => 5, 'price' => 79.99, 'description' => 'Professional plan for growing teams (90 days)'],
            ['category' => 'Business', 'name' => 'Business', 'slug' => 'business', 'duration_type' => 'business', 'duration_days' => 180, 'max_devices' => 10, 'price' => 149.99, 'description' => 'Commercial business tier (180 days)'],
            ['category' => 'Business', 'name' => 'Premium', 'slug' => 'premium', 'duration_type' => 'premium', 'duration_days' => 365, 'max_devices' => 15, 'price' => 249.99, 'description' => 'Premium tier with priority support (365 days)'],
            ['category' => 'Business', 'name' => 'Enterprise', 'slug' => 'enterprise', 'duration_type' => 'enterprise', 'duration_days' => 365, 'max_devices' => 25, 'price' => 499.99, 'description' => 'Enterprise grade organizational license (365 days)'],
            ['category' => 'Business', 'name' => 'Enterprise Pro', 'slug' => 'enterprise_pro', 'duration_type' => 'enterprise_pro', 'duration_days' => 730, 'max_devices' => 50, 'price' => 899.99, 'description' => 'Enterprise Pro multi-year license (730 days)'],
            ['category' => 'Business', 'name' => 'Ultimate', 'slug' => 'ultimate', 'duration_type' => 'ultimate', 'duration_days' => 365, 'max_devices' => 100, 'price' => 999.99, 'description' => 'Ultimate unlimited business license (365 days)'],
            ['category' => 'Business', 'name' => 'Team', 'slug' => 'team', 'duration_type' => 'team', 'duration_days' => 365, 'max_devices' => 10, 'price' => 199.99, 'description' => 'Team workspace shared license (365 days)'],
            ['category' => 'Business', 'name' => 'Agency', 'slug' => 'agency', 'duration_type' => 'agency', 'duration_days' => 365, 'max_devices' => 30, 'price' => 599.99, 'description' => 'Agency multi-client license (365 days)'],

            // PARTNER PLANS
            ['category' => 'Partner', 'name' => 'Developer', 'slug' => 'developer', 'duration_type' => 'developer', 'duration_days' => 365, 'max_devices' => 10, 'price' => 149.99, 'description' => 'Developer integration & API testing license (365 days)'],
            ['category' => 'Partner', 'name' => 'Reseller', 'slug' => 'reseller', 'duration_type' => 'reseller', 'duration_days' => 365, 'max_devices' => 50, 'price' => 799.99, 'description' => 'Authorized reseller partner license (365 days)'],
            ['category' => 'Partner', 'name' => 'Wholesale', 'slug' => 'wholesale', 'duration_type' => 'wholesale', 'duration_days' => 365, 'max_devices' => 100, 'price' => 1299.99, 'description' => 'Wholesale bulk license distribution tier (365 days)'],
            ['category' => 'Partner', 'name' => 'Custom', 'slug' => 'custom', 'duration_type' => 'custom', 'duration_days' => 365, 'max_devices' => 1, 'price' => 0.00, 'description' => 'Custom administrator configured agreement tier']
        ];

        foreach ($plans as $pl) {
            DatabaseConnection::query(
                "INSERT INTO license_plans (product_id, category, name, slug, duration_type, duration_days, max_devices, price, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$productId, $pl['category'], $pl['name'], $pl['slug'], $pl['duration_type'], $pl['duration_days'], $pl['max_devices'], $pl['price'], $pl['description']]
            );
        }
    }

    private static function seedSettings(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Enterprise License Management Platform', 'group' => 'general', 'desc' => 'Platform System Title'],
            ['key' => 'license_prefix', 'value' => 'GB', 'group' => 'licensing', 'desc' => 'Default License Prefix'],
            ['key' => 'default_expiry_days', 'value' => '30', 'group' => 'licensing', 'desc' => 'Default License Expiry Period'],
            ['key' => 'rate_limit_per_minute', 'value' => '120', 'group' => 'security', 'desc' => 'API Rate Limit'],
            ['key' => 'storage_driver', 'value' => 'local', 'group' => 'storage', 'desc' => 'Default Storage Driver (local, supabase, s3, r2)'],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'system', 'desc' => 'System Maintenance Toggle']
        ];

        foreach ($settings as $s) {
            $check = DatabaseConnection::fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$s['key']]);
            if (!$check) {
                DatabaseConnection::query(
                    "INSERT INTO settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, ?, ?)",
                    [$s['key'], $s['value'], $s['group'], $s['desc']]
                );
            }
        }
    }

    private static function seedSuperAdmin(): void
    {
        $superRole = DatabaseConnection::fetchOne("SELECT id FROM roles WHERE slug = 'super_admin'");
        if (!$superRole) return;

        $adminEmail = 'admin@system.com';
        $adminPass = 'Admin@123456';
        $passwordHash = SecurityHelper::hashPassword($adminPass);
        $uuid = SecurityHelper::generateUuid();

        $checkAdmin = DatabaseConnection::fetchOne("SELECT id FROM users WHERE email = ?", [$adminEmail]);
        if (!$checkAdmin) {
            DatabaseConnection::query(
                "INSERT INTO users (uuid, role_id, full_name, email, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')",
                [$uuid, $superRole['id'], 'System Super Administrator', $adminEmail, $passwordHash]
            );
        }
    }
}
