# CLAUDE.md - AI Assistant Guidelines for Medical ERP Smart

## Project Overview

**Medical ERP Smart** (نظام تخطيط الموارد الطبية الذكي) is a cloud-based Enterprise Resource Planning system designed for healthcare facilities management. The system features AI-powered decision support, complete role-based access control, integrated biometric device support, and comprehensive audit trails for compliance.

### Key Features
- Human Resources management with multiple contract types
- Smart Inventory with FEFO (First Expiry First Out) policy
- Roster scheduling with biometric integration (ZKTeco)
- Finance module with insurance claims and clawback system
- WPS-compliant payroll processing
- Multi-language support (Arabic/English with RTL)
- Google Gemini AI integration for decision support

## Technology Stack

| Layer | Technology | Version |
|-------|------------|---------|
| Backend API | Laravel | 11.x |
| Backend Language | PHP | 8.2+ |
| Frontend Framework | React | 18.x |
| Build Tool | Vite | - |
| Styling | Tailwind CSS | - |
| State Management | React Context API | - |
| Data Fetching | TanStack React Query | - |
| Database | PostgreSQL | 16 |
| Cache/Queue | Redis | 7 |
| Authentication | JWT / Laravel Sanctum | - |
| Container Platform | Docker Compose | 3.8 |
| Desktop App | NativePHP | - |
| AI Integration | Google Gemini API | - |

## Project Structure

```
medical-erp/
├── backend/                    # Laravel REST API
│   ├── app/
│   │   ├── Models/            # Eloquent ORM Models
│   │   ├── Http/Controllers/  # API Controllers
│   │   ├── Services/          # Business Logic Layer
│   │   └── Policies/          # Authorization Policies
│   ├── database/
│   │   └── migrations/        # Database Schema (7 migration files)
│   └── routes/
│       └── api.php            # API Route Definitions
│
├── frontend/                   # React Single Page Application
│   ├── src/
│   │   ├── components/        # Reusable UI Components
│   │   ├── pages/             # Page Components (lazy-loaded)
│   │   ├── hooks/             # Custom React Hooks
│   │   ├── contexts/          # React Context Providers
│   │   └── services/          # API Service Clients
│   └── public/                # Static Assets
│
├── docker/                     # Docker Configuration
│   ├── docker-compose.yml     # Multi-container Orchestration
│   ├── nginx/                 # Reverse Proxy Config
│   └── biometric-agent/       # ZKTeco Device Integration
│
└── docs/                       # Documentation
    └── Implementation_Plan_AR.docx
```

## Quick Start Commands

```bash
# Clone and setup
git clone https://github.com/ENMA97/medical-manage-smart.git
cd medical-manage-smart
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Start with Docker
cd docker
docker-compose up -d
docker-compose exec backend php artisan migrate --seed

# Access points
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000/api
# API Docs: http://localhost:8000/api/documentation
```

## Architecture Patterns

### Backend (Laravel)

1. **Service-Oriented Architecture**: Business logic lives in `app/Services/`, controllers are thin
2. **Policy-Based Authorization**: Use Laravel Policies in `app/Policies/` for access control
3. **Form Request Validation**: Validate incoming data with Form Request classes
4. **RESTful API Design**: Follow REST conventions for endpoint naming
5. **Queue System**: Async processing via Redis queues

### Frontend (React)

1. **Code Splitting**: All pages are lazy-loaded with `React.lazy()`
2. **React Query for Data Fetching**: 5-minute stale time, 1 retry on failure
3. **Context API for State**: Auth and Locale contexts wrap the application
4. **Protected Routes**: All authenticated routes use `ProtectedRoute` wrapper
5. **Toast Notifications**: Use `react-hot-toast` for user feedback

## Database Conventions

### Naming Standards
- **Tables**: `snake_case` plural (e.g., `employees`, `inventory_items`)
- **Columns**: `snake_case` (e.g., `created_at`, `employee_id`)
- **Foreign Keys**: `{singular_table}_id` (e.g., `department_id`, `user_id`)
- **Pivot Tables**: Alphabetical order (e.g., `role_permissions`, `user_roles`)

### Required Patterns
- **UUID Primary Keys**: All tables use `$table->uuid('id')->primary()`
- **Timestamps**: Always include `$table->timestamps()`
- **Soft Deletes**: Include `$table->softDeletes()` on most tables
- **Optimistic Locking**: Use `version` column for concurrent operations (inventory, stocks)

### Immutable Audit Tables
These tables have NO `updated_at` column - records cannot be modified:
- `inventory_movements` - Stock movement audit trail
- `audit_logs` - System audit trail
- `fact_service_profitability` - Financial analytics

### Key Indexes to Maintain
- Date-based queries (e.g., `created_at`, `service_date`)
- Status fields (e.g., `status`, `is_active`)
- Foreign key relationships
- Composite indexes for common query patterns

## Module Reference

### 1. HR Module
**Tables**: `employees`, `contracts`, `custodies`, `clearance_requests`

**Key Business Rules**:
- Employees cannot be terminated without custody clearance
- Multi-step clearance workflow: Custody → Finance → HR → IT
- Contract types: `full_time`, `part_time`, `tamheer`, `percentage`, `locum`

### 2. Inventory Module
**Tables**: `warehouses`, `inventory_items`, `warehouse_stocks`, `inventory_movements`, `item_quotas`, `quota_consumptions`

**Key Business Rules**:
- FEFO policy: Always dispense items with earliest expiry first
- Optimistic locking prevents concurrent stock modifications
- Quota system limits department consumption (daily/weekly/monthly)
- Blue Code tracking for emergency crash cart usage
- Warehouse types: `main`, `sub`, `dressing_male`, `dressing_female`, `emergency`, `pharmacy`, `crash_cart`

### 3. Roster Module
**Tables**: `shift_patterns`, `rosters`, `roster_assignments`, `shift_swap_requests`, `attendance_records`, `biometric_devices`, `roster_validation_rules`

**Key Business Rules**:
- Sterilization manager coverage must be verified (gap analysis)
- Shift patterns support overnight and split shifts
- Biometric integration with ZKTeco devices
- Late/early leave detection with automatic calculations

### 4. Finance Module
**Tables**: `cost_centers`, `doctors`, `medical_services`, `fact_service_profitability`, `insurance_companies`, `insurance_claims`, `commission_adjustments`, `aging_snapshots`

**Key Business Rules**:
- ABC Costing for overhead allocation
- Insurance claim workflow: Draft → Pending Scrub → Submitted → Approved → Paid
- Clawback system for rejected claim commission recovery
- Aging reports track outstanding claims (0-30, 31-60, 61-90, 91-120, 120+ days)

### 5. Payroll Module
**Tables**: `payrolls`, `payroll_items`, `employee_loans`

**Key Business Rules**:
- WPS file generation for payment processing
- GOSI calculations (employee and employer portions)
- Loan installment deductions
- Clawback deductions from doctor commissions

## API Conventions

### Endpoint Patterns
```
GET    /api/{resource}          # List (paginated)
GET    /api/{resource}/{id}     # Show single
POST   /api/{resource}          # Create
PUT    /api/{resource}/{id}     # Update
DELETE /api/{resource}/{id}     # Delete (soft delete)
```

### Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Success message",
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## Frontend Conventions

### Component Structure
```jsx
// pages/{module}/{PageName}.jsx
import React from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
// Component logic with hooks
```

### Route Organization
- `/hr/*` - Human Resources routes
- `/payroll/*` - Payroll routes
- `/inventory/*` - Inventory routes
- `/roster/*` - Roster/scheduling routes
- `/finance/*` - Finance routes
- `/settings/*` - System settings routes

### State Management
- `AuthContext` - User authentication state
- `LocaleContext` - i18n and RTL support
- React Query for server state

## Testing Requirements

```bash
# Backend tests
docker-compose exec backend php artisan test

# Frontend tests
docker-compose exec frontend npm run test

# Coverage report
docker-compose exec backend php artisan test --coverage
```

**Coverage Target**: ≥80% for financial operations (payroll, claims, commissions)

## Security Considerations

1. **Authentication**: JWT tokens via Laravel Sanctum
2. **Authorization**: Role-Based Access Control (RBAC)
3. **Audit Trail**: All sensitive operations logged immutably
4. **Concurrency**: Optimistic locking prevents data races
5. **Input Validation**: Server-side validation on all endpoints
6. **Sensitive Data**: Never commit `.env` files, API keys, or credentials

## Common Development Tasks

### Adding a New Database Table
1. Create migration: `php artisan make:migration create_{table}_table`
2. Use UUID primary key: `$table->uuid('id')->primary()`
3. Include timestamps and soft deletes
4. Add appropriate indexes
5. Define foreign key relationships

### Adding a New API Endpoint
1. Create controller method following REST conventions
2. Add route in `routes/api.php`
3. Create Form Request for validation
4. Create Policy for authorization
5. Document in Swagger

### Adding a New React Page
1. Create page component in `src/pages/{module}/`
2. Use lazy loading in `App.jsx`: `const NewPage = React.lazy(() => import('./pages/module/NewPage'))`
3. Add route inside protected routes
4. Use React Query for data fetching

## Environment Variables

### Backend (.env)
```
APP_ENV=local
APP_KEY=
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=medical_erp
DB_USERNAME=
DB_PASSWORD=
REDIS_HOST=redis
GEMINI_API_KEY=
```

### Frontend (.env)
```
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME="Medical ERP Smart"
```

## Docker Services

| Service | Container Name | Port | Purpose |
|---------|---------------|------|---------|
| PostgreSQL | medical_erp_db | 5432 | Primary database |
| Redis | medical_erp_redis | 6379 | Cache and queues |
| Laravel | medical_erp_backend | 8000 | REST API |
| Queue Worker | medical_erp_queue | - | Async job processing |
| Scheduler | medical_erp_scheduler | - | Cron tasks |
| React | medical_erp_frontend | 3000 | Web UI |
| Nginx | medical_erp_nginx | 80/443 | Reverse proxy (production) |
| Biometric | medical_erp_biometric | - | ZKTeco sync (optional) |

### Docker Profiles
- Default: Development environment
- `production`: Includes Nginx reverse proxy
- `with-biometric`: Includes biometric agent service

## Important Business Domain Knowledge

### Arabic-English Bilingual Support
- All user-facing text has both `name` (English) and `name_ar` (Arabic) fields
- Frontend uses `LocaleContext` for RTL support
- Toast notifications positioned top-left for RTL compatibility

### Financial Compliance
- WPS (Wage Protection System) integration for Saudi labor law compliance
- GOSI (General Organization for Social Insurance) deduction calculations
- Immutable audit trails for financial accountability

### Medical Industry Specifics
- Controlled substance tracking (`is_controlled` flag on inventory items)
- Prescription requirements (`requires_prescription` flag)
- Insurance claim scrubbing workflow
- Diagnosis and procedure code tracking

## AI Assistant Guidelines

When working on this codebase:

1. **Respect immutable records**: Never modify tables without `updated_at` column
2. **Maintain bilingual fields**: Always provide both English and Arabic content where applicable
3. **Follow FEFO for inventory**: Query stock by `expiry_date` ascending
4. **Use optimistic locking**: Check version before updating inventory/stocks
5. **Preserve audit trails**: Log all sensitive operations to `audit_logs`
6. **Test financial operations**: Ensure ≥80% coverage for money-related code
7. **Follow REST conventions**: Maintain consistent API response formats
8. **Use lazy loading**: All new pages should be lazy-loaded in React
9. **Validate server-side**: Never trust client-side validation alone
10. **Document migrations**: Include Arabic comments explaining business logic
