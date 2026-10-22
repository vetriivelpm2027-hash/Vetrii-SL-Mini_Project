<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>RKSMSHOME | Student Command Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #FEF7E8;
            overflow-x: hidden;
        }

        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap');

        :root {
            --rks-orange: #F97316;
            --rks-orange-dark: #EA580C;
            --rks-orange-glow: rgba(249, 115, 22, 0.4);
            --rks-orange-soft: #FFF4E6;
            --rks-orange-gradient: linear-gradient(135deg, #FF8C42 0%, #F97316 100%);
            --rks-shadow: 0 20px 35px -12px rgba(249, 115, 22, 0.2);
            --rks-card-bg: rgba(255, 255, 255, 0.95);
            --rks-border: 1px solid rgba(249, 115, 22, 0.15);
            --rks-text-dark: #1E1B1A;
            --rks-text-muted: #7C6E65;
        }

        /* animated orbs */
        .orb-bg {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -2;
            overflow: hidden;
            background: radial-gradient(circle at 20% 30%, #FFF2E0, #FEEAD6);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.5;
            animation: floatOrb 22s infinite alternate ease-in-out;
        }

        .orb-1 {
            width: 450px;
            height: 450px;
            background: #FFB347;
            top: -150px;
            right: -100px;
            animation-duration: 18s;
        }

        .orb-2 {
            width: 550px;
            height: 550px;
            background: #FF8A3C;
            bottom: -200px;
            left: -150px;
            animation-duration: 25s;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 350px;
            height: 350px;
            background: #FFD966;
            top: 40%;
            left: 20%;
            filter: blur(90px);
            animation-duration: 20s;
        }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(4%, 6%) scale(1.08); }
        }

        /* dashboard layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* SIDEBAR — premium glassmorph */
        .sidebar {
            width: 290px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-right: 2px solid rgba(249, 115, 22, 0.2);
            position: fixed;
            height: 100vh;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            z-index: 100;
            box-shadow: 8px 0 28px rgba(0, 0, 0, 0.03);
        }

        .sidebar-header {
            padding: 32px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(249, 115, 22, 0.2);
            margin-bottom: 20px;
        }

        .logo-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: var(--rks-orange-gradient);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 25px -8px rgba(249, 115, 22, 0.5);
            transition: transform 0.3s;
        }

        .logo-circle i {
            font-size: 34px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 800;
            background: var(--rks-orange-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
        }

        .sidebar-sub {
            font-size: 11px;
            font-weight: 500;
            color: var(--rks-text-muted);
            margin-top: 6px;
        }

        .nav-menu {
            list-style: none;
            padding: 0 16px;
        }

        .nav-menu li {
            margin: 8px 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 18px;
            border-radius: 20px;
            font-weight: 500;
            color: #4A3B32;
            text-decoration: none;
            transition: all 0.25s;
            font-size: 15px;
        }

        .nav-menu a i {
            width: 26px;
            font-size: 1.2rem;
            color: var(--rks-orange);
            transition: all 0.2s;
        }

        .nav-menu a.active {
            background: var(--rks-orange-gradient);
            color: white;
            box-shadow: 0 8px 18px rgba(249, 115, 22, 0.25);
        }

        .nav-menu a.active i {
            color: white;
        }

        .nav-menu a:not(.active):hover {
            background: var(--rks-orange-soft);
            transform: translateX(5px);
        }

        .badge-msg {
            background: #F97316;
            color: white;
            border-radius: 40px;
            padding: 2px 9px;
            font-size: 11px;
            font-weight: bold;
            margin-left: auto;
        }

        /* main content */
        .main {
            flex: 1;
            margin-left: 290px;
            padding: 28px 36px;
            transition: margin 0.3s;
        }

        /* top header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 800;
            background: var(--rks-orange-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .user-card {
            background: white;
            padding: 8px 20px 8px 16px;
            border-radius: 100px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--rks-shadow);
            border: var(--rks-border);
            backdrop-filter: blur(4px);
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            background: var(--rks-orange-gradient);
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: white;
            box-shadow: 0 4px 12px rgba(249,115,22,0.3);
        }

        /* stats grid */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 22px;
            margin-bottom: 38px;
        }

        .stat-tile {
            background: white;
            border-radius: 28px;
            padding: 20px 22px;
            transition: all 0.25s ease;
            border: var(--rks-border);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
        }

        .stat-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 30px -15px rgba(249, 115, 22, 0.25);
            border-color: rgba(249, 115, 22, 0.4);
        }

        .stat-icon {
            background: #FFF0E3;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            margin-bottom: 18px;
        }

        .stat-icon i {
            font-size: 26px;
            color: var(--rks-orange);
        }

        .stat-tile h4 {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--rks-text-muted);
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 38px;
            font-weight: 800;
            color: var(--rks-orange-dark);
            line-height: 1;
        }

        /* profile card */
        .profile-modern {
            background: white;
            border-radius: 32px;
            padding: 28px 32px;
            margin-bottom: 36px;
            border: var(--rks-border);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.04);
        }

        .profile-flex {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            align-items: center;
        }

        .avatar-large {
            width: 112px;
            height: 112px;
            background: linear-gradient(145deg, #FFB347, #F97316);
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
            color: white;
            box-shadow: 0 15px 30px rgba(249, 115, 22, 0.3);
        }

        .info-details h2 {
            font-size: 28px;
            font-weight: 800;
            color: #2D241F;
        }

        .dept-badge {
            background: var(--rks-orange-soft);
            color: var(--rks-orange-dark);
            padding: 5px 15px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            margin: 10px 0;
        }

        .info-grid-sm {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .info-pill {
            background: #FEF4EC;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 14px;
        }

        .tags-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 20px 0 10px;
        }

        .tag-orange {
            background: var(--rks-orange-gradient);
            padding: 7px 20px;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }

        .video-status-card {
            background: #FEF3E8;
            border-left: 6px solid var(--rks-orange);
            padding: 14px 22px;
            border-radius: 20px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* messages */
        .section-glass {
            background: white;
            border-radius: 32px;
            padding: 26px 28px;
            margin-bottom: 32px;
            border: var(--rks-border);
            transition: all 0.2s;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2D241F;
        }

        .message-item {
            background: #FEFAF5;
            padding: 18px;
            border-radius: 24px;
            margin-bottom: 14px;
            transition: all 0.2s;
            border: 1px solid rgba(249,115,22,0.1);
        }

        .message-item:hover {
            background: #FFF5EB;
            transform: translateX(6px);
        }

        /* events */
        .event-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .event-card-mini {
            background: #FFF9F2;
            border-radius: 24px;
            padding: 16px 18px;
            flex: 1;
            min-width: 180px;
            border-left: 4px solid var(--rks-orange);
        }

        .btn-group {
            display: flex;
            gap: 14px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 11px 26px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--rks-orange-gradient);
            color: white;
            box-shadow: 0 8px 18px rgba(249,115,22,0.25);
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--rks-orange);
            color: var(--rks-orange-dark);
        }

        .btn-primary:hover, .btn-outline:hover {
            transform: translateY(-3px);
        }

        /* debug info toggle */
        .debug-collapse {
            background: rgba(249, 115, 22, 0.08);
            border-radius: 24px;
            padding: 14px 22px;
            margin-bottom: 28px;
            font-size: 13px;
            backdrop-filter: blur(4px);
        }

        /* responsive */
        @media (max-width: 880px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 520px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            .profile-flex {
                flex-direction: column;
                text-align: center;
            }
        }

        /* loader */
        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255,247,240,0.95);
            backdrop-filter: blur(8px);
            z-index: 999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s;
        }
        .spinner-orange {
            width: 56px;
            height: 56px;
            border: 5px solid #FFDFC4;
            border-top: 5px solid var(--rks-orange);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="loader-overlay" id="loader">
    <div class="spinner-orange"></div>
</div>
<div class="orb-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-wrapper">
                <div class="logo-circle">
                    <i class="fas fa-crown"></i>
                </div>
            </div>
            <div class="sidebar-brand">RKSMSHOME</div>
            <div class="sidebar-sub">Student Command Center</div>
        </div>
        <ul class="nav-menu">
            <li><a href="#" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="#"><i class="fas fa-id-card"></i> View Profile</a></li>
            <li><a href="#"><i class="fas fa-pen-fancy"></i> Edit Profile</a></li>
            <li><a href="#"><i class="fas fa-paper-plane"></i> Message Teacher</a></li>
            <li><a href="#"><i class="fas fa-inbox"></i> Inbox <span class="badge-msg" id="msgCountBadge">3</span></a></li>
            <li><a href="#"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <div style="padding: 28px 20px; margin-top: auto;">
            <div style="background: #FFF1E6; border-radius: 28px; padding: 15px;">
                <i class="fas fa-clock" style="color: #F97316;"></i>
                <div style="font-weight:600; margin-top: 6px;">System ready</div>
                <div id="liveClock" style="font-size:13px; color:#5D4A3A;">--:-- --</div>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main">
        <div class="top-header">
            <div class="page-title">
                <h1><i class="fas fa-tachometer-alt"></i> Student Dashboard</h1>
                <div style="font-size: 13px; color:#B87C4E;">welcome back, energetic learner</div>
            </div>
            <div class="user-card">
                <div class="user-avatar">AJ</div>
                <span style="font-weight:600;">Aarav Joshi</span>
                <i class="fas fa-chevron-down" style="color:#F97316;"></i>
            </div>
        </div>

        <!-- Debug info box (smart) -->
        <div class="debug-collapse" id="debugBox">
            <div style="display: flex; justify-content: space-between; align-items:center;">
                <span><i class="fas fa-check-circle" style="color:#F97316;"></i> <strong>Dashboard operational</strong> — student session active</span>
                <button id="toggleDebugBtn" style="background:none; border:none; color:#F97316; cursor:pointer;">🔍 View debug</button>
            </div>
            <div id="debugDetails" style="display: none; margin-top: 12px; font-family: monospace; font-size:12px; background:#FEF2E6; padding:12px; border-radius:16px;">
                <strong>Session Info:</strong> user_id = 1042, role = student<br>
                <strong>Student record:</strong> ID: STU-2412, Department: Computer Science & Engineering<br>
                <strong>Query status:</strong> all prepared statements ✓<br>
                <strong>Timestamp:</strong> <span id="liveTimestamp"></span><br>
                ✅ Sports & activities loaded, attendance synced.
            </div>
        </div>

        <!-- Statistics grid -->
        <div class="stats-row">
            <div class="stat-tile"><div class="stat-icon"><i class="fas fa-qrcode"></i></div><h4>Registration No</h4><div class="stat-number">RKS/CS/2409</div></div>
            <div class="stat-tile"><div class="stat-icon"><i class="fas fa-layer-group"></i></div><h4>Semester</h4><div class="stat-number">IV</div></div>
            <div class="stat-tile"><div class="stat-icon"><i class="fas fa-futbol"></i></div><h4>Sports</h4><div class="stat-number">2</div></div>
            <div class="stat-tile"><div class="stat-icon"><i class="fas fa-theater-masks"></i></div><h4>Activities</h4><div class="stat-number">3</div></div>
            <div class="stat-tile"><div class="stat-icon"><i class="fas fa-envelope"></i></div><h4>Messages</h4><div class="stat-number" id="msgCountDisplay">3</div></div>
            <div class="stat-tile"><div class="stat-icon"><i class="fas fa-video"></i></div><h4>Video Status</h4><div class="stat-number">✓</div></div>
        </div>

        <!-- Enhanced Profile Section -->
        <div class="profile-modern">
            <div class="profile-flex">
                <div class="avatar-large">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="info-details">
                    <h2>Aarav Joshi <span style="font-size: 16px; background:#FFF0E3; padding:2px 12px; border-radius:40px;">Active</span></h2>
                    <div class="dept-badge"><i class="fas fa-microchip"></i> Computer Science & Engineering</div>
                    <p><i class="fas fa-envelope"></i> aarav.joshi@rksmshome.edu.in &nbsp;|&nbsp; <i class="fas fa-phone-alt"></i> +91 98765 43210</p>
                    <p><i class="fas fa-map-marker-alt"></i> Bhopal, Madhya Pradesh, India</p>
                </div>
            </div>
            <div class="info-grid-sm">
                <div class="info-pill"><i class="fas fa-calendar-alt"></i> DOB: 12 May 2004</div>
                <div class="info-pill"><i class="fas fa-user-friends"></i> Father: Rakesh Joshi</div>
                <div class="info-pill"><i class="fas fa-id-card"></i> Aadhar: XXXX-XXXX-1234</div>
            </div>
            <div class="tags-group">
                <span class="tag-orange"><i class="fas fa-futbol"></i> Football (Striker)</span>
                <span class="tag-orange"><i class="fas fa-table-tennis"></i> Table Tennis</span>
                <span class="tag-orange"><i class="fas fa-microphone-alt"></i> Debate Club</span>
                <span class="tag-orange"><i class="fas fa-code"></i> Coding Ninjas</span>
                <span class="tag-orange"><i class="fas fa-dumbbell"></i> Fitness Club</span>
            </div>
            <div class="video-status-card">
                <i class="fas fa-check-circle" style="font-size: 28px; color:#F97316;"></i>
                <div><strong style="font-size: 15px;">🎥 Promotional video uploaded</strong><br>Your profile video is active & visible to recruiters</div>
            </div>
            <div class="btn-group">
                <a href="#" class="btn btn-primary"><i class="fas fa-eye"></i> Full profile</a>
                <a href="#" class="btn btn-outline"><i class="fas fa-edit"></i> Edit details</a>
                <a href="#" class="btn btn-outline"><i class="fas fa-paper-plane"></i> Message coordinator</a>
            </div>
        </div>

        <!-- Recent Messages Section -->
        <div class="section-glass">
            <div class="section-title"><i class="fas fa-envelope-open-text" style="color:#F97316;"></i> Latest conversations</div>
            <div id="messagesList">
                <div class="message-item"><div><i class="fas fa-chalkboard-teacher"></i> <strong>Prof. Meera Sharma</strong> · <span style="font-size:12px;">2 hours ago</span></div><div style="font-weight:500;">Project submission deadline extended</div><div>Please submit your mini-project by 25th March. Great work so far!</div></div>
                <div class="message-item"><div><i class="fas fa-user-tie"></i> <strong>Sports Office</strong> · <span style="font-size:12px;">Yesterday</span></div><div style="font-weight:500;">Football trials reminder</div><div>Trials on Saturday 8:00 AM at university ground.</div></div>
                <div class="message-item"><div><i class="fas fa-laptop-code"></i> <strong>Hackathon Cell</strong> · <span style="font-size:12px;">3 days ago</span></div><div style="font-weight:500;">Team formation for HackRKS</div><div>Register before 20th March to secure slot.</div></div>
            </div>
            <div style="margin-top: 20px; text-align:center;"><a href="#" class="btn btn-outline"><i class="fas fa-arrow-right"></i> View all messages</a></div>
        </div>

        <!-- Events & Announcements -->
        <div class="section-glass">
            <div class="section-title"><i class="fas fa-calendar-star"></i> Upcoming Highlights</div>
            <div class="event-row">
                <div class="event-card-mini"><div style="font-weight:800; font-size:26px; color:#F97316;">21</div><div style="font-weight:600;">March</div><div>🏆 Inter-college Cricket Finals</div><div style="font-size:12px;">Venue: Central Sports Complex</div></div>
                <div class="event-card-mini"><div style="font-weight:800; font-size:26px; color:#F97316;">25</div><div style="font-weight:600;">March</div><div>💡 Tech Talk: AI in Sports Analytics</div><div style="font-size:12px;">Auditorium, 4PM</div></div>
                <div class="event-card-mini"><div style="font-weight:800; font-size:26px; color:#F97316;">30</div><div style="font-weight:600;">March</div><div>📸 Annual Cultural Fest registration</div><div style="font-size:12px;">Last date to enroll</div></div>
            </div>
        </div>
    </main>
</div>

<script>
    // loader fadeout
    window.addEventListener('load', () => {
        const loader = document.getElementById('loader');
        if(loader) {
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }, 700);
        }
    });

    // live clock
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        const clockElem = document.getElementById('liveClock');
        if(clockElem) clockElem.innerText = timeStr;
        const tsElem = document.getElementById('liveTimestamp');
        if(tsElem) tsElem.innerText = now.toLocaleString();
    }
    updateClock();
    setInterval(updateClock, 1000);

    // dynamic badge & message count interaction
    let msgCount = 3;
    const msgBadge = document.getElementById('msgCountBadge');
    const msgDisplay = document.getElementById('msgCountDisplay');
    function updateMsgUI() {
        if(msgBadge) msgBadge.innerText = msgCount;
        if(msgDisplay) msgDisplay.innerText = msgCount;
    }

    // simulate new message after 15 sec (just for advanced vibe)
    setTimeout(() => {
        msgCount = 4;
        updateMsgUI();
        const msgContainer = document.getElementById('messagesList');
        const newMsg = document.createElement('div');
        newMsg.className = 'message-item';
        newMsg.innerHTML = `<div><i class="fas fa-bell"></i> <strong>RKS System</strong> · <span style="font-size:12px;">just now</span></div><div style="font-weight:500;">New sports facility booking available</div><div>Book slots for basketball & swimming pool now.</div>`;
        msgContainer.insertBefore(newMsg, msgContainer.firstChild);
        // tiny ripple effect
    }, 15000);

    // Debug toggle
    const debugBtn = document.getElementById('toggleDebugBtn');
    const debugDetailsDiv = document.getElementById('debugDetails');
    if(debugBtn) {
        debugBtn.addEventListener('click', () => {
            if(debugDetailsDiv.style.display === 'none') {
                debugDetailsDiv.style.display = 'block';
                debugBtn.innerHTML = '🔽 Hide debug';
            } else {
                debugDetailsDiv.style.display = 'none';
                debugBtn.innerHTML = '🔍 View debug';
            }
        });
    }

    // Mobile sidebar toggle simulation (optional)
    let isMobile = window.innerWidth <= 880;
    function handleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        if(window.innerWidth <= 880) {
            if(!document.querySelector('.menu-toggle-btn')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.className = 'menu-toggle-btn';
                toggleBtn.style.position = 'fixed';
                toggleBtn.style.bottom = '20px';
                toggleBtn.style.right = '20px';
                toggleBtn.style.zIndex = '1000';
                toggleBtn.style.background = '#F97316';
                toggleBtn.style.border = 'none';
                toggleBtn.style.width = '52px';
                toggleBtn.style.height = '52px';
                toggleBtn.style.borderRadius = '60px';
                toggleBtn.style.color = 'white';
                toggleBtn.style.fontSize = '24px';
                toggleBtn.style.boxShadow = '0 8px 20px rgba(249,115,22,0.4)';
                toggleBtn.style.cursor = 'pointer';
                document.body.appendChild(toggleBtn);
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('mobile-open');
                });
            }
        } else {
            const existingBtn = document.querySelector('.menu-toggle-btn');
            if(existingBtn) existingBtn.remove();
            sidebar.classList.remove('mobile-open');
        }
    }
    window.addEventListener('resize', handleMobileSidebar);
    handleMobileSidebar();

    // ripple effect for buttons (optional)
    document.querySelectorAll('.btn, .nav-menu a').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(255,255,255,0.5)';
            ripple.style.width = '20px';
            ripple.style.height = '20px';
            ripple.style.transform = 'scale(0)';
            ripple.style.transition = 'transform 0.5s, opacity 0.5s';
            ripple.style.pointerEvents = 'none';
            const rect = this.getBoundingClientRect();
            const offsetX = e.clientX - rect.left;
            const offsetY = e.clientY - rect.top;
            ripple.style.left = offsetX + 'px';
            ripple.style.top = offsetY + 'px';
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            requestAnimationFrame(() => {
                ripple.style.transform = 'scale(20)';
                ripple.style.opacity = '0';
            });
            setTimeout(() => ripple.remove(), 500);
        });
    });

    // Advanced: parallax for orbs (gentle)
    document.addEventListener('mousemove', (e) => {
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        const orbs = document.querySelectorAll('.orb');
        orbs.forEach((orb, idx) => {
            const moveX = (x - 0.5) * (idx + 1) * 12;
            const moveY = (y - 0.5) * (idx + 1) * 12;
            orb.style.transform = `translate(${moveX}px, ${moveY}px) scale(1.02)`;
        });
    });

    // Stats hover glow
    const tiles = document.querySelectorAll('.stat-tile');
    tiles.forEach(tile => {
        tile.addEventListener('mouseenter', () => {
            tile.style.transition = 'all 0.2s';
        });
    });
</script>
</body>
</html>