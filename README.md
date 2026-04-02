# Medical Manage Smart - ENMA HRMS

نظام إنماء لإدارة الموارد البشرية للمنشآت الطبية

## Tech Stack

- **Backend:** Laravel 12 + PHP 8.2+ + Laravel Sanctum
- **Frontend:** React 19 + Vite 7 + Tailwind CSS 4
- **Database:** PostgreSQL 16 (production) / SQLite (development)
- **Cache:** Redis 7
- **Deployment:** Docker + Supervisor (nginx + php-fpm)

## Features

- Employee management with full CRUD
- Contract management with alerts and renewals
- Leave management with approval workflows
- Payroll processing and salary management
- Loan management with installment tracking
- Disciplinary actions and investigations
- Custody/asset tracking
- Letter template generation
- Dashboard with analytics
- AI-powered predictions and analysis
- PWA support with offline capabilities
- Full Arabic RTL interface
- Role-based access control (Super Admin, HR Manager, Department Manager, Employee)

## Quick Start (Development)

```bash
# Backend
cd medical-erp/backend
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve

# Frontend (in another terminal)
cd medical-erp/frontend
npm install
npm run dev
```

## Test Accounts

| Role | Employee Number | Phone |
|------|----------------|-------|
| Super Admin | 1001 | 0512345001 |
| HR Manager | 1002 | 0512345002 |
| Doctor | 2001 | 0512345003 |
| Nurse | 3001 | 0512345004 |

## Docker Deployment

```bash
cd medical-erp
docker compose -f docker/docker-compose.yml up -d
```

## Project Structure

```
medical-erp/
├── backend/          # Laravel 12 API
│   ├── app/          # Models, Controllers, Services
│   ├── database/     # Migrations & Seeders
│   ├── routes/       # API routes
│   └── config/       # Configuration
├── frontend/         # React 19 SPA
│   ├── src/
│   │   ├── pages/       # Page components
│   │   ├── components/  # Reusable UI components
│   │   ├── services/    # API service modules
│   │   ├── contexts/    # React contexts
│   │   └── layouts/     # Layout wrappers
│   └── public/
├── docker/           # Docker Compose & configs
└── Dockerfile        # Multi-stage production build
```
