<?php
require_once __DIR__ . '/../config/database.php';

// Hash password using bcrypt (secure and modern)
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Verify password using bcrypt
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Login user
function loginUser($username, $password) {
    global $conn;
    
    // Use prepared statement to prevent SQL injection
    $query = "SELECT id, username, password, role, email, department FROM users 
              WHERE (username = ? OR email = ?) LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify password
        if (verifyPassword($password, $user['password'])) {
            return $user;
        }
    }
    
    $stmt->close();
    return false;
}

// Register user
function registerUser($data) {
    global $conn;
    
    $debug = [];
    $debug['step_1'] = 'Function started';
    
    $username = $data['username'];
    $password = hashPassword($data['password']);
    $email = $data['email'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $dept_id = intval($data['department']);
    
    $debug['input_data'] = [
        'username' => $username,
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'dept_id' => $dept_id
    ];
    $debug['step_2'] = 'Variables extracted';
    
    // Check if username exists using prepared statement
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$check_stmt) {
        return ['success' => false, 'message' => 'Failed to prepare username check: ' . $conn->error, 'debug' => $debug];
    }
    $debug['step_3'] = 'Username check prepared';
    
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $debug['step_4'] = 'Username check executed, rows found: ' . $check_result->num_rows;
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        return ['success' => false, 'message' => 'Username already exists', 'debug' => $debug];
    }
    $check_stmt->close();
    $debug['step_5'] = 'Username check passed';
    
    // Insert user using prepared statement
    $insert_stmt = $conn->prepare("INSERT INTO users (username, password, email, role, department) 
              VALUES (?, ?, ?, ?, ?)");
    
    if (!$insert_stmt) {
        return ['success' => false, 'message' => 'Failed to prepare user insert: ' . $conn->error, 'debug' => $debug];
    }
    $debug['step_6'] = 'User insert prepared';
    
    $role = 'student';
    $insert_stmt->bind_param("ssssi", $username, $password, $email, $role, $dept_id);
    $debug['step_7'] = 'Bind params done';
    
    if ($insert_stmt->execute()) {
        $user_id = $conn->insert_id;
        $debug['step_8'] = 'User inserted with ID: ' . $user_id;
        $insert_stmt->close();
        
        // Insert student record
        // registration number may be provided externally (e.g. transfer student)
        if (isset($data['reg_no']) && !empty($data['reg_no'])) {
            $reg_no = $data['reg_no'];
        } else {
            $reg_no = "RKS" . date('Y') . str_pad($user_id, 5, "0", STR_PAD_LEFT);
        }
        
        // Handle other activities
        $other_activities = isset($data['other_activity']) && !empty($data['other_activity']) ? $data['other_activity'] : '';
        
        // Get video file from data array
        $video_file = isset($data['video_file']) && !empty($data['video_file']) ? $data['video_file'] : '';
        
        // Prepare additional fields
        $father_name = isset($data['father_name']) ? $data['father_name'] : '';
        $mother_name = isset($data['mother_name']) ? $data['mother_name'] : '';
        $aadhar_number = isset($data['aadhar']) ? $data['aadhar'] : '';
        $dob = isset($data['dob']) ? $data['dob'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $student_email = isset($data['email']) ? $data['email'] : '';
        $semester = isset($data['semester']) ? intval($data['semester']) : 1;

        $student_stmt = $conn->prepare("INSERT INTO students (user_id, registration_number, first_name, last_name, department_id, video_file, other_activities, father_name, mother_name, aadhar_number, date_of_birth, semester, mobile, email) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$student_stmt) {
            return ['success' => false, 'message' => 'Failed to prepare student insert: ' . $conn->error];
        }
        
        $student_stmt->bind_param("isssissssssiss", 
            $user_id, $reg_no, $first_name, $last_name, $dept_id, 
            $video_file, $other_activities, $father_name, $mother_name, 
            $aadhar_number, $dob, $semester, $mobile, $student_email);
        
        if ($student_stmt->execute()) {
            $student_id = $conn->insert_id;
            $student_stmt->close();
            
            // Add selected sports
            if (isset($data['sports']) && is_array($data['sports']) && count($data['sports']) > 0) {
                $sport_stmt = $conn->prepare("INSERT INTO student_sports (student_id, sport_id) VALUES (?, ?)");
                if ($sport_stmt) {
                    foreach ($data['sports'] as $sport_id) {
                        $sport_id = intval($sport_id);
                        $sport_stmt->bind_param("ii", $student_id, $sport_id);
                        $sport_stmt->execute();
                    }
                    $sport_stmt->close();
                }
            }
            
            // Add "other sport" if provided
            if (isset($data['other_sport']) && !empty($data['other_sport'])) {
                $other_sport = $data['other_sport'];
                
                // Check if this custom sport already exists
                $check_sport_stmt = $conn->prepare("SELECT id FROM sports WHERE sport_name = ?");
                if ($check_sport_stmt) {
                    $check_sport_stmt->bind_param("s", $other_sport);
                    $check_sport_stmt->execute();
                    $sport_result = $check_sport_stmt->get_result();
                    
                    if ($sport_result->num_rows > 0) {
                        $sport_row = $sport_result->fetch_assoc();
                        $sport_id = $sport_row['id'];
                    } else {
                        // Create new sport
                        $insert_sport_stmt = $conn->prepare("INSERT INTO sports (sport_name, description) VALUES (?, ?)");
                        if ($insert_sport_stmt) {
                            $description = 'User-added sport';
                            $insert_sport_stmt->bind_param("ss", $other_sport, $description);
                            $insert_sport_stmt->execute();
                            $sport_id = $conn->insert_id;
                            $insert_sport_stmt->close();
                        }
                    }
                    $check_sport_stmt->close();
                    
                    // Link sport to student
                    $link_sport_stmt = $conn->prepare("INSERT INTO student_sports (student_id, sport_id) VALUES (?, ?)");
                    if ($link_sport_stmt) {
                        $link_sport_stmt->bind_param("ii", $student_id, $sport_id);
                        $link_sport_stmt->execute();
                        $link_sport_stmt->close();
                    }
                }
            }
            
            // Add selected activities
            if (isset($data['activities']) && is_array($data['activities']) && count($data['activities']) > 0) {
                $activity_stmt = $conn->prepare("INSERT INTO student_activities (student_id, activity_id) VALUES (?, ?)");
                if ($activity_stmt) {
                    foreach ($data['activities'] as $activity_id) {
                        $activity_id = intval($activity_id);
                        $activity_stmt->bind_param("ii", $student_id, $activity_id);
                        $activity_stmt->execute();
                    }
                    $activity_stmt->close();
                }
            }
            
            // Add "other activity" if provided  
            if (!empty($other_activities)) {
                // Check if this custom activity already exists
                $check_activity_stmt = $conn->prepare("SELECT id FROM activities WHERE activity_name = ?");
                if ($check_activity_stmt) {
                    $check_activity_stmt->bind_param("s", $other_activities);
                    $check_activity_stmt->execute();
                    $activity_result = $check_activity_stmt->get_result();
                    
                    if ($activity_result->num_rows > 0) {
                        $activity_row = $activity_result->fetch_assoc();
                        $activity_id = $activity_row['id'];
                    } else {
                        // Create new activity
                        $insert_activity_stmt = $conn->prepare("INSERT INTO activities (activity_name, description) VALUES (?, ?)");
                        if ($insert_activity_stmt) {
                            $description = 'User-added activity';
                            $insert_activity_stmt->bind_param("ss", $other_activities, $description);
                            $insert_activity_stmt->execute();
                            $activity_id = $conn->insert_id;
                            $insert_activity_stmt->close();
                        }
                    }
                    $check_activity_stmt->close();
                    
                    // Link activity to student
                    $link_activity_stmt = $conn->prepare("INSERT INTO student_activities (student_id, activity_id) VALUES (?, ?)");
                    if ($link_activity_stmt) {
                        $link_activity_stmt->bind_param("ii", $student_id, $activity_id);
                        $link_activity_stmt->execute();
                        $link_activity_stmt->close();
                    }
                }
            }
            
            $debug['step_final_success'] = 'All operations completed successfully';
            $debug['user_id'] = $user_id;
            $debug['registration_number'] = $reg_no;
            $debug['student_id'] = $student_id;
            
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id, 'registration_number' => $reg_no, 'debug' => $debug];
        } else {
            $error_msg = 'Failed to create student record: ' . $student_stmt->error;
            $debug['step_error'] = 'Student insert failed';
            $debug['student_insert_error'] = $student_stmt->error;
            $student_stmt->close();
            return ['success' => false, 'message' => $error_msg, 'debug' => $debug];
        }
    } else {
        $debug['step_error'] = 'User insert failed';
        $debug['user_insert_error'] = $insert_stmt->error;
        $insert_stmt->close();
        return ['success' => false, 'message' => 'Registration failed: ' . $debug['user_insert_error'], 'debug' => $debug];
    }
}

// Get user by ID
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $user;
}

// Update password
function updatePassword($user_id, $old_password, $new_password) {
    global $conn;
    
    $user = getUserById($user_id);
    if (!$user) return false;
    
    if (!verifyPassword($old_password, $user['password'])) {
        return false;
    }
    
    $new_pass = hashPassword($new_password);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$stmt) return false;
    
    $stmt->bind_param("si", $new_pass, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Reset password (for forgot password)
function resetPassword($email) {
    global $conn;
    
    $temp_pass = bin2hex(random_bytes(4)); // Generate temporary password
    $hashed_pass = hashPassword($temp_pass);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    if (!$stmt) {
        return ['success' => false];
    }
    
    $stmt->bind_param("ss", $hashed_pass, $email);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'temp_password' => $temp_pass];
    }
    
    $stmt->close();
    return ['success' => false];
}

// Get total users count
function getTotalUsers() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    return 0;
}

// Get active users today
function getActiveUsersToday() {
    global $conn;
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM activity_log WHERE DATE(created_at) = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }
    $stmt->close();
    return $count;
}

// Get system status
function getSystemStatus() {
    global $conn;
    
    // Check if database connection is active
    if ($conn->connect_error) {
        return 'offline';
    }
    
    return 'online';
}

// Log activity
function logActivity($user_id, $action, $description) {
    global $conn;
    
    $user_id = intval($user_id);
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) return false;
    
    $stmt->bind_param("iss", $user_id, $action, $description);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// send a message to one or multiple users
function sendMessageToUsers($sender_id, $receiver_ids, $subject, $message) {
    global $conn;
    
    if (!is_array($receiver_ids)) {
        $receiver_ids = [$receiver_ids];
    }
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
    if (!$stmt) return false;
    
    $sender_id = intval($sender_id);
    
    foreach ($receiver_ids as $rid) {
        $rid = intval($rid);
        if ($rid > 0) {
            $stmt->bind_param("iiss", $sender_id, $rid, $subject, $message);
            $stmt->execute();
        }
    }
    
    $stmt->close();
    return true;
}

// notify administrators, hods and class-teachers in student's department
function notifyProfileEdit($student_id, $customMessage = '') {
    global $conn;
    
    $student_id = intval($student_id);
    
    $sql = "SELECT s.*, d.dept_code, u.username FROM students s 
            LEFT JOIN departments d ON s.department_id = d.id 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows == 0) {
        $stmt->close();
        return false;
    }
    
    $student = $res->fetch_assoc();
    $stmt->close();
    
    $sender_id = intval($student['user_id']);
    $dept_code = $student['dept_code'] ?? '';

    $name = trim($student['first_name'] . ' ' . $student['last_name']);
    $regno = $student['registration_number'] ?? '';
    $subject = "Profile Update Notification";
    
    if ($customMessage) {
        $message = $customMessage;
    } else {
        $message = "Student $name (Reg: $regno) has updated their profile information.";
    }

    $receivers = [];
    
    // Get admin users
    $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = ?");
    if ($admin_stmt) {
        $role = 'admin';
        $admin_stmt->bind_param("s", $role);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        while ($row = $admin_result->fetch_assoc()) {
            $receivers[] = $row['id'];
        }
        $admin_stmt->close();
    }
    
    if ($dept_code) {
        // Get HOD for the department
        $hod_stmt = $conn->prepare("SELECT id FROM users WHERE role = ? AND department = ?");
        if ($hod_stmt) {
            $role = 'hod';
            $hod_stmt->bind_param("ss", $role, $dept_code);
            $hod_stmt->execute();
            $hod_result = $hod_stmt->get_result();
            while ($row = $hod_result->fetch_assoc()) {
                $receivers[] = $row['id'];
            }
            $hod_stmt->close();
        }
        
        // Get class teachers for the department
        $teacher_stmt = $conn->prepare("SELECT id FROM users WHERE role = ? AND department = ?");
        if ($teacher_stmt) {
            $role = 'class_teacher';
            $teacher_stmt->bind_param("ss", $role, $dept_code);
            $teacher_stmt->execute();
            $teacher_result = $teacher_stmt->get_result();
            while ($row = $teacher_result->fetch_assoc()) {
                $receivers[] = $row['id'];
            }
            $teacher_stmt->close();
        }
    }

    if (!empty($receivers)) {
        // Remove duplicate IDs
        $receivers = array_unique($receivers);
        return sendMessageToUsers($sender_id, $receivers, $subject, $message);
    }
    
    return false;
}
?>
