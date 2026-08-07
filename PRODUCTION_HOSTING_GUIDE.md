# Crewly360 — Production Hosting & Deployment Architecture Guide

This guide details the recommended cloud infrastructure architectures for hosting **Crewly360** in production as a high-availability, multi-tenant Enterprise SaaS application.

---

## 🏗️ 1. Recommended Production Stack Overview

```
                      ┌────────────────────────────────────────┐
                      │    Cloudflare CDN & DNS / WAF          │
                      └───────────────────┬────────────────────┘
                                          │
                      ┌───────────────────┴────────────────────┐
                      │    SSL / HTTPS Load Balancer          │
                      └───────────────────┬────────────────────┘
                                          │
       ┌──────────────────────────────────┼──────────────────────────────────┐
       │                                  │                                  │
┌──────┴─────────────────┐    ┌───────────┴────────────┐         ┌───────────┴────────────┐
│ Web App Server Cluster │    │ Queue Workers (Horizon)│         │ WebSocket Server       │
│ (Laravel 12 + Nginx)   │    │ (Background Processing)│         │ (Laravel Reverb)       │
└──────────────┬─────────┘    └───────────┬────────────┘         └───────────┬────────────┘
               │                          │                                  │
               └──────────────────────────┼──────────────────────────────────┘
                                          │
       ┌──────────────────────────────────┼──────────────────────────────────┐
       │                                  │                                  │
┌──────┴─────────────────┐    ┌───────────┴────────────┐         ┌───────────┴────────────┐
│ Managed Database       │    │ Managed Redis          │         │ Private Object Storage │
│ (PostgreSQL / MySQL)   │    │ (Sessions, Cache, Jobs)│         │ (AWS S3 / R2 Bucket)   │
└────────────────────────┘    └────────────────────────┘         └────────────────────────┘
```

---

## 🚀 2. Top 3 Hosting Option Comparison

### Option A: Laravel Forge + AWS / DigitalOcean (Recommended for Speed & Control)
- **Deployment Platform**: [Laravel Forge](https://forge.laravel.com)
- **Cloud Provider**: AWS EC2, DigitalOcean, or Hetzner
- **Database**: Managed PostgreSQL (AWS RDS or DigitalOcean DBaaS)
- **Cache & Queues**: Managed Redis + Laravel Horizon
- **File Storage**: AWS S3 / Cloudflare R2
- **Pros**: 
  - Zero-downtime deployment script built-in.
  - Native integration with Laravel 12, Octane, Horizon, and SSL certificates.
  - Complete control over servers without container complexity.
- **Estimated Cost**: ~$50 – $120/month.

---

### Option B: Serverless Containers (GCP Cloud Run / AWS ECS Fargate)
- **Deployment Platform**: GCP Cloud Run or AWS ECS (Fargate)
- **Database**: GCP Cloud SQL or AWS Aurora Serverless v2
- **Cache & Queues**: MemoryStore / ElastiCache Redis
- **File Storage**: Google Cloud Storage / AWS S3
- **Pros**:
  - Auto-scales automatically from 0 to 1,000s of container instances.
  - Pay-per-second execution pricing (ideal for cost efficiency at early stage).
  - High availability multi-region failover.
- **Estimated Cost**: Pay-per-usage (~$20 – $150/month).

---

### Option C: PaaS Developer Platform (Render / Fly.io / Railway)
- **Deployment Platform**: [Fly.io](https://fly.io) or [Render.com](https://render.com)
- **Database**: Render/Fly Managed PostgreSQL
- **Cache**: Render/Fly Redis
- **Pros**:
  - Easiest setup for small teams (deploy via `git push`).
  - Automatic SSL and health checks.
- **Estimated Cost**: ~$25 – $70/month.

---

## 🛠️ 3. Step-by-Step Deployment Checklist for Crewly360

### Step 1: Environment Variables Setup
Configure production `.env` variables:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=your-db-instance.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=crewly360_prod
DB_USERNAME=crewly360_db_user
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_AWS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_AWS_SECRET
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=crewly360-payslips-prod

MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_YOUR_RESEND_KEY
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Crewly360"
```

### Step 2: Production Build & Deployment Script
Run these deployment commands on server release:
```bash
# 1. Pull latest release
git pull origin main

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Compile frontend production assets
npm ci
npm run build

# 4. Execute database migrations
php artisan migrate --force

# 5. Optimize Laravel caches
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers
php artisan horizon:terminate
```

---

## 🔒 4. Production Security Hardening

1. **Enforce SSL/TLS**: Enable HSTS and force HTTPS redirect (`APP_URL=https://...`).
2. **Database Isolation**: Keep database instance inside private VPC / subnet, non-accessible from public internet.
3. **S3 Private Bucket Policy**: Ensure payslip bucket blocks public access (`BlockPublicAccess: true`) and downloads are authenticated via pre-signed URLs.
4. **Exception Monitoring**: Install Sentry (`sentry/sentry-laravel`) or Flare to capture production errors in real-time.
