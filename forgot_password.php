<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
session_start();

// If already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'secretary':
            header("Location: secretary/dashboard.php");
            break;
        case 'principal':
            header("Location: principal/dashboard.php");
            break;
        case 'hod':
            header("Location: hod/dashboard.php");
            break;
        case 'class_teacher':
            header("Location: teacher/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // Check if email exists using prepared statement
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $reset_result = resetPassword($email);
            
            if ($reset_result['success']) {
                $success = 'Password reset successful! Your temporary password is: <strong>' . htmlspecialchars($reset_result['temp_password']) . '</strong><br>Please use this to login and change your password.';
                
                // In production, you would send this via email
                // mail($email, 'Password Reset - RKS Student System', "Your temporary password is: " . $reset_result['temp_password']);
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } else {
            $error = 'Email address not found in our system.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RKMS Home Student Information System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background shapes */
        .shape {
            position: absolute;
            filter: blur(60px);
            opacity: 0.6;
            animation: float 20s infinite;
            z-index: 0;
        }

        .shape1 {
            top: -100px;
            left: -100px;
            width: 400px;
            height: 400px;
            background: #FF8C42; /* Orange */
            border-radius: 50%;
        }

        .shape2 {
            bottom: -100px;
            right: -100px;
            width: 350px;
            height: 350px;
            background: #FF6B35; /* Darker Orange */
            border-radius: 50%;
            animation-delay: -5s;
        }

        .shape3 {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            background: #FFD700; /* Gold accent */
            opacity: 0.4;
            animation: pulse 15s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.4; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.2; }
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 
                        0 0 0 2px rgba(255, 140, 66, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
            animation: slideUp 0.6s ease-out;
            border: 1px solid rgba(255, 140, 66, 0.2);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-wrapper {
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            width: 80px;
            height: 80px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px -5px rgba(255, 140, 66, 0.4);
            transform: rotate(-5deg);
            transition: transform 0.3s ease;
        }

        .logo-wrapper:hover {
            transform: rotate(0deg);
        }

        .logo-wrapper i {
            font-size: 40px;
            color: white;
        }

        .header h1 {
            color: #1a1f36;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .header p {
            color: #64748b;
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
        }

        .header p i {
            color: #FF8C42;
            margin-right: 5px;
        }

        /* Alert Styles */
        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-in-out;
            border: 1px solid transparent;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .alert i {
            font-size: 20px;
        }

        .alert-danger {
            background: #fff1f0;
            border-color: #ffccc7;
            color: #f5222d;
        }

        .alert-success {
            background: #f6ffed;
            border-color: #b7eb8f;
            color: #52c41a;
        }

        .alert-success strong {
            color: #FF8C42;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .form-group label i {
            color: #FF8C42;
            margin-right: 5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            color: #1e293b;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
        }

        .input-wrapper input:focus + i {
            color: #FF8C42;
        }

        .input-wrapper input::placeholder {
            color: #94a3b8;
            font-size: 15px;
        }

        /* Button Styles */
        .btn-reset {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px -5px rgba(255, 140, 66, 0.4);
        }

        .btn-reset:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-reset:hover:before {
            width: 300px;
            height: 300px;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(255, 140, 66, 0.5);
        }

        .btn-reset i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .btn-reset:hover i {
            transform: translateX(5px) rotate(90deg);
        }

        /* Footer Links */
        .footer-links {
            margin-top: 30px;
            text-align: center;
        }

        .back-link {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            background: #f8fafc;
        }

        .back-link:hover {
            color: #FF8C42;
            background: #fff1e6;
            transform: translateX(-5px);
        }

        .back-link i {
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .back-link:hover i {
            transform: translateX(-3px);
        }

        /* Security Note */
        .security-note {
            margin-top: 25px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .security-note i {
            color: #FF8C42;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .forgot-card {
                padding: 40px 20px;
            }
            
            .shape {
                opacity: 0.3;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }

        /* Loading Animation */
        .btn-reset.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-reset.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Animated Background Shapes -->
    <div class="shape shape1"></div>
    <div class="shape shape2"></div>
    <div class="shape shape3"></div>

    <div class="container">
        <div class="forgot-card">
            <div class="header">
                <div class="logo-wrapper">
                    <i class="fas fa-key"></i>
                </div>
                <h1>Forgot Password?</h1>
                <p><i class="fas fa-lock"></i> No worries, we'll help you reset it</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="forgotForm">
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            placeholder="Enter your registered email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                </div>

                <button type="submit" class="btn-reset" id="resetBtn">
                    <span>RESET PASSWORD</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer-links">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>

            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                <span>We'll send reset instructions to your email if it exists in our system.</span>
            </div>
        </div>
    </div>

    <script>
        // Loading animation on form submit
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('resetBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<span>PROCESSING...</span> <i class="fas fa-spinner"></i>';
        });

        // Real-time email validation
        document.getElementById('email').addEventListener('input', function(e) {
            const email = e.target.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            
            if (email && !isValid) {
                this.style.borderColor = '#f5222d';
            } else {
                this.style.borderColor = '';
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>