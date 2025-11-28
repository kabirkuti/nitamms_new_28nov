<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get selected academic year (default to current year if not set)
$selected_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '2025-2026';

// Success/error messages
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $student_count = isset($_GET['students']) ? intval($_GET['students']) : 0;
    $section_name = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : '';
    $success_message = "Assignment created successfully! 🎉 It's now visible to {$student_count} students in section {$section_name}.";
}

// Get all assignments created by this teacher (filtered by academic year)
$assignments_query = "SELECT a.*, c.class_name, c.section, c.academic_year,
                      COUNT(DISTINCT sub.id) as submission_count,
                      (SELECT COUNT(DISTINCT s2.id) 
                       FROM students s2 
                       JOIN classes c2 ON s2.class_id = c2.id 
                       WHERE c2.section = c.section 
                       AND c2.academic_year = c.academic_year 
                       AND s2.is_active = 1) as total_students
                      FROM assignments a
                      JOIN classes c ON a.class_id = c.id
                      LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id
                      WHERE a.teacher_id = ? AND c.academic_year = ?
                      GROUP BY a.id
                      ORDER BY a.created_at DESC";

$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("is", $teacher_id, $selected_academic_year);
$stmt->execute();
$assignments = $stmt->get_result();

// Get statistics (filtered by academic year)
$stats_query = "SELECT 
                COUNT(*) as total_assignments,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired
                FROM assignments a
                JOIN classes c ON a.class_id = c.id
                WHERE a.teacher_id = ? AND c.academic_year = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("is", $teacher_id, $selected_academic_year);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get available academic years for this teacher
$years_query = "SELECT DISTINCT c.academic_year 
                FROM classes c 
                JOIN assignments a ON c.id = a.class_id 
                WHERE a.teacher_id = ? 
                ORDER BY c.academic_year DESC";
$years_stmt = $conn->prepare($years_query);
$years_stmt->bind_param("i", $teacher_id);
$years_stmt->execute();
$academic_years = $years_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Teacher Dashboard</title>
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
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .navbar h1 {
            color: white;
            font-size: 24px;
        }
        
        .navbar-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .year-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 8px;
        }
        
        .year-selector label {
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .year-selector select {
            padding: 8px 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.95);
            color: #2c3e50;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .year-selector select:hover {
            background: white;
            border-color: #667eea;
        }
        
        .year-selector select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .year-info-badge {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
            padding: 15px 25px;
            border-radius: 12px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
            text-align: center;
            font-size: 16px;
            color: #667eea;
            font-weight: 600;
        }
        
        .year-info-badge strong {
            color: #764ba2;
            font-size: 18px;
        }
        
        .assignment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
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
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active { background: #d4edda; color: #155724; }
        .badge-expired { background: #f8d7da; color: #721c24; }
        
        .progress-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s;
        }
        
        .assignment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .navbar-actions {
                width: 100%;
                justify-content: center;
                flex-direction: column;
            }
            
            .assignment-header {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 My Assignments</h1>
        <div class="navbar-actions">
            <div class="year-selector">
                <label for="academic_year_select">📅 Academic Year:</label>
                <select id="academic_year_select" onchange="changeAcademicYear(this.value)">
                    <?php 
                    // If no assignments exist, get years from classes table
                    if ($academic_years->num_rows == 0) {
                        $all_years_query = "SELECT DISTINCT academic_year FROM classes WHERE teacher_id = ? ORDER BY academic_year DESC";
                        $all_years_stmt = $conn->prepare($all_years_query);
                        $all_years_stmt->bind_param("i", $teacher_id);
                        $all_years_stmt->execute();
                        $academic_years = $all_years_stmt->get_result();
                    }
                    
                    $academic_years->data_seek(0);
                    while ($year = $academic_years->fetch_assoc()): 
                    ?>
                        <option value="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                <?php echo ($year['academic_year'] == $selected_academic_year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year['academic_year']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <a href="create_assignment.php?academic_year=<?php echo urlencode($selected_academic_year); ?>" class="btn btn-primary">
                ➕ Create New Assignment
            </a>
            <a href="index.php" class="btn btn-secondary">
                ← Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="main-content">
        <?php if ($success_message): ?>
            <div class="success-message">
                <span style="font-size: 24px;">✅</span>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="year-info-badge">
            📅 Showing assignments for Academic Year: <strong><?php echo htmlspecialchars($selected_academic_year); ?></strong>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📝 TOTAL ASSIGNMENTS</h3>
                <div class="number"><?php echo $stats['total_assignments']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ ACTIVE</h3>
                <div class="number" style="color: #28a745;"><?php echo $stats['active']; ?></div>
            </div>
            <div class="stat-card">
                <h3>⏰ EXPIRED</h3>
                <div class="number" style="color: #dc3545;"><?php echo $stats['expired']; ?></div>
            </div>
        </div>

        <!-- Assignments List -->
        <?php if ($assignments->num_rows > 0): ?>
            <?php while ($assignment = $assignments->fetch_assoc()): 
                $due_date = new DateTime($assignment['due_date']);
                $now = new DateTime();
                $is_expired = $now > $due_date;
                
                $submission_percentage = $assignment['total_students'] > 0 
                    ? round(($assignment['submission_count'] / $assignment['total_students']) * 100) 
                    : 0;
            ?>
            <div class="assignment-card">
                <div class="assignment-header">
                    <div>
                        <div class="assignment-title">
                            <?php echo htmlspecialchars($assignment['title']); ?>
                        </div>
                        <span class="badge badge-<?php echo $is_expired ? 'expired' : 'active'; ?>">
                            <?php echo $is_expired ? '⏰ Expired' : '✅ Active'; ?>
                        </span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 24px; font-weight: 700; color: #667eea;">
                            <?php echo $assignment['submission_count']; ?>/<?php echo $assignment['total_students']; ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Submissions</div>
                    </div>
                </div>

                <div class="assignment-meta">
                    <div class="meta-item">
                        <span>🏫</span>
                        <span><?php echo htmlspecialchars($assignment['section']); ?> - <?php echo htmlspecialchars($assignment['class_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>📅</span>
                        <span>Due: <?php echo $due_date->format('d M Y, h:i A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>📊</span>
                        <span>Max Marks: <?php echo $assignment['max_marks']; ?></span>
                    </div>
                    <?php if ($assignment['subject']): ?>
                    <div class="meta-item">
                        <span>📖</span>
                        <span><?php echo htmlspecialchars($assignment['subject']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span>👥</span>
                        <span><?php echo $assignment['total_students']; ?> students in section (<?php echo htmlspecialchars($assignment['academic_year']); ?>)</span>
                    </div>
                </div>

                <?php if ($assignment['description']): ?>
                <p style="color: #666; margin: 10px 0;">
                    <?php echo nl2br(htmlspecialchars(substr($assignment['description'], 0, 150))); ?>
                    <?php echo strlen($assignment['description']) > 150 ? '...' : ''; ?>
                </p>
                <?php endif; ?>

                <!-- Progress Bar -->
                <div style="margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-size: 13px; color: #666;">Submission Progress</span>
                        <span style="font-size: 13px; font-weight: 600; color: #667eea;"><?php echo $submission_percentage; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $submission_percentage; ?>%"></div>
                    </div>
                </div>

                <div class="assignment-actions">
                    <a href="view_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary btn-sm">
                        👁️ View Details
                    </a> 
                    <a href="assignment_submissions.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary btn-sm">
                        📋 View Submissions (<?php echo $assignment['submission_count']; ?>)
                    </a>
                    <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary btn-sm">
                        ✏️ Edit
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 80px; margin-bottom: 20px;">📝</div>
                <h2>No Assignments for <?php echo htmlspecialchars($selected_academic_year); ?></h2>
                <p style="color: #666; margin: 15px 0;">You haven't created any assignments for this academic year yet.</p>
                <a href="create_assignment.php?academic_year=<?php echo urlencode($selected_academic_year); ?>" class="btn btn-primary">
                    ➕ Create Your First Assignment
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function changeAcademicYear(year) {
            window.location.href = 'assignments.php?academic_year=' + encodeURIComponent(year);
        }
    </script>
</body>
</html>