<?php
// superadmin/db_superadmin.php
session_start();

$host = 'localhost';
$db = 'nitcollege_attendance_system';  // Your existing database
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

// Superadmin credentials
define('SUPERADMIN_USERNAME', 'superadmin');
define('SUPERADMIN_PASSWORD', 'Super@2024#Admin');

function isSuperAdminLoggedIn() {
    return isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true;
}

function redirectIfNotSuperAdmin() {
    if (!isSuperAdminLoggedIn()) {
        header('Location: ../superadmin_login.php');
        exit();
    }
}

function logSuperAdminAction($pdo, $action, $description) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $admin_id = $_SESSION['superadmin_id'] ?? null;
        
        $sql = "INSERT INTO superadmin_logs (action, description, admin_id, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$action, $description, $admin_id, $ip]);
    } catch(Exception $e) {
        error_log("Error logging action: " . $e->getMessage());
    }
}

// Get user counts from existing tables
function getUserCounts($pdo) {
    try {
        $counts = [];
        
        // Count users table (this is your main users table)
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM users");
            $counts['total_users'] = $result->fetch()['count'] ?? 0;
        } catch(Exception $e) {
            $counts['total_users'] = 0;
        }
        
        // Count students
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM students");
            $counts['students'] = $result->fetch()['count'] ?? 0;
        } catch(Exception $e) {
            $counts['students'] = 0;
        }
        
        // Count teachers (from user table where role='teacher' or from class_teachers)
        try {
            $result = $pdo->query("SELECT COUNT(DISTINCT teacher_id) as count FROM class_teachers");
            $counts['teachers'] = $result->fetch()['count'] ?? 0;
        } catch(Exception $e) {
            $counts['teachers'] = 0;
        }
        
        // Count departments
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM departments");
            $counts['departments'] = $result->fetch()['count'] ?? 0;
        } catch(Exception $e) {
            $counts['departments'] = 0;
        }
        
        // Count classes
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM classes");
            $counts['classes'] = $result->fetch()['count'] ?? 0;
        } catch(Exception $e) {
            $counts['classes'] = 0;
        }
        
        // Count attendance records
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM student_attendance");
            $counts['attendance_records'] = $result->fetch()['count'] ?? 0;
        } catch(Exception $e) {
            $counts['attendance_records'] = 0;
        }
        
        return $counts;
    } catch(Exception $e) {
        return [
            'total_users' => 0,
            'students' => 0,
            'teachers' => 0,
            'departments' => 0,
            'classes' => 0,
            'attendance_records' => 0
        ];
    }
}

// Initialize tables
function initializeSuperAdminTables($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS superadmin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(255),
            description TEXT,
            admin_id INT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45)
        )";
        $pdo->exec($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS system_health (
            id INT AUTO_INCREMENT PRIMARY KEY,
            status VARCHAR(50),
            memory_usage FLOAT,
            database_size BIGINT,
            active_users INT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
    } catch(Exception $e) {
        error_log("Error initializing tables: " . $e->getMessage());
    }
}

?>