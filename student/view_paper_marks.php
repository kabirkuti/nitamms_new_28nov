<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student ID from users table
$user_id = $_SESSION['user_id'];
$student_query = $conn->query("SELECT * FROM students WHERE id = (SELECT id FROM students WHERE email = (SELECT email FROM users WHERE id = $user_id))");
$student_data = $student_query->fetch_assoc();
$student_id = $student_data['id'];

// Get filter values
$filter_academic_year = $_GET['academic_year'] ?? '';
$filter_exam_type = $_GET['exam_type'] ?? '';
$filter_semester = $_GET['semester'] ?? '';

// Build query - only show published marks for this student
$query = "SELECT * FROM v_student_paper_marks WHERE student_id = ? AND is_published = 1";
$params = [$student_id];
$types = "i";

if ($filter_academic_year) {
    $query .= " AND academic_year = ?";
    $params[] = $filter_academic_year;
    $types .= "s";
}
if ($filter_exam_type) {
    $query .= " AND exam_type = ?";
    $params[] = $filter_exam_type;
    $types .= "s";
}
if ($filter_semester) {
    $query .= " AND semester = ?";
    $params[] = $filter_semester;
    $types .= "i";
}

$query .= " ORDER BY academic_year DESC, semester DESC, exam_type";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$marks_result = $stmt->get_result();

// Get filter options
$academic_years = $conn->query("SELECT DISTINCT year_name FROM academic_years ORDER BY year_name DESC");

// Calculate statistics for student
$stats_query = "SELECT 
    COUNT(*) as total_exams,
    AVG(CASE WHEN marks_obtained IS NOT NULL THEN percentage END) as avg_percentage,
    MAX(percentage) as highest_percentage,
    MIN(percentage) as lowest_percentage
FROM v_student_paper_marks 
WHERE student_id = $student_id AND is_published = 1 AND marks_obtained IS NOT NULL";

$stats = $conn->query($stats_query)->fetch_assoc();

// Calculate exam-wise averages
$exam_stats_query = "SELECT 
    exam_type,
    AVG(percentage) as avg_percentage,
    COUNT(*) as exam_count
FROM v_student_paper_marks 
WHERE student_id = $student_id AND is_published = 1 AND marks_obtained IS NOT NULL
GROUP BY exam_type";

$exam_stats_result = $conn->query($exam_stats_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Paper Marks - Student</title>
    <link rel="stylesheet" href="../includes/paper_marks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="marks-container">
        <!-- Header -->
        <div class="marks-header">
            <h1><i class="fas fa-graduation-cap"></i> My Paper Marks</h1>
            <p>Student Dashboard - View your exam performance</p>
            <div style="margin-top: 10px;">
                <strong>Name:</strong> <?php echo htmlspecialchars($student_data['full_name']); ?> | 
                <strong>Roll No:</strong> <?php echo htmlspecialchars($student_data['roll_number']); ?> |
                <strong>Year:</strong> <?php echo $student_data['year']; ?> | 
                <strong>Semester:</strong> <?php echo $student_data['semester']; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_exams']); ?></div>
                <div class="stat-label">Total Exams</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo number_format($stats['avg_percentage'], 2); ?>%</div>
                <div class="stat-label">Overall Average</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['highest_percentage'], 2); ?>%</div>
                <div class="stat-label">Highest Score</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?php echo number_format($stats['lowest_percentage'], 2); ?>%</div>
                <div class="stat-label">Lowest Score</div>
            </div>
        </div>

        <!-- Exam-wise Performance -->
        <div class="marks-card">
            <h2 class="card-title">Exam-wise Performance</h2>
            <div class="stats-grid">
                <?php while ($exam_stat = $exam_stats_result->fetch_assoc()): ?>
                    <div class="stat-card">
                        <span class="exam-badge <?php echo strtolower($exam_stat['exam_type']); ?>" 
                              style="margin-bottom: 10px; display: inline-block;">
                            <?php echo $exam_stat['exam_type']; ?>
                        </span>
                        <div class="stat-value"><?php echo number_format($exam_stat['avg_percentage'], 2); ?>%</div>
                        <div class="stat-label"><?php echo $exam_stat['exam_count']; ?> Subject(s)</div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Academic Year</label>
                        <select name="academic_year">
                            <option value="">All Years</option>
                            <?php while ($year = $academic_years->fetch_assoc()): ?>
                                <option value="<?php echo $year['year_name']; ?>" 
                                    <?php echo $filter_academic_year == $year['year_name'] ? 'selected' : ''; ?>>
                                    <?php echo $year['year_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Semester</label>
                        <select name="semester">
                            <option value="">All Semesters</option>
                            <option value="1" <?php echo $filter_semester == '1' ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="2" <?php echo $filter_semester == '2' ? 'selected' : ''; ?>>Semester 2</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Exam Type</label>
                        <select name="exam_type">
                            <option value="">All Exams</option>
                            <option value="MST1" <?php echo $filter_exam_type == 'MST1' ? 'selected' : ''; ?>>MST1</option>
                            <option value="MST2" <?php echo $filter_exam_type == 'MST2' ? 'selected' : ''; ?>>MST2</option>
                            <option value="PREBOARD" <?php echo $filter_exam_type == 'PREBOARD' ? 'selected' : ''; ?>>PREBOARD</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="view_paper_marks.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Marks Table -->
        <div class="marks-card">
            <h2 class="card-title">Detailed Marks</h2>
            
            <?php if ($marks_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th>Academic Year</th>
                                <th>Sem</th>
                                <th>Subject</th>
                                <th>Exam Type</th>
                                <th>Marks Obtained</th>
                                <th>Maximum Marks</th>
                                <th>Percentage</th>
                                <th>Performance</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($mark = $marks_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mark['academic_year']); ?></td>
                                    <td>Sem <?php echo $mark['semester']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mark['subject_code']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($mark['subject_name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="exam-badge <?php echo strtolower($mark['exam_type']); ?>">
                                            <?php echo $mark['exam_type']; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format($mark['marks_obtained'], 2); ?></strong></td>
                                    <td><?php echo $mark['max_marks']; ?></td>
                                    <td><strong><?php echo number_format($mark['percentage'], 2); ?>%</strong></td>
                                    <td>
                                        <?php
                                        $perf_class = 'poor';
                                        $perf_text = 'Need Improvement';
                                        if ($mark['percentage'] >= 75) {
                                            $perf_class = 'excellent';
                                            $perf_text = 'Excellent';
                                        } elseif ($mark['percentage'] >= 60) {
                                            $perf_class = 'good';
                                            $perf_text = 'Good';
                                        } elseif ($mark['percentage'] >= 40) {
                                            $perf_class = 'average';
                                            $perf_text = 'Average';
                                        }
                                        ?>
                                        <span class="performance-indicator <?php echo $perf_class; ?>">
                                            <?php echo $perf_text; ?>
                                        </span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $mark['percentage']; ?>%;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $mark['remarks'] ? htmlspecialchars($mark['remarks']) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No marks have been published yet. Check back later!
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>