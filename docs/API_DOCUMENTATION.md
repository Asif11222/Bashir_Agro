# Bashir Agro Mobile REST API Documentation (V1)

## Base URL
```
https://admin.bashiragro.com/api/v1
```

All requests expect:
```http
Accept: application/json
Content-Type: application/json
```
For authenticated requests:
```http
Authorization: Bearer <sanctum_personal_access_token>
```

---

## 1. Authentication Endpoints

### 1.1 Farmer / Admin Login
- **Endpoint**: `POST /auth/login`
- **Public**: Yes
- **Payload**:
  ```json
  {
    "login": "01711223344",
    "password": "secret_password",
    "device_name": "Redmi Note 12"
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "লগইন সফল হয়েছে।",
    "data": {
      "token": "1|qX8Z...",
      "token_type": "Bearer",
      "user": {
        "id": 12,
        "name": "আব্দুর রহিম",
        "email": null,
        "phone": "01711223344",
        "role": "farmer"
      },
      "farmer_profile": {
        "id": 5,
        "farmer_code": "F-000005",
        "full_name": "আব্দুর রহিম"
      }
    }
  }
  ```

### 1.2 Current Authenticated User & Profile
- **Endpoint**: `GET /auth/me`
- **Public**: No (Bearer Token)
- **Success Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "user": { "id": 12, "name": "আব্দুর রহিম", "role": "farmer" },
      "farmer_profile": { "id": 5, "farmer_code": "F-000005", "full_name": "আব্দুর রহিম" }
    }
  }
  ```

### 1.3 Logout
- **Endpoint**: `POST /auth/logout`
- **Public**: No (Bearer Token)
- **Success Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "সফলভাবে লগআউট হয়েছে।"
  }
  ```

---

## 2. Farmer Profile Endpoints

### 2.1 Get Farmer Profile
- **Endpoint**: `GET /farmer/profile`
- **Public**: No (Bearer Token)
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 5,
      "farmer_code": "F-000005",
      "full_name": "আব্দুর রহিম",
      "phone": "01711223344",
      "address": "পাবনা সদর",
      "primary_activity": "সবজি চাষ",
      "emergency_contact": {
        "name": "মোঃ করিম",
        "phone": "01799887711",
        "relationship": "ভাই"
      },
      "locations": [
        {
          "id": 3,
          "location_code": "LOC-000003",
          "name": "উত্তর সবজি মাঠ",
          "coordinates": { "latitude": 24.0123456, "longitude": 89.1234567 },
          "geofence_radius_meters": 30
        }
      ]
    }
  }
  ```

---

## 3. Task Templates Endpoints

### 3.1 List Active Tasks
- **Endpoint**: `GET /tasks`
- **Query Parameters**:
  - `category` (optional, filter by task category)
  - `per_page` (default 20)
- **Response**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 2,
        "task_code": "TASK-000002",
        "name": "কীটনাশক প্রয়োগ",
        "category": "ফসল সুরক্ষা",
        "instructions": "অনুমোদিত মাত্রায় কীটনাশক স্প্রে করুন।",
        "requires_evidence_photo": true,
        "min_photos_required": 1,
        "requires_geofence_validation": true,
        "status": "active"
      }
    ]
  }
  ```

---

## 4. Task Assignments Endpoints

### 4.1 List Assigned Tasks for Farmer
- **Endpoint**: `GET /assignments`
- **Query Parameters**:
  - `status` (optional: `assigned`, `in_progress`, `completed`, `cancelled`)
  - `date` (optional: `YYYY-MM-DD`)
- **Response**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 14,
        "scheduled_date": "2026-09-30",
        "start_time": "08:00:00",
        "priority": "important",
        "status": "assigned",
        "task": { "name": "কীটনাশক প্রয়োগ", "requires_geofence_validation": true },
        "location": { "name": "উত্তর সবজি মাঠ", "geofence_radius_meters": 30 }
      }
    ]
  }
  ```

### 4.2 Start Task Assignment
- **Endpoint**: `POST /assignments/{id}/start`
- **Public**: No (Bearer Token)
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "কাজটি চলমান হিসেবে চিহ্নিত করা হয়েছে।",
    "data": { "id": 14, "status": "in_progress", "started_at": "2026-09-30T15:00:00Z" }
  }
  ```

---

## 5. Task Submissions & Geofence Verification

### 5.1 Submit Task with Evidence & GPS Geofence
- **Endpoint**: `POST /assignments/{id}/submit`
- **Content-Type**: `multipart/form-data`
- **Payload**:
  - `latitude`: `24.0123400` (required, float)
  - `longitude`: `89.1234500` (required, float)
  - `accuracy`: `4.2` (optional, float)
  - `notes`: `কাজ সুন্দরভাবে শেষ করা হয়েছে।` (optional, string)
  - `photos[]`: binary image file(s) (optional / required if task requires photos)
- **Geofence Passed Response (201 Created)**:
  ```json
  {
    "success": true,
    "message": "কাজের প্রমাণ ও প্রতিবেদন সফলভাবে জমা হয়েছে।",
    "data": {
      "id": 8,
      "assignment_id": 14,
      "coordinates": { "latitude": 24.01234, "longitude": 89.12345, "accuracy": 4.2 },
      "geofence": { "passed": true, "distance_meters": 2.45 },
      "status": "submitted",
      "verification_status": "pending",
      "submitted_at": "2026-09-30T15:05:00Z",
      "photos": ["submissions/photos/abc12345.jpg"]
    }
  }
  ```
- **Geofence Failed Response (422 Unprocessable Content)**:
  ```json
  {
    "success": false,
    "message": "আপনি নির্ধারিত ফার্ম লোকেশনের জিও-ফেন্স পরিধির বাইরে অবস্থান করছেন। (দূরত্ব: 512.30 মিটার, গ্রহণযোগ্য: 30 মিটার)",
    "geofence": {
      "passed": false,
      "distance_meters": 512.3,
      "radius_meters": 30
    }
  }
  ```

### 5.2 View Submission Details
- **Endpoint**: `GET /assignments/{id}/submission`
- **Public**: No (Bearer Token)
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 8,
      "status": "submitted",
      "evidence_photos": [
        { "id": 1, "url": "https://admin.bashiragro.com/storage/submissions/photos/abc12345.jpg" }
      ]
    }
  }
  ```

---

## 6. Farm Locations Endpoints

### 6.1 List Locations
- **Endpoint**: `GET /locations`
- **Response**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 3,
        "location_code": "LOC-000003",
        "name": "উত্তর সবজি মাঠ",
        "type": "সবজি ক্ষেত",
        "coordinates": { "latitude": 24.0123456, "longitude": 89.1234567 },
        "geofence_radius_meters": 30,
        "status": "active"
      }
    ]
  }
  ```
