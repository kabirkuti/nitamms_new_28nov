<?php
require_once '../db.php';
checkRole(['student']);

$user = getCurrentUser();
$student_id = $user['student_id'];
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get submission details
$submission_query = "SELECT sub.*, a.title, a.max_marks, a.due_date,
                     u.full_name as teacher_name
                     FROM assignment_submissions sub
                     JOIN assignments a ON sub.assignment_id = a.id
                     JOIN users u ON a.teacher_id = u.id
                     WHERE sub.id = ? AND sub.student_id = ?";

$stmt = $conn->prepare($submission_query);
$stmt->bind_param("ii", $submission_id, $student_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    header("Location: assignments.php?error=not_found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submission</title>
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
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 { color: white; font-size: 24px; }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .main-content {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .submission-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .submission-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            line-height: 1.8;
            margin: 20px 0;
        }
        
        .attachment-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .badge-submitted { background: #d4edda; color: #155724; }
        .badge-late { background: #f8d7da; color: #721c24; }
        .badge-graded { background: #d1ecf1; color: #0c5460; }
        
        .grade-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .grade-score {
            font-size: 72px;
            font-weight: 800;
            margin: 20px 0;
        }
        
        .feedback-box {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📋 My Submission</h1>
        <a href="assignments.php" class="btn-secondary">← Back to Assignments</a>
    </nav>

    <div class="main-content">
        <div class="card">
            <div class="submission-header">
                <h2 style="margin-bottom: 15px;"><?php echo htmlspecialchars($submission['title']); ?></h2>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($submission['teacher_name']); ?></p>
                        <p><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($submission['submitted_at'])); ?></p>
                        <p><strong>Max Marks:</strong> <?php echo $submission['max_marks']; ?></p>
                    </div>
                    <div>
                        <?php if ($submission['status'] === 'late'): ?>
                            <span class="badge badge-late">⏰ Late Submission</span>
                        <?php elseif ($submission['marks_obtained'] !== null): ?>
                            <span class="badge badge-graded">📊 Graded</span>
                        <?php else: ?>
                            <span class="badge badge-submitted">✅ Submitted</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h3 style="margin: 25px 0 15px;">📝 Your Answer</h3>
            <div class="submission-content">
                <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
            </div>

            <?php if ($submission['attachment_path']): ?>
            <div class="attachment-box">
                <strong>📎 Your Attachment:</strong>
                <a href="../<?php echo htmlspecialchars($submission['attachment_path']); ?>" 
                   target="_blank" 
                   style="color: #2196F3; margin-left: 10px;">
                    📥 <?php echo htmlspecialchars($submission['attachment_name']); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($submission['marks_obtained'] !== null): ?>
        <div class="card">
            <div class="grade-box">
                <h3 style="font-size: 24px; margin-bottom: 10px;">Your Grade</h3>
                <div class="grade-score">
                    <?php echo $submission['marks_obtained']; ?><span style="font-size: 48px;">/<?php echo $submission['max_marks']; ?></span>
                </div>
                <p style="font-size: 20px; opacity: 0.9;">
                    <?php 
                    $percentage = ($submission['marks_obtained'] / $submission['max_marks']) * 100;
                    echo round($percentage, 2) . '%';
                    ?>
                </p>
                <p style="margin-top: 10px; opacity: 0.8;">
                    Graded on: <?php echo date('d M Y, h:i A', strtotime($submission['graded_at'])); ?>
                </p>
            </div>

            <?php if ($submission['feedback']): ?>
            <div class="feedback-box">
                <h4 style="margin-bottom: 10px; color: #856404;">💬 Teacher's Feedback</h4>
                <p style="line-height: 1.8; color: #856404;">
                    <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <div style="font-size: 64px; margin-bottom: 15px;">⏳</div>
            <h3 style="color: #666;">Pending Grading</h3>
            <p style="color: #999; margin-top: 10px;">Your teacher hasn't graded this submission yet.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>