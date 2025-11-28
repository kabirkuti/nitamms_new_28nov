<?php
// superadmin/index.php
require_once 'db_superadmin.php';
redirectIfNotSuperAdmin();
initializeSuperAdminTables($pdo);

$counts = getUserCounts($pdo);
$studentCount = $counts['students'];
$teacherCount = $counts['teachers'];
$departmentCount = $counts['departments'];
$classCount = $counts['classes'];
$attendanceCount = $counts['attendance_records'];
$totalUsers = $counts['total_users'];

logSuperAdminAction($pdo, 'DASHBOARD_ACCESS', 'Superadmin accessed dashboard');

// Handle AI Chat API Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ai_message'])) {
    header('Content-Type: application/json');
    $userMessage = trim($_POST['ai_message']);
    $chatHistory = isset($_POST['chat_history']) ? json_decode($_POST['chat_history'], true) : [];
    
    $systemContext = "You are an intelligent AI assistant for NIT AMMS Dashboard. Be helpful and friendly.
    System Stats: Students: $studentCount, Teachers: $teacherCount, Departments: $departmentCount, Classes: $classCount, Attendance Records: $attendanceCount, Total Users: $totalUsers, System Health: 98.5%, Response Time: 45ms.
    Answer any question the user asks - system data, general knowledge, help, etc.";
    
    $messages = [];
    if (!empty($chatHistory)) {
        foreach ($chatHistory as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    $apiKey = 'YOUR_ANTHROPIC_API_KEY';
    $data = ['model' => 'claude-sonnet-4-20250514', 'max_tokens' => 1024, 'system' => $systemContext, 'messages' => $messages];
    
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01']]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $aiResponse = $result['content'][0]['text'] ?? 'Sorry, I could not process that.';
    } else {
        $msg = strtolower($userMessage);
        if (strpos($msg, 'student') !== false) $aiResponse = "There are **$studentCount students** in the system.";
        elseif (strpos($msg, 'teacher') !== false) $aiResponse = "We have **$teacherCount teachers** across $departmentCount departments.";
        elseif (strpos($msg, 'attendance') !== false) $aiResponse = "Total attendance records: **$attendanceCount**.";
        elseif (strpos($msg, 'department') !== false) $aiResponse = "There are **$departmentCount departments**.";
        elseif (strpos($msg, 'class') !== false) $aiResponse = "We have **$classCount active classes**.";
        elseif (strpos($msg, 'system') !== false || strpos($msg, 'health') !== false) $aiResponse = "System Health: 98.5% ✓\nResponse Time: 45ms ✓\nAll systems operational!";
        elseif (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false) $aiResponse = "Hello! 👋 I'm your AI assistant. Ask me anything!";
        elseif (strpos($msg, 'help') !== false) $aiResponse = "I can help with: system data, students, teachers, attendance, and general questions!";
        else $aiResponse = "I'm here to help! Ask about students, teachers, attendance, or anything else.";
    }
    echo json_encode(['success' => true, 'response' => $aiResponse]);
    exit;
}

$recentActivities = [
    ['id' => 1, 'user' => 'John Doe', 'action' => 'Marked attendance', 'status' => 'present', 'time' => '2 min ago'],
    ['id' => 2, 'user' => 'Sarah Smith', 'action' => 'Updated profile', 'status' => 'success', 'time' => '5 min ago'],
    ['id' => 3, 'user' => 'Mike Johnson', 'action' => 'Submitted report', 'status' => 'pending', 'time' => '10 min ago']
];
$notifications = [
    ['id' => 1, 'type' => 'info', 'message' => 'New student registered', 'time' => '5 min ago'],
    ['id' => 2, 'type' => 'warning', 'message' => 'System backup scheduled', 'time' => '1 hour ago'],
    ['id' => 3, 'type' => 'success', 'message' => 'Database optimized', 'time' => '2 hours ago']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard - NIT AMMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);color:#333;min-height:100vh;overflow-x:hidden}
        @keyframes slideIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}
        @keyframes float{0%,100%{transform:translateY(0) rotate(0deg);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-100vh) rotate(360deg);opacity:0}}
        @keyframes typing{0%,80%,100%{opacity:.4}40%{opacity:1}}
        @keyframes iconFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        .particles{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none}
        .particle{position:absolute;background:rgba(255,255,255,0.15);border-radius:50%;animation:float 15s infinite ease-in-out}
        .navbar{background:rgba(26,31,58,0.95);backdrop-filter:blur(20px);padding:20px 40px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 8px 32px rgba(0,0,0,0.3);border-bottom:2px solid rgba(255,255,255,0.1);position:sticky;top:0;z-index:1000;flex-wrap:wrap;gap:20px}
        .navbar-brand{display:flex;align-items:center;gap:15px}
        .navbar-logo{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:24px;animation:pulse 2s ease-in-out infinite}
        .navbar h1{color:#fff;font-size:24px;font-weight:700;margin:0}
        .navbar p{color:rgba(255,255,255,0.6);font-size:12px;margin:0}
        .navbar-right{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
        .voice-btn,.sound-btn,.notif-btn{background:rgba(255,255,255,0.1);border:none;border-radius:12px;padding:12px;cursor:pointer;transition:all .3s;color:#fff;font-size:20px;width:44px;height:44px;display:flex;align-items:center;justify-content:center}
        .voice-btn.active{background:linear-gradient(135deg,#28a745,#20c997)}
        .sound-btn.active{background:linear-gradient(135deg,#667eea,#764ba2)}
        .voice-btn:hover,.sound-btn:hover,.notif-btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(102,126,234,0.4)}
        .notif-btn{position:relative}
        .notif-badge{position:absolute;top:5px;right:5px;width:8px;height:8px;background:#ff4444;border-radius:50%;animation:pulse 2s ease-in-out infinite}
        .user-profile{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.1);padding:10px 20px;border-radius:50px;border:1px solid rgba(255,255,255,0.2)}
        .user-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#f093fb,#f5576c);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;color:#fff}
        .user-info{color:#fff}
        .user-info .username{font-weight:600;font-size:14px;display:block}
        .user-info .role{font-size:11px;opacity:.8}
        .btn-logout{background:linear-gradient(135deg,#e74c3c,#c0392b);color:#fff;border:none;padding:12px 25px;border-radius:12px;cursor:pointer;font-weight:600;font-size:13px;transition:all .3s;display:flex;align-items:center;gap:8px}
        .btn-logout:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(231,76,60,0.4)}
        .main-content{padding:40px;max-width:1600px;margin:0 auto;position:relative;z-index:1}
        .hero-welcome{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);padding:50px;border-radius:30px;margin-bottom:40px;box-shadow:0 20px 60px rgba(0,0,0,0.3);border:2px solid rgba(255,255,255,0.5);animation:slideIn .6s ease-out}
        .hero-content{display:grid;grid-template-columns:1fr auto;gap:40px;align-items:center}
        .hero-text h2{font-size:42px;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:15px;font-weight:800}
        .hero-text p{font-size:18px;color:#666;margin-bottom:25px}
        .hero-stats{display:flex;gap:30px;margin-top:30px;flex-wrap:wrap}
        .hero-stat-item{text-align:center}
        .hero-stat-value{font-size:36px;font-weight:700;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .hero-stat-label{font-size:13px;color:#666;text-transform:uppercase;letter-spacing:1px;margin-top:5px}
        .glass-clock{background:rgba(255,255,255,0.2);backdrop-filter:blur(20px);padding:30px;border-radius:25px;text-align:center;border:2px solid rgba(255,255,255,0.3);min-width:280px}
        .clock-icon{font-size:48px;margin-bottom:15px;animation:pulse 2s ease-in-out infinite}
        .glass-clock .time{font-size:48px;font-weight:800;font-family:'Courier New',monospace;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .glass-clock .date{font-size:14px;color:#666;margin-top:10px;font-weight:500}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:25px;margin-bottom:40px}
        .stat-card{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:20px;padding:30px;box-shadow:0 10px 40px rgba(0,0,0,0.1);border:2px solid rgba(255,255,255,0.5);position:relative;overflow:hidden;transition:all .4s cubic-bezier(.4,0,.2,1);cursor:pointer}
        .stat-card::before{content:'';position:absolute;top:0;left:0;width:100%;height:4px;background:linear-gradient(90deg,var(--card-color),var(--card-color-light))}
        .stat-card:hover{transform:translateY(-10px) scale(1.02);box-shadow:0 20px 60px rgba(102,126,234,0.4)}
        .stat-card-1{--card-color:#667eea;--card-color-light:#667eeacc}
        .stat-card-2{--card-color:#764ba2;--card-color-light:#764ba2cc}
        .stat-card-3{--card-color:#f093fb;--card-color-light:#f093fbcc}
        .stat-card-4{--card-color:#28a745;--card-color-light:#28a745cc}
        .stat-card-5{--card-color:#ffc107;--card-color-light:#ffc107cc}
        .stat-card-6{--card-color:#e74c3c;--card-color-light:#e74c3ccc}
        .stat-icon-wrapper{width:70px;height:70px;border-radius:18px;background:linear-gradient(135deg,var(--card-color),var(--card-color-light));display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;margin-bottom:20px;box-shadow:0 10px 30px rgba(102,126,234,0.4);animation:iconFloat 3s ease-in-out infinite}
        .stat-details h4{color:#666;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
        .stat-value-large{font-size:42px;font-weight:800;background:linear-gradient(135deg,var(--card-color),var(--card-color-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:10px}
        .stat-trend{font-size:13px;display:flex;align-items:center;gap:8px;font-weight:600}
        .trend-up{color:#28a745}
        .section-title{font-size:28px;font-weight:700;color:#fff;margin:50px 0 30px;display:flex;align-items:center;gap:15px;text-shadow:0 2px 10px rgba(0,0,0,0.3)}
        .section-title::before{content:'';width:5px;height:40px;background:linear-gradient(180deg,#fff,transparent);border-radius:10px}
        .quick-actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:25px;margin-bottom:50px}
        .action-card{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:20px;padding:35px 25px;text-align:center;cursor:pointer;transition:all .4s cubic-bezier(.4,0,.2,1);box-shadow:0 10px 40px rgba(0,0,0,0.1);border:2px solid rgba(255,255,255,0.5);text-decoration:none;color:#2c3e50}
        .action-card:hover{transform:translateY(-15px) scale(1.05);box-shadow:0 25px 60px rgba(102,126,234,0.5)}
        .action-icon{width:80px;height:80px;margin:0 auto 20px;border-radius:50%;background:linear-gradient(135deg,var(--action-color),var(--action-color-light));display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;box-shadow:0 10px 30px rgba(102,126,234,0.4);animation:pulse 2s ease-in-out infinite}
        .action-card-5{--action-color:#667eea;--action-color-light:#764ba2}
        .action-label{font-size:16px;font-weight:700;margin-bottom:8px}
        .action-description{font-size:13px;color:#666}
        
        /* AI Modal */
        .ai-modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.85);backdrop-filter:blur(15px);z-index:2000;align-items:center;justify-content:center;padding:20px}
        .ai-modal.active{display:flex}
        .ai-modal-content{background:linear-gradient(145deg,#1e2140,#151829);border-radius:25px;width:100%;max-width:750px;height:85vh;max-height:680px;display:flex;flex-direction:column;box-shadow:0 30px 100px rgba(0,0,0,0.7),0 0 80px rgba(102,126,234,0.15);overflow:hidden;animation:slideIn .4s ease-out;border:1px solid rgba(102,126,234,0.25)}
        .ai-modal-header{background:linear-gradient(135deg,#667eea,#764ba2);padding:22px 28px;display:flex;justify-content:space-between;align-items:center;color:#fff}
        .ai-header-left{display:flex;align-items:center;gap:15px}
        .ai-avatar{width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:26px;animation:pulse 2s ease-in-out infinite;border:2px solid rgba(255,255,255,0.3)}
        .ai-header-info h3{font-size:20px;font-weight:700;margin:0 0 4px 0}
        .ai-header-info p{font-size:12px;margin:0;opacity:.9;display:flex;align-items:center;gap:8px}
        .ai-status-dot{width:8px;height:8px;background:#00ff88;border-radius:50%;animation:pulse 2s infinite}
        .ai-modal-close{background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:42px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;transition:all .3s;font-size:22px}
        .ai-modal-close:hover{background:rgba(255,255,255,0.3);transform:rotate(90deg)}
        .ai-chat-area{flex:1;padding:25px;overflow-y:auto;background:#0d1025;display:flex;flex-direction:column;gap:16px}
        .ai-chat-area::-webkit-scrollbar{width:6px}
        .ai-chat-area::-webkit-scrollbar-track{background:rgba(255,255,255,0.05);border-radius:3px}
        .ai-chat-area::-webkit-scrollbar-thumb{background:rgba(102,126,234,0.4);border-radius:3px}
        .ai-welcome{text-align:center;padding:40px 20px;color:rgba(255,255,255,0.7)}
        .ai-welcome-icon{font-size:70px;margin-bottom:20px;animation:pulse 3s ease-in-out infinite}
        .ai-welcome h4{font-size:22px;color:#fff;margin-bottom:12px;font-weight:600}
        .ai-welcome p{font-size:14px;opacity:.8;max-width:400px;margin:0 auto;line-height:1.6}
        .chat-message{display:flex;gap:12px;animation:slideIn .3s ease-out}
        .chat-message.user{flex-direction:row-reverse}
        .msg-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
        .chat-message.user .msg-avatar{background:linear-gradient(135deg,#f093fb,#f5576c)}
        .chat-message.ai .msg-avatar{background:linear-gradient(135deg,#667eea,#764ba2)}
        .msg-content{max-width:75%;padding:14px 18px;border-radius:18px;font-size:14px;line-height:1.7}
        .chat-message.user .msg-content{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:18px 18px 4px 18px}
        .chat-message.ai .msg-content{background:rgba(255,255,255,0.08);color:#e0e0e0;border-radius:18px 18px 18px 4px;border:1px solid rgba(255,255,255,0.1)}
        .msg-content strong{color:#a8b4ff;font-weight:600}
        .msg-content p{margin:0 0 10px 0}
        .msg-content p:last-child{margin-bottom:0}
        .msg-content ul,.msg-content ol{margin:10px 0;padding-left:20px}
        .msg-content li{margin:5px 0}
        .msg-content code{background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;font-family:monospace;font-size:13px}
        .msg-time{font-size:11px;opacity:.6;margin-top:6px;text-align:right}
        .typing-indicator{display:flex;gap:12px;animation:slideIn .3s ease-out}
        .typing-indicator .msg-content{background:rgba(255,255,255,0.08);padding:16px 20px;display:flex;gap:6px;align-items:center}
        .typing-dot{width:8px;height:8px;background:#667eea;border-radius:50%;animation:typing 1.4s ease-in-out infinite}
        .typing-dot:nth-child(2){animation-delay:.2s}
        .typing-dot:nth-child(3){animation-delay:.4s}
        .ai-input-area{padding:20px 25px;background:rgba(30,33,64,0.95);border-top:1px solid rgba(255,255,255,0.1)}
        .ai-input-row{display:flex;gap:12px;align-items:center}
        .ai-input{flex:1;padding:14px 20px;border-radius:25px;border:2px solid rgba(102,126,234,0.3);background:rgba(255,255,255,0.05);color:#fff;font-size:14px;outline:none;transition:all .3s;font-family:'Poppins',sans-serif}
        .ai-input::placeholder{color:rgba(255,255,255,0.4)}
        .ai-input:focus{border-color:#667eea;background:rgba(255,255,255,0.08)}
        .ai-voice-btn,.ai-send-btn{background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:50%;width:48px;height:48px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;transition:all .3s;font-size:20px;flex-shrink:0}
        .ai-voice-btn.listening{background:linear-gradient(135deg,#28a745,#20c997);animation:pulse 1s ease-in-out infinite}
        .ai-voice-btn:hover,.ai-send-btn:hover{transform:scale(1.1);box-shadow:0 8px 25px rgba(102,126,234,0.4)}
        .ai-send-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
        .ai-suggestions{display:flex;gap:10px;margin-top:15px;flex-wrap:wrap}
        .suggestion-btn{background:rgba(102,126,234,0.15);border:1px solid rgba(102,126,234,0.3);border-radius:20px;padding:8px 16px;font-size:12px;cursor:pointer;transition:all .3s;color:#a8b4ff;font-weight:500}
        .suggestion-btn:hover{background:rgba(102,126,234,0.25);transform:translateY(-2px)}
        
        /* Notifications */
        .notifications-dropdown{display:none;position:fixed;top:80px;right:40px;width:350px;background:#fff;border-radius:20px;box-shadow:0 15px 50px rgba(0,0,0,0.3);z-index:1500;overflow:hidden;animation:slideIn .3s ease-out}
        .notifications-dropdown.active{display:block}
        .notif-header{padding:20px;border-bottom:1px solid #e0e0e0;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}
        .notif-header h4{margin:0;font-size:18px;font-weight:700}
        .notif-list{max-height:400px;overflow-y:auto}
        .notif-item{padding:15px 20px;border-bottom:1px solid #f0f0f0;cursor:pointer;transition:all .3s;display:flex;align-items:flex-start;gap:12px}
        .notif-item:hover{background:#f8f9fa}
        .notif-dot{width:8px;height:8px;border-radius:50%;margin-top:6px;flex-shrink:0}
        .notif-dot.info{background:#667eea}
        .notif-dot.warning{background:#ffc107}
        .notif-dot.success{background:#28a745}
        .notif-content{flex:1}
        .notif-message{font-size:14px;color:#2c3e50;margin-bottom:5px}
        .notif-time{font-size:12px;color:#999}
        .notif-footer{padding:15px 20px;text-align:center;border-top:1px solid #e0e0e0}
        .notif-close-btn{background:transparent;border:none;color:#667eea;font-size:13px;font-weight:600;cursor:pointer}
        
        @media(max-width:768px){
            .navbar{padding:15px 20px}
            .navbar h1{font-size:18px}
            .main-content{padding:20px}
            .hero-welcome{padding:30px 20px}
            .hero-content{grid-template-columns:1fr;text-align:center}
            .hero-text h2{font-size:28px}
            .hero-stats{justify-content:center}
            .stats-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px}
            .quick-actions-grid{grid-template-columns:repeat(2,1fr);gap:15px}
            .notifications-dropdown{right:20px;width:calc(100% - 40px)}
            .ai-modal-content{height:90vh;max-height:none;border-radius:20px}
        }
    </style>
</head>
<body>
    <div class="particles">
        <?php for($i=0;$i<20;$i++): ?>
        <div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;left:<?=rand(0,100)?>%;animation-delay:<?=rand(0,15)?>s;animation-duration:<?=rand(10,20)?>s;"></div>
        <?php endfor; ?>
    </div>

    <div class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">⭐</div>
            <div>
                <h1>NIT AMMS Superadmin</h1>
                <p>Advanced Management System</p>
            </div>
        </div>
        <div class="navbar-right">
            <button class="voice-btn" id="voiceBtn" onclick="toggleVoice()" title="Voice Assistant">🎤</button>
            <button class="sound-btn active" id="soundBtn" onclick="toggleSound()" title="Sound">🔊</button>
            <button class="notif-btn" onclick="toggleNotifications()" title="Notifications">🔔<span class="notif-badge"></span></button>
            <div class="user-profile">
                <div class="user-avatar">HP</div>
                <div class="user-info">
                    <span class="username"><?=htmlspecialchars($_SESSION['Himanshu Patil'])?></span>
                    <span class="role">System Administrator</span>
                </div>
            </div>
            <form method="POST" action="logout.php" style="margin:0">
                <button type="submit" class="btn-logout">🚪 Logout</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="hero-welcome">
            <div class="hero-content">
                <div class="hero-text">
                    <h2>Welcome Back, Super Admin! 👋</h2>
                    <p>Your complete control center for managing NIT AMMS. All systems operational.</p>
                    <div class="hero-stats">
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?=$totalUsers?></div>
                            <div class="hero-stat-label">Total Users</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value" style="background:linear-gradient(135deg,#28a745,#20c997);-webkit-background-clip:text;-webkit-text-fill-color:transparent">98.5%</div>
                            <div class="hero-stat-label">System Health</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value" style="background:linear-gradient(135deg,#f093fb,#f5576c);-webkit-background-clip:text;-webkit-text-fill-color:transparent">45ms</div>
                            <div class="hero-stat-label">Response Time</div>
                        </div>
                    </div>
                </div>
                <div class="glass-clock">
                    <div class="clock-icon">🕐</div>
                    <div class="time" id="clock">--:--:--</div>
                    <div class="date" id="date">Loading...</div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-card-1"><div class="stat-icon-wrapper">👨‍🎓</div><div class="stat-details"><h4>Students</h4><div class="stat-value-large"><?=$studentCount?></div><div class="stat-trend trend-up">↗ 12% vs last month</div></div></div>
             <div class="stat-card stat-card-3"><div class="stat-icon-wrapper">🏢</div><div class="stat-details"><h4>Departments</h4><div class="stat-value-large"><?=$departmentCount?></div><div class="stat-trend">→ No change</div></div></div>
            <div class="stat-card stat-card-4"><div class="stat-icon-wrapper">📚</div><div class="stat-details"><h4>Classes</h4><div class="stat-value-large"><?=$classCount?></div><div class="stat-trend trend-up">↗ 15% vs last month</div></div></div>
            <div class="stat-card stat-card-5"><div class="stat-icon-wrapper">📊</div><div class="stat-details"><h4>Attendance</h4><div class="stat-value-large"><?=$attendanceCount?></div><div class="stat-trend trend-up">↗ 5% vs last month</div></div></div>
            <div class="stat-card stat-card-6"><div class="stat-icon-wrapper">👥</div><div class="stat-details"><h4>Total Users</h4><div class="stat-value-large"><?=$totalUsers?></div><div class="stat-trend trend-up">↗ 10% vs last month</div></div></div>
        </div>

        <div class="section-title">🎛️ Quick Control Access</div>
        <div class="quick-actions-grid">
            <div class="action-card action-card-5" onclick="openAIAssistant()">
                <div class="action-icon">🤖</div>
                <div class="action-label">AI Assistant</div>
                <div class="action-description">Chat with intelligent AI</div>
            </div>
        </div>
    </div>

    <!-- AI Chat Modal -->
    <div class="ai-modal" id="aiModal">
        <div class="ai-modal-content">
            <div class="ai-modal-header">
                <div class="ai-header-left">
                    <div class="ai-avatar">🤖</div>
                    <div class="ai-header-info">
                        <h3>AI Assistant</h3>
                        <p><span class="ai-status-dot"></span> Online & Ready</p>
                    </div>
                </div>
                <button class="ai-modal-close" onclick="closeAIAssistant()">✕</button>
            </div>
            <div class="ai-chat-area" id="chatArea">
                <div class="ai-welcome">
                    <div class="ai-welcome-icon">🤖</div>
                    <h4>Hello! I'm your AI Assistant</h4>
                    <p>Ask me anything - system stats, general questions, help with tasks, or just chat. I'm here to help!</p>
                </div>
            </div>
            <div class="ai-input-area">
                <div class="ai-input-row">
                    <input type="text" class="ai-input" id="aiInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter')sendMessage()">
                    <button class="ai-voice-btn" id="aiVoiceBtn" onclick="startVoiceInput()">🎤</button>
                    <button class="ai-send-btn" id="sendBtn" onclick="sendMessage()">➤</button>
                </div>
                <div class="ai-suggestions">
                    <button class="suggestion-btn" onclick="sendSuggestion('How many students?')">📊 Students count</button>
                    <button class="suggestion-btn" onclick="sendSuggestion('System health status')">💚 System health</button>
                    <button class="suggestion-btn" onclick="sendSuggestion('Show all statistics')">📈 All stats</button>
                    <button class="suggestion-btn" onclick="sendSuggestion('Help me')">❓ Help</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="notifications-dropdown" id="notificationsDropdown">
        <div class="notif-header"><h4>Notifications</h4></div>
        <div class="notif-list">
            <?php foreach($notifications as $n): ?>
            <div class="notif-item"><div class="notif-dot <?=$n['type']?>"></div><div class="notif-content"><div class="notif-message"><?=htmlspecialchars($n['message'])?></div><div class="notif-time"><?=htmlspecialchars($n['time'])?></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="notif-footer"><button class="notif-close-btn" onclick="toggleNotifications()">Close</button></div>
    </div>

    <script>
    let voiceEnabled=false,soundEnabled=true,chatHistory=[],isProcessing=false;
    
    function updateClock(){const n=new Date();document.getElementById('clock').textContent=n.toLocaleTimeString();document.getElementById('date').textContent=n.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}
    setInterval(updateClock,1000);updateClock();
    
    function toggleVoice(){voiceEnabled=!voiceEnabled;document.getElementById('voiceBtn').classList.toggle('active',voiceEnabled);if(voiceEnabled&&soundEnabled)speak('Voice assistant activated')}
    function toggleSound(){soundEnabled=!soundEnabled;document.getElementById('soundBtn').classList.toggle('active',soundEnabled);if(!soundEnabled)window.speechSynthesis.cancel()}
    function toggleNotifications(){document.getElementById('notificationsDropdown').classList.toggle('active')}
    function openAIAssistant(){document.getElementById('aiModal').classList.add('active');document.getElementById('aiInput').focus()}
    function closeAIAssistant(){document.getElementById('aiModal').classList.remove('active')}
    
    function speak(t){if(!soundEnabled)return;window.speechSynthesis.cancel();const u=new SpeechSynthesisUtterance(t);u.rate=1;u.pitch=1;window.speechSynthesis.speak(u)}
    
    function startVoiceInput(){
        if(!('webkitSpeechRecognition' in window)&&!('SpeechRecognition' in window)){alert('Voice not supported in this browser');return}
        const SR=window.SpeechRecognition||window.webkitSpeechRecognition,r=new SR();
        r.continuous=false;r.interimResults=false;
        const btn=document.getElementById('aiVoiceBtn');btn.classList.add('listening');
        r.onresult=e=>{document.getElementById('aiInput').value=e.results[0][0].transcript;btn.classList.remove('listening');sendMessage()};
        r.onerror=()=>btn.classList.remove('listening');
        r.onend=()=>btn.classList.remove('listening');
        r.start()
    }
    
    function sendSuggestion(m){document.getElementById('aiInput').value=m;sendMessage()}
    
    function addMessage(content,isUser){
        const area=document.getElementById('chatArea'),welcome=area.querySelector('.ai-welcome');
        if(welcome)welcome.remove();
        const div=document.createElement('div');
        div.className='chat-message '+(isUser?'user':'ai');
        const time=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
        div.innerHTML=`<div class="msg-avatar">${isUser?'👤':'🤖'}</div><div class="msg-content">${isUser?escapeHtml(content):marked.parse(content)}<div class="msg-time">${time}</div></div>`;
        area.appendChild(div);area.scrollTop=area.scrollHeight
    }
    
    function showTyping(){
        const area=document.getElementById('chatArea'),div=document.createElement('div');
        div.className='typing-indicator';div.id='typingIndicator';
        div.innerHTML='<div class="msg-avatar">🤖</div><div class="msg-content"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
        area.appendChild(div);area.scrollTop=area.scrollHeight
    }
    function hideTyping(){const t=document.getElementById('typingIndicator');if(t)t.remove()}
    
    function escapeHtml(t){const d=document.createElement('div');d.textContent=t;return d.innerHTML}
    
    async function sendMessage(){
        if(isProcessing)return;
        const input=document.getElementById('aiInput'),msg=input.value.trim();
        if(!msg)return;
        input.value='';addMessage(msg,true);
        chatHistory.push({role:'user',content:msg});
        isProcessing=true;showTyping();document.getElementById('sendBtn').disabled=true;
        
        try{
            const form=new FormData();
            form.append('ai_message',msg);
            form.append('chat_history',JSON.stringify(chatHistory.slice(-10)));
            const res=await fetch('',{method:'POST',body:form});
            const data=await res.json();
            hideTyping();
            if(data.success){
                addMessage(data.response,false);
                chatHistory.push({role:'assistant',content:data.response});
                if(soundEnabled)speak(data.response.replace(/[*#_`]/g,'').substring(0,200))
            }else{addMessage('Sorry, something went wrong. Please try again.',false)}
        }catch(e){hideTyping();addMessage('Connection error. Please try again.',false)}
        isProcessing=false;document.getElementById('sendBtn').disabled=false;document.getElementById('aiInput').focus()
    }
    
    window.onclick=e=>{if(e.target.id==='aiModal')closeAIAssistant();if(!e.target.closest('.notif-btn')&&!e.target.closest('.notifications-dropdown'))document.getElementById('notificationsDropdown').classList.remove('active')};
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAIAssistant()});
    </script>
</body>
</html>