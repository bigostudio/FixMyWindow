# FixMyWindow (FMW) — Low Level Design (LLD)

**Version:** 1.0  
**Date:** June 2026  
**Status:** Draft — for engineering review  
**Scope basis:** SRS v1.0 (June 2026) — authoritative reference  
**Stack:** Laravel (PHP) · MySQL 8.0+ · JWT · Cloud file storage · React (web) · Flutter (planned)

---

## 0. Scope & Cycle boundaries

This LLD is anchored exclusively to **SRS v1.0 (June 2026)**, the authoritative platform specification. All design decisions derive from it. This document covers a **fresh implementation** — no migration from any prior system is assumed.

The system is delivered in two cycles:

| Cycle | Contents | LLD treatment |
|---|---|---|
| **Cycle 1** | Auth (OTP + admin), Enquiry (B2C + B2B), Survey & Go/No-Go, Measurement, Project / Execution Tracker, Activity Timeline, Snag Management, Handover, Notification templates & logging, Settings, Service Catalogue, User Management, core Reports | **Fully designed and in scope** |
| **Cycle 2** | Quotation, Payment gateway dispatch, Invoice / Billing, SMS / Email gateway dispatch, full AMC, Partner (Builder/Fabricator) portal, Self Inspection flow | Schema hooks and FK placeholders designed in Cycle 1; logic deferred |

> **Self Inspection is out of scope for Phase 1.** Per SRS §5.2: *"Only Expert Site Inspection (₹1,000) is available for B2C customers in the current scope."* No self-inspection table, endpoints, or validation logic is implemented in Phase 1. The data model is designed so it can be added in Cycle 2 without breaking changes.

---

## 1. Architecture Overview

### 1.1 Layered architecture (Laravel)

The backend follows a strict layered request lifecycle. Controllers stay thin; all business logic lives in Services; all persistence lives in Repositories.

```
HTTP Request
   │
   ▼
[Route] ──> [Middleware stack] ──> [Controller]
                 │                      │
   auth:jwt, role:*, throttle          │ delegates
   set-locale                          ▼
                              [Form Request]  (validation + authorization)
                                      │ validated DTO
                                      ▼
                                 [Service]      (business rules, transactions, ID gen, events)
                                   │     │
                                   │     └──> [Event] ──> [Listener] ──> [Queued Job]
                                   ▼                                     (notifications, PDF gen, timeline)
                              [Repository] ──> [Eloquent Model] ──> MySQL
                                   │
                                   ▼
                              [API Resource]   (response envelope shaping)
                                      │
                                      ▼
                              JSON Response  { success, data, message }
```

### 1.2 Directory layout

```
app/
 ├─ Http/
 │   ├─ Controllers/
 │   │   ├─ Customer/ (Auth, Enquiry, Blueprint, Profile)
 │   │   └─ Admin/    (Auth, User, Service, Enquiry, Survey, Measurement,
 │   │                 Project, Snag, Handover, Notification, Settings, Report)
 │   ├─ Requests/     (one FormRequest per write endpoint)
 │   ├─ Resources/    (API Resource per entity + collection variants)
 │   └─ Middleware/   (JwtAuth, RoleGuard, ResponseEnvelope, ForceJson)
 ├─ Services/         (one service per module; orchestrates repos + events)
 ├─ Repositories/     (interface + Eloquent implementation per aggregate)
 ├─ Models/           (Eloquent models; JSON casts; relationships)
 ├─ Events/           (EnquiryConfirmed, SurveyDecided, ProjectStatusChanged, ...)
 ├─ Listeners/        (WriteTimelineEntry, QueueNotification, GeneratePdf)
 ├─ Jobs/             (SendSms, SendEmail, GenerateHandoverCertificate, ...)
 ├─ Policies/         (per-model authorization; backs role + ownership checks)
 ├─ Support/
 │   ├─ Enums/        (PHP 8.1 enums: EnquiryStatus, ProjectStatus, Role, ...)
 │   ├─ Concerns/     (traits: HasUuid, Auditable, WritesTimeline)
 │   └─ Sequence/     (EnquiryNumberGenerator)
 └─ Exceptions/       (Handler renders envelope; BusinessRuleException -> 422)
```

### 1.3 Cross-cutting components

| Component | Responsibility |
|---|---|
| `ResponseEnvelope` middleware | Wraps every controller return into `{success, data, message}` |
| `Handler` (exceptions) | Maps exception types to HTTP codes + envelope (§11) |
| `RoleGuard` middleware | RBAC enforcement at route level (§5) |
| Policies | Fine-grained per-record authorization (ownership + assignment scope) |
| Event / Listener bus | Decouples side effects (timeline writes, notifications, PDF gen) from business logic |
| Queue (database / redis) | All notifications and PDF generation run async via Jobs |

---

## 2. Technology & Conventions

| Concern | Decision |
|---|---|
| API prefix | `/api/v1/` on all routes (SRS §18.1) |
| Auth | JWT — 24h access token, 30d refresh token (SRS §4.3) |
| Internal PKs | `BIGINT UNSIGNED AUTO_INCREMENT`; public-facing uses `FMW-YYYYMMDD-XXXX` string |
| Money | `INT` (INR rupees, no paise) |
| Timestamps | UTC, ISO 8601, `TIMESTAMP` columns |
| JSON | MySQL `JSON` columns; fields needing indexes exposed via **virtual generated columns** |
| Soft delete | `deleted_at` on master entities (services, users); **never** on `project_timeline` (append-only) |
| Pagination | `page` (default 1), `limit` (default 20, max 100), `sort` (default `created_at`), `order` (default `desc`) |
| Naming | snake_case columns, plural table names, RESTful routes |

---

## 3. Data Model — Full Schema

### 3.1 Hybrid storage decision rule

| Store as **Column** | Store as **JSON** |
|---|---|
| Filtered / sorted / aggregated (`WHERE`, `ORDER BY`, `GROUP BY`) | Always read & written as one unit |
| FK / used in JOIN | Dynamic / variable shape (array of N items) |
| Drives status logic or validation | Config blob whose shape varies per provider |
| Appears on dashboards / reports | Never filtered individually across records |
| Has referential integrity (Enum / FK) | Adding sub-fields must not require a DB migration |

---

### 3.2 `users` (all internal staff roles)

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| name | VARCHAR(150) | | |
| email | VARCHAR(190) | UNIQUE | Login credential for all internal roles |
| phone | VARCHAR(15) | IDX | |
| password | VARCHAR(255) | | bcrypt |
| role | ENUM | IDX | 7 internal values — see §5.1 |
| is_active | BOOLEAN | IDX | Deactivated users lose login access immediately |
| two_factor_secret | VARCHAR(255) NULL | | Super Admin 2FA (TOTP) |
| created_at / updated_at / deleted_at | TIMESTAMP | | Soft delete only; preserves FK + actor_name references in timeline |

---

### 3.3 `customers`

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| phone | VARCHAR(15) | UNIQUE | OTP login key |
| name | VARCHAR(150) NULL | | Filled at billing step |
| email | VARCHAR(190) NULL | | |
| type | ENUM(`b2c`, `b2b`) | IDX | |
| gst_number | VARCHAR(20) NULL | | B2B customers |
| created_at / updated_at | TIMESTAMP | | |

---

### 3.4 `otp_requests`

Tracks OTP issuance, expiry (5 min), single-use consumption, resend rate limit (3/hr per phone), and failed-attempt lockout (5 failures → 15-min lock).

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| phone | VARCHAR(15) | IDX(phone, created_at) | Composite for rate-limit query |
| otp_hash | VARCHAR(255) | | bcrypt — OTP never stored plaintext |
| expires_at | DATETIME | | `created_at + 5 minutes` |
| consumed_at | DATETIME NULL | | Set on first successful use; single-use enforced |
| failed_attempts | TINYINT DEFAULT 0 | | Incremented on wrong OTP entry |
| locked_until | DATETIME NULL | | Set when `failed_attempts >= 5`; auto-clears after 15 min |
| created_at | TIMESTAMP | | Used for resend-rate window: `COUNT(*) WHERE phone=? AND created_at > NOW()-1hr < 3` |

---

### 3.5 `refresh_tokens`

| Column | Type | Notes |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| tokenable_type / tokenable_id | morph | Polymorphic: `customers` or `users` |
| token_hash | VARCHAR(255) UNIQUE | bcrypt |
| expires_at | DATETIME | +30 days from issue |
| revoked_at | DATETIME NULL | Set on logout |

---

### 3.6 `service_categories` & `services`

**`service_categories`**: `id`, `name` VARCHAR, `description` TEXT, `is_active` BOOL, timestamps.

**`services`**:

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| category_id | FK | IDX | |
| name | VARCHAR(150) | | |
| description | TEXT | | |
| material | ENUM(`uPVC`, `Aluminium`, `Facade`) | IDX | Phase 1: only uPVC is bookable; others shown as Coming Soon |
| is_active | BOOLEAN | IDX | Inactive services hidden from customer listings |
| created_at / updated_at / deleted_at | TIMESTAMP | | |

**`service_cities`** (junction — city as plain string per SRS §5.3):

| Column | Notes |
|---|---|
| service_id FK | |
| city VARCHAR(100) | e.g. `Bangalore`, `Chennai`, `Coimbatore` |
| UNIQUE(service_id, city) | |

---

### 3.7 `enquiries`

The central entity for every customer job request (B2C and B2B). The `enquiry_number` is the customer-facing ID.

| Column | Type | Index | Storage | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | Column | Internal key |
| enquiry_number | VARCHAR(20) | UNIQUE | Column | `FMW-YYYYMMDD-XXXX` — generated on confirm, not on initiate |
| customer_id | FK | IDX | Column | |
| service_id | FK | IDX | Column | |
| type | ENUM(`b2c`, `b2b`) | IDX | Column | |
| city | VARCHAR(100) | IDX | Column | Filter by city |
| property_type | ENUM | IDX | Column | `Home/Individual` · `Commercial Building/RWA` |
| material_type | ENUM | IDX | Column | uPVC (Phase 1 only) |
| inspection_type | ENUM(`expert`, `self`) | IDX | Column | Phase 1: always `expert`; `self` reserved for Cycle 2 |
| status | ENUM | IDX | Column | `New` · `Assigned` · `In Progress` · `Completed` · `Cancelled` |
| latitude / longitude | DECIMAL(10,7) | | Column | GPS of service location |
| address | TEXT | | Column | Full formatted address |
| inspection_fee | INT DEFAULT 1000 | | Column | ₹1,000 fixed (Phase 1) |
| payment_status | ENUM(`Pending`, `Paid`, `Failed`) | IDX | Column | |
| payment_amount | INT NULL | | Column | |
| payment_ref | VARCHAR(100) NULL | | Column | Cycle 2 — payment gateway reference |
| receipt_url | VARCHAR(255) NULL | | Column | File server path |
| blueprint_id | FK NULL | IDX | Column | B2B only — links to `blueprints` |
| quotation_id | FK NULL | IDX | Column | Cycle 2 placeholder |
| booking_date | DATE | IDX | Column | |
| created_at / updated_at | TIMESTAMP | IDX(created_at) | Column | |

---

### 3.8 `blueprints` (B2B)

| Column | Type | Storage | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | Column | |
| customer_id | FK | Column | |
| society_name | VARCHAR(190) | Column | Searchable |
| number_of_towers | INT | Column | Input to auto-generation |
| floors_per_tower | INT | Column | Input |
| flats_per_floor | INT | Column | Input |
| windows_per_flat | INT | Column | Input |
| status | ENUM(`draft`, `confirmed`, `surveyed`, `locked`) | Column (IDX) | State machine |
| towers | JSON | JSON | Auto-generated + customisable array: Tower → Floor → Flat → Aperture |
| created_at / updated_at | TIMESTAMP | Column | |

> **Auto-generation:** `total_apertures = towers × floors × flats × windows`. For large projects (>500 apertures) the generation runs in a **queued job** and the endpoint returns HTTP 202 with a polling or webhook pattern. Must support up to 10,000 apertures (SRS §19.3).

> **Edit rules:** `towers` JSON is only editable while `status = draft`. Once `confirmed`, blueprint is locked for further customer edits; only admins can adjust.

---

### 3.9 `projects`

Created automatically when an enquiry is confirmed or when a Survey GO decision is recorded.

**Dedicated columns (filterable / logic-driving):**

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| enquiry_id | FK | IDX | |
| blueprint_id | FK NULL | IDX | B2B only |
| project_name | VARCHAR(190) | FULLTEXT, IDX | Searchable |
| assigned_om_id | FK | IDX | References users |
| assigned_supervisor_id | FK | IDX | References users |
| status | ENUM | IDX | `On Track` · `At Risk` · `Delayed` · `Due to Dependency` · `Completed` · `Cancelled` |
| delay_reason | ENUM NULL | IDX | Required when status ∈ {Delayed, Due to Dependency} |
| expected_start_date | DATE | IDX | Timeline calculations |
| planned_completion_date | DATE | IDX | Drives Delayed status logic |
| installation_started_date | DATE NULL | | Actual start |
| final_completion_date | DATE NULL | | Set when status = Completed |
| total_units | INT | | Total apertures/windows |
| units_completed | INT | | Updated on progress reports |
| progress_percent | DECIMAL(5,2) | IDX | Computed: `(units_completed / total_units) × 100`; stored for dashboard speed |
| created_at / updated_at | TIMESTAMP | IDX(created_at) | |

**JSON columns:**

| JSON Column | Contents | Why JSON |
|---|---|---|
| `material_status` | `{units_received_full_set, units_received_incomplete, units_not_received, damage:{outer,shutter,glass,lock,handle,mesh}}` | All 9 fields updated together in one material-check pass; damage sub-fields never filtered individually |
| `quality_checks` | `{alignment_verified:{done,total}, level_checked:{done,total}, lock_working, silicone_completed, gap_inspection_done, cleaning_completed}` | 6 QC items reviewed and written together in one quality pass |
| `project_details` | Full 10-section Project Details form (Sections 1–10 per SRS §7.5) | All 10 sections always read/written together; no individual field queried across projects |

**Delay/dependency reasons (ENUM values):** Visible Transition Damage · Site not ready · Civil work incomplete · Missing hardware · Damaged glass received · Client change request · Window missing parts · Glass damaged · Customer design change · Weather issue.

---

### 3.10 `project_timeline` (append-only audit log)

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| project_id | FK NULL | IDX | |
| enquiry_id | FK NULL | IDX | For events that occur before a project record exists |
| status | ENUM | IDX | Full event list per SRS §11.3 (see below) |
| description | TEXT | | Human-readable event description |
| actor_type | ENUM | IDX | `System` · `Admin` · `Ops Admin` · `Project Manager` · `Surveyor` · `Installer` · `QC Engineer` · `Customer` |
| actor_id | FK NULL | | Null when actor_type = System |
| actor_name | VARCHAR(150) | | **Denormalised at write time** — preserved even if the user is later deactivated |
| created_at | TIMESTAMP | IDX(project_id, created_at) | **Immutable.** Never updated. Ordered ASC for display. |

> **Hard enforcement rules:**
> 1. Model `booted()` guard throws `\LogicException` on any `update()` or `delete()` call on this model.
> 2. Every project/enquiry status change inserts a timeline row **in the same database transaction** — no status change without a corresponding log.
> 3. `actor_name` is always written as a snapshot string; never read via JOIN on the users table.
> 4. System-initiated entries use `actor_id = null`, `actor_name = 'System'`.

**Status ENUM values (from SRS §11.3):**
`New` · `Surveyor Assigned` · `Survey Passed` · `Survey Rejected` · `Inspection Scheduled` · `Inspection Completed` · `Quotation Generated` · `Quotation Approved` · `Work Order Created` · `Team Assigned` · `Work In Progress` · `On Track` · `At Risk` · `Delayed` · `Due to Dependency` · `Snag Raised` · `Snag Closed` · `QC Approved` · `Payment Received` · `Completed` · `Cancelled`

---

### 3.11 `surveys`

| Column | Type | Storage | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | Column | |
| enquiry_id | FK | Column (IDX) | |
| surveyor_id | FK | Column (IDX) | References users |
| final_outcome | ENUM(`GO`, `HOLD`, `NO-GO`) NULL | Column (IDX) | Promoted from JSON — drives work-order trigger and dashboard filter |
| status | ENUM(`Pending`, `Scheduled`, `Completed`) | Column (IDX) | |
| scheduled_at | DATETIME NULL | Column (IDX) | |
| client_details | JSON | JSON | Section A — project & client details (always read together) |
| gonogo_matrix | JSON | JSON | Sections B rows (10 items × Yes/No/TBD); `final_outcome` extracted to column |
| feasibility | JSON | JSON | Section C — installation feasibility checklist |
| opening_data | JSON | JSON | Section D — per-aperture opening survey records (dynamic array of N) |
| risk_register | JSON | JSON | Section E — risk type, severity, mitigation |
| photos | JSON | JSON | Array of file server paths |
| created_at / updated_at | TIMESTAMP | Column | |

---

### 3.12 `measurements` (per aperture)

All 12 measurement-point dimensions are **dedicated INT columns** (each independently queryable for accuracy reporting). `manufacturing_width_mm` and `manufacturing_height_mm` are **MySQL virtual generated columns** (`opening − deduction`) — no application code needed to maintain them.

| Column | Type | Notes |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| aperture_id | FK | References aperture within blueprint/project |
| opening_width_mm / opening_height_mm | INT | Actual measured opening |
| width_top_mm / width_middle_mm / width_bottom_mm | INT | 3 horizontal readings |
| height_left_mm / height_center_mm / height_right_mm | INT | 3 vertical readings |
| diagonal_1_mm / diagonal_2_mm | INT | Squareness verification |
| sill_height_mm | INT | |
| lintel_height_mm | INT NULL | |
| wall_thickness_mm | INT | |
| deduction_width_mm / deduction_height_mm | INT | Frame clearance deductions |
| manufacturing_width_mm | INT AS (opening_width_mm - deduction_width_mm) VIRTUAL | Generated |
| manufacturing_height_mm | INT AS (opening_height_mm - deduction_height_mm) VIRTUAL | Generated |
| opening_type | ENUM(`Window`, `Door`, `Ventilator`) | |
| opening_direction | ENUM(`Inward`, `Outward`, `Sliding`) | |
| photos | JSON | Min 2 per aperture — array of file server paths |
| remarks | TEXT NULL | |
| approval_status | ENUM(`Pending`, `Approved`, `Rejected`) | IDX |
| surveyor_id | FK | |
| measured_at | TIMESTAMP | |

---

### 3.13 `snags`

| Column | Type | Index | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| project_id | FK | IDX | |
| aperture_id | FK | IDX | |
| tower / floor / flat | VARCHAR | | Location identifiers |
| issue | TEXT | | Defect description |
| severity | ENUM(`High`, `Medium`, `Low`) | IDX | |
| assigned_to | FK | IDX | Technician assigned to fix |
| status | ENUM(`Open`, `In Progress`, `Resolved`, `Closed`, `Escalated`) | IDX | |
| photos_before | JSON | | Array of file server paths (required on creation) |
| photos_after | JSON NULL | | Required to close a snag |
| due_date | DATE | IDX | |
| closure_date | DATE NULL | | |
| created_by | FK | | QC Engineer who raised the snag |
| created_at / updated_at | TIMESTAMP | IDX | |

---

### 3.14 `handovers`

| Column | Type | Notes |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| project_id | FK | |
| flat_id / home_id | FK | Unit being handed over |
| customer_photo_url | VARCHAR(255) | File server path |
| digital_signature | VARCHAR(255) | File server path (signature image) |
| rating | TINYINT | 1–5 stars |
| remarks | TEXT NULL | Customer remarks |
| handover_date | DATE | |
| handover_by | FK | Supervisor / OM who conducted handover |
| certificate_url | VARCHAR(255) NULL | Handover Certificate PDF — auto-generated |
| warranty_start_date | DATE | Same as handover_date |
| warranty_end_date | DATE | Calculated from `settings.warranty_period` config |

---

### 3.15 `notification_templates`

| Column | Type | Storage | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | Column | |
| event_type | ENUM | Column (IDX) | e.g. `enquiry_confirmed` · `otp_sent` · `snag_raised` — lookup key |
| is_enabled | BOOLEAN | Column (IDX) | Toggleable from admin — frequently filtered |
| channel | ENUM(`sms`, `email`, `both`) | Column (IDX) | |
| template_body | JSON | JSON | `{subject, sms_body, email_body, placeholders[]}` — shape varies per channel; always consumed whole |
| updated_at | TIMESTAMP | Column | |

---

### 3.16 `notification_logs`

| Column | Type | Notes |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| event_type | ENUM | |
| channel | ENUM | |
| recipient | VARCHAR(190) | Phone or email |
| payload | JSON | Rendered message content |
| status | ENUM(`queued`, `sent`, `failed`) | |
| error | TEXT NULL | Gateway error message |
| created_at | TIMESTAMP | |

---

### 3.17 `settings`

| Column | Type | Storage | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | Column | |
| setting_key | VARCHAR(100) | Column (UNIQUE) | Lookup key |
| is_enabled | BOOLEAN | Column (IDX) | Active toggle |
| gateway_config | JSON | JSON | Provider-specific credentials; shape varies per SMS / Email / Payment provider |
| updated_at | TIMESTAMP | Column | |

**Seeded keys:**

| Key | Type | Notes |
|---|---|---|
| `sms_gateway` | JSON | `{provider, api_key, sender_id}` |
| `email_gateway` | JSON | SMTP or API config |
| `payment_gateway` | JSON | Cycle 2 |
| `business_hours` | JSON | Working days, hours, holidays |
| `warranty_period` | INT column | Default warranty in months |
| `inspection_fee` | INT column | ₹1,000 (Phase 1 fixed) |

---

### 3.18 `enquiry_counter` (global sequence — see §4)

Single-row table: `id = 1`, `last_value BIGINT`, `updated_at TIMESTAMP`.

---

### 3.19 Entity Relationship Summary

```
customers ─1:N─ enquiries ──────────────────────────────── service_cities
customers ─1:N─ blueprints ──FK─ enquiries                  │
                                       │                services ─M:N─ ┘
                                       │            service_categories ─1:N─ services
                                 1:1 projects
                                       │
                    ┌──────────────────┼────────────────────┐
                    │                  │                     │
               1:N surveys        1:N project_timeline   1:N snags
               1:N measurements   1:N handovers
```

---

## 4. Enquiry Number Generation — `FMW-YYYYMMDD-XXXX`

**Format:** `FMW` + `YYYYMMDD` (UTC) + 4-digit zero-padded global counter that **never resets and never repeats**.

### 4.1 Why not MAX() or COUNT()

Under concurrent requests, two transactions could read the same `MAX(last_value)` before either commits — producing a duplicate ID. A dedicated single-row counter with `lockForUpdate` is the correct MySQL 8.0 pattern.

### 4.2 Concurrency-safe algorithm

```php
// Inside EnquiryService::confirm(), wrapped in DB::transaction()

DB::table('enquiry_counter')
    ->where('id', 1)
    ->lockForUpdate()
    ->update(['last_value' => DB::raw('last_value + 1')]);

$n = DB::table('enquiry_counter')->where('id', 1)->value('last_value');

$number = sprintf('FMW-%s-%04d', now('UTC')->format('Ymd'), $n);
// $n grows past 9999 naturally — the %04d padding is display-only
```

> **Number is generated on `confirm()`, not on `initiate()`.** Draft/abandoned enquiries do not consume counter values.

---

## 5. Authentication & RBAC

### 5.1 Role model

| Role | Type | Authentication | Notes |
|---|---|---|---|
| Super Admin | Internal | Email + password + **2FA (TOTP)** | Full system access |
| Operations Admin | Internal | Email + password | Manages all projects and workflows |
| Project Manager | Internal | Email + password | Monitors assigned projects |
| Surveyor / Measurement | Internal | Email + password | Site visits, measurements |
| Installer / Technician | Internal | Email + password | Field execution |
| QC Engineer | Internal | Email + password | Quality inspection, snags |
| Accounts / Finance | Internal | Email + password | Invoices, payments (Cycle 2) |
| Builder / Fabricator | External | Cycle 2 partner portal | Assigned projects view only |
| Customer (B2C/B2B) | External | **Mobile OTP only** | Own enquiries and profile |

> All internal accounts are created by **Super Admin only** — no self-registration for internal roles (SRS §4.2).

### 5.2 Customer OTP flow

```
POST /api/v1/auth/send-otp
  1. Check locked_until > NOW()  →  429 (lockout active)
  2. COUNT otp_requests WHERE phone=? AND created_at > NOW()-1hr
     If count >= 3  →  429 (rate limit exceeded)
  3. Generate 6-digit OTP, hash with bcrypt, persist with expires_at = NOW()+5min
  4. Queue SendSms job  (Cycle 1: log only; Cycle 2: live gateway)
  →  200

POST /api/v1/auth/verify-otp
  1. Load latest unconsumed OTP record for phone
  2. consumed_at IS NOT NULL  →  422 (already used)
  3. expires_at < NOW()  →  422 (expired)
  4. bcrypt check fails:
     failed_attempts++
     If failed_attempts >= 5: set locked_until = NOW()+15min  →  429
     Else  →  401
  5. bcrypt check passes:
     SET consumed_at = NOW()
     UPSERT customers(phone) — creates new customer or retrieves existing
     Issue JWT access (24h) + refresh (30d)
  →  200 { tokens, customer }

POST /api/v1/auth/refresh-token   (refresh token → new access token)
POST /api/v1/auth/logout           (revoke refresh token)
```

### 5.3 Admin authentication

```
POST /api/v1/admin/auth/login          (email + password; Super Admin also validates 2FA)
POST /api/v1/admin/auth/logout         (revoke refresh)
POST /api/v1/admin/auth/refresh-token
POST /api/v1/admin/auth/change-password
```

Session auto-logout configurable (default 8 hours, NFR §19.2).

### 5.4 Permission enforcement

**Route-level** (coarse): `auth:jwt` + `role:Surveyor,QC` middleware on route groups.

**Policy-level** (fine): a Surveyor sees only enquiries/projects they're assigned to; a Customer sees only their own enquiries (ownership check on `customer_id`). Super Admin and Ops Admin bypass all scoping.

**Module permission matrix** (from SRS §3.4) is encoded as a `permissions.php` config map consumed by `RoleGuard` middleware and individual Policies. No hardcoded role strings in business logic.

---

## 6. Module LLD

All request and response bodies use the standard response envelope (`{success, data, message}`). All write endpoints are protected by their respective `FormRequest` class for validation. Roles listed are the minimum required — higher roles inherit.

---

### 6.1 Enquiry Module (B2C)

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| POST | `/api/v1/enquiries/initiate` | Customer | Create draft enquiry |
| PUT | `/api/v1/enquiries/:id/address` | Customer | Set/update service location |
| POST | `/api/v1/enquiries/:id/payment` | Customer | Initiate payment (Cycle 2 stub → returns Paid) |
| POST | `/api/v1/enquiries/:id/confirm` | Customer | Confirm after payment |
| GET | `/api/v1/enquiries` | Customer | List own enquiries (paginated) |
| GET | `/api/v1/enquiries/:id` | Customer | Get enquiry detail |
| GET | `/api/v1/admin/enquiries` | Ops Admin, PM | List all with filters |
| PUT | `/api/v1/admin/enquiries/:id/assign` | Ops Admin | Assign to surveyor/team |
| PUT | `/api/v1/admin/enquiries/:id/status` | Ops Admin | Update status |

**EnquiryService responsibilities:**

- `initiate()` — resolve city-service availability (422 if service not offered in city); set `inspection_type = expert` (Phase 1); create draft record; do **not** generate `enquiry_number` yet.
- `confirm()` — called after payment confirmation:
  1. Validate `payment_status = Paid` (or ₹0 path in future self-inspection cycle).
  2. Generate `enquiry_number` using counter (§4) — within transaction.
  3. Set `status = New`.
  4. Insert first `project_timeline` row: status `New`, actor System.
  5. Auto-create a `projects` shell record.
  6. Commit.
  7. Fire `EnquiryConfirmed` event → queue: notify Admin + Ops Admin (SMS+Email), generate receipt PDF.

**Phase 1 rule:** `inspection_type` is always `expert`; inspection fee is always ₹1,000. Any attempt to set `inspection_type = self` returns 422 (`Self inspection is not available in this phase`).

---

### 6.2 Enquiry Module (B2B) + Blueprint Builder

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| POST | `/api/v1/enquiries/b2b/initiate` | Customer | Create B2B enquiry shell |
| POST | `/api/v1/enquiries/b2b/blueprint/generate` | Customer | Auto-generate blueprint from form inputs |
| PUT | `/api/v1/enquiries/b2b/blueprint/:id` | Customer | Customise blueprint (draft only) |
| POST | `/api/v1/enquiries/b2b/blueprint/:id/confirm` | Customer | Lock blueprint, attach to enquiry |
| PUT | `/api/v1/enquiries/:id/address` | Customer | Add service address (shared with B2C) |
| POST | `/api/v1/enquiries/:id/payment` | Customer | Same payment stub as B2C |
| POST | `/api/v1/enquiries/:id/confirm` | Customer | Same confirm flow as B2C |

**BlueprintService:**

- `generate()` — compute `total_apertures = towers × floors × flats × windows`. If ≤ 500, build `towers` JSON inline and return 201. If > 500, dispatch `GenerateBlueprintJob` and return 202 `{status: "generating", blueprint_id}`.
- `customise()` — only editable while `status = draft`; 409 otherwise.
- `confirm()` — sets `status = confirmed`; blueprint becomes immutable for customer edits.

---

### 6.3 Survey & Go/No-Go

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| POST | `/api/v1/admin/surveys` | Ops Admin, PM | Create survey record for enquiry |
| GET | `/api/v1/admin/surveys` | Ops Admin, PM | List with filters |
| GET | `/api/v1/admin/surveys/:id` | Ops Admin, PM, Surveyor | Detail |
| PUT | `/api/v1/admin/surveys/:id/checklist` | Surveyor | Submit site checklist (Sections A–E) |
| PUT | `/api/v1/admin/surveys/:id/gonogo` | Surveyor, Ops Admin | Submit Go/No-Go decision |
| POST | `/api/v1/admin/surveys/:id/photos` | Surveyor | Upload site photos |

**SurveyService.submitGoNoGo()** (transactional):
1. Persist `final_outcome` column + `gonogo_matrix` JSON.
2. Write timeline entry (`Survey Passed` or `Survey Rejected`).
3. If `GO`:
   - Auto-advance project status; write timeline `Work Order Created`.
   - Fire `WorkOrderCreated` event → notify Admin + Ops Admin.
4. If `NO-GO`:
   - Set enquiry `status = Cancelled`.
   - Write timeline `Cancelled`.
   - Fire `SurveyRejected` event → notify customer.
5. If `HOLD`:
   - Write timeline entry; notify Admin for manual review; no auto-action.

---

### 6.4 Measurement

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| POST | `/api/v1/admin/measurements` | Surveyor | Create aperture measurement |
| GET | `/api/v1/admin/measurements/:id` | Ops Admin, PM, Surveyor | |
| PUT | `/api/v1/admin/measurements/:id` | Surveyor | Update measurement |
| POST | `/api/v1/admin/measurements/:id/photos` | Surveyor | Upload photos (min 2 → 422 if fewer) |
| PUT | `/api/v1/admin/measurements/:id/approve` | Ops Admin, PM | Approve for manufacturing release |
| POST | `/api/v1/admin/measurements/:id/release` | Ops Admin, PM | Generate manufacturing release sheet |
| GET | `/api/v1/admin/measurements/:id/certificate` | Ops Admin, PM | Download measurement certificate PDF |
| GET | `/api/v1/admin/projects/:id/measurements` | Ops Admin, PM, Surveyor | All measurements for a project |

**Rules:**
- `manufacturing_width/height_mm` are virtual generated columns — computed by DB, never set by application.
- Release is blocked (422) if `approval_status ≠ Approved`.
- Certificate and manufacturing release PDFs are generated via queued jobs; `certificate_url` written back to the row when complete.

---

### 6.5 Project / Execution Tracker

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| GET | `/api/v1/admin/projects` | Ops Admin, PM | List all with filters |
| GET | `/api/v1/admin/projects/:id` | Ops Admin, PM, Supervisor | Detail |
| POST | `/api/v1/admin/projects/:id/assign` | Ops Admin | Assign supervisor and initial team |
| PUT | `/api/v1/admin/projects/:id/status` | Ops Admin, PM, Supervisor | Update status + progress |
| GET/PUT | `/api/v1/admin/projects/:id/details` | Ops Admin, PM, Supervisor | Read/write project_details JSON form |
| PUT | `/api/v1/admin/projects/:id/material` | Ops Admin, Supervisor | Update material_status JSON |
| PUT | `/api/v1/admin/projects/:id/qc` | QC Engineer | Update quality_checks JSON |
| POST/GET | `/api/v1/admin/projects/:id/photos` | Installer, Supervisor | Upload / list execution photos |
| GET | `/api/v1/admin/projects/:id/team` | Ops Admin, PM, Supervisor | Get full team |
| POST | `/api/v1/admin/projects/:id/technicians` | Ops Admin, Supervisor | Add technician |
| DELETE | `/api/v1/admin/projects/:id/technicians/:tid` | Ops Admin, Supervisor | Remove technician |
| GET | `/api/v1/admin/projects/:id/timeline` | Ops Admin, PM, Supervisor | Full timeline ASC |

**ProjectService.updateStatus()** — the core transactional method:

```
1. Validate status transition (must be a permitted forward/backward move).
2. If new status ∈ {Delayed, Due to Dependency}: require delay_reason  →  422 if absent.
3. If new status = Completed:
   - Require QC checks all passed  →  422 if not.
   - Set final_completion_date = TODAY().
4. Recompute progress_percent = ROUND(units_completed / total_units * 100, 2).
5. Save project row.
6. INSERT project_timeline row (same transaction).
7. COMMIT.
8. Fire ProjectStatusChanged event  →  queue email to Admin + Ops Admin.
```

---

### 6.6 Activity Timeline

Read-only API. Writes happen only via the `WritesTimeline` trait called inside other services — **there is no public write endpoint for timeline**.

| Method | Endpoint | Role |
|---|---|---|
| GET | `/api/v1/admin/projects/:id/timeline` | Ops Admin, PM, Supervisor |
| GET | `/api/v1/admin/enquiries/:id/timeline` | Ops Admin, PM |

Results ordered `created_at ASC`. No pagination — the full timeline is always returned (max events per project is bounded and manageable). If growth becomes a concern, cursor pagination is added without breaking the interface.

---

### 6.7 Snag Management

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| POST | `/api/v1/admin/projects/:id/snags` | QC Engineer | Raise a snag |
| GET | `/api/v1/admin/projects/:id/snags` | Ops Admin, PM, QC | List all snags for project |
| GET | `/api/v1/admin/snags/:id` | Ops Admin, QC, Technician | Detail |
| PUT | `/api/v1/admin/snags/:id` | QC, Ops Admin | Update (assign, severity, notes) |
| PUT | `/api/v1/admin/snags/:id/resolve` | Installer, Technician | Mark as resolved + upload photos_after |
| PUT | `/api/v1/admin/snags/:id/close` | QC Engineer | Close after re-inspection |
| GET | `/api/v1/admin/projects/:id/snags/summary` | Ops Admin, PM, QC | Snag counts by severity/status |

**Rules:**
- `snags/close` requires `photos_after` to be present — 422 if absent.
- Handover initiation is blocked (422) while any snag for the unit has `status ≠ Closed`.
- Every snag creation writes a `Snag Raised` timeline entry; every closure writes `Snag Closed`.

---

### 6.8 Handover

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| POST | `/api/v1/admin/projects/:id/handover` | Supervisor, Ops Admin | Initiate handover for a flat/home |
| PUT | `/api/v1/admin/handover/:id/signature` | Supervisor, Ops Admin | Submit signature + rating |
| POST | `/api/v1/admin/handover/:id/generate-cert` | System (auto) | Queue certificate generation |
| GET | `/api/v1/admin/handover/:id/certificate` | Ops Admin, Customer | Download handover cert PDF |
| GET | `/api/v1/admin/handover/:id/warranty` | Ops Admin, Customer | Download warranty cert PDF |

**HandoverService.initiate()** prerequisites gate (422 if any unmet):
1. All apertures in the unit have `qc_status = Passed`.
2. All snags for the unit are `status = Closed`.
3. Installation completion photos are present for all apertures.

On customer sign-off:
1. Capture `digital_signature`, `rating`, `remarks`.
2. Compute `warranty_end_date` from `settings.warranty_period`.
3. Queue two PDF generation jobs (Handover Certificate + Warranty Certificate).
4. When PDFs complete, write `certificate_url` / `warranty_url` back to the row.
5. Queue email to customer with both PDFs attached.
6. Write `Completed` timeline entry in same transaction as status update.

---

### 6.9 Notification Module

**Endpoints:**

| Method | Endpoint | Role |
|---|---|---|
| GET | `/api/v1/admin/notifications/settings` | Super Admin |
| PUT | `/api/v1/admin/notifications/settings` | Super Admin |
| GET | `/api/v1/admin/notifications/templates` | Super Admin |
| PUT | `/api/v1/admin/notifications/templates/:id` | Super Admin |
| GET | `/api/v1/admin/notifications/logs` | Ops Admin, Super Admin |

**Design principle (SRS §8.1):** the gateway provider must be changeable from the admin panel **without code changes**. No provider names are hardcoded in application logic; the `SendSms` / `SendEmail` jobs read `settings.sms_gateway` / `settings.email_gateway` at runtime and instantiate the appropriate driver via a `GatewayFactory`.

**Cycle 1 behaviour:** every domain event fires an app Event → `QueueNotification` listener resolves the template by `event_type` from `notification_templates`, renders placeholders (e.g. `{{customer_name}}`), writes a `notification_logs` row with `status = queued`. The `SendSms` / `SendEmail` job bodies are stubbed (log-only). **No code change** is required in Cycle 2 — only the gateway config row in `settings` needs filling.

**Placeholder rendering:**

```php
// Example placeholder keys per event (stored in template_body.placeholders)
'enquiry_confirmed' => ['{{enquiry_number}}', '{{customer_name}}', '{{service_type}}', '{{inspection_date}}']
'otp_sent'         => ['{{otp}}', '{{expires_in_minutes}}']
'snag_raised'      => ['{{snag_id}}', '{{project_name}}', '{{aperture_id}}', '{{severity}}']
```

---

### 6.10 Service Catalogue

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| GET | `/api/v1/services` | Public | List active services, filtered by `?city=` query param |
| GET | `/api/v1/services/:id` | Public | Service detail |
| POST | `/api/v1/admin/services` | Super Admin | Create |
| PUT | `/api/v1/admin/services/:id` | Super Admin | Update |
| DELETE | `/api/v1/admin/services/:id` | Super Admin | Soft-deactivate |
| POST | `/api/v1/admin/services/:id/cities` | Super Admin | Add city availability |
| DELETE | `/api/v1/admin/services/:id/cities/:city` | Super Admin | Remove city availability |

---

### 6.11 User Management

**Endpoints:**

| Method | Endpoint | Role | Notes |
|---|---|---|---|
| GET | `/api/v1/admin/users` | Super Admin | List internal users |
| POST | `/api/v1/admin/users` | Super Admin | Create user (role assigned at creation) |
| GET | `/api/v1/admin/users/:id` | Super Admin, Ops Admin | |
| PUT | `/api/v1/admin/users/:id` | Super Admin | Update details / role |
| DELETE | `/api/v1/admin/users/:id` | Super Admin | Soft-deactivate (revokes login, preserves FK) |
| GET | `/api/v1/admin/customers` | Ops Admin | List all customers |
| GET | `/api/v1/admin/customers/:id` | Ops Admin | Customer detail + enquiry history |

---

### 6.12 Settings

**Endpoints:**

| Method | Endpoint | Role |
|---|---|---|
| GET | `/api/v1/admin/settings` | Super Admin |
| PUT | `/api/v1/admin/settings/:key` | Super Admin |

`gateway_config` JSON is stored encrypted at rest (use Laravel's `encrypted` cast or application-level encryption) — no API keys visible in plaintext in the DB (NFR §19.2).

---

### 6.13 Reports / MIS (Cycle 1 subset)

All reports are **read-only**. Aggregations run over **column data only** — never `JSON_EXTRACT` in `WHERE` clauses on hot paths. Heavy exports run as **queued jobs** and are returned as file download links.

| Report | Endpoint | Format |
|---|---|---|
| Measurement Certificate | GET `/admin/measurements/:id/certificate` | PDF |
| Manufacturing Release Sheet | GET `/admin/measurements/:id/release` | PDF |
| Project progress (multi-level) | GET `/admin/projects/:id/report` | JSON + Excel export |
| Snag summary | GET `/admin/projects/:id/snags/summary` | JSON |
| Daily installation summary | GET `/admin/reports/installation/daily` | JSON + Excel |
| QC inspection report | GET `/admin/reports/qc` | JSON + PDF |
| Handover certificate | GET `/admin/handover/:id/certificate` | PDF |
| Warranty certificate | GET `/admin/handover/:id/warranty` | PDF |

Financial reports (Invoice, Payment, Outstanding, GST) are **Cycle 2**.

---

## 7. Key Sequence Flows

### 7.1 Customer OTP login / register

```
Client ──POST /auth/send-otp──► OtpService
                                 ├─ check lockout (locked_until > now? 429)
                                 ├─ check rate (count last 1hr ≥ 3? 429)
                                 ├─ store hashed OTP, expires in 5 min
                                 └─ queue SendSms (Cycle 1: log) ──► 200

Client ──POST /auth/verify-otp──► OtpService
                                   ├─ validate hash, expiry, single-use
                                   ├─ on fail: increment failed_attempts; 5 fails → lock 15min
                                   ├─ on pass: consume OTP; upsert customer
                                   └─ JwtService.issue(access 24h, refresh 30d) ──► 200 {tokens}
```

### 7.2 B2C enquiry → project creation

```
initiate (draft) → address → payment stub (→ Paid) → confirm
                                                         │
                                              EnquiryService.confirm() [DB TX]
                                                 │
                                                 ├─ acquire counter lock → generate enquiry_number
                                                 ├─ set status = New
                                                 ├─ INSERT project_timeline {New, System}
                                                 ├─ create projects shell
                                                 └─ COMMIT
                                                         │
                                                 event EnquiryConfirmed
                                                   ├─ queue notify Admin + Ops Admin
                                                   └─ queue GenerateReceiptPdf
```

### 7.3 Survey → execution → handover

```
Admin assigns surveyor ──► timeline 'Surveyor Assigned' ──► notify Surveyor

Surveyor submits Go/No-Go
  ├─ GO ──► [TX] timeline 'Survey Passed' + 'Work Order Created' ──► event WorkOrderCreated
  │          │                                                           └─ notify Admin + Ops Admin
  │          └─ OM assigns team ──► timeline 'Team Assigned' ──► notify Supervisor
  │
  └─ NO-GO ──► [TX] enquiry Cancelled + timeline 'Survey Rejected' ──► notify Customer

Project status updates (On Track → At Risk → Delayed → Completed)
  └─ each: [TX] project row + timeline entry ──► event ──► notify OM + Admin

QC Engineer inspects
  ├─ all pass ──► timeline 'QC Approved'
  └─ fail ──► raise snag ──► timeline 'Snag Raised' ──► notify Technician
               close snag ──► timeline 'Snag Closed'

All snags Closed + QC Passed ──► Handover.initiate()
  ├─ customer sign-off
  └─ [TX] Completed timeline + handover record
     └─ queue: GenerateHandoverCert + GenerateWarrantyCert + email Customer
```

---

## 8. Validation & Business Rules Catalogue

| Rule | Service | HTTP code |
|---|---|---|
| OTP: 6-digit, 5-min expiry, single-use | OtpService | 422 |
| OTP resend ≤ 3 per hour per phone | OtpService | 429 |
| OTP 5 failed attempts → 15-min lockout | OtpService | 429 |
| Phone number unique (customer upsert, not duplicate) | CustomerService | 409 |
| Phase 1: inspection_type must be `expert` | EnquiryService | 422 |
| Enquiry number generated on confirm, not initiate | EnquiryService | — |
| Payment required before confirm (Cycle 1 stub accepts Paid) | EnquiryService | 422 |
| Blueprint editable only in draft status | BlueprintService | 409 |
| Measurement min 2 photos per aperture | MeasurementService | 422 |
| Release blocked until measurement is Approved | MeasurementService | 422 |
| Delayed/Dependency status requires delay_reason | ProjectService | 422 |
| Completed status requires QC all passed | ProjectService | 422 |
| Timeline rows are insert-only; update/delete throws | ProjectTimeline model | — |
| Every status change writes timeline in same transaction | All status-mutating services | — |
| Snag close requires photos_after | SnagService | 422 |
| Handover blocked while any snag ≠ Closed | HandoverService | 422 |
| Handover blocked while any aperture QC ≠ Passed | HandoverService | 422 |
| Role/ownership scoping | Policies | 403 |
| Missing or invalid JWT | JwtAuth middleware | 401 |
| Valid JWT but insufficient role | RoleGuard / Policy | 403 |

---

## 9. Error Handling & Response Envelope

### 9.1 Standard envelope (every response — success and error)

```json
{ "success": true,  "data": { },                                      "message": "OK"                }
{ "success": false, "data": { "errors": { "field": ["message"] } },   "message": "Validation failed" }
{ "success": false, "data": null,                                      "message": "Unauthorised"      }
```

### 9.2 Exception → HTTP code map

| Exception class | HTTP | Scenario |
|---|---|---|
| `ValidationException` | 422 | FormRequest validation failure |
| `BusinessRuleException` | 422 | Domain rule violation (e.g. insufficient photos) |
| `AuthenticationException` | 401 | Missing/invalid/expired JWT |
| `AuthorizationException` | 403 | Valid token but insufficient permission |
| `ModelNotFoundException` | 404 | Resource not found |
| `ConflictException` | 409 | Duplicate entry |
| `RateLimitException` | 429 | OTP rate limit or API throttle |
| Any uncaught `Throwable` | 500 | Unexpected server error (safe error message only; no stack trace in response) |

The `Handler::render()` method intercepts all exceptions and formats them through the envelope. Raw Laravel error pages are never returned from the API (`ForceJson` middleware sets `Accept: application/json` on every request).

---

## 10. File Storage Design

All binary files are stored on the **cloud file server — not in the database**. The database stores only the path/URL reference string.

| Asset | Referenced from | Path convention |
|---|---|---|
| Enquiry receipt PDF | `enquiries.receipt_url` | `enquiries/{id}/receipt_{ts}.pdf` |
| Survey site photos | `surveys.photos` (JSON array) | `surveys/{id}/photos/{ts}_{name}` |
| Measurement aperture photos (min 2) | `measurements.photos` (JSON array) | `projects/{pid}/aperture/{aid}/{ts}_{name}` |
| Execution / installation photos | project photos table | `projects/{pid}/execution/{ts}_{name}` |
| Snag photos (before / after) | `snags.photos_before/after` | `projects/{pid}/snags/{sid}/{ts}_{name}` |
| Handover / warranty certificates (PDF) | `handovers.certificate_url/warranty_url` | `projects/{pid}/handover/...pdf` |

**Storage abstraction:** Laravel `Storage` facade with a named disk (`cloud`). Cycle 1 can use the Hostinger local disk or any S3-compatible provider; switching requires changing only the `filesystems.php` config — no application code change.

**Accepted formats:** JPG, PNG (photos); PDF (documents). Max file size: **10 MB per file** (NFR §19.5, to be confirmed by engineering).

**Security:** pre-signed / time-limited URLs for download links; no PII in URL path segments or query strings (NFR §19.2).

---

## 11. Indexing & Performance

- **Indexes on every FK column** (required by MySQL for FK constraint performance and JOIN speed).
- **Status/enum columns** that appear in `WHERE` or dashboard GROUP BY clauses all carry an index.
- **Date columns** (`expected_start_date`, `planned_completion_date`, `booking_date`, `created_at`) indexed for timeline and SLA queries.
- **`project_timeline`** composite index on `(project_id, created_at)` for ordered log reads.
- **`project_timeline`** composite index on `(enquiry_id, created_at)` for pre-project event reads.
- **JSON columns are never in `WHERE` clauses on hot paths.** Any JSON sub-field that needs filtering is promoted to a dedicated column (e.g. `surveys.final_outcome`, `projects.status`) or a **MySQL virtual generated column + index** (e.g. `manufacturing_width_mm`).
- **`progress_percent`** stored as a column and updated on each status write — dashboard reads never compute it on-the-fly.
- **Blueprint generation** for large projects (>500 apertures) is queued to avoid blocking the HTTP request (NFR §19.3: must support 10,000 apertures).
- **PDF generation** always runs in a background job — HTTP response is never blocked waiting for PDF output.
- Target: p95 < 500ms standard endpoints, < 3s report endpoints (SRS NFR §19.1).

---

## 12. Cycle 2 Placeholders

These hooks are designed into the Cycle 1 schema so Cycle 2 features add without breaking changes:

| Cycle 2 Feature | Hook designed in Cycle 1 |
|---|---|
| Quotation | `enquiries.quotation_id` nullable FK; `quotation_pending` state reserved in enquiry status ENUM; timeline statuses `Quotation Generated` / `Quotation Approved` in enum |
| Payment gateway | `payment_*` columns present; `POST /enquiries/:id/payment` endpoint exists as stub; gateway config row in `settings.payment_gateway` |
| SMS / Email dispatch | `notification_templates` and `notification_logs` tables complete; `SendSms` / `SendEmail` job bodies are stubbed — only job internals change in Cycle 2 |
| Self Inspection | `inspection_type ENUM` already has `self` value; no schema change needed; just implement the flow and remove the Phase 1 422 guard |
| Invoice / Billing | New `invoices` table; FK from `projects`; no existing table changes |
| AMC | New `amc_contracts` and `amc_visits` tables; FK from `customers` and `projects` |
| Partner portal | `users.role` ENUM already has `Builder/Fabricator`; portal auth and scoping logic added in Cycle 2 |
| Native app (Flutter) | API is the contract; zero server-side change required |

---

## 13. Open Items (from SRS §21.1 — must be resolved before schema lock)

| # | Item | Blocks |
|---|---|---|
| 1 | SMS gateway provider (MSG91 recommended) | `settings.sms_gateway` config values; SendSms job driver |
| 2 | Payment gateway provider (Razorpay recommended) | `settings.payment_gateway`; payment stub → live |
| 3 | B2B inspection fee (same ₹1,000 as B2C, or different?) | `enquiries.inspection_fee` default value |
| 4 | Can a customer hold multiple active enquiries simultaneously? | EnquiryService validation rule |
| 5 | Photo file size limit and accepted formats | FormRequest validation in photo upload endpoints |
| 6 | Warranty period (months) | `settings.warranty_period` seed value; `handovers.warranty_end_date` calculation |
| 7 | Snag resolution SLAs by severity (High/Medium/Low) | `snags.due_date` calculation logic |
| 8 | Public holidays for the 3-business-day inspection timeline | `settings.business_hours` seed data |
| 9 | Super Admin 2FA mechanism (TOTP app vs SMS) | Auth implementation detail |

---

*— End of Low Level Design v1.0 —*