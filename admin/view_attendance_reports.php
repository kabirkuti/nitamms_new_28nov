<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_department = isset($_GET['department']) ? intval($_GET['department']) : '';
$filter_class = isset($_GET['class']) ? intval($_GET['class']) : '';

$where_clauses = ["sa.attendance_date = '$filter_date'"];
if ($filter_department) $where_clauses[] = "d.id = $filter_department";
if ($filter_class) $where_clauses[] = "c.id = $filter_class";
$where_sql = implode(' AND ', $where_clauses);

$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name,
                     c.class_name, d.dept_name, u.full_name as teacher_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     JOIN classes c ON sa.class_id = c.id
                     JOIN departments d ON c.department_id = d.id
                     JOIN users u ON sa.marked_by = u.id
                     WHERE $where_sql ORDER BY c.class_name, s.roll_number";
$attendance_records = $conn->query($attendance_query);

$section_stats_query = "SELECT c.id as class_id, c.class_name, d.dept_name,
                COUNT(DISTINCT sa.student_id) as total_students,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance sa
                JOIN classes c ON sa.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                WHERE sa.attendance_date = '$filter_date'
                " . ($filter_department ? "AND d.id = $filter_department" : "") . "
                " . ($filter_class ? "AND c.id = $filter_class" : "") . "
                GROUP BY c.id, c.class_name, d.dept_name ORDER BY d.dept_name, c.class_name";
$section_stats = $conn->query($section_stats_query);

$stats_query = "SELECT COUNT(DISTINCT sa.student_id) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance sa
                JOIN classes c ON sa.class_id = c.id
                JOIN departments d ON c.department_id = d.id WHERE $where_sql";
$stats = $conn->query($stats_query)->fetch_assoc();

$unique_students = $stats['total'];
$attendance_percentage = $unique_students > 0 ? round(($stats['present'] / $unique_students) * 100, 1) : 0;

$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");
$classes = $conn->query("SELECT c.*, d.dept_name FROM classes c LEFT JOIN departments d ON c.department_id = d.id ORDER BY c.class_name");
$is_today = ($filter_date === date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
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
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 25px;
            color: white;
        }

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(193, 125, 125, 0.03) 0%, transparent 70%);
        }

        .stat-card.present { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 10px 40px rgba(17, 153, 142, 0.3); }
        .stat-card.absent { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); box-shadow: 0 10px 40px rgba(235, 51, 73, 0.3); }
        .stat-card.late { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); box-shadow: 0 10px 40px rgba(247, 151, 30, 0.3); }
        .stat-card h3 { font-size: 14px; margin: 0 0 10px; opacity: 0.9; font-weight: 500; }
        .stat-value { font-size: 42px; font-weight: 700; margin: 5px 0; }
        .stat-label { font-size: 13px; opacity: 0.8;  }

        .today-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(255, 65, 108, 0.4);
            animation: pulse 2s infinite;
        }
        .today-badge::before {
            content: '';
            width: 10px;
            height: 10px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.03); } }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        .section-container { margin-bottom: 40px; }
        .section-header-title {
            font-size: 26px;
            font-weight: 700;
            color: white;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
        }
        .section-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.25), 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            padding: 25px 25px 20px;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .section-card.high .card-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .section-card.medium .card-header { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
        .section-card.low .card-header { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: inherit;
            clip-path: ellipse(60% 100% at 50% 0%);
        }
        .class-info { position: relative; z-index: 1; }
        .class-name {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .class-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .dept-name {
            font-size: 13px;
            opacity: 0.9;
            margin-left: 50px;
        }

        .percentage-circle {
            position: absolute;
            top: 15px;
            right: 20px;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255,255,255,0.3);
        }
        .percentage-value {
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
        }
        .percentage-label {
            font-size: 9px;
            text-transform: uppercase;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }

        .card-body { padding: 30px 25px 20px; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px 8px;
            border-radius: 16px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }
        .stat-item:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .stat-item.total { background: linear-gradient(135deg, #e0e5ec, #f8fafc); }
        .stat-item.present { background: linear-gradient(135deg, #d1fae5, #ecfdf5); }
        .stat-item.absent { background: linear-gradient(135deg, #fee2e2, #fef2f2); }
        .stat-item.late { background: linear-gradient(135deg, #fef3c7, #fffbeb); }
        .stat-num {
            font-size: 26px;
            font-weight: 700;
            display: block;
            line-height: 1.2;
        }
        .stat-item.total .stat-num { color: #64748b; }
        .stat-item.present .stat-num { color: #059669; }
        .stat-item.absent .stat-num { color: #dc2626; }
        .stat-item.late .stat-num { color: #d97706; }
        .stat-text {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-top: 4px;
        }

        .progress-container { margin-bottom: 20px; }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
            color: #64748b;
        }
        .progress-bar {
            height: 10px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            position: relative;
            transition: width 1s ease;
        }
        .section-card.high .progress-fill { background: linear-gradient(90deg, #11998e, #38ef7d); }
        .section-card.medium .progress-fill { background: linear-gradient(90deg, #f7971e, #ffd200); }
        .section-card.low .progress-fill { background: linear-gradient(90deg, #eb3349, #f45c43); }
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

        .view-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .view-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .filter-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        .filter-container h3 { 
            color: #1a1a2e; 
            margin: 0 0 20px; 
            font-size: 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        .form-group label { 
            color: #64748b; 
            font-weight: 600; 
            display: block; 
            margin-bottom: 8px; 
            font-size: 13px; 
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            background: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .btn-filter {
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-filter:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5); 
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        .table-container h3 {
            color: #1a1a2e;
            margin: 0 0 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header-with-search {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        #searchInput {
            width: 400px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s;
        }

        #searchInput:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #searchInput::placeholder {
            color: #999;
        }

        table { width: 100%; border-collapse: collapse; }
        table thead tr { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        table thead th { padding: 16px; text-align: left; font-weight: 600; font-size: 13px; }
        table thead th:first-child { border-radius: 12px 0 0 12px; }
        table thead th:last-child { border-radius: 0 12px 12px 0; }
        table tbody tr { border-bottom: 1px solid #f1f5f9; transition: all 0.3s ease; }
        table tbody tr:hover { background: #f8fafc; }
        table tbody td { padding: 16px; font-size: 14px; color: #334155; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .badge-danger { background: linear-gradient(135deg, #eb3349, #f45c43); color: white; }
        .badge-warning { background: linear-gradient(135deg, #f7971e, #ffd200); color: white; }

        .no-data {
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 20px;
        }
        .no-data h3 { color: #64748b; margin: 0 0 10px; }
        .no-data p { color: #94a3b8; margin: 0; }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .section-grid {
                grid-template-columns: 1fr;
            }
            #searchInput {
                width: 100%;
            }
            .table-header-with-search {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
        }
    </style>
    <script>
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('attendanceTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let txtValue = '';
                const td = tr[i].getElementsByTagName('td');
                
                // Search through roll no, student name, class, department, teacher columns
                for (let j = 0; j <= 5; j++) {
                    if (td[j]) {
                        txtValue += td[j].textContent || td[j].innerText;
                        txtValue += ' ';
                    }
                }
                
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <div><h1>🎓 NIT AMMS - Attendance Reports</h1></div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="filter-container">
            <h3>🔍 Filter Attendance Records</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>📅 Date</label>
                    <input type="date" name="date" value="<?php echo $filter_date; ?>" required>
                </div>
                <div class="form-group">
                    <label>🏢 Department</label>
                    <select name="department">
                        <option value="">All Departments</option>
                        <?php $departments->data_seek(0); while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $filter_department == $dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>📚 Class</label>
                    <select name="class">
                        <option value="">All Classes</option>
                        <?php $classes->data_seek(0); while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">🔎 Apply Filter</button>
                </div>
            </form>
        </div>

        <?php if ($is_today): ?>
        <div style="text-align: center;"><span class="today-badge">LIVE - Today's Attendance</span></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Students</h3>
                <div class="stat-value"><?php echo $unique_students; ?></div>
                <div class="stat-label"><?php echo date('d M Y', strtotime($filter_date)); ?></div>
            </div>
            <div class="stat-card present">
                <h3>✅ Present</h3>
                <div class="stat-value"><?php echo $stats['present']; ?></div>
                <div class="stat-label"><?php echo $attendance_percentage; ?>% Attendance</div>
            </div>
            <div class="stat-card absent">
                <h3>❌ Absent</h3>
                <div class="stat-value"><?php echo $stats['absent']; ?></div>
                <div class="stat-label"><?php echo $unique_students > 0 ? round(($stats['absent'] / $unique_students) * 100, 1) : 0; ?>% of total</div>
            </div>
            <div class="stat-card late">
                <h3>⏰ Late Arrival</h3>
                <div class="stat-value"><?php echo $stats['late']; ?></div>
                <div class="stat-label"><?php echo $unique_students > 0 ? round(($stats['late'] / $unique_students) * 100, 1) : 0; ?>% of total</div>
            </div>
        </div>

        <div class="section-container">
            <h2 class="section-header-title">📚 Section-wise Attendance</h2>
            <?php if ($section_stats->num_rows > 0): ?>
            <div class="section-grid">
                <?php while ($section = $section_stats->fetch_assoc()): 
                    $pct = $section['total_students'] > 0 ? round(($section['present'] / $section['total_students']) * 100, 1) : 0;
                    $lvl = $pct >= 75 ? 'high' : ($pct >= 50 ? 'medium' : 'low');
                ?>
                <div class="section-card <?php echo $lvl; ?>">
                    <div class="card-header">
                        <div class="class-info">
                            <h4 class="class-name">
                                <span class="class-icon">📖</span>
                                <?php echo htmlspecialchars($section['class_name']); ?>
                            </h4>
                            <p class="dept-name">🏛️ <?php echo htmlspecialchars($section['dept_name']); ?></p>
                        </div>
                        <div class="percentage-circle">
                            <span class="percentage-value"><?php echo $pct; ?>%</span>
                            <span class="percentage-label">Present</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="stats-row">
                            <div class="stat-item late">
                                <span class="stat-num"><?php echo $section['late']; ?></span>
                                <span class="stat-text">Late</span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span>Attendance Progress</span>
                                <span><?php echo $pct; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                        <a href="?date=<?php echo $filter_date; ?>&class=<?php echo $section['class_id']; ?>" class="view-btn">
                            View Details →
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-data"><h3>📭 No Section Data</h3><p>No attendance records found for sections on this date.</p></div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <div class="table-header-with-search">
                <h3>📝 Detailed Attendance Records</h3>
                <input type="text" id="searchInput" onkeyup="searchTable()" 
                       placeholder="🔍 Search by roll no, name, class, department...">
            </div>
            <?php if ($attendance_records->num_rows > 0): ?>
            <table id="attendanceTable">
                <thead>
                    <tr>
                        <th>Roll No.</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Marked By</th>
                        <th>Time</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $attendance_records->data_seek(0); while ($r = $attendance_records->fetch_assoc()): 
                        $sc = $r['status'] === 'present' ? 'badge-success' : ($r['status'] === 'absent' ? 'badge-danger' : 'badge-warning');
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['dept_name']); ?></td>
                        <td><span class="badge <?php echo $sc; ?>"><?php echo strtoupper($r['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($r['teacher_name']); ?></td>
                        <td><strong><?php echo date('h:i A', strtotime($r['marked_at'])); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['remarks'] ?? '-'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data"><h3>📭 No Records Found</h3><p>No attendance records found for the selected filters.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden; margin-top: 50px;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">💼 Development Team</p>
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3);">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3);">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3);">Database</span>
                </div>
            </div>
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: #888; font-size: 12px; margin: 0 0 10px;">© 2025 NIT AMMS. All rights reserved.</p>
                <p style="color: #666; font-size: 11px; margin: 0;">Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software</p>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>