<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get submission details
$submission_query = "SELECT sub.*, s.full_name, s.roll_number, 
                     a.title as assignment_title, a.max_marks, a.teacher_id
                     FROM assignment_submissions sub
                     JOIN students s ON sub.student_id = s.id
                     JOIN assignments a ON sub.assignment_id = a.id
                     WHERE sub.id = ?";

$stmt = $conn->prepare($submission_query);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission || $submission['teacher_id'] != $user['id']) {
    header("Location: assignments.php?error=unauthorized");
    exit();
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marks = intval($_POST['marks']);
    $feedback = sanitize($_POST['feedback']);
    
    if ($marks < 0 || $marks > $submission['max_marks']) {
        $error = "Marks must be between 0 and " . $submission['max_marks'];
    } else {
        $update_query = "UPDATE assignment_submissions 
                        SET marks_obtained = ?, feedback = ?, graded_at = NOW(), graded_by = ?, status = 'graded'
                        WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("isii", $marks, $feedback, $user['id'], $submission_id);
        
        if ($stmt->execute()) {
            header("Location: assignment_submissions.php?id=" . $submission['assignment_id'] . "&success=graded");
            exit();
        } else {
            $error = "Failed to save grades. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission</title>
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
        
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .submission-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            line-height: 1.8;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .attachment-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Grade Submission</h1>
        <a href="assignment_submissions.php?id=<?php echo $submission['assignment_id']; ?>" class="btn-secondary">← Back</a>
    </nav>

    <div class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-bottom: 20px;">Student Submission</h2>
            
            <div class="student-info">
                <p><strong>Assignment:</strong> <?php echo htmlspecialchars($submission['assignment_title']); ?></p>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($submission['full_name']); ?></p>
                <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($submission['roll_number']); ?></p>
                <p><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($submission['submitted_at'])); ?></p>
                <p><strong>Maximum Marks:</strong> <?php echo $submission['max_marks']; ?></p>
            </div>

            <?php if ($submission['submission_text']): ?>
            <div style="margin: 20px 0;">
                <h3 style="margin-bottom: 10px;">📝 Submission Text:</h3>
                <div class="submission-content">
                    <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($submission['attachment_path']): ?>
            <div class="attachment-box">
                <strong>📎 Attachment:</strong>
                <a href="../<?php echo htmlspecialchars($submission['attachment_path']); ?>" 
                   target="_blank" 
                   style="color: #2196F3; margin-left: 10px;">
                    📥 <?php echo htmlspecialchars($submission['attachment_name']); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">Grade & Feedback</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="marks">Marks Obtained (out of <?php echo $submission['max_marks']; ?>) *</label>
                    <input type="number" 
                           id="marks" 
                           name="marks" 
                           min="0" 
                           max="<?php echo $submission['max_marks']; ?>"
                           value="<?php echo $submission['marks_obtained'] ?? ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="feedback">Feedback for Student</label>
                    <textarea id="feedback" 
                              name="feedback" 
                              placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-primary">
                    <?php echo $submission['marks_obtained'] !== null ? '✏️ Update Grade' : '✅ Submit Grade'; ?>
                </button>
            </form>
        </div>
    </div>
</body>
</html>