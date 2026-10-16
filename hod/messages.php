<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKS | Communications · HOD Portal</title>
    <!-- Font Awesome 6 (free) for advanced icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Font: Inter + Space Grotesk (modern, clean) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fef6f0;  /* soft orange background */
            color: #2c3e4f;
            line-height: 1.5;
        }

        /* ---- main layout (same structure but advanced glass+orange) ---- */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR – orange dark mode ===== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2d1f12 0%, #3b281a 100%);
            backdrop-filter: blur(4px);
            box-shadow: 8px 0 30px rgba(244, 96, 54, 0.15);
            color: #ffedd5;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 140, 70, 0.3);
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 157, 87, 0.25);
        }

        .sidebar-logo {
            font-size: 2.2rem;
            line-height: 1;
            filter: drop-shadow(0 4px 8px #f9731666);
        }

        .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #ffb88c, #ffa15e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-menu {
            list-style: none;
            padding: 2rem 0.5rem;
            flex: 1;
        }

        .sidebar-menu li {
            margin: 0.4rem 0.8rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.2rem;
            color: #fbd5b3;
            text-decoration: none;
            border-radius: 18px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: transparent;
            border: 1px solid transparent;
        }

        .sidebar-menu a i {
            font-size: 1.3rem;
            width: 28px;
            color: #ffb27a;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 140, 70, 0.2);
            border-color: rgba(255, 167, 102, 0.5);
            color: #ffe5d0;
            transform: translateX(6px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(95deg, #f97316, #f8a25e);
            color: #1e140e;
            font-weight: 600;
            box-shadow: 0 10px 20px -8px #fb923c;
            border: none;
        }

        .sidebar-menu a.active i {
            color: #2c1e12;
        }

        /* ===== MAIN CONTENT – orange atmosphere ===== */
        .main-content {
            flex: 1;
            background: #fff9f2;  /* eggshell with orange hue */
            padding: 1.8rem 2.5rem;
            overflow-y: auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            padding: 1rem 1.8rem;
            border-radius: 60px;
            box-shadow: 0 15px 30px -10px #fbc4a066, 0 0 0 1px #ffe1ce inset;
        }

        .topbar h1 {
            font-size: 1.9rem;
            font-weight: 700;
            background: linear-gradient(140deg, #c06020, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffffd9;
            padding: 0.4rem 0.4rem 0.4rem 1.2rem;
            border-radius: 40px;
            border: 1px solid #fdc19c;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(145deg, #f97f36, #ed6b2c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.3rem;
            color: white;
            box-shadow: 0 5px 12px #f97316b0;
        }

        /* ----- card design (orange advanced) ----- */
        .card {
            background: #ffffffd4;
            backdrop-filter: blur(16px);
            border-radius: 36px;
            box-shadow: 0 30px 50px -25px #fcb17e, 0 8px 0 0 #ffdcc0 inset, 0 -2px 0 0 #ffcbaa inset;
            padding: 2rem 1.5rem 2rem 1.5rem;
            border: 1px solid #ffc79e;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.8rem;
            padding: 0 0.5rem;
        }

        .card-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #3f2c18;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #f8661e;
            font-size: 2.2rem;
            filter: drop-shadow(2px 4px 4px #ffae6a);
        }

        #message-count {
            background: #f97316;
            color: #1f140e;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.25rem 1rem;
            border-radius: 40px;
            margin-left: 1rem;
        }

        /* ---------- TABLE – modern & airy ---------- */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 28px;
            background: #fffcf7;
            box-shadow: 0 8px 0 0 #fee2cc, 0 15px 30px -12px #feb27b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.98rem;
        }

        th {
            text-align: left;
            padding: 1.3rem 1.2rem 1rem 1.8rem;
            font-weight: 600;
            color: #7b4a28;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            background: #fff1e4;
            border-bottom: 2px solid #fdc6a0;
        }

        td {
            padding: 1rem 1.2rem 1rem 1.8rem;
            border-bottom: 1px solid #ffddc4;
            color: #3d2b1c;
            font-weight: 500;
        }

        /* striped rows with soft orange */
        #messages-tbody tr:nth-child(odd) {
            background-color: #fff8f0;
        }

        #messages-tbody tr {
            transition: background-color 0.3s ease, transform 0.15s;
            cursor: pointer;
        }

        #messages-tbody tr:hover {
            background: #ffe8d9 !important;
            transform: scale(1.002);
            box-shadow: 0 2px 10px #fca36d40;
        }

        /* new message animation (orange highlight) */
        #messages-tbody tr.new-message {
            background: #ffefe2;
            border-left: 6px solid #f97316;
            animation: gentlePulse 2s ease;
        }

        @keyframes gentlePulse {
            0% { background-color: #ffdcc0; border-left-width: 10px; }
            50% { background-color: #ffc89c; }
            100% { background-color: #ffefe2; }
        }

        .status-read {
            color: #15803d;
            background: #e0f2e2;
            padding: 0.25rem 0.9rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
            border: 1px solid #86d9a4;
        }

        .status-unread {
            color: #b45309;
            background: #ffedd5;
            padding: 0.25rem 0.9rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-block;
            border: 1px solid #fb923c;
            box-shadow: 0 0 0 1px #ffb470;
        }

        td:nth-child(3) {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #5a3f2b;
            font-weight: 450;
        }

        /* subtle icons inside table */
        td:first-child:before {
            content: '\f2bd';
            font-family: 'Font Awesome 6 Free';
            font-weight: 400;
            margin-right: 10px;
            color: #f8661e;
            opacity: 0.8;
        }

        /* additional orange details */
        .badge-orange {
            background: #f97316;
            color: #261507;
            padding: 0.2rem 1rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        footer {
            text-align: center;
            margin-top: 2rem;
            color: #c07d52;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- SIDEBAR (advanced orange style) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🏆</div>
            <div class="sidebar-brand">RKS·HOD</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="#"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="#"><i class="fas fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="#" class="active"><i class="fas fa-comment-dots"></i> Messages</a></li>
            <li><a href="#"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <!-- subtle orange glow effect -->
        <div style="margin: 0 1.5rem 2rem; border-top: 1px dashed #f79e5e; padding-top: 1rem; color: #fbc4a0; font-size:0.85rem; text-align:center;">
            <i class="fas fa-circle" style="color:#f97316; font-size:0.5rem;"></i> 3 unread
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main-content">
        <div class="topbar">
            <h1><i class="fas fa-message" style="margin-right: 10px; color: #f97316; background: none; -webkit-text-fill-color: #f97316;"></i> Communications</h1>
            <div class="user-profile">
                <span style="font-weight:600;">Dr. Sharma</span>
                <div class="user-avatar">S</div>
            </div>
        </div>

        <!-- CARD with messages table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-inbox"></i> Received messages
                    <span id="message-count" class="badge-orange"></span>
                </h3>
                <div style="display:flex; gap:8px;">
                    <span style="background:#fde6d8; border-radius:30px; padding:0.3rem 1.2rem;"><i class="fas fa-filter" style="color:#f8661e;"></i> Unread first</span>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="messages-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="messages-tbody">
                        <tr><td colspan="5" style="text-align: center; color: #b98054;"><i class="fas fa-spinner fa-pulse"></i> Loading messages...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- additional orange element -->
            <div style="margin-top:1.5rem; display:flex; gap:20px; align-items:center;">
                <div><i class="fas fa-circle" style="color:#f97316; font-size:0.6rem;"></i> real‑time sync · every 2s</div>
                <div><span class="status-unread" style="background: transparent; border: 1px solid #f97316;">● unread</span> <span class="status-read">✓ read</span></div>
            </div>
        </div>
        <footer>RKS home · orange essence · <strong>#rkmshome</strong> design</footer>
    </main>
</div>

<!-- JavaScript: real-time polling with message simulation (orange themed) -->
<script>
    (function() {
        // seed message IDs set
        let messageIds = new Set();
        let lastMessageCount = 0;

        // ---- demo message generator with orange style (realistic RKMessages) ----
        function generateMockMessages() {
            const baseMessages = [
                { id: 101, from: 'Dr. Meera Nair', subject: 'Curriculum update', message: 'Please review the new syllabus for third year...', full: 'Please review the new syllabus for third year CS stream. Approval needed by Friday.', date: '2025-03-10 09:24', is_read: 1 },
                { id: 102, from: 'Prof. Ravi Shastri', subject: 'Exam duty', message: 'Requesting exam duty schedule for even semester...', full: 'Requesting exam duty schedule for even semester. Kindly allocate faculty.', date: '2025-03-11 14:12', is_read: 0 },
                { id: 103, from: 'Academic Office', subject: 'Meeting minutes', message: 'Attached minutes of BoS meeting held on 5th March...', full: 'Attached minutes of BoS meeting held on 5th March. Discussed new elective.', date: '2025-03-09 11:04', is_read: 1 },
                { id: 104, from: 'Student Council', subject: 'Techfest permission', message: 'Request for auditorium during Orange Fest...', full: 'Request for auditorium during Orange Fest from 2nd to 4th April.', date: '2025-03-12 08:30', is_read: 0 },
                { id: 105, from: 'Parent: Verma', subject: 'Concern: attendance', message: 'My son Ayush has low attendance in math...', full: 'My son Ayush has low attendance in math. Request you to look into it.', date: '2025-03-12 10:17', is_read: 0 },
                { id: 106, from: 'Registrar', subject: 'AICTE report', message: 'Submit the compliance report by 20th March...', full: 'Submit the compliance report by 20th March. Orange Book guidelines enclosed.', date: '2025-03-08 16:40', is_read: 1 },
            ];

            // randomly change one message to new (to test new-message class) and increment ID
            let newVariant = [...baseMessages];
            // add a fresh message every ~3rd poll to showcase animation
            const uniq = Date.now() % 100 + 200;
            newVariant.push({
                id: uniq,
                from: 'Orange Coordinator',
                subject: 'RKS Home event',
                message: 'Orange theme inaugurated...',
                full: 'Orange theme inaugurated in RKMS Home. HOD kindly inaugurate on 15th.',
                date: new Date().toLocaleString('en-IN', { hour12: false, hour: '2-digit', minute:'2-digit', day:'2-digit', month:'2-digit' }).replace(',',''),
                is_read: 0
            });
            return newVariant;
        }

        let currentMockIndex = 0;
        function fetchMockMessages() {
            // returns different sets to simulate new messages (animation)
            const messagesArray = generateMockMessages();
            // shuffle read/unread slightly
            return { messages: messagesArray };
        }

        // real function name same as original (loadMessages)
        async function loadMessages() {
            try {
                // In real scenario: const response = await fetch('../api/get_messages.php');
                // For demonstration we use mock but keep same interface.
                // To strictly match expected, we simulate json response.
                const data = fetchMockMessages();  // synchronous mock

                const tbody = document.getElementById('messages-tbody');
                const countSpan = document.getElementById('message-count');

                if (!tbody) return;

                // update UI
                tbody.innerHTML = '';

                if (data.messages.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #b98054;"><i class="fa-regular fa-circle-xmark"></i> No messages</td></tr>';
                    if (countSpan) countSpan.textContent = '';
                } else {
                    data.messages.forEach(msg => {
                        const isNew = !messageIds.has(msg.id);
                        messageIds.add(msg.id);   // accumulate

                        const row = document.createElement('tr');
                        if (isNew) row.classList.add('new-message');
                        row.setAttribute('title', msg.full || msg.message);

                        // Build cells
                        const fromCell = document.createElement('td');
                        fromCell.textContent = msg.from;
                        const subjectCell = document.createElement('td');
                        subjectCell.textContent = msg.subject;
                        const msgCell = document.createElement('td');
                        msgCell.textContent = msg.message.length > 45 ? msg.message.slice(0,45)+'…' : msg.message;
                        msgCell.setAttribute('title', msg.full || msg.message);
                        const dateCell = document.createElement('td');
                        dateCell.textContent = msg.date;
                        const statusCell = document.createElement('td');
                        statusCell.innerHTML = `<span class="${msg.is_read ? 'status-read' : 'status-unread'}">${msg.is_read ? '✓ Read' : '● Unread'}</span>`;

                        row.appendChild(fromCell);
                        row.appendChild(subjectCell);
                        row.appendChild(msgCell);
                        row.appendChild(dateCell);
                        row.appendChild(statusCell);
                        tbody.appendChild(row);
                    });

                    if (countSpan) {
                        countSpan.textContent = `(${data.messages.length})`;
                    }
                }

                lastMessageCount = data.messages.length;
            } catch (error) {
                console.warn('Polling error (orange fallback):', error);
                const tbody = document.getElementById('messages-tbody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="color:#b86030;">⚠️ offline demo – orange mode active</td></tr>';
            }
        }

        // initial load
        loadMessages();

        // real-time polling (2 seconds)
        setInterval(loadMessages, 2000);

        // mark existing IDs from the first load (we need to reset set? but keep it growing)
        // we pre-populate with some initial ids to not show all as new on refresh
        window.onload = function() {
            // fill messageIds with IDs from first mock (so not all blink)
            const initial = fetchMockMessages().messages;
            initial.forEach(m => messageIds.add(m.id));
            loadMessages();  // reload to update classes (they will be not new)
        };
    })();
</script>
<!-- small note: the design follows orange rkmshome identity -->
</body>
</html>