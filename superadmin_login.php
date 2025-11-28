<?php
// superadmin_login.php (Place in project root - NIT folder)
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true) {
    header('Location: superadmin/index.php');
    exit();
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Credentials (Change these after first login!)
    $valid_username = 'superadmin';
    $valid_password = 'Super@2024#Admin';
    
    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['superadmin_logged_in'] = true;
        $_SESSION['superadmin_id'] = 1;
        $_SESSION['superadmin_username'] = $username;
        $_SESSION['login_time'] = time();
        
        header('Location: superadmin/index.php');
        exit();
    } else {
        $error = 'Invalid credentials!';
    }
}

// Check for logout success
if (isset($_GET['success']) && $_GET['success'] === 'logout') {
    $success = 'Logged out successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#667eea">
    <title>Superadmin Login - NIT AMMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .blob {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            animation: float 15s infinite;
        }
        
        .blob1 {
            width: 300px;
            height: 300px;
            background: white;
            top: -50px;
            left: -50px;
        }
        
        .blob2 {
            width: 200px;
            height: 200px;
            background: white;
            bottom: -50px;
            right: -50px;
            animation-delay: 5s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(30px); }
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .login-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-header p {
            color: #999;
            font-size: 14px;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            animation: slideIn 0.4s ease-out;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            font-size: 18px;
            z-index: 2;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        .form-group input::placeholder {
            color: #ccc;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .credentials-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            text-align: center;
        }
        
        .credentials-info h3 {
            color: #667eea;
            font-size: 13px;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }
        
        .cred-item {
            margin-bottom: 12px;
            text-align: left;
        }
        
        .cred-label {
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }
        
        .cred-value {
            background: white;
            padding: 10px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #333;
            word-break: break-all;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 25px;
            }
            
            .login-header h1 {
                font-size: 26px;
            }
            
            .form-group input {
                padding: 12px 14px 12px 44px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background -->
    <div class="bg-animation">
        <div class="blob blob1"></div>
        <div class="blob blob2"></div>
    </div>
    
    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="badge">⭐ Superadmin Portal</div>
                <h1>NIT AMMS</h1>
                <p>Superadmin Control Panel</p>
            </div>
            
            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>👤 Username</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Enter superadmin username" required autofocus>
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>🔐 Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter superadmin password" required>
                        <span class="input-icon">🔑</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">🔓 Login to Dashboard</button>
            </form>
            
            <!-- Credentials Info -->
            <div class="credentials-info">
                <h3>📝 Test Credentials</h3>
                <div class="cred-item">
                    <span class="cred-label">Username:</span>
                    <div class="cred-value">superadmin</div>
                </div>
                <div class="cred-item">
                    <span class="cred-label">Password:</span>
                    <div class="cred-value">Super@2024#Admin</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>