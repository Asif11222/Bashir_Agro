# Bashir Agro Flutter Farmer App Workflow

## 1. Overview
The Flutter Farmer Application serves field farmers to receive real-time task assignments, navigate to designated farm plots, record execution progress, capture proof photos, and submit completion reports.

---

## 2. Authentication & Profile Flow
1. **Login**:
   - Farmer enters Phone Number & Password (or OTP in future phases).
   - Backend authenticates against `users` table where `role = 'farmer'`.
   - Backend issues Sanctum Bearer token: `plainTextToken`.
   - Token stored locally in Flutter Secure Storage (`flutter_secure_storage`).
2. **Profile Fetch**:
   - `GET /api/v1/profile`
   - Returns linked `Farmer` profile, emergency contacts, and active locations.

---

## 3. Daily Task Lifecycle
```
+--------------------+
|  Assigned (বরাদ্দ)  | <--- Admin schedules task for farmer at plot
+---------+----------+
          |
          | Farmer taps "কাজ শুরু করুন" (Start Task)
          v
+--------------------+
| In Progress (চলমান) | <--- App records started_at & validates GPS location
+---------+----------+
          |
          | Farmer works, captures required evidence photos
          v
+--------------------+
| Submitting (জমা)   | <--- App uploads photos with GPS exif, notes & timestamp
+---------+----------+
          |
          v
+--------------------+
| Completed (সম্পন্ন) | <--- Admin receives notification, marks verified
+--------------------+
```

---

## 4. Location & Geofence Verification
- **GPS Coordinates**: Device fetches current coordinates via `geolocator`.
- **Distance Calculation**: Distance to plot centroid calculated via Haversine formula.
- **Geofence Flag**: If `distance <= plot.geofence_radius_meters`, `is_within_geofence = true`. Otherwise flagged as out-of-bounds with admin review required.

---

## 5. Offline Capabilities (Future Preparation)
- **Local SQLite / Hive**: Cached task templates and daily schedule.
- **Background Sync**: When network returns, queued task submissions and cached images upload asynchronously via `workmanager`.
