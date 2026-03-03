<div dir="rtl">

# هيكل قاعدة البيانات - نظام إدارة شؤون الموظفين (HRMS)
# Database Schema — Human Resource Management System

</div>

---

## Overview | نظرة عامة

This document describes the complete database schema for the HRMS system covering **6 core modules** with **45+ tables** and their relationships.

**Database Engine:** PostgreSQL 16
**ORM:** Laravel Eloquent (UUID primary keys, soft deletes, timestamps)

---

## Table of Contents

1. [Foundation Tables](#1-foundation-tables)
2. [Module 1: Core HR & Employee Profiles](#2-module-1-core-hr--employee-profiles)
3. [Module 2: Contracts & Operations](#3-module-2-contracts--operations)
4. [Module 3: Payroll & End of Service](#4-module-3-payroll--end-of-service)
5. [Module 4: Leave Management System](#5-module-4-leave-management-system)
6. [Module 5: Dashboards & Analytics](#6-module-5-dashboards--analytics)
7. [Module 6: AI Suggestions](#7-module-6-ai-suggestions)
8. [System & Shared Tables](#8-system--shared-tables)
9. [Entity Relationship Diagram (ERD)](#9-entity-relationship-diagram)
10. [Indexes & Performance](#10-indexes--performance)

---

## 1. Foundation Tables

### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| username | VARCHAR (UNIQUE) | Login username |
| email | VARCHAR (UNIQUE) | Email address |
| password | VARCHAR | Hashed password |
| full_name | VARCHAR | Full name (EN) |
| full_name_ar | VARCHAR | Full name (AR) |
| user_type | ENUM | employee, admin, hr_manager, department_manager, general_manager, super_admin |
| employee_id | UUID (FK → employees) | Link to employee record |
| preferred_language | VARCHAR(5) | Default: 'ar' |
| is_active | BOOLEAN | Account status |
| receive_notifications | BOOLEAN | In-app notifications |
| receive_email_notifications | BOOLEAN | Email notifications |
| fcm_token | VARCHAR | Firebase push token |
| last_login_at | TIMESTAMP | Last login time |

### `departments`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| code | VARCHAR (UNIQUE) | Department code |
| name / name_ar | VARCHAR | Department name (EN/AR) |
| parent_id | UUID (FK → departments) | Parent department (hierarchical) |
| manager_id | UUID (FK → employees) | Department manager |
| is_active | BOOLEAN | Active status |

### `positions`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| code | VARCHAR (UNIQUE) | Position code |
| title / title_ar | VARCHAR | Job title (EN/AR) |
| department_id | UUID (FK → departments) | Default department |
| category | ENUM | medical, administrative, technical, support |
| min_salary / max_salary | DECIMAL(12,2) | Salary range |

---

## 2. Module 1: Core HR & Employee Profiles

### `employees` (Main Employee Record)
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_number | VARCHAR (UNIQUE) | Auto-generated employee number |
| department_id | UUID (FK → departments) | Department |
| position_id | UUID (FK → positions) | Job position |
| direct_manager_id | UUID (FK → employees) | Direct supervisor |
| hire_date | DATE | Hire date |
| status | ENUM | active, inactive, on_leave, suspended, terminated, pending_onboard |
| employment_type | ENUM | full_time, part_time, contract, tamheer, locum |
| first_name / last_name | VARCHAR | Name (EN) |
| first_name_ar / last_name_ar | VARCHAR | Name (AR) |
| gender | ENUM | male, female |
| national_id | VARCHAR (UNIQUE) | National ID / Iqama number |
| id_type | ENUM | national_id, iqama, passport, border_number |
| id_expiry_date | DATE | ID expiry date |
| nationality / nationality_ar | VARCHAR | Nationality |
| email | VARCHAR (UNIQUE) | Work email |
| phone | VARCHAR | Phone number |
| bank_name / iban | VARCHAR | Bank details |
| gosi_number | VARCHAR | Social insurance number |

### `employee_documents`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_id | UUID (FK → employees) | Employee |
| document_type | ENUM | contract, national_id, iqama, passport, medical_certificate, qualification, etc. |
| file_path | VARCHAR | Storage path |
| expiry_date | DATE | Document expiry |
| is_verified | BOOLEAN | Verification status |
| is_archived | BOOLEAN | Archive status |

### `employee_emergency_contacts`
Emergency contact details with primary contact flag.

### `employee_qualifications`
Academic qualifications and professional certificates.

### `employee_experiences`
Previous work experience records.

### `employee_onboardings`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_id | UUID (FK → employees, UNIQUE) | One onboarding per employee |
| status | ENUM | pending, in_progress, completed, cancelled |
| profile_completed | BOOLEAN | Checklist: profile done |
| documents_submitted | BOOLEAN | Checklist: documents submitted |
| contract_signed | BOOLEAN | Checklist: contract signed |
| bank_info_provided | BOOLEAN | Checklist: bank info |
| it_setup_done | BOOLEAN | Checklist: IT setup |
| workspace_assigned | BOOLEAN | Checklist: workspace |
| orientation_completed | BOOLEAN | Checklist: orientation |
| policies_acknowledged | BOOLEAN | Checklist: policies read |

### `employee_notes`
Confidential notes, performance records, warnings, commendations.

---

## 3. Module 2: Contracts & Operations

### `contracts`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_id | UUID (FK → employees) | Employee |
| contract_number | VARCHAR (UNIQUE) | Auto-generated |
| contract_type | ENUM | full_time, part_time, temporary, tamheer, percentage, locum, probation |
| status | ENUM | draft, pending_approval, active, expired, terminated, renewed, suspended |
| start_date / end_date | DATE | Contract period |
| basic_salary | DECIMAL(12,2) | Base salary |
| housing_allowance | DECIMAL(12,2) | Housing allowance |
| transport_allowance | DECIMAL(12,2) | Transport allowance |
| food_allowance | DECIMAL(12,2) | Food allowance |
| total_salary | DECIMAL(12,2) | Calculated total |
| annual_leave_days | INT | Default: 30 |
| notice_period_days | INT | Default: 60 |
| previous_contract_id | UUID (FK → contracts) | For renewals |

### `contract_alerts`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| contract_id | UUID (FK → contracts) | Related contract |
| alert_type | ENUM | expiry_reminder, probation_end, renewal_due, document_expiry, id_expiry |
| days_before_expiry | INT | Alert threshold (e.g., 60 days) |
| alert_date | DATE | When to send alert |
| status | ENUM | pending, sent, acknowledged, action_taken, dismissed |
| sent_to_employee / sent_to_manager / sent_to_hr | BOOLEAN | Delivery tracking |

### `contract_renewals`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| contract_id | UUID (FK → contracts) | Current contract |
| employee_response | ENUM | wants_renewal, wants_termination, no_response |
| management_decision | ENUM | approve_renewal, reject_renewal, modify_terms, pending |
| new_contract_id | UUID (FK → contracts) | New contract if renewed |
| status | ENUM | initiated, awaiting_employee, awaiting_management, approved, rejected, completed, cancelled |

### `letter_templates`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| code | VARCHAR (UNIQUE) | Template code |
| letter_type | ENUM | experience_certificate, salary_certificate, employment_certificate, insurance_exclusion, etc. |
| body_template / body_template_ar | TEXT | Template content with {{variables}} |
| available_variables | JSON | List of available template variables |
| requires_approval | BOOLEAN | Needs approval before printing |

### `generated_letters`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| template_id | UUID (FK → letter_templates) | Source template |
| employee_id | UUID (FK → employees) | Target employee |
| letter_number | VARCHAR (UNIQUE) | Auto-generated letter number |
| content / content_ar | TEXT | Generated content |
| generated_file_path | VARCHAR | PDF file path |
| status | ENUM | draft, pending_approval, approved, printed, delivered, cancelled |

---

## 4. Module 3: Payroll & End of Service

### `payrolls`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| payroll_number | VARCHAR (UNIQUE) | Payroll run number |
| month / year | INT | Pay period |
| status | ENUM | draft, calculating, pending_review, reviewed, pending_approval, approved, processing, paid, cancelled |
| total_basic_salary | DECIMAL(14,2) | Sum of basic salaries |
| total_net_salary | DECIMAL(14,2) | Sum of net salaries |
| employees_count | INT | Number of employees |
| payment_date | DATE | Payment date |

### `payroll_items`
Per-employee payroll line items with full salary breakdown (basic, allowances, deductions, GOSI, overtime, net).

### `payroll_additions_deductions`
Custom one-time or recurring additions/deductions for specific employees.

### `employee_loans`
Employee loan/advance tracking with installment plans.

### `loan_installments`
Individual installment records linked to payroll items.

### `end_of_service_calculations`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_id | UUID (FK → employees) | Employee |
| contract_id | UUID (FK → contracts) | Contract |
| service_start_date / service_end_date | DATE | Service period |
| total_service_years / months / days | INT | Service duration |
| last_total_salary | DECIMAL(12,2) | Last salary (for calculation) |
| termination_reason | ENUM | resignation, employer_termination, contract_end, mutual_agreement, retirement, death, force_majeure |
| first_5_years_amount | DECIMAL(12,2) | EOS for first 5 years (½ month per year) |
| after_5_years_amount | DECIMAL(12,2) | EOS after 5 years (1 month per year) |
| total_eos_amount | DECIMAL(12,2) | Total end of service benefit |
| eos_multiplier | DECIMAL(5,2) | Entitlement factor based on reason |
| leave_compensation | DECIMAL(12,2) | Unused leave compensation |
| net_settlement | DECIMAL(12,2) | Final net settlement |
| calculation_breakdown | JSON | Step-by-step calculation details |

### `payroll_exports`
Records of payroll file exports to banks (WPS, CSV, Excel, PDF).

---

## 5. Module 4: Leave Management System

### `leave_types`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| code | VARCHAR (UNIQUE) | Leave type code |
| name / name_ar | VARCHAR | Leave type name |
| category | ENUM | annual, sick, emergency, maternity, paternity, bereavement, marriage, hajj, unpaid, compensatory, study, other |
| default_days_per_year | INT | Default annual allocation |
| is_paid | BOOLEAN | Paid leave |
| pay_percentage | DECIMAL(5,2) | Salary percentage (e.g., 75% for extended sick) |
| requires_attachment | BOOLEAN | Requires supporting document |
| requires_substitute | BOOLEAN | Requires substitute employee |
| carries_forward | BOOLEAN | Balance carries to next year |

### `leave_balances`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_id | UUID (FK → employees) | Employee |
| leave_type_id | UUID (FK → leave_types) | Leave type |
| year | INT | Calendar year |
| total_entitled | DECIMAL(8,2) | Total days entitled |
| carried_forward | DECIMAL(8,2) | Days from previous year |
| used | DECIMAL(8,2) | Days used |
| pending | DECIMAL(8,2) | Days in pending requests |
| remaining | DECIMAL(8,2) | Available balance |
| **UNIQUE** | | (employee_id, leave_type_id, year) |

### `leave_requests`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| request_number | VARCHAR (UNIQUE) | Auto-generated |
| employee_id | UUID (FK → employees) | Requesting employee |
| leave_type_id | UUID (FK → leave_types) | Leave type |
| start_date / end_date | DATE | Leave period |
| total_days | INT | Number of days |
| substitute_employee_id | UUID (FK → employees) | Substitute employee |
| status | ENUM | draft, submitted, pending_substitute, pending_supervisor, pending_hr, pending_admin_manager, pending_general_manager, approved, rejected, cancelled, in_progress, completed, cut_short |
| current_approval_step | INT | Current step in approval chain |

### `leave_attachments`
Supporting documents for leave requests (medical reports, appointment letters, etc.).

### `leave_approvals` (Approval Matrix)
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| leave_request_id | UUID (FK → leave_requests) | Leave request |
| step_order | INT | Step order (1-5) |
| approval_role | ENUM | substitute, supervisor, medical_director, hr, admin_manager, general_manager |
| approver_id | UUID (FK → users) | Approver user |
| status | ENUM | pending, approved, rejected, skipped, delegated |
| comment / comment_ar | TEXT | Approver's comment |
| balance_before / balance_after | DECIMAL(8,2) | Balance tracking (HR step) |
| balance_sufficient | BOOLEAN | Balance check result |
| **UNIQUE** | | (leave_request_id, step_order) |

**Default Approval Flow:**
```
Step 1: Substitute Employee → Confirms coverage availability
Step 2: Direct Supervisor (or Medical Director for doctors) → Approves/Rejects
Step 3: HR Department → Validates balance and entitlement
Step 4: Admin Manager → Confirms substitute availability
Step 5: General Manager → Final approval and leave decision issuance
```

### `approval_matrix_settings`
Configurable approval chains per department, leave type, or position.

### `leave_balance_transactions`
Immutable ledger of all balance changes (allocations, deductions, restorations, adjustments).

---

## 6. Module 5: Dashboards & Analytics

### `saved_reports`
Saved report configurations with filters and column selections.

### `hr_snapshots`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| snapshot_date | DATE | Snapshot date |
| period_type | ENUM | daily, weekly, monthly, quarterly, yearly |
| total_employees | INT | Total headcount |
| active_employees | INT | Active employees |
| on_leave_count | INT | Currently on leave |
| new_hires / terminations | INT | Period movements |
| by_department | JSON | Distribution by department |
| by_nationality | JSON | Distribution by nationality |
| by_position | JSON | Distribution by position |
| expiring_contracts_30/60/90 | INT | Contracts expiring soon |
| absence_rate | DECIMAL(5,2) | Absence rate % |
| turnover_rate | DECIMAL(5,2) | Turnover rate % |

### `kpi_definitions`
KPI metric definitions with targets and thresholds.

### `kpi_values`
Periodic KPI measurements linked to definitions, with trend tracking.

### `scheduled_reports`
Auto-generated reports sent via email on configured schedules.

### `dashboard_alerts`
Smart alerts for contract expiry, leave conflicts, low staffing, high absence, etc.

---

## 7. Module 6: AI Suggestions

### `ai_analysis_logs`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| analysis_type | ENUM | leave_pattern, staffing_prediction, turnover_prediction, scheduling_optimization, absence_analysis, workload_analysis, seasonal_trend, anomaly_detection |
| input_parameters | JSON | Analysis input |
| results | JSON | Analysis output |
| confidence_score | DECIMAL(5,4) | Confidence level (0.0000-1.0000) |
| processing_time_ms | INT | Processing duration |
| status | ENUM | pending, processing, completed, failed, expired |

### `ai_predictions`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| analysis_log_id | UUID (FK → ai_analysis_logs) | Source analysis |
| prediction_type | ENUM | staff_shortage, leave_peak, turnover_risk, overtime_need, hiring_need, budget_overrun |
| department_id | UUID (FK → departments) | Affected department |
| prediction_date | DATE | Predicted date |
| probability | DECIMAL(5,4) | Prediction probability |
| impact_level | ENUM | low, medium, high, critical |
| suggested_actions | JSON | Recommended actions |
| was_accurate | BOOLEAN | Feedback for model improvement |

### `ai_recommendations`
Proactive HR recommendations (scheduling, retention, hiring, cost saving).

### `leave_patterns`
Detected patterns in leave behavior (seasonal peaks, day-of-week trends, cascading leaves).

### `turnover_risk_scores`
| Column | Type | Description |
|--------|------|-------------|
| id | UUID (PK) | Primary key |
| employee_id | UUID (FK → employees) | Assessed employee |
| risk_score | DECIMAL(5,4) | Risk score (0-1) |
| risk_level | ENUM | low, moderate, high, very_high |
| risk_factors | JSON | Contributing factors with weights |
| recommended_actions | JSON | Retention recommendations |
| is_latest | BOOLEAN | Latest assessment flag |

---

## 8. System & Shared Tables

### `roles` / `permissions` / `role_permissions` / `user_roles`
Full RBAC (Role-Based Access Control) system.

### `notifications`
Multi-channel notifications (in_app, email, sms, push) with polymorphic entity linking.

### `audit_logs`
Immutable audit trail for all sensitive operations with old/new value tracking.

### `system_settings`
Key-value system configuration store with type safety and grouping.

### `workflow_logs`
Status transition history for any entity using workflows.

### `public_holidays`
Official holiday calendar for leave calculations.

### `delegations`
Authority delegation system (leave approval, contract approval, etc.) with date ranges.

---

## 9. Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        FOUNDATION LAYER                             │
│                                                                     │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │  users   │◄───│ departments  │───►│  positions   │              │
│  └────┬─────┘    └──────┬───────┘    └──────────────┘              │
│       │                 │                                           │
└───────┼─────────────────┼───────────────────────────────────────────┘
        │                 │
        ▼                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    MODULE 1: CORE HR                                │
│                                                                     │
│  ┌──────────────┐                                                  │
│  │  employees   │◄─── (central entity)                             │
│  └──┬──┬──┬──┬──┘                                                  │
│     │  │  │  │                                                     │
│     │  │  │  ├──► employee_documents                               │
│     │  │  │  ├──► employee_emergency_contacts                      │
│     │  │  │  ├──► employee_qualifications                          │
│     │  │  │  ├──► employee_experiences                             │
│     │  │  │  ├──► employee_onboardings                             │
│     │  │  │  └──► employee_notes                                   │
│     │  │  │                                                        │
└─────┼──┼──┼────────────────────────────────────────────────────────┘
      │  │  │
      ▼  │  │
┌────────┐│  │ ┌──────────────────────────────────────────────────────┐
│MODULE 2││  │ │              CONTRACTS & OPERATIONS                  │
│        ││  │ │                                                      │
│  ┌─────┴──┐│ │  contracts ◄──── contract_alerts                    │
│  │contracts││ │      │                                              │
│  └─────┬──┘│ │      ├──────── contract_renewals                    │
│        │   │ │      │                                              │
│        │   │ │  letter_templates ──► generated_letters              │
│        │   │ │                                                      │
└────────┼───┘ └──────────────────────────────────────────────────────┘
         │  │
         ▼  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   MODULE 3: PAYROLL                                  │
│                                                                     │
│  payrolls ──► payroll_items ◄── payroll_additions_deductions       │
│                     │                                               │
│                     ▼                                               │
│  employee_loans ──► loan_installments                              │
│                                                                     │
│  end_of_service_calculations                                       │
│                                                                     │
│  payroll_exports                                                   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│              MODULE 4: LEAVE MANAGEMENT                             │
│                                                                     │
│  leave_types ──► leave_balances ◄── leave_balance_transactions     │
│       │                │                                            │
│       ▼                ▼                                            │
│  approval_matrix    leave_requests ──► leave_attachments           │
│  _settings              │                                          │
│                         ▼                                          │
│                   leave_approvals (5-step chain)                   │
│                   ┌─────────────────────────────┐                  │
│                   │ Step 1: Substitute          │                  │
│                   │ Step 2: Supervisor          │                  │
│                   │ Step 3: HR                  │                  │
│                   │ Step 4: Admin Manager       │                  │
│                   │ Step 5: General Manager     │                  │
│                   └─────────────────────────────┘                  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│            MODULE 5: DASHBOARDS & ANALYTICS                         │
│                                                                     │
│  saved_reports ──► scheduled_reports                                │
│                                                                     │
│  hr_snapshots (periodic snapshots)                                 │
│                                                                     │
│  kpi_definitions ──► kpi_values                                    │
│                                                                     │
│  dashboard_alerts                                                  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│              MODULE 6: AI SUGGESTIONS                               │
│                                                                     │
│  ai_analysis_logs ──► ai_predictions                               │
│        │                                                            │
│        ├──────────► ai_recommendations                             │
│        │                                                            │
│        └──────────► leave_patterns                                 │
│                                                                     │
│  turnover_risk_scores (per employee)                               │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                    SYSTEM / SHARED                                   │
│                                                                     │
│  roles ◄──► permissions     (RBAC)                                 │
│    │            │                                                   │
│    └── user_roles ── role_permissions                              │
│                                                                     │
│  notifications    audit_logs    system_settings                    │
│  workflow_logs    public_holidays    delegations                   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 10. Indexes & Performance

### Key Indexes

| Table | Index | Purpose |
|-------|-------|---------|
| employees | (department_id, status) | Filter by dept + status |
| employees | hire_date | Date range queries |
| employees | nationality | Nationality statistics |
| contracts | (employee_id, status) | Active contracts lookup |
| contracts | end_date | Expiry alerts |
| contract_alerts | (status, alert_date) | Pending alerts |
| leave_requests | (employee_id, status) | Employee's requests |
| leave_requests | (start_date, end_date) | Date range / conflict detection |
| leave_approvals | (approver_id, status) | Pending approvals queue |
| payroll_items | (payroll_id, employee_id) | Unique per payroll |
| audit_logs | (auditable_type, auditable_id) | Entity audit trail |
| audit_logs | (user_id, created_at) | User activity |
| notifications | (user_id, read_at) | Unread notifications |
| hr_snapshots | snapshot_date | Time-series queries |
| turnover_risk_scores | (employee_id, is_latest) | Latest risk score |

### Design Principles

1. **UUID Primary Keys** — Distributed generation, no collisions
2. **Soft Deletes** — Data preservation for audit compliance
3. **Immutable Logs** — audit_logs and leave_balance_transactions have no `updated_at`
4. **JSON Fields** — Flexible storage for metadata, breakdowns, and configurations
5. **Bilingual Support** — `name` / `name_ar` pattern throughout
6. **Composite Unique Constraints** — Prevent duplicate records (e.g., one payroll item per employee per period)

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Total Tables | 47 |
| Foundation Tables | 3 |
| Core HR Tables | 7 |
| Contract Tables | 5 |
| Payroll Tables | 7 |
| Leave Management Tables | 7 |
| Dashboard Tables | 6 |
| AI Tables | 5 |
| System Tables | 10 |
| Foreign Keys | 80+ |
| Indexes | 50+ |
