<?php
// teacher/edit_paper_marks.php
session_start();
require_once '../db.php';
require_once '../includes/auth_helper.php';
require_once '../admin/paper_marks_handler.php';

// Check role
checkRole(['teacher', 'admin']);

$user = getCurrentUser();
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$examType = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'MST1';
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y') . '-' . (date('Y') + 1);

// Initialize marks manager
$marksManager = new PaperMarksManager($conn);

// Get student info
$studentQuery = "SELECT s.*, c.class_name, c.section FROM students s 
                 LEFT JOIN classes c ON s.class_id = c.id 
                 WHERE s.id = ? AND s.is_active = 1";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found");
}

// Get class subjects
$subjectsQuery = "SELECT * FROM subjects WHERE class_id = ? ORDER BY subject_name";
$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $classId);
$stmt->execute();
$subjects = $stmt->get_result();

// Get existing marks
$existingMarksQuery = "SELECT * FROM paper_marks 
                      WHERE student_id = ? AND exam_type = ? AND academic_year = ?";
$stmt = $conn->prepare($existingMarksQuery);
$stmt->bind_param("iss", $studentId, $examType, $academicYear);
$stmt->execute();
$existingMarks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$marksArray = [];
foreach ($existingMarks as $mark) {
    $marksArray[$mark['subject_id']] = $mark;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = (int)$_POST['subject_id'];
    $obtainedMarks = (float)$_POST['obtained_marks'];
    $totalMarks = (float)$_POST['total_marks'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    
    // Get semester from student's class
    $semesterQuery = "SELECT semester FROM classes WHERE id = ?";
    $stmt = $conn->prepare($semesterQuery);
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $classInfo = $stmt->get_result()->fetch_assoc();
    $semester = $classInfo['semester'] ?? 1;
    
    $result = $marksManager->addMarks(
        $studentId, $classId, $subjectId, $examType,
        $obtainedMarks, $totalMarks, $user['id'], $academicYear, $semester, $remarks
    );
    
    if ($result) {
        $_SESSION['success'] = "Marks saved successfully!";
        header("Location: ?class_id=$classId&student_id=$studentId&exam_type=$examType");
        exit;
    } else {
        $_SESSION['error'] = "Failed to save marks. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student Marks</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .edit-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .edit-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .edit-header h1 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .info-item {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 15px;
            border-radius: 10px;
            border-left: 3px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 700;
            margin-top: 5px;
        }

        .marks-grid {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .marks-grid h2 {
            color: #2c3e50;
            margin-top: 0;
        }

        .marks-form {
            display: grid;
            gap: 25px;
        }

        .subject-form {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(250, 250, 250, 0.9));
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }

        .subject-form:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-3px);
        }

        .subject-form h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .marks-display {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
        }

        .mark-stat {
            text-align: center;
        }

        .mark-stat .label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        .mark-stat .value {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin-top: 5px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.95);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="edit-container">
    <a href="../admin/manage_paper_marks.php?class_id=<?php echo $classId; ?>&exam_type=<?php echo $examType; ?>" class="btn btn-back">← Back</a>

    <!-- Header -->
    <div class="edit-header">
        <h1>✏️ Edit Student Paper Marks</h1>
        
        <!-- Student Info -->
        <div class="student-info">
            <div class="info-item">
                <div class="info-label">Student Name</div>
                <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Roll Number</div>
                <div class="info-value"><?php echo htmlspecialchars($student['roll_number']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Class/Section</div>
                <div class="info-value"><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Exam Type</div>
                <div class="info-value"><?php echo htmlspecialchars($examType); ?></div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            ✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            ✗ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Edit Marks -->
    <div class="marks-grid">
        <h2>📝 Enter Marks for Each Subject</h2>
        
        <div class="marks-form">
            <?php 
            $subjects->data_seek(0);
            while ($subject = $subjects->fetch_assoc()): 
                $mark = $marksArray[$subject['id']] ?? null;
                $percentage = $mark ? round(($mark['obtained_marks'] / $mark['total_marks']) * 100, 2) : 0;
            ?>
            <div class="subject-form">
                <h3>📚 <?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                
                <form method="POST" class="form-row">
                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                    
                    <div class="form-group">
                        <label>Obtained Marks *</label>
                        <input type="number" name="obtained_marks" step="0.01" min="0" max="100" 
                               value="<?php echo $mark ? $mark['obtained_marks'] : ''; ?>" 
                               placeholder="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Total Marks *</label>
                        <input type="number" name="total_marks" step="0.01" min="1" max="100"
                               value="<?php echo $mark ? $mark['total_marks'] : '100'; ?>" 
                               placeholder="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks</label>
                        <input type="text" name="remarks" 
                               value="<?php echo $mark ? htmlspecialchars($mark['remarks']) : ''; ?>" 
                               placeholder="Optional remarks">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-save">💾 Save Marks</button>
                    </div>
                </form>
                
                <?php if ($mark): ?>
                <div class="marks-display">
                    <div class="mark-stat">
                        <div class="label">Obtained</div>
                        <div class="value"><?php echo round($mark['obtained_marks'], 2); ?></div>
                    </div>
                    <div class="mark-stat">
                        <div class="label">Total</div>
                        <div class="value"><?php echo round($mark['total_marks'], 2); ?></div>
                    </div>
                    <div class="mark-stat">
                        <div class="label">Percentage</div>
                        <div class="value"><?php echo round($percentage, 2); ?>%</div>
                    </div>
                    <div class="mark-stat">
                        <div class="label">Grade</div>
                        <div class="value"><?php echo htmlspecialchars($mark['grade']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>