# Hospital Project Tracking System (PMS) — Developer Guide

Last updated: 2026-02-19

## 1) What this system is

A web-based **Hospital Project Tracking System** to manage:

- Projects (with status, priority, department, owner)
- Project tasks (with task statuses)
- Project milestones
- External risk/issues (synced or manually created)
- Lookups (departments, roles, statuses, priorities, severities, external sources, risk issue types)
- Audit logs for changes across entities
- Role-based access control (Admin, Auditor, PMO, PM, Staff)

---

## 2) Tech stack

### Backend
- **Laravel 12** (PHP 8.4)
- **Laravel Sanctum** (token auth for SPA / API)
- MySQL (example DB name: `hospital_tracker`)
- FormRequest validation + API Resources
- Centralized exception handling + API error codes
- Audit logging (`dt_audit_logs`)

### Frontend
- **Angular 21** (standalone components)
- Angular HttpClient + interceptor (Bearer token)
- Angular dev proxy to backend (`proxy.conf.json`)

---

## 3) Repository / folder structure (high level)

### Backend (Laravel)
Common folders you’ll touch:

- `app/Http/Controllers/Api/`  
  API controllers (Projects, Tasks, Milestones, Lookups, Audit logs, etc.)

- `app/Http/Requests/Api/`  
  FormRequests (centralized request validation)

- `app/Http/Resources/`  
  API Resources (centralized response formatting)

- `app/Support/`  
  Shared helpers such as:
  - `ApiErrorCode.php`
  - `Audit.php`, `AuditDiff.php`
  - `ApiResponse.php` (if used)

- `app/Exceptions/Handler.php`  
  Centralized JSON error response for API.

- `routes/api.php`  
  API endpoints + role middleware groups.

- `database/migrations/`  
  Table schemas.

> Naming convention:
> - `lt_*` = lookup tables
> - `st_*` = status tables
> - `dt_*` = data / transaction tables

### Frontend (Angular)
Recommended to keep a **stable, simple structure**:

- `src/app/core/`  
  Cross-cutting concerns (auth, interceptors, services)
  - `core/auth/auth.ts` (AuthService)
  - `core/auth/auth.interceptor.ts` (adds Bearer token)
  - `core/services/api.service.ts`

- `src/app/features/`  
  Feature pages (login, dashboard, modules)

- `src/app/app.routes.ts`  
  Routes

- `src/app/app.config.ts`  
  Providers: router + http + interceptors

- `src/environments/environment.ts`  
  API base config (typically `/api`)

---

## 4) Data model overview (key tables)

### Users & roles
- `users`  
  - `id`, `name`, `username` (optional), `email`, `department_id`, `password`, timestamps
- `lt_roles`  
  - `id`, `code`, `name`, `is_active`
- `dt_user_roles` (pivot)
  - `user_id`, `role_id`, timestamps
- `lt_departments`
  - `id`, `code`, `name`, `is_active`

### Projects
- `dt_projects`
  - `department_id`, `owner_user_id`, `project_status_id`, `priority_id`, progress fields, dates, etc.
- `st_project_statuses` (project statuses)
- `lt_priorities`
- `dt_project_tasks`
- `st_task_statuses`
- `dt_project_milestones`

### Risk / issues
- `dt_external_risk_issues`
- `lt_external_sources`
- `lt_risk_issue_types`
- `st_risk_issue_statuses`
- `st_severities`

### Audit
- `dt_audit_logs`
  - `entity_type`, `entity_id`, `action`, `performed_by_user_id`, `performed_at`, `changes` (JSON)

---

## 5) Authentication & authorization flow

### Login (Sanctum token)
- `POST /api/login`
  - Accepts `login` (username OR email) and `password`
  - Returns `{ token, user }`
  - `user` includes `roles` and `department` when loaded

### Using token
- Frontend stores token (e.g. `localStorage: pms_token`)
- Angular interceptor sends:
  - `Authorization: Bearer <token>`

### Get current user
- `GET /api/me`
  - Returns `{ user }` with roles + department (loaded)

### Role-based access
Routes are grouped by middleware:

- All authenticated:
  - `/dashboard/overview`
  - `/lookups`
  - read-only project endpoints
- PMO + PM:
  - create/update/delete projects, tasks, milestones, external risk issues
- Auditor + Admin:
  - read audit logs
- Admin only:
  - user management, roles, departments, and lookup maintenance

---

## 6) API endpoints (summary)

Health
- `GET /api/health`

Auth
- `POST /api/login`
- `POST /api/logout` (auth)
- `GET /api/me` (auth)

Projects
- `GET /api/projects`
- `GET /api/projects/{project}`
- `POST /api/projects` (PMO/PM)
- `PUT /api/projects/{project}` (PMO/PM)
- `DELETE /api/projects/{project}` (PMO/PM)

Tasks
- `GET /api/projects/{project}/gantt`
- `POST /api/projects/{project}/tasks` (PMO/PM)
- `PUT /api/tasks/{task}` (PMO/PM)
- `DELETE /api/tasks/{task}` (PMO/PM)

Milestones
- `GET /api/projects/{project}/milestones`
- `GET /api/projects/{project}/milestones/{milestone}`
- `POST /api/projects/{project}/milestones` (PMO/PM)
- `PUT /api/projects/{project}/milestones/{milestone}` (PMO/PM)
- `DELETE /api/projects/{project}/milestones/{milestone}` (PMO/PM)

External Risk Issues
- `GET /api/external-risk-issues`
- `GET /api/external-risk-issues/{issue}`
- `POST /api/external-risk-issues` (PMO/PM)
- `PUT /api/external-risk-issues/{issue}` (PMO/PM)
- `DELETE /api/external-risk-issues/{issue}` (PMO/PM)

Audit Logs (Auditor/Admin)
- `GET /api/audit-logs`
- `GET /api/audit-logs/{id}`

Admin maintenance
- `/api/users`, `/api/roles`, `/api/departments`
- `apiResource`: project-statuses, task-statuses, risk-statuses, severities, priorities, external-sources, risk-issue-types

---

## 7) How to run locally (development)

### 7.1 Prerequisites
- PHP 8.4 + Composer
- MySQL 8.x
- Node.js 24.x + npm 11.x
- Git

---

## 7.2 Backend setup (Laravel)

From the backend project root:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
- `DB_DATABASE=hospital_tracker`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

Run migrations (and seed if you have seeders):
```bash
php artisan migrate
# optional
php artisan db:seed
```

Run backend:
```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Test:
- `http://127.0.0.1:8001/api/health`
  should return:
  ```json
  {"ok":true,"message":"API is working"}
  ```

---

## 7.3 Frontend setup (Angular)

From the frontend project root:

```bash
npm install
npm start
```

### Proxy setup (important)

Your frontend uses `/api/...` paths. During development, Angular should proxy those to Laravel.

Create `proxy.conf.json` at the Angular project root:

```json
{
  "/api": {
    "target": "http://127.0.0.1:8001",
    "secure": false,
    "changeOrigin": true,
    "logLevel": "debug"
  }
}
```

In `package.json`:
```json
"start": "ng serve --proxy-config proxy.conf.json"
```

Then open:
- `http://localhost:4200`

If the proxy is working:
- `http://localhost:4200/api/health` should return backend JSON.

---

## 8) Common development commands

Backend:
```bash
php artisan route:list
php artisan migrate:fresh --seed
php artisan optimize:clear
```

Frontend:
```bash
npx ng version
npx ng g component features/auth/login --standalone
npx ng g service core/services/api
```

---

## 9) API error handling & error codes

### Central handler
`app/Exceptions/Handler.php` converts common exceptions into consistent JSON:

- Validation → `VALIDATION_FAILED` (422)
- Unauthenticated → `UNAUTHORIZED` (401)
- Forbidden → `FORBIDDEN` (403)
- Not found → `NOT_FOUND` (404)
- DB error → `DB_ERROR` (500)
- Server error → `SERVER_ERROR` (500)

### Custom codes
`app/Support/ApiErrorCode.php` contains feature-specific codes (e.g. create/update/delete failures, duplicate external id, invalid raw payload, etc.).

> Tip: prefer throwing a custom exception (or returning `ApiResponse::error(...)`) so every controller returns consistent `{ ok, error: { code, message } }` format.

---

## 10) Troubleshooting

### A) Angular shows “Loading…” forever
- Check browser devtools → Network → the call to `/api/health`
- If it’s failing:
  - verify backend is running at `127.0.0.1:8001`
  - verify `proxy.conf.json` exists and `npm start` uses it
  - verify your frontend code calls `${environment.apiBaseUrl}/health` where `apiBaseUrl='/api'`

### B) Sanctum token not sent
- Ensure interceptor is registered in `app.config.ts` with:
  - `provideHttpClient(withInterceptorsFromDi())`
  - `HTTP_INTERCEPTORS` provider
- Ensure token stored under the same key used by AuthService

### C) “Column 'id' is ambiguous”
- Happens when joining tables that both have `id`.
- Fix by qualifying selects:
  - `->get(['lt_roles.id','lt_roles.code','lt_roles.name'])`

### D) Roles missing in `/me`
- Ensure you eager-load roles in `me()`:
  - `$request->user()->load('roles:id,code,name', 'department:id,code,name')`

---

## 11) Next steps (frontend)
Once backend is stable, frontend usually needs:
- route guards (auth + role)
- reusable layout (sidebar/topbar)
- shared UI components
- pages:
  - Projects list + details
  - Gantt view
  - Admin maintenance (users/roles/lookups)
  - Audit log viewer

---

## 12) Notes
This document is intended as a quick **setup + structure** reference for developers maintaining the Hospital PMS.
