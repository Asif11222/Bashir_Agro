# Bashir Agro: farmer engagement status consistency (23 October 2026)

Rule: `is_assigned` is true only when a farmer has one or more non-deleted tasks whose assignment status is not `completed` or `cancelled`. This is based on ALL unfinished assignments, regardless of which date/location is selected for progress/KPI reporting. A farmer with only completed tasks remains `is_assigned: false`; task history and progress are retained.

## Laravel changes

- Shared `Farmer::unfinishedTaskAssignments` relationship and `Farmer::withTaskProgress`/`loadTaskProgress` query counts (`active_tasks`, `total_tasks`, `completed_tasks`).
- `/api/v1/owner/dashboard`, `/api/v1/farmers`, `/api/v1/farmers/{farmer}`, `/api/v1/farmer/profile` now return the same authoritative `is_assigned` flag.
- Farmer completion, cancellation and future pending assignments are tested in `tests/Feature/Api/FarmerProgressConsistencyTest.php`.
- No database migration, new dependency, background polling, or new stored status field.

## Deployment (Laravel backend, from the project directory)

```
git status
# Merge the updated Laravel files into your existing working tree, then:
php -l app/Models/Farmer.php
php -l app/Http/Resources/FarmerResource.php
php -l app/Http/Controllers/Api/V1/FarmerController.php
php -l app/Http/Controllers/Api/V1/DashboardApiController.php
php artisan test --filter=FarmerProgressConsistencyTest
php artisan optimize:clear
```

Run `php artisan test` only in a configured testing environment. Preserve existing server `.env`, storage and database. The backend must be deployed for the mobile status change to become authoritative.
