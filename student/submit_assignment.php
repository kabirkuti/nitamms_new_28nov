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
        throw new Exception("Unable to determine student ID");
    }

    $assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($assignment_id <= 0) {
        throw new Exception("Invalid assignment ID");
    }

    // Get student's section
    $student_query = "SELECT s.*, c.section FROM students s 
                     JOIN classes c ON s.class_id = c.id 
                     WHERE s.id = ? AND s.is_active = 1";
    $stmt = $conn->prepare($student_query);
    if (!$stmt) throw new Exception("Database error");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        throw new Exception("Student not found");
    }
    
    $student_data = $student_result->fetch_assoc();
    $section = $student_data['section'];
    $stmt->close();

    // Get assignment details - verify it's for student's section
    $assignment_query = "SELECT a.*, u.full_name as teacher_name, c.section
                         FROM assignments a
                         JOIN users u ON a.teacher_id = u.id
                         JOIN classes c ON a.class_id = c.id
                         WHERE a.id = ? AND c.section = ? AND a.status = 'active'";

    $stmt = $conn->prepare($assignment_query);
    if (!$stmt) throw new Exception("Database error");
    $stmt->bind_param("is", $assignment_id, $section);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$assignment) {
        header("Location: assignments.php?error=not_found");
        exit();
    }

    // Check if already submitted
    $check_query = "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
    $stmt = $conn->prepare($check_query);
    if (!$stmt) throw new Exception("Database error");
    $stmt->bind_param("ii", $assignment_id, $student_id);
    $stmt->execute();
    $already_submitted = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($already_submitted) {
        header("Location: assignments.php?error=already_submitted");
        exit();
    }

    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submission_text = isset($_POST['submission_text']) ? trim($_POST['submission_text']) : '';
        
        if (empty($submission_text)) {
            $error = "Please write your answer/solution before submitting.";
        } else {
            $attachment_data = null;
            $attachment_name = null;
            $attachment_size = null;

            // Handle file upload - store in database as BLOB
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
                    $error = "Error uploading file: " . $_FILES['attachment']['error'];
                } else {
                    $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                    $file_size = $_FILES['attachment']['size'];
                    $max_size = 10 * 1024 * 1024; // 10MB
                    $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];

                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Invalid file type. Allowed: " . implode(", ", $allowed_extensions);
                    } elseif ($file_size > $max_size) {
                        $error = "File size exceeds 10MB limit.";
                    } else {
                        // Read file into memory
                        $file_content = @file_get_contents($_FILES['attachment']['tmp_name']);
                        if ($file_content === false) {
                            $error = "Failed to read uploaded file.";
                        } else {
                            $attachment_data = $file_content;
                            $attachment_name = $_FILES['attachment']['name'];
                            $attachment_size = $file_size;
                        }
                    }
                }
            }

            // Insert submission
            if (!$error) {
                $due_date = new DateTime($assignment['due_date']);
                $now = new DateTime();
                $status = ($now > $due_date) ? 'late' : 'submitted';

                $insert_query = "INSERT INTO assignment_submissions 
                                (assignment_id, student_id, submission_text, attachment_data, attachment_name, attachment_size, status, submitted_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("iisssis", $assignment_id, $student_id, $submission_text, $attachment_data, $attachment_name, $attachment_size, $status);
                    
                    if ($stmt->execute()) {
                        header("Location: assignments.php?success=submitted");
                        exit();
                    } else {
                        $error = "Failed to submit: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }

    $due_date = new DateTime($assignment['due_date']);
    $now = new DateTime();
    $is_late = $now > $due_date;

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Submit Assignment Error: " . $error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment</title>
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
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .main-content {
            max-width: 900px;
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
        
        .assignment-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .assignment-info p {
            margin: 10px 0;
            color: #555;
        }
        
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
        }
        
        .late-warning {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 180px;
            resize: vertical;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .file-upload {
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .file-upload input { display: none; }
        
        .file-upload p {
            color: #666;
            margin: 5px 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📤 Submit Assignment</h1>
        <a href="assignments.php" class="btn-secondary">← Back</a>
    </nav>

    <div class="main-content">
        <?php if (isset($error) && $error): ?>
            <div class="alert-error">
                <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_late): ?>
            <div class="warning-box late-warning">
                <strong>⚠️ Late Submission Warning!</strong>
                <p style="margin-top: 5px;">The due date has passed. Your submission will be marked as late.</p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-bottom: 20px;">📋 Assignment Details</h2>
            <div class="assignment-info">
                <p><strong>Title:</strong> <?php echo htmlspecialchars($assignment['title']); ?></p>
                <p><strong>Teacher:</strong> <?php echo htmlspecialchars($assignment['teacher_name']); ?></p>
                <p><strong>Due Date:</strong> <?php echo $due_date->format('d M Y, h:i A'); ?></p>
                <p><strong>Max Marks:</strong> <?php echo intval($assignment['max_marks']); ?></p>
                <p><strong>Section:</strong> <?php echo htmlspecialchars($assignment['section']); ?></p>
            </div>

            <?php if ($assignment['description']): ?>
            <div style="margin: 20px 0; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <strong style="color: #2c3e50;">📝 Instructions:</strong>
                <p style="margin-top: 10px; line-height: 1.8; color: #555;">
                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ($assignment['attachment_path']): ?>
            <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <strong>📎 Assignment File:</strong>
                <a href="../<?php echo htmlspecialchars($assignment['attachment_path']); ?>" 
                   target="_blank" 
                   style="color: #2196F3; margin-left: 10px;">
                    📥 Download
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">✏️ Your Submission</h2>
            <form method="POST" enctype="multipart/form-data" id="submitForm" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="submission_text">Write Your Answer/Solution <span style="color: #dc3545;">*</span></label>
                    <textarea id="submission_text" 
                              name="submission_text" 
                              placeholder="Type your answer, explanation, or solution here..."
                              required></textarea>
                </div>

                <div class="form-group">
                    <label>Attach File <span style="color: #999;">(Optional)</span></label>
                    <div class="file-upload" onclick="document.getElementById('file').click()">
                        <input type="file" 
                               id="file" 
                               name="attachment" 
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt,.jpg,.jpeg,.png"
                               onchange="updateFileName(this)">
                        <p id="file-name">📎 Click to upload file</p>
                        <div style="font-size: 12px; color: #999; margin-top: 10px;">
                            Accepted: PDF, DOC, DOCX, PPT, PPTX, ZIP, TXT, JPG, PNG (Max 10MB)
                        </div>
                    </div>
                    <div id="file-error" style="color: #dc3545; margin-top: 10px; display: none;"></div>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    ✅ Submit Assignment
                </button>
            </form>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileError = document.getElementById('file-error');
            fileError.style.display = 'none';
            
            if (input.files[0]) {
                const fileName = input.files[0].name;
                const fileSize = input.files[0].size;
                const maxSize = 10 * 1024 * 1024;
                
                if (fileSize > maxSize) {
                    fileError.textContent = 'File size exceeds 10MB limit.';
                    fileError.style.display = 'block';
                    input.value = '';
                    document.getElementById('file-name').textContent = '📎 Click to upload file';
                    return;
                }
                
                const sizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                document.getElementById('file-name').innerHTML = '✅ ' + fileName + ' <span style="font-size: 12px;">(' + sizeMB + ' MB)</span>';
            } else {
                document.getElementById('file-name').textContent = '📎 Click to upload file';
            }
        }

        function validateForm() {
            const submissionText = document.getElementById('submission_text').value.trim();
            
            if (!submissionText) {
                alert('Please write your answer/solution before submitting.');
                return false;
            }
            
            if (!confirm('Are you sure you want to submit? You cannot edit after submission.')) {
                return false;
            }
            
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = '⏳ Submitting...';
            return true;
        }
    </script>
</body>
</html>