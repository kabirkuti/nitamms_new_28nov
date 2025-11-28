<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['teacher']);

$user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($assignment_id <= 0) {
    header("Location: assignments.php?error=invalid_id");
    exit();
}

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'graded') {
    $success_message = 'Grades saved successfully! ✅';
}

try {
    // Get assignment details
    $assignment_query = "SELECT a.id, a.title, a.description, a.max_marks, a.due_date, a.class_id, a.teacher_id,
                                c.class_name, c.section
                         FROM assignments a
                         JOIN classes c ON a.class_id = c.id
                         WHERE a.id = ? AND a.teacher_id = ?";

    $stmt = $conn->prepare($assignment_query);
    $stmt->bind_param("ii", $assignment_id, $user_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$assignment) {
        die("Assignment not found or you don't have permission to view it.");
    }

    // Get all students in this section (section-based, not class-based)
    $students_query = "SELECT DISTINCT s.id, s.full_name, s.roll_number 
                       FROM students s
                       JOIN classes c ON s.class_id = c.id
                       WHERE c.section = ? AND s.is_active = 1 
                       ORDER BY s.roll_number ASC";
    
    $stmt = $conn->prepare($students_query);
    $stmt->bind_param("s", $assignment['section']);
    $stmt->execute();
    $students_result = $stmt->get_result();
    $students = [];
    
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    // Get all submissions for this assignment
    $submissions_query = "SELECT sub.*, s.full_name, s.roll_number
                          FROM assignment_submissions sub
                          JOIN students s ON sub.student_id = s.id
                          WHERE sub.assignment_id = ?";
    
    $stmt = $conn->prepare($submissions_query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $submissions_result = $stmt->get_result();
    $submissions_data = [];
    
    while ($row = $submissions_result->fetch_assoc()) {
        $submissions_data[$row['student_id']] = $row;
    }
    $stmt->close();

    // Build final submissions array
    $submissions = [];
    $submitted_count = 0;
    $graded_count = 0;

    foreach ($students as $student) {
        $student_id = $student['id'];
        if (isset($submissions_data[$student_id])) {
            $sub = $submissions_data[$student_id];
            $submissions[] = [
                'student_id' => $student_id,
                'name' => $student['full_name'],
                'roll_number' => $student['roll_number'],
                'submission_id' => $sub['id'],
                'submitted_at' => $sub['submitted_at'],
                'submission_text' => $sub['submission_text'],
                'attachment_name' => $sub['attachment_name'],
                'marks_obtained' => $sub['marks_obtained'],
                'feedback' => $sub['feedback'],
                'status' => $sub['status']
            ];
            $submitted_count++;
            if ($sub['marks_obtained'] !== null) {
                $graded_count++;
            }
        } else {
            $submissions[] = [
                'student_id' => $student_id,
                'name' => $student['full_name'],
                'roll_number' => $student['roll_number'],
                'submission_id' => null,
                'submitted_at' => null,
                'submission_text' => null,
                'attachment_name' => null,
                'marks_obtained' => null,
                'feedback' => null,
                'status' => 'pending'
            ];
        }
    }

    $total_students = count($students);
    $pending_count = $total_students - $submitted_count;

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions - <?php echo htmlspecialchars($assignment['title']); ?></title>
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
        
        .main-content {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
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
        
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
        }
        
        .header h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 20px;
            border-radius: 10px;
            color: white;
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 800;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-submitted {
            background: #d4edda;
            color: #155724;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-graded {
            background: #cfe2ff;
            color: #084298;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .action-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-right: 15px;
            transition: color 0.3s;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
        }
        
        .action-link:hover {
            color: #764ba2;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .submission-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            max-height: 300px;
            overflow-y: auto;
        }

        .small-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📋 Submissions</h1>
        <a href="assignments.php" class="btn btn-secondary">← Back</a>
    </nav>

    <div class="main-content">
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="header">
                <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <p><?php echo htmlspecialchars($assignment['section']); ?> - <?php echo htmlspecialchars($assignment['class_name']); ?></p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="stat-number"><?php echo $submitted_count; ?></div>
                    <div class="stat-label">Submitted</div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <div class="stat-number"><?php echo $graded_count; ?></div>
                    <div class="stat-label">Graded</div>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="table-container">
                <?php if (count($submissions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll No.</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Submission Date</th>
                            <th>Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($sub['name']); ?></td>
                            <td>
                                <?php if ($sub['submission_id']): ?>
                                    <?php if ($sub['marks_obtained'] !== null): ?>
                                        <span class="status-graded">✓ Graded</span>
                                    <?php else: ?>
                                        <span class="status-submitted">✓ Submitted</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-pending">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if ($sub['submission_id'] && $sub['submitted_at']) {
                                        $date = new DateTime($sub['submitted_at']);
                                        echo $date->format('d M Y, h:i A');
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if ($sub['marks_obtained'] !== null) {
                                        echo '<strong>' . htmlspecialchars($sub['marks_obtained']) . '/' . htmlspecialchars($assignment['max_marks']) . '</strong>';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if ($sub['submission_id']): ?>
                                    <button onclick="viewSubmission(<?php echo htmlspecialchars(json_encode($sub)); ?>, <?php echo intval($assignment['max_marks']); ?>)" class="action-link">👁️ View</button>
                                    <button onclick="openGradeModal(<?php echo intval($sub['submission_id']); ?>, '<?php echo addslashes($sub['marks_obtained'] ?? ''); ?>', '<?php echo addslashes($sub['feedback'] ?? ''); ?>')" class="action-link">⭐ Grade</button>
                                <?php else: ?>
                                    <span style="color: #999;">Not Submitted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 60px; margin-bottom: 20px;">🔭</div>
                    <h3>No Students</h3>
                    <p>No active students found in section <?php echo htmlspecialchars($assignment['section']); ?>.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Submission Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">📄 View Submission</div>
            <div id="viewContent"></div>
            <div class="modal-buttons">
                <button onclick="closeViewModal()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>

    <!-- Grade Modal -->
    <div id="gradeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">📊 Grade Submission</div>
            <form id="gradeForm" method="POST" action="save_grades.php">
                <input type="hidden" id="submissionId" name="submission_id" value="">
                <input type="hidden" name="assignment_id" value="<?php echo intval($assignment_id); ?>">
                
                <div class="form-group">
                    <label>Marks</label>
                    <input type="number" id="marksInput" name="marks" min="0" max="<?php echo intval($assignment['max_marks']); ?>" step="0.5" required>
                    <small class="small-text">Maximum: <?php echo intval($assignment['max_marks']); ?></small>
                </div>
                
                <div class="form-group">
                    <label>Feedback</label>
                    <textarea id="feedbackInput" name="feedback" placeholder="Enter feedback for the student..."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" onclick="closeGradeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">💾 Save Grades</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewSubmission(sub, maxMarks) {
            const content = `
                <div style="margin-bottom: 20px;">
                    <strong>Student:</strong> ${sub.name} (${sub.roll_number})<br>
                    <strong>Submitted:</strong> ${new Date(sub.submitted_at).toLocaleString()}<br>
                    ${sub.marks_obtained !== null ? `<strong>Marks:</strong> ${sub.marks_obtained}/${maxMarks}<br>` : ''}
                </div>
                <div class="submission-preview">
                    <strong>Submission Text:</strong><br>
                    ${sub.submission_text ? sub.submission_text.replace(/\n/g, '<br>') : 'No text submitted'}
                </div>
                ${sub.attachment_name ? `<div style="margin-top: 15px;"><strong>Attachment:</strong> ${sub.attachment_name}</div>` : ''}
                ${sub.feedback ? `<div class="submission-preview" style="margin-top: 15px;"><strong>Feedback:</strong><br>${sub.feedback.replace(/\n/g, '<br>')}</div>` : ''}
            `;
            document.getElementById('viewContent').innerHTML = content;
            document.getElementById('viewModal').classList.add('show');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('show');
        }

        function openGradeModal(submissionId, marks, feedback) {
            document.getElementById('submissionId').value = submissionId;
            document.getElementById('marksInput').value = marks || '';
            document.getElementById('feedbackInput').value = feedback || '';
            document.getElementById('gradeModal').classList.add('show');
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').classList.remove('show');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        document.getElementById('gradeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const marks = parseFloat(document.getElementById('marksInput').value);
            const maxMarks = <?php echo intval($assignment['max_marks']); ?>;
            
            if (isNaN(marks) || marks < 0) {
                alert('Please enter a valid marks value');
                return;
            }
            
            if (marks > maxMarks) {
                alert('Marks cannot exceed ' + maxMarks);
                return;
            }
            
            this.submit();
        });
    </script>
</body>
</html>