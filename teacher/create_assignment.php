<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get selected academic year (default to current year if not set)
$selected_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '2025-2026';

// Check if class is pre-selected from dashboard
$preselected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$preselected_section = isset($_GET['section']) ? $_GET['section'] : '';

// Get teacher's classes filtered by academic year with CORRECT student counts (year-wise)
$classes_query = "SELECT c.id, c.class_name, c.section, c.year, c.semester, c.academic_year,
                  (SELECT COUNT(DISTINCT s2.id) 
                   FROM students s2 
                   JOIN classes c2 ON s2.class_id = c2.id 
                   WHERE c2.section = c.section 
                   AND c2.academic_year = c.academic_year 
                   AND s2.is_active = 1) as student_count
                  FROM classes c
                  WHERE c.teacher_id = ? AND c.academic_year = ?
                  GROUP BY c.id, c.class_name, c.section, c.year, c.semester
                  ORDER BY c.section, c.year, c.semester";
$stmt = $conn->prepare($classes_query);
$stmt->bind_param("is", $teacher_id, $selected_academic_year);
$stmt->execute();
$classes = $stmt->get_result();

// Get available academic years for this teacher
$years_query = "SELECT DISTINCT academic_year FROM classes WHERE teacher_id = ? ORDER BY academic_year DESC";
$years_stmt = $conn->prepare($years_query);
$years_stmt->bind_param("i", $teacher_id);
$years_stmt->execute();
$academic_years = $years_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $subject = sanitize($_POST['subject']);
    $class_id = intval($_POST['class_id']);
    $due_date = sanitize($_POST['due_date']);
    $max_marks = intval($_POST['max_marks']);
    
    // Handle file upload
    $attachment_path = null;
    $attachment_name = null;
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/assignments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $unique_name = uniqid() . '_' . basename($_FILES['attachment']['name']);
            $target_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
                $attachment_path = 'uploads/assignments/' . $unique_name;
                $attachment_name = $_FILES['attachment']['name'];
            }
        }
    }
    
    // Insert assignment
    $insert_query = "INSERT INTO assignments (teacher_id, class_id, title, description, subject, due_date, max_marks, attachment_path, attachment_name, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iissssiss", $teacher_id, $class_id, $title, $description, $subject, $due_date, $max_marks, $attachment_path, $attachment_name);
    
    if ($stmt->execute()) {
        // Get the section and academic year for success message
        $section_query = "SELECT section, academic_year FROM classes WHERE id = ?";
        $section_stmt = $conn->prepare($section_query);
        $section_stmt->bind_param("i", $class_id);
        $section_stmt->execute();
        $section_result = $section_stmt->get_result()->fetch_assoc();
        $section_name = $section_result['section'];
        $academic_year = $section_result['academic_year'];
        
        // Count students who will see this assignment (year-wise)
        $count_query = "SELECT COUNT(DISTINCT s.id) as total 
                       FROM students s 
                       JOIN classes c ON s.class_id = c.id 
                       WHERE c.section = ? 
                       AND c.academic_year = ? 
                       AND s.is_active = 1";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("ss", $section_name, $academic_year);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        
        header("Location: assignments.php?success=created&students=" . $count_result['total'] . "&section=" . urlencode($section_name));
        exit();
    } else {
        $error = "Failed to create assignment. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - NIT AMMS</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="css/create_assignment_style.css">

    <style>
        * { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

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
    display: flex;
    align-items: center;
    gap: 12px;
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
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.form-container {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
}

.form-header h2 {
    font-size: 32px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
}

.form-header p {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.selected-year-badge {
    display: inline-block;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    padding: 10px 20px;
    border-radius: 25px;
    border: 2px solid rgba(102, 126, 234, 0.3);
    font-size: 14px;
    color: #667eea;
    margin-top: 10px;
}

.selected-year-badge strong {
    color: #764ba2;
    font-weight: 700;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #2c3e50;
    font-size: 14px;
}

.form-group label .required {
    color: #dc3545;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group select {
    cursor: pointer;
}

.form-group select option {
    padding: 10px;
}

.class-info-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-radius: 20px;
    font-size: 13px;
    color: #667eea;
    font-weight: 600;
}

.class-info-display {
    margin-top: 12px;
    padding: 12px;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
    border-radius: 10px;
    border-left: 4px solid #28a745;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.student-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
}

.student-count-badge::before {
    content: '👥';
    font-size: 16px;
}

.file-upload {
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.file-upload:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.file-upload input[type="file"] {
    display: none;
}

.file-upload-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.file-upload-text {
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.file-upload-hint {
    font-size: 12px;
    color: #999;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 14px 30px;
    font-size: 16px;
    width: 100%;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.info-box {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    border: 2px solid rgba(102, 126, 234, 0.2);
}

.info-box h3 {
    color: #667eea;
    margin-bottom: 12px;
    font-size: 16px;
}

.info-box ul {
    list-style-position: inside;
    color: #2c3e50;
    line-height: 1.8;
}

.info-box li {
    font-size: 14px;
}

.info-box strong {
    color: #764ba2;
    font-weight: 700;
}

@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 15px 20px;
    }
    
    .navbar h1 {
        font-size: 18px;
    }
    
    .navbar-actions {
        width: 100%;
        justify-content: center;
        flex-direction: column;
    }
    
    .year-selector {
        width: 100%;
        justify-content: center;
    }
    
    .main-content {
        padding: 20px 15px;
    }
    
    .form-container {
        padding: 25px 20px;
    }
    
    .grid-2 {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
    </style>

    <script>
        // Change academic year and reload page
function changeAcademicYear(year) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('academic_year', year);
    
    // Preserve class_id and section if they exist
    const classId = urlParams.get('class_id');
    const section = urlParams.get('section');
    
    let newUrl = window.location.pathname + '?' + urlParams.toString();
    window.location.href = newUrl;
}

// Update file name display when file is selected
function updateFileName(input) {
    const fileNameElement = document.getElementById('file-name');
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / (1024 * 1024)).toFixed(2);
        fileNameElement.innerHTML = `<strong>${fileName}</strong><br><span style="font-size: 12px; color: #666;">(${fileSize} MB)</span>`;
        
        // Check file size (max 10MB)
        if (input.files[0].size > 10 * 1024 * 1024) {
            alert('⚠️ File size exceeds 10MB limit. Please choose a smaller file.');
            input.value = '';
            fileNameElement.textContent = 'Click to upload attachment';
        }
    } else {
        fileNameElement.textContent = 'Click to upload attachment';
    }
}

// Display selected class information
function displayClassInfo() {
    const classSelect = document.getElementById('class_id');
    const selectedOption = classSelect.options[classSelect.selectedIndex];
    const infoDisplay = document.getElementById('classInfoDisplay');
    const badge = document.getElementById('studentCountBadge');
    
    if (selectedOption.value) {
        const studentCount = selectedOption.getAttribute('data-students');
        const section = selectedOption.getAttribute('data-section');
        
        badge.textContent = `${studentCount} students in ${section} will see this assignment`;
        infoDisplay.style.display = 'block';
    } else {
        infoDisplay.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to current date/time
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const dueDateInput = document.getElementById('due_date');
    dueDateInput.min = now.toISOString().slice(0, 16);
    
    // Set default due date to 7 days from now
    const nextWeek = new Date();
    nextWeek.setDate(nextWeek.getDate() + 7);
    nextWeek.setMinutes(nextWeek.getMinutes() - nextWeek.getTimezoneOffset());
    dueDateInput.value = nextWeek.toISOString().slice(0, 16);
    
    // Add event listener for class selection
    const classSelect = document.getElementById('class_id');
    if (classSelect) {
        classSelect.addEventListener('change', displayClassInfo);
        
        // Display info if pre-selected
        if (classSelect.value) {
            displayClassInfo();
        }
    }
    
    // Form validation before submit
    const form = document.getElementById('assignmentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const classId = document.getElementById('class_id').value;
            const title = document.getElementById('title').value.trim();
            const dueDate = document.getElementById('due_date').value;
            const maxMarks = parseInt(document.getElementById('max_marks').value);
            
            // Validate class selection
            if (!classId) {
                e.preventDefault();
                alert('⚠️ Please select a class/section');
                document.getElementById('class_id').focus();
                return false;
            }
            
            // Validate title
            if (!title || title.length < 3) {
                e.preventDefault();
                alert('⚠️ Please enter a valid assignment title (minimum 3 characters)');
                document.getElementById('title').focus();
                return false;
            }
            
            // Validate due date
            if (!dueDate) {
                e.preventDefault();
                alert('⚠️ Please select a due date');
                document.getElementById('due_date').focus();
                return false;
            }
            
            // Check if due date is in the past
            const selectedDate = new Date(dueDate);
            const currentDate = new Date();
            if (selectedDate <= currentDate) {
                e.preventDefault();
                alert('⚠️ Due date must be in the future');
                document.getElementById('due_date').focus();
                return false;
            }
            
            // Validate max marks
            if (!maxMarks || maxMarks < 1 || maxMarks > 1000) {
                e.preventDefault();
                alert('⚠️ Please enter valid maximum marks (1-1000)');
                document.getElementById('max_marks').focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Creating Assignment...';
            
            return true;
        });
    }
});

// Prevent accidental form abandonment
let formModified = false;
document.addEventListener('DOMContentLoaded', function() {
    const formInputs = document.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            formModified = true;
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formModified) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Remove warning when form is submitted
    const form = document.getElementById('assignmentForm');
    if (form) {
        form.addEventListener('submit', function() {
            formModified = false;
        });
    }
});
    </script>
</head>
<body>
    <nav class="navbar">
        <h1>
            <span>➕</span>
            Create New Assignment
        </h1>
        <div class="navbar-actions">
            <div class="year-selector">
                <label for="academic_year_select">📅 Academic Year:</label>
                <select id="academic_year_select" onchange="changeAcademicYear(this.value)">
                    <?php 
                    $academic_years->data_seek(0); // Reset pointer
                    while ($year = $academic_years->fetch_assoc()): 
                    ?>
                        <option value="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                <?php echo ($year['academic_year'] == $selected_academic_year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year['academic_year']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h2>Create Assignment</h2>
                <p>Assignment will be visible to all students in the selected section</p>
                <div class="selected-year-badge">
                    Academic Year: <strong><?php echo htmlspecialchars($selected_academic_year); ?></strong>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span>❌</span>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>📢 Important Information</h3>
                <ul>
                    <li>✅ Assignment will be visible to ALL students in the selected section</li>
                    <li>📊 Student count shows total active students in that section</li>
                    <li>📅 Students can view and submit assignments from their dashboard</li>
                    <li>🔔 Students will be notified about new assignments</li>
                    <li>🎓 Only classes from <strong><?php echo htmlspecialchars($selected_academic_year); ?></strong> are shown</li>
                </ul>
            </div>

            <?php if ($classes->num_rows == 0): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span>No classes found for academic year <strong><?php echo htmlspecialchars($selected_academic_year); ?></strong>. Please select a different year or contact admin.</span>
                </div>
            <?php else: ?>

            <form method="POST" enctype="multipart/form-data" id="assignmentForm">
                <div class="form-group">
                    <label for="title">
                        Assignment Title <span class="required">*</span>
                    </label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Chapter 5 - Data Structures Assignment">
                </div>

                <div class="form-group">
                    <label for="class_id">
                        Select Class/Section <span class="required">*</span>
                    </label>
                    <select id="class_id" name="class_id" required>
                        <option value="">-- Select Class/Section --</option>
                        <?php 
                        $classes->data_seek(0); // Reset pointer
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['id']; ?>" 
                                    data-students="<?php echo $class['student_count']; ?>"
                                    data-section="<?php echo htmlspecialchars($class['section']); ?>"
                                <?php echo ($class['id'] == $preselected_class_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['section']); ?> - 
                                <?php echo htmlspecialchars($class['class_name']); ?> 
                                (Year <?php echo $class['year']; ?>, Sem <?php echo $class['semester']; ?>) - 
                                <strong><?php echo $class['student_count']; ?> students</strong>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div id="classInfoDisplay" class="class-info-display" style="display: none;">
                        <span class="student-count-badge" id="studentCountBadge"></span>
                    </div>
                    <?php if ($preselected_section): ?>
                        <div class="class-info-badge">
                            ✨ Creating assignment for <strong><?php echo htmlspecialchars($preselected_section); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" 
                           placeholder="e.g., Computer Science, Mathematics, Physics">
                </div>

                <div class="form-group">
                    <label for="description">Description/Instructions</label>
                    <textarea id="description" name="description" 
                              placeholder="Provide detailed instructions for the assignment...&#10;&#10;• What students need to do&#10;• Submission format&#10;• Evaluation criteria&#10;• Additional notes"></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="due_date">
                            Due Date & Time <span class="required">*</span>
                        </label>
                        <input type="datetime-local" id="due_date" name="due_date" required>
                    </div>

                    <div class="form-group">
                        <label for="max_marks">
                            Maximum Marks <span class="required">*</span>
                        </label>
                        <input type="number" id="max_marks" name="max_marks" 
                               min="1" max="1000" required 
                               placeholder="e.g., 100" value="100">
                    </div>
                </div>

                <div class="form-group">
                    <label>Attachment (Optional)</label>
                    <div class="file-upload" onclick="document.getElementById('file').click()">
                        <input type="file" id="file" name="attachment" 
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt,.jpg,.jpeg,.png"
                               onchange="updateFileName(this)">
                        <div class="file-upload-icon">📎</div>
                        <p class="file-upload-text" id="file-name">Click to upload attachment</p>
                        <p class="file-upload-hint">Supported: PDF, DOC, PPT, ZIP, TXT, Images (Max: 10MB)</p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    ✅ Create Assignment & Notify Students
                </button>
            </form>

            <?php endif; ?>
        </div>
    </div>

    <script src="js/create_assignment_script.js"></script>
</body>
</html>