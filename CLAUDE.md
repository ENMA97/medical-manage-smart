# CLAUDE.md

## Project Overview

Medical ERP system — Laravel 11 backend (PHP 8.2+) with React 18 + Vite frontend, using PostgreSQL 16 and Redis 7. Dockerized infrastructure.

## Project Structure

- `medical-erp-project (1).zip` — Source archive (needs extraction)
- `backend/` — Laravel API (Models, Controllers, Services, Policies)
- `frontend/` — React SPA (components, pages, hooks, contexts, services)
- `docker/` — Docker Compose config, Nginx, biometric agent
- `docs/` — Documentation

## Commands

```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec backend php artisan migrate --seed

# Backend tests
docker-compose exec backend php artisan test

# Backend test coverage
docker-compose exec backend php artisan test --coverage

# Frontend tests
docker-compose exec frontend npm run test
```

## Endpoints

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- Swagger Docs: http://localhost:8000/api/documentation

## Key Tech Stack

- **Backend**: Laravel 11, Sanctum (JWT auth), Eloquent ORM, PostgreSQL
- **Frontend**: React 18, React Query, React Router, Tailwind CSS
- **Infra**: Docker Compose, Redis, Nginx, Laravel Queue Worker, Laravel Scheduler
- **AI**: Google Gemini API integration

## Modules

HR management, smart inventory, rostering, payroll, finance/BI, AI features, audit logging, RBAC security, offline PWA support, ZKTeco biometric integration.
