# Enterprise License Management System (ELMS)

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Database](https://img.shields.io/badge/Database-PostgreSQL%20%7C%20MySQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Security](https://img.shields.io/badge/Security-Argon2id%20%7C%20JWT-red?style=for-the-badge&logo=security)](https://jwt.io)

Commercial-grade, production-ready **License Management & Device Binding Platform** built with **PHP 8.2+**, **PostgreSQL / MySQL**, **Bootstrap 5**, and **Vanilla JavaScript**.

Designed specifically for **Chrome Extensions**, SaaS Web Applications, Desktop Software, and WordPress Plugins.

---

## 🌟 Key Capabilities

- 🔐 **Dual Database Support**: 100% native compatibility with both **PostgreSQL** and **MySQL** databases.
- 🔑 **Cryptographically Secure Key Engine**: High-performance key generator (`GB-XXXX-XXXX-XXXX-XXXX`) with collision detection and batch transactions (100 to 10,000 keys/sec).
- 💻 **Hardware & Device Binding**: Fingerprint registration, IP geolocation tracking, allowed device limits, and session heartbeat renewal.
- 🛡️ **Enterprise Security Hardening**: Argon2id password hashing, zero-dependency JWT engine, token rotation, account lockout, CSRF protection, CSP, and HSTS headers.
- 📊 **Executive Analytics & Real-Time Metrics**: Filterable dashboard counters, activations breakdown, recent activity logs, and export options.
- 👥 **Role-Based Access Control (RBAC)**: Fine-grained permissions for Super Admin, Admin, Reseller, Employee, and Viewer roles.
- 🧩 **Chrome Extension Integration Reference**: Complete Manifest V3 extension reference bundle in `chrome_extension/`.
- ☁️ **Cloud Storage Drivers**: Abstracted storage drivers for Local storage, Supabase, AWS S3, and Cloudflare R2.
- ⚡ **1-Click Free Cloud Deployment**: Includes configuration files for **Railway**, **Render**, and **Koyeb**.

---

## 📁 Repository Structure

```
├── app/
│   ├── Config/          # Dynamic Env, Database (PgSQL/MySQL), JWT, Storage config
│   ├── Controllers/     # REST API Controllers (/api/v1/) & View Controllers
│   ├── Core/            # Front controller Request parser & Router dispatcher
│   ├── Database/        # PDO Singleton, Schema (22 normalized tables), Seeder
│   ├── Helpers/         # KeyGenerator, ResponseHelper, SecurityHelper, Validator
│   ├── Middleware/      # Auth, RBAC, RateLimit, CSRF, InputSanitizer
│   ├── Models/          # LicenseModel, UserModel, DeviceModel, CustomerModel, PlanModel
│   └── Services/        # LicenseGeneratorService, DeviceManagerService, Storage drivers
├── chrome_extension/    # Complete Manifest V3 reference Chrome Extension
├── public/              # Web root (index.php, .htaccess, openapi.json)
├── routes/              # API (/api/v1/) and Web routes
├── views/               # Commercial Glassmorphism SaaS UI views
├── .env.example         # Environment configuration template
├── DEPLOYMENT.md        # Production cloud deployment guide
├── Procfile             # Railway / Render start command
├── render.yaml          # Render 1-click blueprint manifest
├── nixpacks.toml        # Nixpacks configuration for Railway & Koyeb
├── setup.php            # Automated CLI setup & database migration script
└── test.php             # Automated testing suite
```

---

## 🛠️ Quick Installation Guide

### Prerequisites
- **PHP**: 8.2 or higher (with `pdo`, `pdo_pgsql` or `pdo_mysql`, `mbstring`, `openssl` extensions enabled).
- **Database**: PostgreSQL 13+ or MySQL 8.0+ / MariaDB 10.4+.
- **Composer**: Dependency manager (optional for base install).

### Step 1: Clone Repository
```bash
git clone https://github.com/your-org/enterprise-license-management-system.git
cd enterprise-license-management-system
```

### Step 2: Environment Configuration
Copy `.env.example` to `.env` and fill in your database and environment settings:
```bash
cp .env.example .env
```

Edit `.env`:
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
API_BASE_URL=http://localhost:8000/api/v1

# PostgreSQL (Default)
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=enterprise_license_db
DB_USERNAME=postgres
DB_PASSWORD=your_password

# JWT & Security
JWT_SECRET=your_super_secret_jwt_32char_key_change_in_prod
```

### Step 3: Run Database Migration & Seeder
Execute the automated CLI setup script to migrate database tables and seed initial system data:
```bash
php setup.php
```

### Step 4: Launch Development Server
```bash
php -S localhost:8000 -t public
```

Access the Admin Panel at `http://localhost:8000/login`.

---

## 🔑 Default Administrator Credentials

Upon running `setup.php`, the initial Super Admin account is provisioned:

| Attribute | Default Value |
| :--- | :--- |
| **Login URL** | `http://localhost:8000/login` |
| **Email** | `admin@system.com` |
| **Password** | `Admin@123456` |

> [!IMPORTANT]
> Change the default password immediately after your first login!

---

## 🧪 Running Automated Unit & Integration Tests

Run the built-in regression test suite:
```bash
php test.php
```

Output:
```
========================================================
 ENTERPRISE LICENSE MANAGEMENT PLATFORM - SYSTEM SUITE
========================================================

[TEST] Database Connection Singleton Test                 [PASS]
[TEST] Schema Tables Verification (22 Tables)             [PASS]
[TEST] Argon2id Password Cryptography Test                [PASS]
[TEST] JWT Engine Encoding & Decoding Test                [PASS]
[TEST] Cryptographic Key Generator Test                   [PASS]
[TEST] LicenseGeneratorService Single Key Creation        [PASS]
[TEST] LicenseGeneratorService Bulk 100 Keys Transaction  [PASS]
[TEST] Client Device Activation & Limit Enforcement Test  [PASS]

========================================================
 TEST SUMMARY: Total: 8 | Passed: 8 | Failed: 0
========================================================
```

---

## 🌐 API Overview

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/api/v1/auth/login` | `POST` | Authenticate user & receive JWT tokens |
| `/api/v1/licenses/generate` | `POST` | Generate single license key |
| `/api/v1/licenses/bulk-generate` | `POST` | Bulk generate up to 10,000 keys |
| `/api/activate-license` | `POST` | Extension / client device activation |
| `/api/upload-authorize` | `POST` | Authorize file upload for active license |
| `/api/v1/licenses/export` | `GET` | Export licenses dataset to CSV |
| `/api/v1/licenses/import` | `POST` | Import bulk licenses from CSV |

Full Swagger OpenAPI documentation is available at `public/openapi.json`.

---

## ☁️ Production Cloud Deployment

For detailed 1-click cloud deployment instructions on **Railway**, **Render**, and **Koyeb**, refer to **[DEPLOYMENT.md](file:///c:/Users/Akaria%20Innovations/OneDrive/Desktop/aDMIN%20PANEL/DEPLOYMENT.md)**.

---

## 📄 License

This project is open-source software licensed under the [MIT License](LICENSE).
