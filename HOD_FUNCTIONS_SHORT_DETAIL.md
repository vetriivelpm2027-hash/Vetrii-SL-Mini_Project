# HOD Dashboard - 10 Key Functions
## SHORT + DETAILED EXPLANATION

---

## 1️⃣ **requireLogin()** - Check User Login
**SHORT:** Verify user is logged in before accessing dashboard  
**DETAILED:** Calls authentication function that checks if $_SESSION['user_id'] exists. If NO session found, redirects to login page. This prevents unauthorized access. Runs on LINE 9.  
**CRITICAL** ⚠️

---

## 2️⃣ **SELECT * FROM users WHERE id = ?** - Verify HOD Role
**SHORT:** Confirm logged-in user is actually an HOD (not student/teacher)  
**DETAILED:** Gets user record from database using current session ID. Then checks if $user['role'] == 'hod'. If not HOD, redirects to unauthorized.php. Multiple roles exist (student, teacher, admin, hod) so we must verify the correct role. Uses prepared statement for security.  
**CRITICAL** ⚠️

---

## 3️⃣ **SELECT COUNT(*) FROM students** - Count All Students
**SHORT:** Get total number of students in system  
**DETAILED:** Counts rows in students table. Uses COUNT(*) function which is fast and returns just one number instead of all student records. Result shown on dashboard stat card. Default to 0 if query fails. Shows HOD the size of student body.  
**HIGH** 📊

---

## 4️⃣ **SELECT COUNT(*) FROM teachers** - Count All Teachers
**SHORT:** Get total number of teachers in system  
**DETAILED:** Same as students count, but for teachers table. Shows HOD how many faculty members available. Used for resource planning and workforce overview. Also defaults to 0 if query fails.  
**HIGH** 📊

---

## 5️⃣ **SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0** - Count Unread Messages
**SHORT:** Get count of new/unread messages for this HOD  
**DETAILED:** Filters messages table with TWO conditions:
- receiver_id = current HOD's ID
- is_read = 0 (unread/new)

Shows in red notification badge on dashboard. Helps HOD stay updated on communications from students/teachers. Used for notification system.  
**HIGH** 📊

---

## 6️⃣ **CREATE TABLE IF NOT EXISTS profile_edit_requests** - Setup Profile Edit Table
**SHORT:** Create database table to track student profile edit requests  
**DETAILED:** Runs SQL CREATE TABLE with IF NOT EXISTS, so it's safe to run multiple times. Table stores:
- student_id (who requested)
- status (pending/approved/rejected)
- requested_at (timestamp)
- approved_by_hod (which HOD approved)
- hod_approval_date
- rejection_reason

Uses FOREIGN KEYS to delete requests when student deleted. Infrastructure for approval workflow.  
**CRITICAL** ⚠️

---

## 7️⃣ **Nested SELECT - Profile Edit Stats** - Count Profile Requests by Status
**SHORT:** Count how many profile edits are pending/approved/rejected for HOD's department  
**DETAILED:** Complex NESTED query with 3 levels:
1. Find departments WHERE hod_id = current HOD
2. Find students WHERE department_id IN those departments  
3. Count profile_edit_requests WHERE student_id IN those students, grouped by status

Uses CASE WHEN for conditional counting:
- SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) = count pending

Shows HOD: total requests, pending, approved, rejected. Only shows his department data (security).  
**CRITICAL** ⚠️

---

## 8️⃣ **SELECT COUNT(*) FROM timetable WHERE day = DAYNAME(CURDATE())** - Today's Classes
**SHORT:** Count how many classes scheduled for today  
**DETAILED:** CURDATE() = today's date  
DAYNAME() = converts date to day name (Monday, Tuesday, etc.)

Counts timetable rows matching today's day. For example:
- Today = 2026-03-20 (Friday)
- DAYNAME returns 'Friday'
- Counts all classes on Friday

Helps HOD know daily workload.  
**HIGH** 📊

---

## 9️⃣ **SELECT * FROM students ORDER BY created_at DESC LIMIT 5** - Recent Students
**SHORT:** Get 5 most recently registered students  
**DETAILED:** 
- SELECT * = get all student columns
- ORDER BY created_at DESC = newest first (descending)
- LIMIT 5 = only get 5 rows

Shows new enrollments on dashboard. Typically displays names, ID, department of recent joiners. Helps HOD track new admissions.  
**MEDIUM** 📋

---

## 🔟 **SELECT * FROM requests WHERE status = 'pending' AND department = ? LIMIT 5** - Pending Requests
**SHORT:** Get up to 5 pending requests waiting for HOD approval  
**DETAILED:** Filters requests table:
- status = 'pending' (only unapproved)
- department = HOD's department (only his dept)
- LIMIT 5 (most urgent 5)

Could be leave requests, purchase requests, event approvals, etc. Shows HOD what needs action. Uses parameter binding for security. Keeps HOD focused on items requiring approval.  
**MEDIUM** 📋

---

## SUMMARY TABLE

| # | Function | Type | Status |
|---|----------|------|--------|
| 1 | requireLogin() | Auth Check | 🔴 CRITICAL |
| 2 | User Role Verify | Auth Check | 🔴 CRITICAL |
| 3 | Count Students | Query | 🟡 HIGH |
| 4 | Count Teachers | Query | 🟡 HIGH |
| 5 | Unread Messages | Query | 🟡 HIGH |
| 6 | Create Table | Schema | 🔴 CRITICAL |
| 7 | Profile Stats | Query | 🔴 CRITICAL |
| 8 | Today Classes | Query | 🟡 HIGH |
| 9 | Recent Students | Query | 🟢 MEDIUM |
| 10 | Pending Requests | Query | 🟢 MEDIUM |

---

## KEY CONCEPTS

**Prepared Statements:** All queries use `$conn->prepare()` + parameter binding to prevent SQL injection  
**Error Handling:** Uses `?? 0` or `if ($stmt)` to handle failures gracefully  
**Security:** Only shows data relevant to HOD's department, not all system data  
**Performance:** Uses COUNT() instead of fetching all rows, LIMIT 5 instead of all records

---

**Date Generated:** March 20, 2026  
**File:** `/hod/dashboard.php`  
**Total Lines:** 1138
