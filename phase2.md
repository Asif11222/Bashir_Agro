Proceed to PHASE 2 ONLY: Farmer Management Module.

Use the existing Bashir Agro Laravel project, PostgreSQL database,
Blade layout, authentication, styling, and components already created
in Phase 1.

Do NOT redesign the Admin Panel.
Do NOT implement Locations, Tasks, Assignments, API, Flutter integration,
Monitoring, Reports, or other later phases yet.

GOAL:
Make the existing Farmer Management section fully dynamic using
Laravel + PostgreSQL.

IMPLEMENT:

1. FARMER MODEL AND DATABASE

Use the existing farmers table with:

- id
- farmer_code
- full_name
- phone
- nid
- date_of_birth
- gender
- address
- primary_activity
- experience_years
- profile_photo_path
- app_access_enabled
- status
- created_by
- timestamps
- softDeletes

Farmer code must be generated server-side:

F-000001
F-000002
F-000003

Do not allow the browser to generate the authoritative farmer code.


2. EMERGENCY CONTACT

Use farmer_emergency_contacts:

- id
- farmer_id
- name
- phone
- relationship
- timestamps

Relationship:

Farmer hasOne FarmerEmergencyContact

FarmerEmergencyContact belongsTo Farmer


3. ELOQUENT RELATIONSHIPS

Farmer:

hasOne emergencyContact
belongsToMany farmLocations
hasMany taskAssignments
belongsTo User through created_by


4. FARMER CONTROLLER

Create/use:

app/Http/Controllers/Admin/FarmerController.php

Implement:

index()
create()
store()
show()
edit()
update()
destroy()

Keep controllers reasonably thin.

Use Eloquent and Form Requests.

Do not put database queries inside Blade.


5. FORM REQUESTS

Create:

StoreFarmerRequest.php
UpdateFarmerRequest.php

Validate server-side:

full_name
phone
nid
date_of_birth
gender
address
primary_activity
experience_years
profile photo
app access
status

Emergency-contact fields must also be validated.

Display Bengali-friendly validation messages where practical.


6. CREATE FARMER

Convert the existing "নতুন কৃষক" design into a real Laravel form.

Use:

<form method="POST"
      action="{{ route('admin.farmers.store') }}"
      enctype="multipart/form-data">

@csrf

Preserve the existing design.

Do not replace it with a generic Laravel form.


7. DATABASE TRANSACTION

Creating a farmer should use:

DB::transaction()

Transaction should:

- create farmer
- generate farmer code
- create emergency contact
- save profile-photo path if supplied
- set created_by to authenticated Admin
- create activity log

If an operation fails, rollback the transaction.


8. FARMER LIST

Make Farmer Management list database-driven.

Display:

- profile
- farmer name
- farmer code
- phone
- primary activity
- number of assigned locations
- number of active tasks if relationship is available
- app access
- status
- actions

Remove all hard-coded farmer records from JavaScript.


9. SEARCH

Add server-side search.

Search by:

- full_name
- farmer_code
- phone

Example:

/admin/farmers?search=rahim


10. FILTERS

Implement:

All
Active
Inactive

Support query string:

/admin/farmers?status=active

Search and status filters must work together.


11. PAGINATION

Use Laravel pagination.

Recommended:

20 farmers per page.

Preserve search/filter parameters when navigating pages.


12. FARMER DETAILS

Create:

resources/views/farmers/show.blade.php

Display:

- profile image
- farmer code
- personal information
- contact information
- work/activity information
- emergency contact
- app access
- status
- assigned locations summary if available
- task summary if available

Do not implement Location or Task management here.


13. EDIT FARMER

Create:

resources/views/farmers/edit.blade.php

Load existing values.

Allow Admin to update:

- personal information
- emergency contact
- primary activity
- experience
- profile photo
- app access
- status

Use UpdateFarmerRequest.


14. DELETE / DEACTIVATE

Use Laravel SoftDeletes.

Do not permanently destroy historical farmer records.

Require confirmation before deletion.

If the existing UI has deactivate behavior, preserve it separately
from soft deletion where appropriate.


15. PROFILE PHOTO

Store farmer profile images using Laravel Storage.

Do not store image binary data in PostgreSQL.

Store only:

profile_photo_path

Validate:

- image MIME/type
- reasonable file-size limit

Delete/replace old image safely when Admin uploads a replacement.


16. SECURITY

All Farmer Management routes must require authenticated Admin access.

Use:

CSRF protection
Form Requests
authorization
mass-assignment protection
password/session security already established in Phase 1

Never trust browser validation alone.


17. ACTIVITY LOG

Create activity logs for:

farmer.created
farmer.updated
farmer.deactivated
farmer.deleted

Store:

user_id
action
subject_type
subject_id
description
old_values when useful
new_values when useful
ip_address


18. ROUTES

Use Laravel resource routes:

Route::resource(
    'farmers',
    FarmerController::class
);

Keep routes under:

/admin/farmers


19. UI REQUIREMENT

IMPORTANT:

Use the existing Bashir Agro HTML design as the visual source of truth.

Preserve:

- Bengali UI
- green color system
- sidebar
- topbar
- cards
- modal/form styling
- table styling
- status pills
- responsive behavior
- spacing
- typography

Do not redesign the interface.

Only make it dynamic.


20. JAVASCRIPT

JavaScript may handle:

- modal interactions
- delete confirmation
- image preview
- UI toggles
- mobile drawer
- toast behavior

JavaScript must NOT contain:

- farmer records
- fake farmer counts
- hard-coded farmer database data


21. TESTS

Create feature tests for:

- authenticated Admin can view farmer list
- unauthenticated user cannot access farmer management
- Admin can create farmer
- farmer code is generated correctly
- emergency contact is created
- invalid form data is rejected
- duplicate phone is rejected
- Admin can edit farmer
- emergency contact can be updated
- Admin can activate/deactivate farmer
- soft delete works
- search works
- status filter works
- pagination works
- profile photo validation works


22. VERIFICATION

After implementation:

Run migrations if required.

Run:

php artisan test

Run:

./vendor/bin/pint

Fix errors before reporting completion.


23. COMPLETION REPORT

When finished, report:

- files created
- files modified
- database changes
- routes added
- tests created
- test results
- any unresolved issue


IMPORTANT:

Do PHASE 2 ONLY.

Do not automatically continue to Farm Locations.

When Farmer Management is fully functional and tests pass:

STOP.

Wait for my instruction before starting Phase 3.