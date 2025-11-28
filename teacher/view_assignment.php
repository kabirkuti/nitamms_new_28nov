<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get assignment details
$assignment_query = "SELECT a.*, c.class_name, c.section,
                     COUNT(DISTINCT s.id) as total_students,
                     COUNT(DISTINCT sub.id) as submission_count,
                     COUNT(CASE WHEN sub.marks_obtained IS NOT NULL THEN 1 END) as graded_count
                     FROM assignments a
                     JOIN classes c ON a.class_id = c.id
                     LEFT JOIN students s ON s.class_id = c.id AND s.is_active = 1
                     LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id
                     WHERE a.id = ? AND a.teacher_id = ?
                     GROUP BY a.id";

$stmt = $conn->prepare($assignment_query);
$stmt->bind_param("ii", $assignment_id, $teacher_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: assignments.php?error=not_found");
    exit();
}

$due_date = new DateTime($assignment['due_date']);
$now = new DateTime();
$is_expired = $now > $due_date;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($assignment['title']); ?></title>
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
        }
        
        .navbar h1 { color: white; font-size: 24px; }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; }
        
        .main-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .detail-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .assignment-title {
            font-size: 32px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .meta-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .meta-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 25px;
            border-radius: 15px;
            color: white;
            text-align: center;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 800;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .attachment-box {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-active { background: #d4edda; color: #155724; }
        .badge-expired { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📄 Assignment Details</h1>
        <a href="assignments.php" class="btn btn-secondary">← Back to Assignments</a>
    </nav>

    <div class="main-content">
        <div class="detail-card">
            <div class="detail-header">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <div class="assignment-title">
                            <?php echo htmlspecialchars($assignment['title']); ?>
                        </div>
                        <span class="badge badge-<?php echo $is_expired ? 'expired' : 'active'; ?>">
                            <?php echo $is_expired ? '⏰ Expired' : '✅ Active'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $assignment['submission_count']; ?>/<?php echo $assignment['total_students']; ?></div>
                    <div class="stat-label">Submissions</div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="stat-number"><?php echo $assignment['graded_count']; ?></div>
                    <div class="stat-label">Graded</div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                    <div class="stat-number"><?php echo $assignment['total_students'] - $assignment['submission_count']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>

            <!-- Details -->
            <div class="meta-grid">
                <div class="meta-item">
                    <div class="meta-label">Class</div>
                    <div class="meta-value"><?php echo htmlspecialchars($assignment['class_name'] . ' - ' . $assignment['section']); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Due Date</div>
                    <div class="meta-value"><?php echo $due_date->format('d M Y, h:i A'); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Maximum Marks</div>
                    <div class="meta-value"><?php echo $assignment['max_marks']; ?></div>
                </div>
                <?php if ($assignment['subject']): ?>
                <div class="meta-item">
                    <div class="meta-label">Subject</div>
                    <div class="meta-value"><?php echo htmlspecialchars($assignment['subject']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($assignment['description']): ?>
            <div style="margin: 30px 0;">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">📝 Description</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Attachment -->
            <?php if ($assignment['attachment_path']): ?>
            <div class="attachment-box">
                <strong>📎 Attachment:</strong>
                <a href="../<?php echo htmlspecialchars($assignment['attachment_path']); ?>" 
                   target="_blank" 
                   style="color: #2196F3; margin-left: 10px; text-decoration: none;">
                    📥 <?php echo htmlspecialchars($assignment['attachment_name']); ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="action-buttons">
                <a href="assignment_submissions.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary">
                    📋 View All Submissions (<?php echo $assignment['submission_count']; ?>)
                </a>
                <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary">
                    ✏️ Edit Assignment
                </a>
                <button onclick="confirmDelete()" class="btn btn-danger">
                    🗑️ Delete Assignment
                </button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this assignment? This action cannot be undone. All student submissions will also be deleted.')) {
                window.location.href = 'delete_assignment.php?id=<?php echo $assignment_id; ?>';
            }
        }
    </script>
</body>
</html>