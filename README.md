# RKMS Student Information System

## Welcome to Ramakrishna Mission Student Information System

A comprehensive web-based student management system built with PHP and MySQL for Ramakrishna Mission Polytechnic College.

---

## 🚀 QUICK START GUIDE

### STEP 1: DATABASE SETUP

1. **Open phpMyAdmin**: http://localhost/phpmyadmin
2. **Create a new database** named `rks_student_system`
3. **Go to the SQL tab** in phpMyAdmin
4. **Copy and paste** the entire SQL code from `/config/database.sql`
5. **Execute the script** to create all tables

Alternatively, run this in your terminal:
```bash
mysql -u root -p < config/database.sql
```

---

## 📝 DEFAULT ADMIN CREDENTIALS

After running the setup, use these credentials to login:

```
Username: admin
Password: ****
Role: Admin
```

**⚠️ IMPORTANT: Change admin password after first login for security!**

---

## 📂 PROJECT STRUCTURE

```
final/
├── config/
│   ├── database.php          (Database configuration)
│   └── database.sql          (Database schema)
├── includes/
│   ├── auth.php              (Authentication functions)
│   └── functions.php         (Utility functions)
├── public/
│   ├── css/
│   │   └── style.css         (Main stylesheet)
│   ├── js/
│   └── images/
├── uploads/
│   ├── profile_pic/          (Student profile pictures)
│   └── certificates/         (Student certificates)
├── admin/                    (Admin panel)
├── secretary/                (Secretary portal)
├── principal/                (Principal portal)
├── hod/                      (HOD portals - DME, DAE, DCE)
├── teacher/                  (Class teacher portal)
├── student/                  (Student portal)
├── index.php                 (Login page)
├── register.php              (Student registration)
├── forgot_password.php       (Password recovery)
└── logout.php                (Logout functionality)
```

---

## 🔐 USER ROLES & ACCESS

### 1. **ADMIN** 
- Full access to system
- Manage users (add, edit, delete)
- Manage students
- View all departments
- Export data (CSV)
- View all messages
- System settings

### 2. **SECRETARY**
- Access to student records
- Export data
- View reports
- Manage documents

### 3. **PRINCIPAL**
- View all statistics
- Departmental information
- Institution-wide reports
- Overview of all activities

### 4. **HOD** (Head of Department)
- Department-specific modules for:
  - Diploma in Mechanical Engineering (DME)
  - Diploma in Automobile Engineering (DAE)
  - Diploma in Civil Engineering (DCE)
- Manage department students
- View department faculty
- Messages and communications
- Department reports

### 5. **CLASS TEACHER**
- View student list
- Receive student messages
- Send messages to students
- Mark attendance (coming soon)

### 6. **STUDENT**
- View personal profile
- Edit profile information
- Upload profile picture
- Select sports and activities (NSS, JRC, etc.)
- Upload certificates
- Send messages to class teachers
- View teacher messages
- Check dashboard with registration number

---

## ✨ KEY FEATURES

### Student Management
- ✅ Registration with username and password
- ✅ Full profile information (Name, Father name, Mother name, Aadhar, DOB)
- ✅ Profile picture upload
- ✅ Sports selection (Cricket, Football, Basketball, Badminton, etc.)
- ✅ Activities selection (NSS, JRC, NCC, Tech Club, etc.)
- ✅ Certificate uploads
- ✅ Edit profile anytime

### Communication System
- ✅ Student to Teacher messaging
- ✅ Teacher to Student messaging
- ✅ Message inbox/outbox
- ✅ Read/Unread status
- ✅ Subject-based organization

### Data Management
- ✅ Export student data to CSV
- ✅ Department-wise reports
- ✅ Student statistics
- ✅ User management

### Security
- ✅ Role-based access control
- ✅ Password hashing
- ✅ Session management
- ✅ Login notifications
- ✅ Forgot password functionality

---

## 🛠 INSTALLATION & SETUP

### Prerequisites
- Apache Web Server (XAMPP)
- MySQL (Local or Remote)
- PHP 7.0 or higher
- Browser (Chrome, Firefox, Edge, Safari)

### Step-by-Step Installation

1. **Extract the project** to your htdocs folder:
   ```
   D:\xampp\htdocs\final\
   ```

2. **Start Apache and MySQL** from XAMPP Control Panel

3. **Import the database**:
   - Open: http://localhost/phpmyadmin
   - Create database: `rks_student_system`
   - Import `/config/database.sql`

4. **Access the application**:
   - Open: http://localhost/final/
   - Login with admin / admin123

5. **Create new admin/staff accounts**:
   - Use Admin Dashboard → Users → Add User

---

## 📊 DATABASE TABLES

### Core Tables
- **users** - Login credentials for all roles
- **students** - Student profile information
- **departments** - Department details (DME, DAE, DCE)
- **sports** - Available sports
- **student_sports** - Student-Sport mapping
- **activities** - Student activities/clubs
- **student_activities** - Student-Activity mapping
- **certificates** - Student certificates
- **messages** - Communication between users

---

## 🎨 FEATURES SUMMARY

| Feature | Admin | Secretary | Principal | HOD | Teacher | Student |
|---------|-------|-----------|-----------|-----|---------|---------|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage Users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Students | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Export Data | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Messages | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit Profile | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Upload Pic | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View Reports | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## 🔄 USER WORKFLOW

### Student Registration & Login
1. Visit http://localhost/final/
2. Click "Register Here"
3. Fill registration form with all details
4. Select Department (DME/DAE/DCE)
5. Create username and password
6. Click Register
7. Login with credentials
8. Complete profile with picture, sports, activities

### Managing Student Profile
1. Student logs in
2. Goes to "Edit Profile"
3. Updates personal information
4. Uploads/changes profile picture
5. Selects sports and activities/clubs
6. Uploads certificates (if any)
7. Saves changes

### Communication
1. Student clicks "Message Teacher"
2. Selects teacher from dropdown
3. Enters subject and message
4. Teacher receives notification
5. Teacher replies to student
6. Student checks inbox for reply

---

## 🗂 IMPORTANT NOTES

1. **Database Configuration**: Edit `/config/database.php` if using remote database
2. **Email Setup**: Configure email settings in main files for notifications
3. **Upload Limits**: Check PHP upload limits for profile pictures
4. **Session Timeout**: Default session timeout is based on browser close
5. **Backup**: Regularly backup your database

---

## 🎯 DEFAULT SPORTS

- Cricket
- Football
- Basketball
- Badminton
- Tennis
- Volleyball
- Hockey
- Athletics

---

## 🏫 DEFAULT ACTIVITIES/CLUBS

- NSS (National Service Scheme)
- JRC (Junior Red Cross)
- NCC (National Cadet Corps)
- Cultural Committee
- Sports Committee
- Tech Club
- Debate Club

---

## ⚙️ CUSTOMIZATION

### Add New Departments
1. Admin Dashboard → Departments
2. Add new department code and name
3. Edit department HOD assignment

### Add New Sports/Activities
1. Edit `database.sql` INSERT statements
2. Or directly add via Admin Dashboard after setup

### Change Styling
- Edit `/public/css/style.css`
- All color schemes, fonts, layouts can be customized

---

## 🐛 TROUBLESHOOTING

### Login Issues
- Check username and password are correct
- Ensure database is running
- Clear browser cache
- Check PHP error logs

### Database Connection Error
- Verify MySQL is running
- Check credentials in `/config/database.php`
- Ensure database `rks_student_system` exists

### Upload Issues
- Check PHP upload_max_filesize setting
- Verify folder permissions for `/uploads/`
- Ensure file format is supported (jpg, png, pdf)

---

## 📧 SUPPORT & MAINTENANCE

For issues or feature requests:
1. Check error logs in `/tmp/` 
2. Verify all files are in correct locations
3. Ensure PHP version compatibility
4. Test with different browsers

---

## 📜 VERSION INFORMATION

- **Version**: 1.0.0
- **PHP Required**: 7.0+
- **MySQL Required**: 5.5+
- **Last Updated**: February 2026

---

## ✉️ CONTACT

**Ramakrishna Mission Education System**
- Polytechnic College, India

---

**Happy Learning! 🎓**
