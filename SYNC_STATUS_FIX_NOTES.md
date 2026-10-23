# Farmer/task status synchronization fixes (23 October 2026)

## Changes

- `Farmer::withTaskProgress` is the shared counted progress query for owner API, farmer listing API, and web admin. Counts use the requested date and exclude cancelled assignments.
- Farmer listing API now exposes real `is_assigned`, `total_tasks`, `completed_tasks`, and `progress_percent` along with existing profile information.
- Owner dashboard defaults to today's date and aggregates task status counts in one query.
- Web admin farmers page uses these same counts, including live progress percentage upon request.
- Web admin dashboard, assignment list, and monitoring view show submission geofence/time results using an eager-loaded, reusable status partial.
- KPI cache invalidation is now applied after farmer creation/edit/deletion, assignment deletion, and self-reported task completion, in addition to existing assignment status transitions.
- Added `tests/Feature/Api/FarmerProgressConsistencyTest.php` for API progress/status consistency.
- No database migrations or additional packages are required.

## Deploy

Keep the current `.env` and server data; they are intentionally excluded from this archive. Install code as usual, then run `composer install --no-dev --optimize-autoloader` and `php artisan optimize:clear` on the target server. Run test suite in a test environment before applying to production. Database schema is unchanged.

## Refresh semantics

The authoritative statuses are served by existing Laravel APIs. The web admin reflects new values on page load/refresh. The Flutter app refreshes owner screens on foreground resume, on pull-to-refresh, and when a date range changes. No unnecessary polling or WebSocket service has been added.
