# Bashir Agro API Architecture (Phase 11 Preparation)

## 1. Overview
The Bashir Agro backend provides a unified, secure foundation supporting both the Web Admin Panel and the future Flutter Farmer Mobile Application. Both applications share the same Laravel framework instance and PostgreSQL database.

```
+--------------------------------------------------------------------+
|                         Client Layer                               |
|   +-----------------------+           +------------------------+   |
|   |   Web Admin Panel     |           |  Flutter Mobile App    |   |
|   |   (Blade / Web)       |           |  (REST API Client)     |   |
|   +-----------+-----------+           +-----------+------------+   |
+---------------|-----------------------------------|----------------+
                | Session / CSRF                    | Bearer Token (Sanctum)
+---------------v-----------------------------------v----------------+
|                     Laravel Backend Routing                        |
|   routes/web.php                                  routes/api.php   |
|   (Admin Controller Layer)                        (/api/v1/...)    |
+--------------------------------------------------------------------+
                                |
+-------------------------------v------------------------------------+
|                       API Resources Layer                          |
|   FarmerResource | TaskResource | AssignmentResource | Location    |
+--------------------------------------------------------------------+
                                |
+-------------------------------v------------------------------------+
|                       Unified Service Layer                        |
|   TaskAssignmentService | DashboardService | SettingsService       |
+--------------------------------------------------------------------+
                                |
+-------------------------------v------------------------------------+
|                       Eloquent & Database Layer                    |
|   PostgreSQL Database (Farmers, Locations, Tasks, Assignments)     |
+--------------------------------------------------------------------+
```

---

## 2. API Versioning
All mobile API endpoints are strictly versioned under `/api/v1/` prefix:
- Standard URL format: `https://domain.com/api/v1/{resource}`
- Future planned routes:
  - `POST /api/v1/auth/login` - Mobile farmer authentication
  - `GET  /api/v1/profile` - Authenticated farmer profile
  - `GET  /api/v1/tasks` - Assigned task list for farmer
  - `GET  /api/v1/tasks/{id}` - Detailed task view
  - `POST /api/v1/tasks/{id}/start` - Task start with GPS ping
  - `POST /api/v1/tasks/{id}/submit` - Evidence upload & submission

---

## 3. Authentication & Security
- **Bearer Token Auth**: Mobile authentication uses **Laravel Sanctum** token-based authentication (`HasApiTokens`).
- **Token Issue**: On successful credential check, an API token with appropriate abilities is generated.
- **Header Structure**: `Authorization: Bearer <token>`
- **Rate Limiting**: API routes are rate-limited via `throttle:api` (60 requests/minute).

---

## 4. Standard Response Envelope
All API responses follow a uniform JSON structure:

### Success Response:
```json
{
  "success": true,
  "message": "অপারেশন সফল হয়েছে।",
  "data": { ... },
  "meta": {
    "timestamp": "2026-09-30T13:30:00Z",
    "version": "v1"
  }
}
```

### Error Response:
```json
{
  "success": false,
  "message": "ত্রুটি বার্তা।",
  "errors": {
    "field_name": ["ভ্যালিডেশন এরর"]
  },
  "error_code": "RESOURCE_NOT_FOUND"
}
```

---

## 5. Architectural Principles
1. **Decoupled Business Logic**: Core business rules reside in dedicated Services (e.g. `TaskAssignmentService`), preventing controller bloat and code duplication between Web and API.
2. **Resource Transformations**: Database models are never directly returned to clients. `JsonResource` classes strictly control field exposure, type casting, and localized representations.
3. **Audit Logging**: Important status changes initiated via mobile app log into the central `audit_logs` table via `AuditService`.
