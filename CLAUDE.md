# CLAUDE.md - AI Assistant Guide for Medical ERP Smart

## Project Overview

**Medical ERP Smart** (نظام تخطيط الموارد الطبية الذكي) is a cloud-based integrated ERP system for managing medical facilities with AI-driven decision-making, complete role separation, and precise accounting integration.

**Status:** Skeleton/template stage with database schema defined and frontend routing established.

**Bilingual Support:** Full Arabic (RTL) and English support throughout the application.

---

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 11 (PHP 8.2+) |
| Frontend | React | 18 + Vite |
| Styling | Tailwind CSS | Latest |
| Database | PostgreSQL | 16 |
| Cache/Queue | Redis | 7 |
| Infrastructure | Docker Compose | 3.8 |
| AI Integration | Google Gemini API | - |
| Desktop | NativePHP | - |
| State Management | React Context + React Query | - |
| Authentication | JWT / Laravel Sanctum | - |

---

## Project Structure

```
medical-manage-smart/
├── backend/
│   └── database/
│       └── migrations/          # 8 migration files defining 48+ tables
│           ├── *_create_employees_table.php      # HR core
│           ├── *_create_contracts_table.php      # Contract types
│           ├── *_create_inventory_tables.php     # Smart inventory
│           ├── *_create_finance_tables.php       # Finance & BI
│           ├── *_create_roster_tables.php        # Smart rostering
│           ├── *_create_payroll_tables.php       # Payroll module
│           ├── *_create_system_tables.php        # System management
│           └── *_create_leave_tables.php         # Leave management
│
├── frontend/
│   └── src/
│       └── App.jsx              # Main routing and component structure
│
├── docker/
│   └── docker-compose.yml       # Complete microservices setup
│
└── docs/
    ├── generate-plan.js         # Implementation plan generator
    └── Implementation_Plan_AR.docx  # Arabic documentation
```

---

## Core Modules

### 1. HR Module (Human Resources)
- **Tables:** `employees`, `contracts`, `custodies`, `clearance_requests`
- **Features:** 5 contract types (Full-time, Part-time, Tamheer, Percentage, Locum), custody tracking, multi-step clearance workflow
- **Routes:** `/hr/*` (6 routes)

### 2. Smart Inventory Module
- **Tables:** `warehouses`, `inventory_items`, `warehouse_stocks`, `inventory_movements`, `item_quotas`, `quota_consumptions`
- **Features:** 7 warehouse types, FEFO policy, optimistic locking, quota management, crash cart support
- **Routes:** `/inventory/*` (6 routes)

### 3. Smart Rostering Module
- **Tables:** `shift_patterns`, `rosters`, `roster_assignments`, `shift_swap_requests`, `attendance_records`, `biometric_devices`, `roster_validation_rules`
- **Features:** 4 shift types, ZKTeco biometric integration, gap analysis, shift swap workflow
- **Routes:** `/roster/*` (4 routes)

### 4. Finance & BI Module
- **Tables:** `cost_centers`, `doctors`, `medical_services`, `fact_service_profitability`, `insurance_companies`, `insurance_claims`, `commission_adjustments`, `aging_snapshots`
- **Features:** ABC Costing, service profitability analysis, insurance claims with scrubber, commission clawback
- **Routes:** `/finance/*` (5 routes)

### 5. Payroll Module
- **Tables:** `payrolls`, `payroll_items`, `employee_loans`, `custodies`
- **Features:** WPS compliance, multi-currency, comprehensive earnings/deductions, loan tracking
- **Routes:** `/payroll/*` (3 routes)

### 6. System Management
- **Tables:** `roles`, `permissions`, `role_permissions`, `user_roles`, `audit_logs`, `integration_configs`, `notifications`, `system_settings`, `purchase_requests`, `workflow_logs`
- **Features:** Bilingual RBAC, immutable audit logging, multi-step approvals
- **Routes:** `/settings/*` (5 routes)

### 7. Leave Management Module (وحدة الإجازات)
- **Tables:** `leave_types`, `leave_balances`, `leave_requests`, `leave_approvals`, `leave_policies`, `leave_balance_adjustments`, `public_holidays`, `department_leave_settings`
- **Features:**
  - 12 leave categories (annual, sick, emergency, unpaid, maternity, paternity, hajj, marriage, bereavement, study, compensatory, other)
  - Multi-level approval workflow (Manager → HR → Department Head)
  - Balance tracking with carry-over support
  - Saudi labor law compliance (21 days minimum annual leave)
  - Department-level concurrent leave limits
  - Blackout periods and peak season restrictions
  - Integration with payroll for unpaid leave deductions
  - Public holidays management (Gregorian & Hijri calendars)
- **Routes:** `/leaves/*` (planned)

#### Leave Workflow
```
طلب الإجازة:
┌──────────┐   ┌──────────────┐   ┌─────────────┐   ┌──────────┐
│  مسودة   │──▶│ المدير       │──▶│ الموارد     │──▶│ معتمدة   │
│  draft   │   │ المباشر      │   │ البشرية    │   │ approved │
└──────────┘   │pending_manager│   │ pending_hr │   └──────────┘
               └──────────────┘   └─────────────┘
                     │                   │
                     ▼                   ▼
               ┌──────────┐        ┌──────────┐
               │ مرفوضة   │        │ مرفوضة   │
               │ rejected │        │ rejected │
               └──────────┘        └──────────┘
```

#### HR Employee Role in Leave Cycle (دور موظف الموارد البشرية)
1. **Balance Verification (التحقق من الرصيد):** Confirm sufficient leave balance exists
2. **Policy Compliance (مطابقة السياسات):** Ensure request meets company policies and labor law
3. **Conflict Check (فحص التعارض):** Verify no department coverage issues
4. **Approval/Rejection (الموافقة/الرفض):** Approve or reject with documented reason
5. **Documentation (التوثيق):** Update employee records and balance
6. **Payroll Integration (الربط بالرواتب):** Flag unpaid leave for salary deduction

---

## Development Commands

### Docker Operations
```bash
# Start all services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f [service-name]

# Run with specific profile
docker-compose --profile production up -d
docker-compose --profile with-biometric up -d
```

### Backend (Laravel)
```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Run migrations with seeding
docker-compose exec backend php artisan migrate --seed

# Run tests
docker-compose exec backend php artisan test

# Run tests with coverage
docker-compose exec backend php artisan test --coverage

# Clear cache
docker-compose exec backend php artisan cache:clear
```

### Frontend (React)
```bash
# Install dependencies
docker-compose exec frontend npm install

# Run development server
docker-compose exec frontend npm run dev

# Run tests
docker-compose exec frontend npm run test

# Build for production
docker-compose exec frontend npm run build
```

---

## Database Conventions

### Naming
- **Tables:** Snake case, plural (e.g., `inventory_items`, `shift_patterns`)
- **Columns:** Snake case (e.g., `created_at`, `cost_center_id`)
- **Enum values:** Lowercase with underscores (e.g., `full_time`, `in_progress`)

### Design Patterns
- **Primary Keys:** UUID for distributed systems compatibility
- **Immutable Audits:** No `updated_at` on audit tables
- **Optimistic Locking:** `version` field for concurrency control
- **Soft Deletes:** `deleted_at` for data preservation
- **JSON Columns:** For flexible metadata storage
- **Timestamps:** UTC with automatic management

### Common Status Flows
```
Payroll:    draft → approved → paid
Claims:     submitted → scrubbed → approved → paid → rejected
Clearance:  pending → finance_approved → hr_approved → it_approved → custody_cleared → completed
Purchase:   pending → manager_approved → finance_approved → ceo_approved → completed
Leave:      draft → pending_manager → pending_hr → approved → in_progress → completed
```

---

## Frontend Conventions

### File Structure
```
frontend/src/
├── components/    # Reusable UI components
├── pages/         # Route-specific page components
├── hooks/         # Custom React hooks
├── services/      # API service functions
├── contexts/      # React Context providers
├── utils/         # Utility functions
└── App.jsx        # Main app with routing
```

### Code Patterns
- **Components:** Functional React with hooks
- **Lazy Loading:** `React.lazy()` for code splitting
- **State:** React Context for global, React Query for server state
- **Styling:** Tailwind CSS with RTL support
- **i18n:** LocaleContext for Arabic/English switching

### Route Structure
- Public: `/login`
- Protected: All other routes wrapped in `ProtectedRoute` + `MainLayout`
- 404: Catch-all for unmatched routes

---

## Security & Compliance

### Implemented
- JWT/Sanctum authentication
- Role-Based Access Control (RBAC) with module-level permissions
- Immutable audit logging with IP/user-agent tracking
- Soft deletes for data preservation
- Password encryption

### Audit Events
`created`, `updated`, `deleted`, `login`, `logout`, `approved`, `rejected`, `exported`

---

## Key Business Rules

### Inventory
- **FEFO:** First Expire First Out policy
- **Quotas:** Daily/weekly/monthly consumption limits by department
- **Crash Cart:** Emergency supplies require separate management
- **Controlled Substances:** Special flags and tracking required

### HR & Payroll
- **Clearance:** Employees cannot separate until all custodies returned
- **Clawback:** Commission adjustments for rejected insurance claims
- **WPS:** Wage Protection System compliance required
- **Contracts:** Support 5 types with different calculation rules

### Leave Management
- **Annual Leave:** Minimum 21 days per year (Saudi Labor Law)
- **Sick Leave:** Requires medical certificate attachment
- **Hajj Leave:** Once during employment, 10-15 days
- **Maternity:** 70 days (10 weeks) fully paid
- **Balance Carry-over:** Configurable per leave type
- **Concurrent Limits:** Department-level restrictions on simultaneous leaves
- **Blackout Periods:** Prevent leaves during critical periods
- **Advance Notice:** Configurable notice period per leave type
- **Delegation:** Require delegate assignment for certain positions

### Finance
- **ABC Costing:** Cost allocation to cost centers
- **Profitability:** Per-doctor, per-service analytics
- **Aging:** Historical snapshot tracking for receivables

---

## Integration Points

| System | Purpose |
|--------|---------|
| ZKTeco | Biometric attendance devices |
| Google Gemini | AI-powered analysis and predictions |
| Insurance APIs | Claims submission and scrubbing |
| Payment Gateways | Payment processing |
| SMS Services | Notifications |
| External Accounting | Financial system sync |

---

## Testing Requirements

- **Coverage Target:** ≥80% for financial operations
- **Backend:** PHPUnit via `php artisan test`
- **Frontend:** Jest/Vitest via `npm run test`

---

## Docker Services

| Service | Port | Purpose |
|---------|------|---------|
| PostgreSQL | 5432 | Primary database |
| Redis | 6379 | Cache and queue |
| Backend | 8000 | Laravel API |
| Frontend | 3000 | React SPA |
| Nginx | 80/443 | Reverse proxy (production) |
| Queue Worker | - | Async job processing |
| Scheduler | - | Cron tasks |
| Biometric Agent | - | ZKTeco sync (optional) |

---

## AI Assistant Guidelines

### When Working on This Codebase

1. **Bilingual Support:** Always consider both Arabic and English when adding UI text or messages
2. **RTL Awareness:** Test layouts for right-to-left rendering
3. **Audit Logging:** Add audit events for sensitive operations
4. **UUID Keys:** Use UUIDs for new table primary keys
5. **Immutability:** Audit and movement tables should not have `updated_at`
6. **Status Workflows:** Follow established status enum patterns
7. **Optimistic Locking:** Use version fields for concurrent update scenarios
8. **Soft Deletes:** Prefer soft deletes over hard deletes

### Code Quality
- Follow Laravel conventions for backend
- Use functional components with hooks for React
- Write tests for financial calculations (≥80% coverage)
- Use Tailwind CSS classes, avoid inline styles
- Implement proper error handling with user-friendly messages

### What's Currently Implemented
- Complete database schema (48+ tables including Leave Management)
- Frontend routing structure (24 lazy-loaded pages)
- Docker containerization with all services
- Authentication architecture
- Leave management module with full workflow support

### What Needs Implementation
- Backend API controllers and services
- Laravel models with relationships
- Frontend page components and forms
- Business logic implementation
- Integration adapters
- API documentation (Swagger/OpenAPI)
- Test suites
- CI/CD pipelines

---

## Quick Start

```bash
# 1. Clone and navigate
git clone https://github.com/ENMA97/medical-manage-smart.git
cd medical-manage-smart

# 2. Start services
docker-compose up -d

# 3. Run migrations
docker-compose exec backend php artisan migrate --seed

# 4. Access application
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000
```
