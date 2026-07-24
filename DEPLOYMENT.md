# Enterprise License Management System - Free Production Cloud Deployment Guide

This guide provides step-by-step instructions for deploying the **Enterprise License Management System** to **free cloud hosting providers** (Railway, Render, Koyeb) with **PostgreSQL** or **MySQL** database support.

---

## 📋 Required Environment Variables

Set the following environment variables in your cloud provider environment dashboard:

```env
# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com # or https://your-app.up.railway.app
API_BASE_URL=https://your-app-name.onrender.com/api/v1
APP_TIMEZONE=UTC

# Database Configuration (PostgreSQL or MySQL)
DB_DRIVER=pgsql
DATABASE_URL=postgres://user:password@host:5432/dbname
# Or set individually:
DB_HOST=your-db-host.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-secure-password
DB_SSL_MODE=require

# Authentication & Security
JWT_SECRET=your_super_secret_jwt_32char_key_change_in_prod
DEFAULT_LICENSE_PREFIX=GB
DEFAULT_EXPIRY_DAYS=30
RATE_LIMIT_PER_MINUTE=120
CORS_ALLOWED_ORIGINS=*
STORAGE_DRIVER=local
```

---

## 🚀 Option 1: Deploy on Railway (Free Tier)

1. **Push Code to GitHub**:
   Push this repository to your GitHub account.

2. **Create New Project on Railway**:
   - Go to [Railway Dashboard](https://railway.app) and click **"New Project"**.
   - Select **"Provision PostgreSQL"** to add a free PostgreSQL database.
   - Click **"Deploy from GitHub repo"** and choose your repository.

3. **Configure Environment Variables**:
   - In Railway, open your Web Service ➜ **Variables**.
   - Add `DATABASE_URL` = `${{Postgres.DATABASE_URL}}` (Railway links this automatically).
   - Add `APP_ENV` = `production`, `APP_DEBUG` = `false`, `JWT_SECRET` = `<your-secret>`.

4. **Build & Deploy**:
   Railway automatically detects `nixpacks.toml` or `Procfile` and deploys your application.
   Tables and initial super admin data (`admin@system.com` / `Admin@123456`) are automatically created on first request via `setup.php` / `Schema::up()`.

---

## 🚀 Option 2: Deploy on Render (Free Tier)

1. **Connect GitHub to Render**:
   - Go to [Render Dashboard](https://render.com) ➜ **"Blueprints"** ➜ **"New Blueprint Instance"**.
   - Connect your GitHub repository.

2. **Deploy via `render.yaml`**:
   - Render detects `render.yaml` automatically.
   - It provisions a **Free PostgreSQL Database** and a **Free PHP Web Service**.
   - Click **"Apply"** to trigger automatic build and deployment.

---

## 🚀 Option 3: Deploy on Koyeb (Free Tier)

1. **Create App on Koyeb**:
   - Go to [Koyeb Dashboard](https://koyeb.com) ➜ **"Create App"**.
   - Choose **GitHub** deployment.

2. **Attach Free PostgreSQL (Supabase / Neon / ElephantSQL)**:
   - Create a free PostgreSQL instance on [Supabase](https://supabase.com) or [Neon.tech](https://neon.tech).
   - Copy the PostgreSQL connection string (`postgres://...`).
   - Paste it as `DATABASE_URL` in Koyeb environment variables.

---

## 🔑 Default Credentials & Post-Deployment Setup

Upon first startup, the database schema migration runs automatically and seeds initial data:

- **Admin Login URL**: `https://<your-app-url>/login`
- **Default Email**: `admin@system.com`
- **Default Password**: `Admin@123456`

> [!IMPORTANT]
> Change the default super admin password immediately after logging into your production instance under **Employees / User Settings**.

---

## 📦 Chrome Extension Production Configuration

To connect the Chrome Extension to your deployed cloud backend:

1. Open `chrome_extension/popup.js` and `chrome_extension/background.js`.
2. Update `api_base_url` in extension storage or set default URL:
   ```javascript
   chrome.storage.local.set({ api_base_url: 'https://your-app-name.onrender.com' });
   ```
3. Load unpacked extension in Chrome via `chrome://extensions`.
