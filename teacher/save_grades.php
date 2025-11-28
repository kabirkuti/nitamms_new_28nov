<?php
require_once '../db.php';
checkRole(['teacher']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    $marks = isset($_POST['marks']) ? floatval($_POST['marks']) : null;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    if ($submission_id <= 0 || $assignment_id <= 0) {
        header("Location: assignment_submissions.php?id=$assignment_id&error=invalid_data");
        exit();
    }
    
    // Verify this submission belongs to an assignment created by this teacher
    $verify_query = "SELECT a.id, a.max_marks 
                     FROM assignments a
                     JOIN assignment_submissions sub ON a.id = sub.assignment_id
                     WHERE sub.id = ? AND a.teacher_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $submission_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: assignment_submissions.php?id=$assignment_id&error=unauthorized");
        exit();
    }
    
    $assignment_data = $result->fetch_assoc();
    $max_marks = $assignment_data['max_marks'];
    
    // Validate marks
    if ($marks < 0 || $marks > $max_marks) {
        header("Location: assignment_submissions.php?id=$assignment_id&error=invalid_marks");
        exit();
    }
    
    // Update the submission with marks and feedback
    $update_query = "UPDATE assignment_submissions 
                     SET marks_obtained = ?, feedback = ?, graded_at = NOW(), status = 'graded'
                     WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("dsi", $marks, $feedback, $submission_id);
    
    if ($stmt->execute()) {
        header("Location: assignment_submissions.php?id=$assignment_id&success=graded");
    } else {
        header("Location: assignment_submissions.php?id=$assignment_id&error=failed");
    }
    exit();
} else {
    header("Location: assignments.php");
    exit();
}
?>