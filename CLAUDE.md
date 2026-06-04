# FixMyWindow (FMW) — Claude Code Project Context

> Read this file completely before writing any code, creating any file, or running any command.
> Every session starts here. Do not assume anything not stated in this file.

---

## 1. Project Overview

FixMyWindow (FMW) is a window installation and repair service platform for the Indian market.
The backend is a RESTful JSON API powering two surfaces: a customer-facing app (B2C + B2B) and
an admin panel for internal operations staff.

- **Authoritative spec:** `docs/LLD.md` (derived from SRS v1.0, June 2026)
- **Development cycle:** Cycle 1 (current) — core platform. Cycle 2 — quotation, payment, gateways.
- **Fresh build:** No legacy code. No migration from a prior system.

---

## 2. Tech Stack & Exact Packages

| Layer | Technology | Package / Version |
|---|---|---|
| Framework | Laravel 11 | `laravel/framework ^11.0` |
| PHP | 8.2+ | PHP 8.1 enums are used throughout |
| Database | MySQL 8.0+ | Use `JSON` columns, not `JSONB` (this is MySQL, not PostgreSQL) |
| Authentication | JWT | `tymon/jwt-auth ^2.0` |
| Queue driver | Database (Hostinger shared) | `QUEUE_CONNECTION=database` in `.env` |
| File storage | Local disk / S3-compatible | Laravel `Storage` facade, disk name: `cloud` |
| PDF generation | DomPDF | `barryvdh/laravel-dompdf ^2.0` |
| Testing | PHPUnit via Laravel | `php artisan test` |

**Do not introduce packages not listed here without asking first.**

---

## 3. HTTP Contract

### 3.1 Request Headers (every API request must send these)

```
Content-Type: application/json
Accept:       application/json
```

For authenticated endpoints, also send:

```
Authorization: Bearer <access_token>
```

### 3.2 JWT Token Delivery

- Access token is sent by the client in the `Authorization: Bearer <token>` header only.
- **Never** in query string. **Never** in cookie (unless explicitly specified for refresh token).
- Refresh token: stored client-side in secure storage; sent in the request body as `refresh_token`.
- The `ForceJson` middleware sets `Accept: application/json` server-side so Laravel returns JSON errors automatically.

### 3.3 Token Specification

| Token | Expiry | Config key |
|---|---|---|
| Access token | 24 hours | `JWT_TTL=1440` (minutes) in `.env` |
| Refresh token | 30 days | `JWT_REFRESH_TTL=43200` (minutes) in `.env` |

### 3.4 Standard Response Envelope

**Every single response — success and error — must use this exact envelope.**
The `ResponseEnvelope` middleware handles wrapping automatically.
Controllers return plain arrays or API Resources; they do not construct the envelope manually.

```json
// Success
{
  "success": true,
  "data": { },
  "message": "OK"
}

// Validation failure (422 from FormRequest)
{
  "success": false,
  "data": { "errors": { "field_name": ["Error message."] } },
  "message": "Validation failed"
}

// Business rule violation (422 from BusinessRuleException)
{
  "success": false,
  "data": null,
  "message": "Self inspection is not available in this phase"
}

// Unauthorised (401)
{
  "success": false,
  "data": null,
  "message": "Unauthenticated"
}

// Forbidden (403)
{
  "success": false,
  "data": null,
  "message": "Forbidden"
}

// Not found (404)
{
  "success": false,
  "data": null,
  "message": "Resource not found"
}
```

### 3.5 HTTP Status Codes

| Code | When |
|---|---|
| 200 | Successful GET, PUT |
| 201 | Successful POST (resource created) |
| 400 | Malformed request |
| 401 | Missing, invalid, or expired JWT token |
| 403 | Valid token but insufficient role permissions |
| 404 | Resource does not exist |
| 409 | Conflict — duplicate entry |
| 422 | Validation error OR business rule violation |
| 429 | Rate limit exceeded (OTP resend, API throttle) |
| 500 | Unexpected server error — return safe message only, never stack trace |

### 3.6 Pagination

All list endpoints accept these query parameters:

```
?page=1&limit=20&sort=created_at&order=desc
```

| Param | Default | Max |
|---|---|---|
| page | 1 | — |
| limit | 20 | 100 |
| sort | created_at | any column |
| order | desc | asc \| desc |

Paginated responses always include metadata:

```json
{
  "success": true,
  "data": {
    "items": [ ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 150,
      "last_page": 8
    }
  },
  "message": "OK"
}
```

### 3.7 API Route Prefix

All routes: `/api/v1/`
Customer routes: `/api/v1/` (e.g. `/api/v1/auth/send-otp`, `/api/v1/enquiries`)
Admin routes: `/api/v1/admin/` (e.g. `/api/v1/admin/projects`)

---

## 4. Architecture & Layers

### 4.1 Request Lifecycle

```
Request
  → ForceJson middleware        (sets Accept: application/json)
  → ResponseEnvelope middleware (wraps response into {success, data, message})
  → auth:jwt middleware         (validates Bearer token — on protected routes)
  → role:RoleName middleware    (RBAC check — on admin routes)
  → throttle middleware
  → Controller
      → FormRequest             (validate + authorize)
      → Service                 (ALL business logic lives here)
          → Repository          (ALL database access lives here)
              → Model           (Eloquent — relationships, casts, guards only)
          → Event (optional)    (fired for side effects)
              → Listener        (queues a Job)
                  → Job         (async: PDF, notification, blueprint gen)
      → API Resource            (shapes the response data)
  → ResponseEnvelope wraps and returns
```

### 4.2 Layer Rules (strictly enforced)

**Controllers** — thin. No `if`, no DB calls, no business logic.
Allowed: call one Service method, return a Resource or array.

**FormRequests** — all input validation AND route-level authorization.
Use `authorize()` to check ownership (e.g. customer owns this enquiry).

**Services** — all business logic, all transactions, all event dispatching.
One service class per module (e.g. `EnquiryService`, `ProjectService`).
Injected via constructor with the relevant Repository interfaces.

**Repositories** — all Eloquent queries. Never call Eloquent directly in Services.
Interface + implementation: `EnquiryRepositoryInterface` → `EloquentEnquiryRepository`.
Bound in `AppServiceProvider`.

**Models** — `$fillable`, `$casts`, `$hidden`, relationships, boot guards.
No business logic in models. Exception: `project_timeline` model has a `booted()` guard
that throws `\LogicException('Timeline is append-only.')` on any update() or delete().

**API Resources** — shape response data. Use Resource classes, not `->toArray()` inline.

### 4.3 Directory Structure

```
app/
├─ Http/
│   ├─ Controllers/
│   │   ├─ Customer/   (AuthController, EnquiryController, BlueprintController, ProfileController)
│   │   └─ Admin/      (AuthController, UserController, ServiceController, EnquiryController,
│   │                   SurveyController, MeasurementController, ProjectController,
│   │                   SnagController, HandoverController, NotificationController,
│   │                   SettingsController, ReportController)
│   ├─ Requests/       (one FormRequest per write operation — e.g. SendOtpRequest)
│   ├─ Resources/      (one Resource per entity — e.g. EnquiryResource, ProjectResource)
│   └─ Middleware/     (ForceJson, ResponseEnvelope, RoleGuard)
├─ Services/
├─ Repositories/
│   ├─ Interfaces/
│   └─ Eloquent/
├─ Models/
├─ Events/
├─ Listeners/
├─ Jobs/
├─ Policies/
└─ Support/
    ├─ Enums/          (PHP 8.1 backed enums)
    └─ Concerns/       (traits: WritesTimeline, HasEnquiryNumber)
```

---

## 5. Database Conventions

### 5.1 General rules

- All PKs: `BIGINT UNSIGNED AUTO_INCREMENT` named `id`.
- All FKs: `BIGINT UNSIGNED` with index and `->constrained()` in migrations.
- Soft delete (`deleted_at`) on: `users`, `services`, `service_categories`. Never on `project_timeline`.
- All timestamps: UTC. Use `TIMESTAMP` type.
- Monetary values: `INT` (INR rupees). **No decimals. No floats.**
- PHP 8.1 enums are used for `$casts` in models. Register them in `Enums/` directory.

### 5.2 JSON column rules (MySQL 8.0)

- Use `JSON` type — **not JSONB** (PostgreSQL syntax — will fail on MySQL).
- In models, cast JSON columns with `'column' => 'array'` or a custom cast class.
- **Never use JSON columns in WHERE clauses on hot paths.**
- If a JSON sub-field needs filtering, it must be promoted to a real column or a virtual generated column.
- Virtual generated columns syntax for MySQL:

```sql
// In migration:
$table->integer('manufacturing_width_mm')
    ->virtualAs('opening_width_mm - deduction_width_mm');
```

### 5.3 JSON columns in this project

These columns are always read and written as a complete unit. Never partially update them.

| Table | JSON Column | Contents |
|---|---|---|
| `projects` | `material_status` | Units received, damage breakdown by part |
| `projects` | `quality_checks` | 6-item QC checklist (done/total counts + status enums) |
| `projects` | `project_details` | Full 10-section project details form |
| `surveys` | `client_details` | Section A — project & client details |
| `surveys` | `gonogo_matrix` | Go/No-Go 10-item matrix rows (Yes/No/TBD) |
| `surveys` | `feasibility` | Section C — installation feasibility |
| `surveys` | `opening_data` | Section D — per-aperture opening survey array |
| `surveys` | `risk_register` | Section E — risks with severity and mitigation |
| `blueprints` | `towers` | Auto-generated tower→floor→flat→aperture hierarchy |
| `self_inspections` | `window_details` | Dynamic array of N window records |
| `self_inspections` | `site_conditions` | Access conditions + site facilities |
| `notification_templates` | `template_body` | subject, sms_body, email_body, placeholders array |
| `settings` | `gateway_config` | Provider-specific credentials (varies per provider) |

### 5.4 Indexing rules

Create indexes on:
- Every FK column
- Every `status` / ENUM column used in WHERE or GROUP BY
- Every `date` / `created_at` column used for range queries
- Composite index on `project_timeline (project_id, created_at)` — used for ordered reads
- Composite index on `project_timeline (enquiry_id, created_at)`
- Composite index on `otp_requests (phone, created_at)` — used for rate-limit count

---

## 6. Authentication & RBAC

### 6.1 Role enum values (PHP 8.1 backed enum: `App\Support\Enums\Role`)

```php
enum Role: string {
    case SuperAdmin        = 'super_admin';
    case OpsAdmin          = 'ops_admin';
    case ProjectManager    = 'project_manager';
    case Surveyor          = 'surveyor';
    case Installer         = 'installer';
    case QcEngineer        = 'qc_engineer';
    case Accounts          = 'accounts';
    case BuilderFabricator = 'builder_fabricator';  // Cycle 2
    case Customer          = 'customer';             // separate customers table
}
```

### 6.2 Route protection

```php
// Public routes (no auth)
Route::post('/auth/send-otp', ...);
Route::post('/auth/verify-otp', ...);
Route::get('/services', ...);

// Customer protected routes
Route::middleware(['auth:api'])->group(function () {
    Route::post('/auth/logout', ...);
    Route::get('/enquiries', ...);
    // etc.
});

// Admin protected routes
Route::prefix('admin')->middleware(['auth:api', 'role:super_admin,ops_admin'])->group(function () {
    // etc.
});
```

### 6.3 `RoleGuard` middleware

Accepts comma-separated role values. Checks `auth()->user()->role` against the allowed list.
Returns 403 envelope if the role is not in the list.

### 6.4 Ownership checks

Done inside `FormRequest::authorize()`, not in middleware.
Example: customer can only access their own enquiry.

```php
// In EnquiryFormRequest::authorize()
$enquiry = Enquiry::findOrFail($this->route('id'));
return $enquiry->customer_id === auth()->id();
```

---

## 7. Critical Business Rules

Read these before implementing any module. These are the rules Copilot and Claude most commonly get wrong.

### 7.1 OTP (Customer Auth)

- OTP is **6 digits**, numeric only.
- Valid for **5 minutes** from generation.
- **Single-use** — `consumed_at` is set on first successful verification; subsequent use is rejected.
- Max **3 resend attempts per hour per phone** — count `otp_requests WHERE phone=? AND created_at > NOW()-1hr`.
- Max **5 failed verifications** → lock phone for 15 minutes (`locked_until = NOW()+15min`).
- OTP is **stored as a bcrypt hash** — never plaintext.
- On `verify-otp` success: upsert the customer (creates if new, retrieves if existing). This is both login and registration.

### 7.2 Enquiry number generation

- Format: `FMW-YYYYMMDD-XXXX` where XXXX is a global 4-digit zero-padded counter.
- Counter lives in `enquiry_counter` table (single row: `id=1, last_value BIGINT`).
- **NEVER use `MAX()` or `COUNT()` for the counter** — race condition under concurrent requests.
- **ALWAYS use `lockForUpdate()`** inside `DB::transaction()`:

```php
DB::transaction(function () {
    DB::table('enquiry_counter')
        ->where('id', 1)
        ->lockForUpdate()
        ->update(['last_value' => DB::raw('last_value + 1')]);

    $n = DB::table('enquiry_counter')->where('id', 1)->value('last_value');
    $number = sprintf('FMW-%s-%04d', now('UTC')->format('Ymd'), $n);
    // ... create enquiry with $number
});
```

- **Generated on `confirm()` only — NOT on `initiate()`.**
  Draft/abandoned enquiries must not consume counter values.

### 7.3 Phase 1 inspection rule

- `inspection_type` is always `expert` in Phase 1.
- If a request submits `inspection_type = 'self'`, return 422:
  `"Self inspection is not available in this phase."`
- The `inspection_type` column and the `self` enum value exist in the schema for Cycle 2 readiness.
- Do NOT remove the column or enum value. Just gate it.

### 7.4 Project timeline — APPEND-ONLY

This is the most important rule in the entire system.

- `project_timeline` rows are NEVER updated or deleted. Ever.
- The model `booted()` method must throw `\LogicException` on update/delete attempts:

```php
protected static function booted(): void
{
    static::updating(fn() => throw new \LogicException('project_timeline is append-only.'));
    static::deleting(fn() => throw new \LogicException('project_timeline is append-only.'));
}
```

- **Every status change that touches a project or enquiry must write a timeline row in the same `DB::transaction()`.**
- Never write a status change and a timeline row as two separate calls.
- The correct pattern:

```php
DB::transaction(function () use ($project, $status, $actor) {
    $project->update(['status' => $status]);          // step 1

    ProjectTimeline::create([                          // step 2 — same transaction
        'project_id'  => $project->id,
        'status'      => $status,
        'description' => '...',
        'actor_type'  => $actor->roleLabel(),
        'actor_id'    => $actor->id,
        'actor_name'  => $actor->name,                // denormalised — always snapshot
    ]);
});
```

- `actor_name` is always written as a snapshot string at event time. Never look it up by JOIN later.
- System-initiated entries: `actor_id = null`, `actor_name = 'System'`.

### 7.5 Status change requires timeline — full list of triggers

| Event | Timeline status label |
|---|---|
| Enquiry confirmed | `New` |
| Surveyor assigned | `Surveyor Assigned` |
| Go/No-Go = GO | `Survey Passed` + `Work Order Created` (two entries, same TX) |
| Go/No-Go = NO-GO | `Survey Rejected` |
| Inspection scheduled | `Inspection Scheduled` |
| Inspection complete | `Inspection Completed` |
| Team assigned | `Team Assigned` |
| Work started | `Work In Progress` |
| Status → On Track | `On Track` |
| Status → At Risk | `At Risk` |
| Status → Delayed | `Delayed` |
| Status → Due to Dependency | `Due to Dependency` |
| Snag raised | `Snag Raised` |
| Snag closed | `Snag Closed` |
| QC passed | `QC Approved` |
| Handover complete | `Completed` |
| Cancelled | `Cancelled` |

### 7.6 Delayed/Dependency status

- `delay_reason` is required (non-null) when status is `Delayed` or `Due to Dependency`.
- Return 422 if `delay_reason` is absent.

```php
enum DelayReason: string {
    case VisibleTransitionDamage = 'visible_transition_damage';
    case SiteNotReady            = 'site_not_ready';
    case CivilWorkIncomplete     = 'civil_work_incomplete';
    case MissingHardware         = 'missing_hardware';
    case DamagedGlassReceived    = 'damaged_glass_received';
    case ClientChangeRequest     = 'client_change_request';
    case WindowMissingParts      = 'window_missing_parts';
    case GlassDamaged            = 'glass_damaged';
    case CustomerDesignChange    = 'customer_design_change';
    case WeatherIssue            = 'weather_issue';
}
```

### 7.7 Handover prerequisites

Handover cannot be initiated (422) unless ALL of these are true:
1. All apertures for the unit have QC status = Passed.
2. All snags for the unit have status = Closed.
3. Installation completion photos are uploaded for all apertures.

### 7.8 Snag close requires photos

A snag cannot be closed (422) unless `photos_after` array is present and non-empty.

### 7.9 Measurement release gate

Manufacturing release cannot be generated (422) unless `approval_status = 'Approved'`.
Measurement must be Approved by Ops Admin or Project Manager before release.

### 7.10 Blueprint edit gate

Blueprint `towers` JSON can only be edited while `status = 'draft'`.
Return 409 if edit is attempted on a non-draft blueprint.

### 7.11 Completed status gate

Project status cannot be set to `Completed` (422) unless all QC checks have passed.
On setting Completed: `final_completion_date = today()` is auto-set.

### 7.12 progress_percent calculation

Always: `ROUND((units_completed / total_units) * 100, 2)`
Stored as a column — never computed on-the-fly in queries.
Recalculate and save this column on every call to `ProjectService::updateStatus()`.

---

## 8. Transactions — Required Patterns

Any operation that involves more than one table write must use `DB::transaction()`.
Never write to two tables sequentially without a transaction.

### Operations that MUST be transactional

| Operation | Tables written |
|---|---|
| Enquiry confirm | enquiries + enquiry_counter + project_timeline + projects |
| Any project status change | projects + project_timeline |
| Survey Go/No-Go decision | surveys + projects + project_timeline (×2 on GO) |
| Snag creation | snags + project_timeline |
| Snag closure | snags + project_timeline |
| Handover sign-off | handovers + projects + project_timeline |

---

## 9. Events, Listeners & Jobs

### 9.1 Naming convention

| Type | Example | When fired |
|---|---|---|
| Event | `EnquiryConfirmed` | After enquiry confirm TX commits |
| Event | `ProjectStatusChanged` | After status update TX commits |
| Event | `SurveyDecisionRecorded` | After Go/No-Go TX commits |
| Event | `HandoverCompleted` | After handover sign-off TX commits |
| Listener | `WriteTimelineEntry` | Synchronous — handles timeline writes inside TX |
| Listener | `QueueNotificationJob` | Async — queues `SendNotificationJob` |
| Listener | `QueuePdfGeneration` | Async — queues `GeneratePdfJob` |

**Fire events AFTER the transaction commits** — use `DB::afterCommit()` or dispatch after `DB::transaction()` closes.
Do not fire events inside transactions unless the listener is synchronous and must be part of the TX.

### 9.2 Notification jobs (Cycle 1 behaviour)

`SendNotificationJob` (queued):
1. Load template from `notification_templates` by `event_type`.
2. If `is_enabled = false` — exit silently.
3. Render placeholders: replace `{{key}}` with values from the job payload.
4. Insert a `notification_logs` row with `status = 'queued'`.
5. **Cycle 1: log only. Do not actually send.** Leave the gateway dispatch code commented with `// TODO: Cycle 2 — wire gateway from settings.sms_gateway / settings.email_gateway`.

---

## 10. File Storage

- All binary files (photos, PDFs) go on the **cloud storage disk** — never in the database.
- The database stores only the URL/path string.
- Storage disk name: `cloud` (configured in `config/filesystems.php`).
- Cycle 1 local disk is fine; switching to S3 later requires only `filesystems.php` config change.

### Path conventions

```
enquiries/{id}/receipt_{timestamp}.pdf
surveys/{survey_id}/photos/{timestamp}_{filename}
projects/{project_id}/aperture/{aperture_id}/{timestamp}_{filename}
projects/{project_id}/execution/{timestamp}_{filename}
projects/{project_id}/snags/{snag_id}/before_{timestamp}_{filename}
projects/{project_id}/snags/{snag_id}/after_{timestamp}_{filename}
projects/{project_id}/handover/certificate_{timestamp}.pdf
projects/{project_id}/handover/warranty_{timestamp}.pdf
```

**Accepted formats:** JPG, PNG (photos), PDF (documents).
**Max file size:** 10MB per file.
**Never put PII in URL path segments or query strings.**

---

## 11. Enums Reference

Define all enums as PHP 8.1 backed string enums in `app/Support/Enums/`.

```php
// EnquiryStatus.php
enum EnquiryStatus: string {
    case New        = 'new';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
}

// ProjectStatus.php
enum ProjectStatus: string {
    case OnTrack         = 'on_track';
    case AtRisk          = 'at_risk';
    case Delayed         = 'delayed';
    case DueToDependency = 'due_to_dependency';
    case Completed       = 'completed';
    case Cancelled       = 'cancelled';
}

// InspectionType.php
enum InspectionType: string {
    case Expert = 'expert';
    case Self   = 'self';   // Cycle 2 — exists for schema readiness, gated in Phase 1
}

// BlueprintStatus.php
enum BlueprintStatus: string {
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Surveyed  = 'surveyed';
    case Locked    = 'locked';
}

// PaymentStatus.php
enum PaymentStatus: string {
    case Pending = 'pending';
    case Paid    = 'paid';
    case Failed  = 'failed';
}

// SurveyOutcome.php
enum SurveyOutcome: string {
    case Go    = 'go';
    case Hold  = 'hold';
    case NoGo  = 'no_go';
}

// SnagStatus.php
enum SnagStatus: string {
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';
    case Escalated  = 'escalated';
}

// SnagSeverity.php
enum SnagSeverity: string {
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';
}

// NotificationChannel.php
enum NotificationChannel: string {
    case Sms   = 'sms';
    case Email = 'email';
    case Both  = 'both';
}

// TimelineActorType.php
enum TimelineActorType: string {
    case System         = 'system';
    case Admin          = 'admin';         // legacy; map to super_admin internally
    case OpsAdmin       = 'ops_admin';
    case ProjectManager = 'project_manager';
    case Surveyor       = 'surveyor';
    case Installer      = 'installer';
    case QcEngineer     = 'qc_engineer';
    case Customer       = 'customer';
}
```

---

## 12. Module Endpoint Reference

Use this table to confirm routes. Do not invent endpoint paths.

### Customer auth
```
POST   /api/v1/auth/send-otp
POST   /api/v1/auth/verify-otp
POST   /api/v1/auth/refresh-token
POST   /api/v1/auth/logout
```

### Admin auth
```
POST   /api/v1/admin/auth/login
POST   /api/v1/admin/auth/logout
POST   /api/v1/admin/auth/refresh-token
POST   /api/v1/admin/auth/change-password
```

### Service catalogue (public read, admin write)
```
GET    /api/v1/services
GET    /api/v1/services/{id}
POST   /api/v1/admin/services
PUT    /api/v1/admin/services/{id}
DELETE /api/v1/admin/services/{id}
POST   /api/v1/admin/services/{id}/cities
DELETE /api/v1/admin/services/{id}/cities/{city}
```

### User management (admin only)
```
GET    /api/v1/admin/users
POST   /api/v1/admin/users
GET    /api/v1/admin/users/{id}
PUT    /api/v1/admin/users/{id}
DELETE /api/v1/admin/users/{id}
GET    /api/v1/admin/customers
GET    /api/v1/admin/customers/{id}
```

### Enquiry — B2C (customer)
```
POST   /api/v1/enquiries/initiate
PUT    /api/v1/enquiries/{id}/address
POST   /api/v1/enquiries/{id}/payment
POST   /api/v1/enquiries/{id}/confirm
GET    /api/v1/enquiries
GET    /api/v1/enquiries/{id}
```

### Enquiry — B2B + Blueprint (customer)
```
POST   /api/v1/enquiries/b2b/initiate
POST   /api/v1/enquiries/b2b/blueprint/generate
PUT    /api/v1/enquiries/b2b/blueprint/{id}
POST   /api/v1/enquiries/b2b/blueprint/{id}/confirm
```

### Enquiry — admin view
```
GET    /api/v1/admin/enquiries
GET    /api/v1/admin/enquiries/{id}
PUT    /api/v1/admin/enquiries/{id}/assign
PUT    /api/v1/admin/enquiries/{id}/status
GET    /api/v1/admin/enquiries/{id}/timeline
```

### Survey & Go/No-Go
```
POST   /api/v1/admin/surveys
GET    /api/v1/admin/surveys
GET    /api/v1/admin/surveys/{id}
PUT    /api/v1/admin/surveys/{id}/checklist
PUT    /api/v1/admin/surveys/{id}/gonogo
POST   /api/v1/admin/surveys/{id}/photos
```

### Measurement
```
POST   /api/v1/admin/measurements
GET    /api/v1/admin/measurements/{id}
PUT    /api/v1/admin/measurements/{id}
POST   /api/v1/admin/measurements/{id}/photos
PUT    /api/v1/admin/measurements/{id}/approve
POST   /api/v1/admin/measurements/{id}/release
GET    /api/v1/admin/measurements/{id}/certificate
GET    /api/v1/admin/projects/{id}/measurements
```

### Projects / Execution Tracker
```
GET    /api/v1/admin/projects
GET    /api/v1/admin/projects/{id}
POST   /api/v1/admin/projects/{id}/assign
PUT    /api/v1/admin/projects/{id}/status
GET    /api/v1/admin/projects/{id}/details
PUT    /api/v1/admin/projects/{id}/details
PUT    /api/v1/admin/projects/{id}/material
PUT    /api/v1/admin/projects/{id}/qc
POST   /api/v1/admin/projects/{id}/photos
GET    /api/v1/admin/projects/{id}/photos
GET    /api/v1/admin/projects/{id}/team
POST   /api/v1/admin/projects/{id}/technicians
DELETE /api/v1/admin/projects/{id}/technicians/{tid}
GET    /api/v1/admin/projects/{id}/timeline
```

### Snag management
```
POST   /api/v1/admin/projects/{id}/snags
GET    /api/v1/admin/projects/{id}/snags
GET    /api/v1/admin/projects/{id}/snags/summary
GET    /api/v1/admin/snags/{id}
PUT    /api/v1/admin/snags/{id}
PUT    /api/v1/admin/snags/{id}/resolve
PUT    /api/v1/admin/snags/{id}/close
```

### Handover
```
POST   /api/v1/admin/projects/{id}/handover
PUT    /api/v1/admin/handover/{id}/signature
POST   /api/v1/admin/handover/{id}/generate-cert
GET    /api/v1/admin/handover/{id}/certificate
GET    /api/v1/admin/handover/{id}/warranty
```

### Notifications
```
GET    /api/v1/admin/notifications/settings
PUT    /api/v1/admin/notifications/settings
GET    /api/v1/admin/notifications/templates
PUT    /api/v1/admin/notifications/templates/{id}
GET    /api/v1/admin/notifications/logs
```

### Settings
```
GET    /api/v1/admin/settings
PUT    /api/v1/admin/settings/{key}
```

---

## 13. Testing Conventions

- All tests are **feature tests** (not unit tests). Use `Tests\Feature` namespace.
- Each test class covers one module. File: `tests/Feature/{ModuleName}Test.php`.
- Use `RefreshDatabase` trait — each test runs in a fresh transaction.
- Use model factories for test data. Every model must have a factory.
- Test both the **happy path** and every **business rule rejection** (e.g. rate limit, lockout, missing photos).
- Run tests after implementing each module: `php artisan test --filter=ModuleNameTest`.
- Tests must assert:
  1. HTTP status code
  2. Response envelope structure (`success`, `data`, `message`)
  3. Database state where relevant

### Example test structure
```php
/** @test */
public function it_locks_account_after_5_failed_otp_attempts(): void
{
    $otp = OtpRequest::factory()->create(['phone' => '9876543210']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543210',
            'otp'   => '000000',  // wrong
        ]);
    }

    $this->assertNotNull(
        OtpRequest::where('phone', '9876543210')->latest()->value('locked_until')
    );
}
```

---

## 14. Deployment Context (Hostinger Shared Hosting)

- **Queue worker:** Hostinger shared hosting does not support `php artisan queue:work` as a persistent daemon. Use a cron job: `* * * * * php /path/to/artisan schedule:run` plus `Schedule::command('queue:work --stop-when-empty')->everyMinute()` in the scheduler.
- **No supervisor / no Redis.** Queue driver is `database`.
- **Storage path:** Files go to the cloud disk, not `storage/app/public` (Hostinger symlinks are unreliable).
- **PHP version:** Confirm with `php -v` before running `composer install`. Target 8.2+.
- **`.env` must never be committed.** Add it to `.gitignore` on project init.

---

## 15. What NOT to Do

These are the most common mistakes. Claude Code must never do these.

| ❌ Never do this | ✅ Do this instead |
|---|---|
| `Enquiry::max('id') + 1` for enquiry number | `lockForUpdate()` on `enquiry_counter` |
| Two separate DB calls for status + timeline | `DB::transaction()` wrapping both |
| `$model->update([...]); Timeline::create([...]);` outside a transaction | Always in `DB::transaction()` |
| `Timeline::find(1)->update(...)` | Not possible — model guard throws |
| `WHERE JSON_EXTRACT(material_status, '$.damage.glass') = 1` on hot path | Promote to real column if filtering needed |
| Returning stack trace in 500 response | Return `"message": "An unexpected error occurred."` only |
| Hardcoding role strings like `if ($user->role === 'admin')` | Use `$user->role === Role::SuperAdmin` (enum comparison) |
| Generating enquiry_number on `initiate()` | Generate only on `confirm()` |
| `JSONB` type in migrations | Use `JSON` — this is MySQL, not PostgreSQL |
| Storing binary files in the database | Store path/URL only; file on cloud disk |
| Sending JWT in query string | Authorization header only |
| Computing `progress_percent` in every query | Read from the stored `progress_percent` column |
| Building the entire module in one session | One phase at a time: migration → model → service → controller → tests |

---

## 16. Session Checklist

Before ending any Claude Code session, verify:

- [ ] `php artisan migrate` runs without errors
- [ ] `php artisan route:list` shows all expected routes for this module
- [ ] `php artisan test --filter=ThisModuleTest` passes
- [ ] No raw Eloquent calls in Controllers or Services
- [ ] All status-changing operations are wrapped in `DB::transaction()`
- [ ] Timeline row is written inside the same transaction as every status change
- [ ] Response envelope is correct on both success and error paths
- [ ] No hardcoded strings where enums should be used

---

*This file is the single source of truth for Claude Code sessions on this project.
Update it immediately when any architectural decision changes.*