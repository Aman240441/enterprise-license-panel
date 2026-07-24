<?php

namespace App\Database;

use PDOException;

class Schema
{
    /**
     * Run all schema creation migrations for 22 normalized enterprise tables
     * Compatible with PostgreSQL & MySQL
     */
    public static function up(): void
    {
        $db = DatabaseConnection::getInstance();
        $isPgsql = DatabaseConnection::isPgsql();

        $queries = [
            // 1. roles
            "CREATE TABLE IF NOT EXISTS roles (
              id SERIAL PRIMARY KEY,
              name VARCHAR(50) NOT NULL UNIQUE,
              slug VARCHAR(50) NOT NULL UNIQUE,
              description VARCHAR(255) NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 2. permissions
            "CREATE TABLE IF NOT EXISTS permissions (
              id SERIAL PRIMARY KEY,
              name VARCHAR(100) NOT NULL,
              slug VARCHAR(100) NOT NULL UNIQUE,
              category VARCHAR(50) NOT NULL,
              description VARCHAR(255) NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 3. role_permissions
            "CREATE TABLE IF NOT EXISTS role_permissions (
              role_id INT NOT NULL,
              permission_id INT NOT NULL,
              PRIMARY KEY (role_id, permission_id),
              CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
              CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
            );",

            // 4. products
            "CREATE TABLE IF NOT EXISTS products (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              name VARCHAR(100) NOT NULL,
              slug VARCHAR(100) NOT NULL UNIQUE,
              product_type VARCHAR(50) NOT NULL DEFAULT 'chrome_extension',
              secret_key VARCHAR(64) NOT NULL UNIQUE,
              description TEXT NULL,
              status VARCHAR(50) DEFAULT 'active',
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 5. users
            "CREATE TABLE IF NOT EXISTS users (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              role_id INT NOT NULL,
              full_name VARCHAR(100) NOT NULL,
              email VARCHAR(150) NOT NULL UNIQUE,
              password_hash VARCHAR(255) NOT NULL,
              status VARCHAR(50) DEFAULT 'active',
              daily_gen_limit INT DEFAULT 0,
              monthly_gen_limit INT DEFAULT 0,
              device_limit_override INT DEFAULT NULL,
              allowed_plans_json TEXT NULL,
              expiry_templates_json TEXT NULL,
              failed_login_attempts SMALLINT DEFAULT 0,
              lockout_until TIMESTAMP NULL,
              two_factor_enabled SMALLINT DEFAULT 0,
              two_factor_secret VARCHAR(100) NULL,
              avatar_url VARCHAR(255) NULL,
              last_login_at TIMESTAMP NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              deleted_at TIMESTAMP NULL,
              CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT
            );",

            // 6. user_permissions
            "CREATE TABLE IF NOT EXISTS user_permissions (
              user_id INT NOT NULL,
              permission_id INT NOT NULL,
              is_granted SMALLINT NOT NULL DEFAULT 1,
              PRIMARY KEY (user_id, permission_id),
              CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
              CONSTRAINT fk_up_perm FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
            );",

            // 7. resellers
            "CREATE TABLE IF NOT EXISTS resellers (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              user_id INT NOT NULL UNIQUE,
              company_name VARCHAR(150) NOT NULL,
              commission_rate DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
              total_sales DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
              total_earnings DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
              allowed_products_json TEXT NULL,
              status VARCHAR(50) DEFAULT 'active',
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_reseller_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            );",

            // 8. customers
            "CREATE TABLE IF NOT EXISTS customers (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              name VARCHAR(100) NOT NULL,
              email VARCHAR(150) NOT NULL UNIQUE,
              phone VARCHAR(30) NULL,
              company VARCHAR(100) NULL,
              country VARCHAR(100) NULL,
              notes TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 9. license_plans
            "CREATE TABLE IF NOT EXISTS license_plans (
              id SERIAL PRIMARY KEY,
              product_id INT NULL,
              category VARCHAR(50) NOT NULL DEFAULT 'Standard',
              name VARCHAR(100) NOT NULL,
              slug VARCHAR(100) NOT NULL,
              duration_type VARCHAR(50) NOT NULL DEFAULT 'monthly',
              duration_days INT NOT NULL DEFAULT 30,
              max_devices INT NOT NULL DEFAULT 1,
              upload_permission SMALLINT NOT NULL DEFAULT 1,
              price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
              description VARCHAR(255) NULL,
              is_active SMALLINT NOT NULL DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_lp_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            );",

            // 10. licenses
            "CREATE TABLE IF NOT EXISTS licenses (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              product_id INT NOT NULL,
              license_key VARCHAR(64) NOT NULL UNIQUE,
              customer_id INT NULL,
              plan_id INT NOT NULL,
              created_by INT NOT NULL,
              reseller_id INT NULL,
              license_type VARCHAR(50) NOT NULL DEFAULT 'monthly',
              status VARCHAR(50) DEFAULT 'active',
              expiry_date TIMESTAMP NULL,
              allowed_devices INT NOT NULL DEFAULT 1,
              current_devices INT NOT NULL DEFAULT 0,
              upload_permission SMALLINT NOT NULL DEFAULT 1,
              notes TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_lic_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
              CONSTRAINT fk_lic_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
              CONSTRAINT fk_lic_plan FOREIGN KEY (plan_id) REFERENCES license_plans (id) ON DELETE RESTRICT,
              CONSTRAINT fk_lic_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT,
              CONSTRAINT fk_lic_reseller FOREIGN KEY (reseller_id) REFERENCES resellers (id) ON DELETE SET NULL
            );",

            // 11. devices
            "CREATE TABLE IF NOT EXISTS devices (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              license_id INT NOT NULL,
              device_fingerprint VARCHAR(128) NOT NULL,
              browser VARCHAR(100) NULL,
              os VARCHAR(100) NULL,
              platform VARCHAR(100) NULL,
              ip_address VARCHAR(45) NULL,
              country VARCHAR(100) NULL,
              city VARCHAR(100) NULL,
              session_token_hash VARCHAR(64) NULL,
              activation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              is_active SMALLINT NOT NULL DEFAULT 1,
              CONSTRAINT fk_dev_license FOREIGN KEY (license_id) REFERENCES licenses (id) ON DELETE CASCADE,
              CONSTRAINT uk_license_fingerprint UNIQUE (license_id, device_fingerprint)
            );",

            // 12. sessions
            "CREATE TABLE IF NOT EXISTS sessions (
              id SERIAL PRIMARY KEY,
              user_id INT NULL,
              device_id INT NULL,
              session_type VARCHAR(50) NOT NULL,
              refresh_token_hash VARCHAR(64) NOT NULL UNIQUE,
              ip_address VARCHAR(45) NOT NULL,
              user_agent VARCHAR(255) NULL,
              expires_at TIMESTAMP NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              revoked_at TIMESTAMP NULL,
              CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
              CONSTRAINT fk_sess_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
            );",

            // 13. webhooks
            "CREATE TABLE IF NOT EXISTS webhooks (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              product_id INT NULL,
              target_url VARCHAR(255) NOT NULL,
              secret VARCHAR(64) NOT NULL,
              events_json TEXT NOT NULL,
              is_active SMALLINT DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_wh_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            );",

            // 14. failed_jobs
            "CREATE TABLE IF NOT EXISTS failed_jobs (
              id BIGSERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              connection VARCHAR(50) NOT NULL,
              queue VARCHAR(50) NOT NULL,
              payload TEXT NOT NULL,
              exception TEXT NOT NULL,
              failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 15. system_logs
            "CREATE TABLE IF NOT EXISTS system_logs (
              id BIGSERIAL PRIMARY KEY,
              level VARCHAR(50) DEFAULT 'info',
              channel VARCHAR(50) NOT NULL DEFAULT 'system',
              message TEXT NOT NULL,
              context_json TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 16. backups
            "CREATE TABLE IF NOT EXISTS backups (
              id SERIAL PRIMARY KEY,
              file_name VARCHAR(255) NOT NULL,
              file_path VARCHAR(255) NOT NULL,
              file_size BIGINT NOT NULL,
              driver VARCHAR(50) NOT NULL DEFAULT 'local',
              status VARCHAR(50) DEFAULT 'completed',
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 17. activity_logs
            "CREATE TABLE IF NOT EXISTS activity_logs (
              id BIGSERIAL PRIMARY KEY,
              user_id INT NULL,
              user_role VARCHAR(50) NULL,
              action VARCHAR(100) NOT NULL,
              endpoint VARCHAR(255) NULL,
              entity_type VARCHAR(50) NULL,
              entity_id INT NULL,
              description TEXT NOT NULL,
              ip_address VARCHAR(45) NULL,
              browser VARCHAR(100) NULL,
              os VARCHAR(100) NULL,
              user_agent VARCHAR(255) NULL,
              response_code SMALLINT NULL,
              payload_json TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
            );",

            // 18. login_logs
            "CREATE TABLE IF NOT EXISTS login_logs (
              id BIGSERIAL PRIMARY KEY,
              user_id INT NULL,
              email_attempted VARCHAR(150) NOT NULL,
              status VARCHAR(50) NOT NULL,
              failure_reason VARCHAR(255) NULL,
              ip_address VARCHAR(45) NOT NULL,
              user_agent VARCHAR(255) NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 19. api_keys
            "CREATE TABLE IF NOT EXISTS api_keys (
              id SERIAL PRIMARY KEY,
              user_id INT NOT NULL,
              product_id INT NULL,
              name VARCHAR(100) NOT NULL,
              api_key_hash VARCHAR(64) NOT NULL UNIQUE,
              prefix VARCHAR(10) NOT NULL,
              permissions_json TEXT NULL,
              rate_limit INT DEFAULT 100,
              last_used_at TIMESTAMP NULL,
              expires_at TIMESTAMP NULL,
              is_active SMALLINT NOT NULL DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_apikey_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
              CONSTRAINT fk_apikey_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            );",

            // 20. settings
            "CREATE TABLE IF NOT EXISTS settings (
              id SERIAL PRIMARY KEY,
              setting_key VARCHAR(100) NOT NULL UNIQUE,
              setting_value TEXT NULL,
              setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
              description VARCHAR(255) NULL,
              is_encrypted SMALLINT NOT NULL DEFAULT 0,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 21. uploads
            "CREATE TABLE IF NOT EXISTS uploads (
              id SERIAL PRIMARY KEY,
              uuid CHAR(36) NOT NULL UNIQUE,
              license_id INT NULL,
              customer_id INT NULL,
              file_name VARCHAR(255) NOT NULL,
              file_path TEXT NOT NULL,
              storage_driver VARCHAR(50) DEFAULT 'local',
              file_size BIGINT NOT NULL,
              mime_type VARCHAR(100) NOT NULL,
              uploaded_by INT NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_upload_lic FOREIGN KEY (license_id) REFERENCES licenses (id) ON DELETE SET NULL,
              CONSTRAINT fk_upload_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
              CONSTRAINT fk_upload_user FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE RESTRICT
            );",

            // 22. notifications
            "CREATE TABLE IF NOT EXISTS notifications (
              id SERIAL PRIMARY KEY,
              user_id INT NOT NULL,
              type VARCHAR(50) NOT NULL,
              channel VARCHAR(50) DEFAULT 'in_app',
              title VARCHAR(150) NOT NULL,
              message TEXT NOT NULL,
              is_read SMALLINT NOT NULL DEFAULT 0,
              metadata_json TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            );"
        ];

        // Convert MySQL AUTO_INCREMENT & types if running on MySQL driver
        if (!$isPgsql) {
            foreach ($queries as &$sql) {
                $sql = str_replace('SERIAL PRIMARY KEY', 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', $sql);
                $sql = str_replace('BIGSERIAL PRIMARY KEY', 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY', $sql);
                $sql = str_replace(');', ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;', $sql);
            }
            unset($sql);
        }

        foreach ($queries as $sql) {
            $db->exec($sql);
        }
    }
}
