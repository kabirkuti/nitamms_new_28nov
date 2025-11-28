<?php
// admin/super_dashboard.php - Place this in your admin folder
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['admin']); // Uses existing admin role

$superadmin = getCurrentUser();

// Handle AI Chat (same as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ai_message'])) {
    header('Content-Type: application/json');
    $userMessage = trim($_POST['ai_message']);
    
    // Get system stats
    $students = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1")->fetch_assoc()['count'];
    $teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1")->fetch_assoc()['count'];
    $hods = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hod' AND is_active = 1")->fetch_assoc()['count'];
    $parents = $conn->query("SELECT COUNT(*) as count FROM parents")->fetch_assoc()['count'];
    $departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
    $classes = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
    $attendance = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE()")->fetch_assoc()['count'];
    $totalUsers = $students + $teachers + $hods + $parents;
    
    // Smart responses based on question
    $msg = strtolower($userMessage);
    
    if (strpos($msg, 'student') !== false) {
        if (strpos($msg, 'how many') !== false || strpos($msg, 'count') !== false) {
            $aiResponse = "📊 **Student Statistics:**\n\n**Total Active Students:** $students\n\n";
            $topDepts = $conn->query("SELECT d.dept_name, COUNT(s.id) as count FROM departments d LEFT JOIN students s ON d.id = s.department_id WHERE s.is_active = 1 GROUP BY d.id ORDER BY count DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
            $aiResponse .= "**Top Departments:**\n";
            foreach ($topDepts as $dept) {
                $aiResponse .= "- {$dept['dept_name']}: {$dept['count']} students\n";
            }
        } else {
            $aiResponse = "There are **$students active students** in the system.";
        }
    }
    else if (strpos($msg, 'teacher') !== false) {
        $aiResponse = "👨‍🏫 **Teachers:** $teachers active teachers\n👔 **HODs:** $hods department heads";
    }
    else if (strpos($msg, 'attendance') !== false) {
        $present = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE() AND status = 'present'")->fetch_assoc()['count'];
        $rate = $attendance > 0 ? round(($present / $attendance) * 100, 1) : 0;
        $aiResponse = "📊 **Today's Attendance:**\n- Total: $attendance\n- Present: $present\n- Rate: {$rate}%";
    }
    else if (strpos($msg, 'all') !== false || strpos($msg, 'stat') !== false) {
        $aiResponse = "📊 **Complete Overview:**\n\n**Users:**\n- Students: $students\n- Teachers: $teachers\n- HODs: $hods\n- Parents: $parents\n- Total: $totalUsers\n\n**Infrastructure:**\n- Departments: $departments\n- Classes: $classes\n- Today's Attendance: $attendance";
    }
    else {
        $aiResponse = "I can help with: student count, teacher info, attendance records, and system statistics. What would you like to know?";
    }
    
    echo json_encode(['success' => true, 'response' => $aiResponse]);
    exit;
}

// Get statistics
$stats = [
    'students' => $conn->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1")->fetch_assoc()['count'],
    'teachers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1")->fetch_assoc()['count'],
    'hods' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hod' AND is_active = 1")->fetch_assoc()['count'],
    'parents' => $conn->query("SELECT COUNT(*) as count FROM parents")->fetch_assoc()['count'],
    'departments' => $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'],
    'classes' => $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'],
    'today_attendance' => $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE()")->fetch_assoc()['count']
];

$stats['total_users'] = $stats['students'] + $stats['teachers'] + $stats['hods'] + $stats['parents'];

$present = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE() AND status = 'present'")->fetch_assoc()['count'];
$stats['attendance_rate'] = $stats['today_attendance'] > 0 ? round(($present / $stats['today_attendance']) * 100, 1) : 0;

$greeting = date('H') < 12 ? 'Good Morning' : (date('H') < 17 ? 'Good Afternoon' : 'Good Evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard - NIT AMMS</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
    
    <!-- Include same styles as before -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); color: #333; min-height: 100vh; }
        
        /* Copy all the styles from the previous artifact here - they're the same */
        /* For brevity, I'll include the key ones */
        
        .navbar { background: rgba(26, 31, 58, 0.95); backdrop-filter: blur(20px); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); position: sticky; top: 0; z-index: 1000; }
        .navbar h1 { color: #fff; font-size: 24px; font-weight: 700; }
        .btn-back { background: #6c757d; color: #fff; padding: 10px 20px; border-radius: 10px; text-decoration: none; }
        .btn-logout { background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff; padding: 10px 20px; border-radius: 10px; text-decoration: none; }
        
        .main-content { padding: 40px; max-width: 1600px; margin: 0 auto; }
        
        .hero-welcome { background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 20px; margin-bottom: 30px; }
        .hero-welcome h2 { font-size: 36px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-value { font-size: 36px; font-weight: 800; color: #667eea; }
        .stat-label { font-size: 14px; color: #666; text-transform: uppercase; }
        
        .btn-ai { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 15px 30px; border: none; border-radius: 15px; font-size: 16px; cursor: pointer; margin: 20px 0; }
        
        .ai-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; }
        .ai-modal.active { display: flex; }
        .ai-modal-content { background: #1e2140; border-radius: 20px; width: 90%; max-width: 700px; height: 80vh; display: flex; flex-direction: column; }
        .ai-chat-area { flex: 1; padding: 20px; overflow-y: auto; background: #0d1025; }
        .chat-message { display: flex; gap: 10px; margin: 10px 0; }
        .msg-content { padding: 12px 16px; border-radius: 15px; max-width: 70%; }
        .chat-message.user .msg-content { background: #667eea; color: #fff; margin-left: auto; }
        .chat-message.ai .msg-content { background: rgba(255,255,255,0.1); color: #e0e0e0; }
        .ai-input { width: 100%; padding: 15px; border: 2px solid #667eea; border-radius: 20px; background: rgba(255,255,255,0.1); color: #fff; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>⭐ NIT AMMS Superadmin Dashboard</h1>
        <div style="display: flex; gap: 15px;">
            <a href="index.php" class="btn-back">← Back to Admin</a>
            <a href="../logout.php" class="btn-logout">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="hero-welcome">
            <h2><?= $greeting ?>, <?= htmlspecialchars($superadmin['full_name']) ?>! 👋</h2>
            <p style="font-size: 18px; color: #666; margin: 15px 0;">Complete system overview and control center</p>
            
            <button class="btn-ai" onclick="openAI()">🤖 Ask AI Assistant</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['students'] ?></div>
                <div class="stat-label">👨‍🎓 Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['teachers'] ?></div>
                <div class="stat-label">👨‍🏫 Teachers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['hods'] ?></div>
                <div class="stat-label">👔 HODs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['parents'] ?></div>
                <div class="stat-label">👨‍👩‍👦 Parents</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['departments'] ?></div>
                <div class="stat-label">🏢 Departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['classes'] ?></div>
                <div class="stat-label">📚 Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['today_attendance'] ?></div>
                <div class="stat-label">📊 Attendance</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['attendance_rate'] ?>%</div>
                <div class="stat-label">✅ Rate</div>
            </div>
        </div>
    </div>

    <!-- AI Modal -->
    <div class="ai-modal" id="aiModal">
        <div class="ai-modal-content">
            <div style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 20px; color: #fff; display: flex; justify-content: space-between;">
                <h3>🤖 AI Assistant</h3>
                <button onclick="closeAI()" style="background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;">✕</button>
            </div>
            <div class="ai-chat-area" id="chatArea">
                <div style="text-align: center; color: rgba(255,255,255,0.7); padding: 40px;">
                    <div style="font-size: 60px;">🤖</div>
                    <h4 style="color: #fff; margin: 15px 0;">Hello! I'm your AI Assistant</h4>
                    <p>Ask me about students, teachers, attendance, or system stats!</p>
                </div>
            </div>
            <div style="padding: 20px;">
                <input type="text" class="ai-input" id="aiInput" placeholder="Ask me anything..." onkeypress="if(event.key==='Enter')sendMsg()">
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
    <script>
    function openAI() { document.getElementById('aiModal').classList.add('active'); }
    function closeAI() { document.getElementById('aiModal').classList.remove('active'); }
    
    function addMsg(content, isUser) {
        const area = document.getElementById('chatArea');
        const welcome = area.querySelector('div[style*="text-align: center"]');
        if (welcome) welcome.remove();
        
        const div = document.createElement('div');
        div.className = 'chat-message ' + (isUser ? 'user' : 'ai');
        div.innerHTML = `<div class="msg-content">${isUser ? content : marked.parse(content)}</div>`;
        area.appendChild(div);
        area.scrollTop = area.scrollHeight;
    }
    
    async function sendMsg() {
        const input = document.getElementById('aiInput');
        const msg = input.value.trim();
        if (!msg) return;
        
        input.value = '';
        addMsg(msg, true);
        
        const formData = new FormData();
        formData.append('ai_message', msg);
        
        const res = await fetch('', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) addMsg(data.response, false);
    }
    </script>
</body>
</html>