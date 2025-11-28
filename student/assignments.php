<?php
require_once '../db.php';
checkRole(['student']);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $user = getCurrentUser();
    
    // Get student ID
    $student_id = null;
    if (isset($_SESSION['student_id'])) {
        $student_id = $_SESSION['student_id'];
    } elseif (isset($user['student_id'])) {
        $student_id = $user['student_id'];
    } elseif (isset($user['id'])) {
        $check_query = "SELECT id FROM students WHERE id = ? AND is_active = 1";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) throw new Exception("Database prepare error");
        $check_stmt->bind_param("i", $user['id']);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $student_id = $user['id'];
        }
        $check_stmt->close();
    }
    
    if (!$student_id) {
        throw new Exception("Unable to determine student ID. Please contact administration.");
    }

    // Get student's complete information including section
    $student_query = "SELECT s.*, c.class_name, c.section, c.id as class_id, c.year, c.semester, d.dept_name
                      FROM students s
                      LEFT JOIN classes c ON s.class_id = c.id
                      LEFT JOIN departments d ON s.department_id = d.id
                      WHERE s.id = ? AND s.is_active = 1";
    
    $stmt = $conn->prepare($student_query);
    if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Student information not found");
    }

    $student_info = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student_info['section']) {
        throw new Exception("You are not assigned to any section. Please contact administration.");
    }

    // Get assignments for student's section (all students in same section see same assignments)
    $assignments_query = "SELECT a.*, 
                          u.full_name as teacher_name,
                          c.section,
                          c.class_name,
                          sub.id as submission_id,
                          sub.submitted_at,
                          sub.marks_obtained,
                          sub.feedback,
                          sub.status as submission_status
                          FROM assignments a
                          JOIN classes c ON a.class_id = c.id
                          JOIN users u ON a.teacher_id = u.id
                          LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id 
                              AND sub.student_id = ?
                          WHERE c.section = ? AND a.status = 'active'
                          ORDER BY a.due_date ASC";

    $stmt = $conn->prepare($assignments_query);
    if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
    
    $stmt->bind_param("is", $student_id, $student_info['section']);
    $stmt->execute();
    $assignments_result = $stmt->get_result();

    $assignments = [];
    while ($row = $assignments_result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();

    // Calculate statistics
    $pending_count = 0;
    $submitted_count = 0;
    $graded_count = 0;
    $overdue_count = 0;

    $now = new DateTime();
    foreach ($assignments as $a) {
        $due_date = new DateTime($a['due_date']);
        $is_overdue = $now > $due_date;
        
        if ($a['submission_id']) {
            $submitted_count++;
            if ($a['marks_obtained'] !== null) {
                $graded_count++;
            }
        } else {
            if ($is_overdue) {
                $overdue_count++;
            } else {
                $pending_count++;
            }
        }
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Assignment Page Error: " . $error_message);
}

$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'submitted') {
        $success_message = 'Assignment submitted successfully! 🎉';
    }
}

$error_param = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'already_submitted') {
        $error_param = 'You have already submitted this assignment.';
    } elseif ($_GET['error'] === 'not_found') {
        $error_param = 'Assignment not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Student Dashboard</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .navbar h1 { color: white; font-size: 24px; font-weight: 700; }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .main-content {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error-container, .error-message-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 42px;
            font-weight: 800;
        }
        
        .student-info {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .student-info h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-weight: 700;
            font-size: 20px;
        }
        
        .student-info p {
            margin: 10px 0;
            color: #666;
            font-weight: 500;
        }
        
        .assignment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid #667eea;
        }
        
        .assignment-card.submitted { border-left-color: #28a745; }
        .assignment-card.late { border-left-color: #dc3545; }
        .assignment-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
        }
        
        .assignment-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .assignment-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 15px 0;
            font-size: 14px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-submitted { background: #d4edda; color: #155724; }
        .badge-late { background: #f8d7da; color: #721c24; }
        .badge-graded { background: #d1ecf1; color: #0c5460; }
        
        .countdown {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .countdown.urgent { 
            background: #fff3cd; 
            color: #856404;
            border: 2px solid #ffc107;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit { 
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            text-decoration: none;
        }
        
        .btn-submit:hover {
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .grading-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #2196F3;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .score-display {
            text-align: right;
        }
        
        .score-display .score {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .score-display .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid #e9ecef;
        }
        
        .attachment-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .assignment-card {
                padding: 20px;
            }

            .assignment-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .score-display {
                text-align: left;
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 My Assignments</h1>
        <div style="display: flex; gap: 15px;">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if ($success_message): ?>
            <div class="success-message">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_param): ?>
            <div class="error-message-box">
                ⚠️ <?php echo htmlspecialchars($error_param); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-container">
                <div style="font-size: 50px; margin-bottom: 10px;">⚠️</div>
                <h2>Unable to Load Assignments</h2>
                <p style="margin: 15px 0;"><?php echo htmlspecialchars($error_message); ?></p>
                <p style="color: #666; margin-top: 15px; font-size: 14px;">Please contact your administrator or try logging out and back in.</p>
                <div style="margin-top: 30px;">
                    <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Student Info Card -->
            <div class="student-info">
                <h3>👤 Student Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student_info['full_name'] ?? 'N/A'); ?></p>
                <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student_info['roll_number'] ?? 'N/A'); ?></p>
                <p><strong>Section:</strong> <?php echo htmlspecialchars($student_info['section'] ?? 'N/A'); ?></p>
                <p><strong>Class:</strong> <?php echo htmlspecialchars($student_info['class_name'] ?? 'N/A'); ?></p>
                <p><strong>Year & Semester:</strong> Year <?php echo intval($student_info['year'] ?? 0); ?>, Semester <?php echo intval($student_info['semester'] ?? 0); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($student_info['dept_name'] ?? 'N/A'); ?></p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>⏳ PENDING</h3>
                    <div class="number" style="color: #ffc107;"><?php echo $pending_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>⌛ OVERDUE</h3>
                    <div class="number" style="color: #dc3545;"><?php echo $overdue_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>✅ SUBMITTED</h3>
                    <div class="number" style="color: #28a745;"><?php echo $submitted_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>📊 GRADED</h3>
                    <div class="number" style="color: #17a2b8;"><?php echo $graded_count; ?></div>
                </div>
            </div>

            <!-- Assignments List -->
            <?php if (count($assignments) > 0): ?>
                <?php foreach ($assignments as $assignment): 
                    $due_date = new DateTime($assignment['due_date']);
                    $now = new DateTime();
                    $interval = $now->diff($due_date);
                    $is_overdue = $now > $due_date;
                    
                    $status_badge = '';
                    $card_class = '';
                    if ($assignment['submission_id']) {
                        $card_class = 'submitted';
                        if ($assignment['marks_obtained'] !== null) {
                            $status_badge = '<span class="badge badge-graded">📊 Graded</span>';
                        } else {
                            $status_badge = '<span class="badge badge-submitted">✅ Submitted</span>';
                        }
                    } else if ($is_overdue) {
                        $card_class = 'late';
                        $status_badge = '<span class="badge badge-late">⏰ Overdue</span>';
                    } else {
                        $status_badge = '<span class="badge badge-pending">⏳ Pending</span>';
                    }
                ?>
                <div class="assignment-card <?php echo $card_class; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                        <div style="flex: 1; min-width: 250px;">
                            <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            <?php echo $status_badge; ?>
                        </div>
                        <?php if ($assignment['marks_obtained'] !== null): ?>
                            <div class="score-display">
                                <div class="score"><?php echo intval($assignment['marks_obtained']); ?>/<?php echo intval($assignment['max_marks']); ?></div>
                                <div class="label">Your Score</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="assignment-meta">
                        <div class="meta-item">
                            <span>👨‍🏫</span>
                            <span><?php echo htmlspecialchars($assignment['teacher_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span>📅</span>
                            <span>Due: <?php echo $due_date->format('d M Y, h:i A'); ?></span>
                        </div>
                        <?php if ($assignment['subject']): ?>
                            <div class="meta-item">
                                <span>📖</span>
                                <span><?php echo htmlspecialchars($assignment['subject']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <span>🎯</span>
                            <span>Max Marks: <?php echo intval($assignment['max_marks']); ?></span>
                        </div>
                    </div>

                    <?php if ($assignment['description']): ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <strong style="color: #2c3e50;">📝 Instructions:</strong>
                            <p style="color: #666; margin-top: 10px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($assignment['attachment_path']): ?>
                        <a href="../<?php echo htmlspecialchars($assignment['attachment_path']); ?>" 
                           class="attachment-link" 
                           download 
                           target="_blank">
                            📎 Download Assignment File
                        </a>
                    <?php endif; ?>

                    <?php if (!$assignment['submission_id'] && !$is_overdue): ?>
                        <?php 
                        $days_left = $interval->days;
                        $hours_left = $interval->h;
                        $is_urgent = $days_left == 0 || ($days_left == 1 && $hours_left < 12);
                        ?>
                        <div class="countdown <?php echo $is_urgent ? 'urgent' : ''; ?>">
                            ⏰ 
                            <?php if ($days_left > 0): ?>
                                <?php echo $days_left; ?> day(s) 
                            <?php endif; ?>
                            <?php echo $hours_left; ?> hour(s) left
                            <?php if ($is_urgent): ?>
                                - <strong>Urgent!</strong>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($assignment['submission_id']): ?>
                        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #28a745;">
                            <strong style="color: #155724;">✅ Submitted</strong>
                            <p style="color: #155724; margin-top: 8px; font-size: 14px;">
                                Submitted on: <?php echo date('d M Y, h:i A', strtotime($assignment['submitted_at'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($assignment['feedback']): ?>
                        <div class="grading-info">
                            <strong style="color: #0c5460;">📋 Teacher Feedback:</strong>
                            <p style="margin-top: 10px; color: #0c5460; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <?php if (!$assignment['submission_id'] && !$is_overdue): ?>
                            <a href="submit_assignment.php?id=<?php echo intval($assignment['id']); ?>" 
                               class="btn btn-submit" 
                               style="text-decoration: none;">
                                📤 Submit Assignment
                            </a>
                        <?php elseif (!$assignment['submission_id'] && $is_overdue): ?>
                            <button class="btn btn-submit" 
                                    onclick="alert('This assignment is overdue. Contact your teacher to discuss late submission.')"
                                    style="opacity: 0.6; cursor: not-allowed;">
                                ⏰ Submission Closed
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 80px; margin-bottom: 20px;">📚</div>
                    <h2>No Assignments Available</h2>
                    <p style="color: #666; margin: 15px 0;">There are no assignments for your section right now.</p>
                    <p style="color: #666; font-size: 14px; margin-top: 10px;">
                        Your section: <strong><?php echo htmlspecialchars($student_info['section']); ?></strong>
                    </p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">← Back to Dashboard</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>