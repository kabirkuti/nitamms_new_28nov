<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get parent's children
$parent_query = $conn->query("SELECT p.*, s.id as student_id, s.full_name, s.roll_number, s.year, s.semester, 
                              d.name as department_name, c.name as class_name
                              FROM parents p
                              JOIN students s ON p.student_id = s.id
                              JOIN departments d ON s.department_id = d.id
                              JOIN classes c ON s.class_id = c.id
                              WHERE p.email = (SELECT email FROM users WHERE id = $user_id)");

$children = [];
while ($child = $parent_query->fetch_assoc()) {
    $children[] = $child;
}

if (empty($children)) {
    die("No student linked to this parent account.");
}

// Select child (default to first child)
$selected_child_id = $_GET['child_id'] ?? $children[0]['student_id'];
$selected_child = null;
foreach ($children as $child) {
    if ($child['student_id'] == $selected_child_id) {
        $selected_child = $child;
        break;
    }
}

// Get filter values
$filter_academic_year = $_GET['academic_year'] ?? '';
$filter_exam_type = $_GET['exam_type'] ?? '';
$filter_semester = $_GET['semester'] ?? '';

// Build query - only show published marks for selected child
$query = "SELECT * FROM v_student_paper_marks WHERE student_id = ? AND is_published = 1";
$params = [$selected_child_id];
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

// Calculate statistics
$stats_query = "SELECT 
    COUNT(*) as total_exams,
    AVG(CASE WHEN marks_obtained IS NOT NULL THEN percentage END) as avg_percentage,
    MAX(percentage) as highest_percentage,
    MIN(percentage) as lowest_percentage,
    SUM(CASE WHEN percentage >= 75 THEN 1 ELSE 0 END) as excellent_count,
    SUM(CASE WHEN percentage >= 60 AND percentage < 75 THEN 1 ELSE 0 END) as good_count,
    SUM(CASE WHEN percentage >= 40 AND percentage < 60 THEN 1 ELSE 0 END) as average_count,
    SUM(CASE WHEN percentage < 40 THEN 1 ELSE 0 END) as poor_count
FROM v_student_paper_marks 
WHERE student_id = $selected_child_id AND is_published = 1 AND marks_obtained IS NOT NULL";

$stats = $conn->query($stats_query)->fetch_assoc();

// Get subject-wise performance
$subject_performance = $conn->query("SELECT 
    subject_name,
    exam_type,
    AVG(percentage) as avg_percentage
FROM v_student_paper_marks 
WHERE student_id = $selected_child_id AND is_published = 1 AND marks_obtained IS NOT NULL
GROUP BY subject_name, exam_type
ORDER BY subject_name, exam_type");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child's Paper Marks - Parent</title>
    <link rel="stylesheet" href="../includes/paper_marks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="marks-container">
        <!-- Header -->
        <div class="marks-header">
            <h1><i class="fas fa-user-friends"></i> Child's Paper Marks</h1>
            <p>Parent Dashboard - Monitor your child's academic performance</p>
        </div>

        <!-- Child Selector -->
        <?php if (count($children) > 1): ?>
            <div class="marks-card" style="margin-bottom: 25px;">
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="child_id">Select Child</label>
                        <select name="child_id" id="child_id" onchange="this.form.submit()" 
                                style="max-width: 400px;">
                            <?php foreach ($children as $child): ?>
                                <option value="<?php echo $child['student_id']; ?>" 
                                        <?php echo $selected_child_id == $child['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($child['full_name'] . ' - ' . $child['roll_number'] . 
                                              ' (Year ' . $child['year'] . ', Sem ' . $child['semester'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Student Info -->
        <div class="marks-card">
            <h2 class="card-title">Student Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($selected_child['full_name']); ?>
                </div>
                <div>
                    <strong>Roll No:</strong> <?php echo htmlspecialchars($selected_child['roll_number']); ?>
                </div>
                <div>
                    <strong>Department:</strong> <?php echo htmlspecialchars($selected_child['department_name']); ?>
                </div>
                <div>
                    <strong>Year:</strong> <?php echo $selected_child['year']; ?>
                </div>
                <div>
                    <strong>Semester:</strong> <?php echo $selected_child['semester']; ?>
                </div>
                <div>
                    <strong>Class:</strong> <?php echo htmlspecialchars($selected_child['class_name']); ?>
                </div>
            </div>
        </div>

        <!-- Performance Statistics -->
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

        <!-- Performance Distribution -->
        <div class="marks-card">
            <h2 class="card-title">Performance Distribution</h2>
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: #10b981;">
                    <div class="stat-value"><?php echo $stats['excellent_count']; ?></div>
                    <div class="stat-label">Excellent (75%+)</div>
                </div>
                <div class="stat-card" style="border-left-color: #3b82f6;">
                    <div class="stat-value"><?php echo $stats['good_count']; ?></div>
                    <div class="stat-label">Good (60-74%)</div>
                </div>
                <div class="stat-card" style="border-left-color: #f59e0b;">
                    <div class="stat-value"><?php echo $stats['average_count']; ?></div>
                    <div class="stat-label">Average (40-59%)</div>
                </div>
                <div class="stat-card" style="border-left-color: #ef4444;">
                    <div class="stat-value"><?php echo $stats['poor_count']; ?></div>
                    <div class="stat-label">Need Improvement (<40%)</div>
                </div>
            </div>
        </div>

        <!-- Subject-wise Performance -->
        <?php if ($subject_performance->num_rows > 0): ?>
            <div class="marks-card">
                <h2 class="card-title">Subject-wise Performance</h2>
                <div class="table-responsive">
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Exam Type</th>
                                <th>Average Percentage</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($subj = $subject_performance->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subj['subject_name']); ?></td>
                                    <td>
                                        <span class="exam-badge <?php echo strtolower($subj['exam_type']); ?>">
                                            <?php echo $subj['exam_type']; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format($subj['avg_percentage'], 2); ?>%</strong></td>
                                    <td>
                                        <?php
                                        $perf_class = 'poor';
                                        if ($subj['avg_percentage'] >= 75) $perf_class = 'excellent';
                                        elseif ($subj['avg_percentage'] >= 60) $perf_class = 'good';
                                        elseif ($subj['avg_percentage'] >= 40) $perf_class = 'average';
                                        ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $subj['avg_percentage']; ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <input type="hidden" name="child_id" value="<?php echo $selected_child_id; ?>">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Academic Year</label>
                        <select name="academic_year">
                            <option value="">All Years</option>
                            <?php 
                            $academic_years->data_seek(0);
                            while ($year = $academic_years->fetch_assoc()): 
                            ?>
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
                    <a href="view_child_paper_marks.php?child_id=<?php echo $selected_child_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Detailed Marks Table -->
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
                                <th>Marks</th>
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
                                    <td>
                                        <strong><?php echo number_format($mark['marks_obtained'], 2); ?></strong> / <?php echo $mark['max_marks']; ?>
                                    </td>
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
                    <i class="fas fa-info-circle"></i> No marks have been published yet for the selected filters.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>