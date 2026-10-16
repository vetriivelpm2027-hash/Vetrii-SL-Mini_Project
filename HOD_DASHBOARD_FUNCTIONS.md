# HOD Dashboard - Most Important Functions
## Project Report Documentation

---

## Overview
The HOD Dashboard is the main interface for Head of Department (HOD) users to manage students, teachers, messages, and profile edit requests. Below are the 10 most critical functions/operations used in this module.

---

## QUICK REFERENCE - SHORT VERSION

| # | Function | Short Explanation |
|---|----------|------------------|
| 1 | requireLogin() | Check if user logged in |
| 2 | User Role Check | Ensure user is HOD, not other role |
| 3 | Count Students | Get total number of students registered |
| 4 | Count Teachers | Get total number of teachers available |
| 5 | Unread Messages | Get count of new messages for HOD |
| 6 | Create Table | Setup profile_edit_requests table |
| 7 | Profile Stats | Count pending/approved profile edits |
| 8 | Today's Classes | How many classes scheduled today |
| 9 | Recent Students | Last 5 students who registered |
| 10 | Pending Requests | Outstanding requests waiting approval |

---

## 1. **Authentication Check - requireLogin()**
**Location:** Line 9  
**SHORT:** Checks if user has active login session  
**DETAILED:** This function verifies that the user has a valid login session. If no session exists, user is redirected to login page. This is the first line of defense against unauthenticated access. Without this check, anyone could access the HOD dashboard.

**Code:**
```php
requireLogin();
```
**Importance:** 🔴 CRITICAL - Guards against unauthorized access

---

## 2. **User Role Verification - SELECT * FROM users**
**Location:** Lines 12-19  
**Purpose:** Verifies that logged-in user has HOD role  
**Code:**
```php
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_result->fetch_assoc();

if (!$user || $user['role'] != 'hod') {
    header("Location: ../unauthorized.php");
    exit();
}
```
**Importance:** 🔴 CRITICAL - Blocks non-HOD users

---

## 3. **Count Total Students**
**Location:** Lines 26-32  
**Purpose:** Retrieves total number of students for dashboard statistics  
**Code:**
```php
$students_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students");
$students_stmt->execute();
$students_count = $students_result->fetch_assoc()['count'] ?? 0;
```
**Importance:** 🟡 HIGH - Key dashboard metric

---

## 4. **Count Total Teachers**
**Location:** Lines 35-41  
**Purpose:** Retrieves total number of teachers for statistics  
**Code:**
```php
$teachers_stmt = $conn->prepare("SELECT COUNT(*) as count FROM teachers");
$teachers_stmt->execute();
$teachers_count = $teachers_result->fetch_assoc()['count'] ?? 0;
```
**Importance:** 🟡 HIGH - Key dashboard metric

---

## 5. **Count Unread Messages**
**Location:** Lines 44-50  
**Purpose:** Retrieves count of unread messages for the HOD  
**Code:**
```php
$messages_stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages 
                                 WHERE receiver_id = ? AND is_read = 0");
$messages_stmt->bind_param("i", $_SESSION['user_id']);
$messages_stmt->execute();
$messages = $messages_result->fetch_assoc()['count'] ?? 0;
```
**Importance:** 🟡 HIGH - Important notification feature

---

## 6. **Create Profile Edit Requests Table**
**Location:** Lines 53-77  
**Purpose:** Ensures profile_edit_requests table exists with proper structure  
**Code:**
```php
$create_table_sql = "CREATE TABLE IF NOT EXISTS profile_edit_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by_hod INT,
    hod_approval_date TIMESTAMP NULL,
    approved_by_admin INT,
    admin_approval_date TIMESTAMP NULL,
    rejection_reason VARCHAR(500),
    FOREIGN KEY (...) ON DELETE CASCADE
)";
$conn->query($create_table_sql);
```
**Importance:** 🔴 CRITICAL - Essential for profile editing workflow

---

## 7. **Get Profile Edit Request Statistics**
**Location:** Lines 79-101  
**Purpose:** Retrieves pending, approved, and rejected profile edit requests for HOD's department  
**Code:**
```php
$profile_edit_stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM profile_edit_requests 
WHERE student_id IN (
    SELECT id FROM students 
    WHERE department_id IN (
        SELECT id FROM departments 
        WHERE hod_id = ?
    )
)");
```
**Importance:** 🔴 CRITICAL - Core profile management feature

---

## 8. **Get Today's Classes Count**
**Location:** Lines 106-113  
**Purpose:** Retrieves number of classes scheduled for today  
**Code:**
```php
$classes_stmt = $conn->prepare("SELECT COUNT(*) as count 
                                FROM timetable 
                                WHERE day = DAYNAME(CURDATE())");
$classes_stmt->execute();
$classes_count = $classes_result->fetch_assoc()['count'] ?? 0;
```
**Importance:** 🟡 HIGH - Schedule visibility

---

## 9. **Get Recent Students**
**Location:** Lines 116-125  
**Purpose:** Retrieves last 5 recently registered students  
**Code:**
```php
$recent_stmt = $conn->prepare("SELECT * FROM students 
                               ORDER BY created_at DESC LIMIT 5");
$recent_stmt->execute();
$recent_students = $recent_stmt->get_result();
```
**Importance:** 🟢 MEDIUM - Dashboard overview feature

---

## 10. **Get Pending Requests**
**Location:** Lines 129-138  
**Purpose:** Retrieves pending requests for the HOD's department  
**Code:**
```php
$requests_stmt = $conn->prepare("SELECT * FROM requests 
                                 WHERE status = 'pending' 
                                 AND department = ? LIMIT 5");
$requests_stmt->bind_param("s", $user['department']);
$requests_stmt->execute();
$pending_requests = $requests_stmt->get_result();
```
**Importance:** 🟢 MEDIUM - Request management

---

## Summary Table

| # | Function | Type | Importance |
|---|----------|------|-----------|
| 1 | requireLogin() | Auth | 🔴 CRITICAL |
| 2 | User Role Verification | Auth | 🔴 CRITICAL |
| 3 | Count Students | Query | 🟡 HIGH |
| 4 | Count Teachers | Query | 🟡 HIGH |
| 5 | Count Messages | Query | 🟡 HIGH |
| 6 | Create Table | Schema | 🔴 CRITICAL |
| 7 | Profile Edit Stats | Query | 🔴 CRITICAL |
| 8 | Today's Classes | Query | 🟡 HIGH |
| 9 | Recent Students | Query | 🟢 MEDIUM |
| 10 | Pending Requests | Query | 🟢 MEDIUM |

---

## Data Security Implementation

✅ **Prepared Statements** - All DB queries use prepared statements to prevent SQL injection  
✅ **Role-Based Access** - HOD role verification before allowing access  
✅ **Parameter Binding** - All user inputs bound with proper data types  
✅ **Error Handling** - Try-catch and null-coalescing for safe operations  

---

## Database Schema Dependencies

- **users** table - For authentication and role verification
- **students** table - For student count and recent students
- **teachers** table - For teacher count
- **messages** table - For unread message notifications
- **profile_edit_requests** table - For profile management workflow
- **timetable** table - For class scheduling information
- **departments** table - For department-specific data filtering

---

**Document Generated:** March 20, 2026  
**File Reference:** `/hod/dashboard.php`  
**Total Lines:** 1138
