<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get assignment
$assignment_query = "SELECT * FROM assignments WHERE id = ? AND teacher_id = ?";
$stmt = $conn->prepare($assignment_query);
$stmt->bind_param("ii", $assignment_id, $user['id']);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: assignments.php?error=not_found");
    exit();
}

// Get teacher's classes
$classes_query = "SELECT id, class_name, section FROM classes WHERE teacher_id = ? ORDER BY class_name";
$stmt = $conn->prepare($classes_query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$classes = $stmt->get_result();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $subject = sanitize($_POST['subject']);
    $class_id = intval($_POST['class_id']);
    $due_date = sanitize($_POST['due_date']);
    $max_marks = intval($_POST['max_marks']);
    
    $update_query = "UPDATE assignments SET 
                     title = ?, description = ?, subject = ?, class_id = ?, 
                     due_date = ?, max_marks = ?
                     WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssisiii", $title, $description, $subject, $class_id, $due_date, $max_marks, $assignment_id, $user['id']);
    
    if ($stmt->execute()) {
        header("Location: view_assignment.php?id=$assignment_id&success=updated");
        exit();
    } else {
        $error = "Failed to update assignment.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignment</title>
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
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select,
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
            font-size: 16px;
            width: 100%;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>✏️ Edit Assignment</h1>
        <a href="view_assignment.php?id=<?php echo $assignment_id; ?>" class="btn-secondary">← Cancel</a>
    </nav>

    <div class="main-content">
        <div class="form-container">
            <?php if (isset($error)): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="title">Assignment Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="class_id">Select Class *</label>
                    <select id="class_id" name="class_id" required>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>" 
                                    <?php echo $class['id'] == $assignment['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($assignment['subject']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description/Instructions</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="due_date">Due Date & Time *</label>
                        <input type="datetime-local" id="due_date" name="due_date" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($assignment['due_date'])); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="max_marks">Maximum Marks *</label>
                        <input type="number" id="max_marks" name="max_marks" 
                               value="<?php echo $assignment['max_marks']; ?>" min="1" max="1000" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary">✅ Update Assignment</button>
            </form>
        </div>
    </div>
</body>
</html>