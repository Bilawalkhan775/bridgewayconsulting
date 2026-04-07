<?php
require_once 'auth_check.php';
require_once 'db.php';


$search = trim($_GET['search'] ?? '');
$job_preference = trim($_GET['job_preference'] ?? '');
$right_to_work_uk = trim($_GET['right_to_work_uk'] ?? '');
$working_flexible = trim($_GET['working_flexible'] ?? '');
$experience_years = trim($_GET['experience_years'] ?? '');
$convenient_shift = trim($_GET['convenient_shift'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR current_location LIKE ?)";
    $searchLike = "%{$search}%";
    array_push($params, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike);
    $types .= 'sssss';
}

if ($job_preference !== '') {
    $where[] = "job_preference = ?";
    $params[] = $job_preference;
    $types .= 's';
}

if ($right_to_work_uk !== '') {
    $where[] = "right_to_work_uk = ?";
    $params[] = $right_to_work_uk;
    $types .= 's';
}

if ($working_flexible !== '') {
    $where[] = "working_flexible = ?";
    $params[] = $working_flexible;
    $types .= 's';
}

if ($experience_years !== '') {
    $where[] = "experience_years = ?";
    $params[] = $experience_years;
    $types .= 's';
}

if ($convenient_shift !== '') {
    $where[] = "convenient_shift LIKE ?";
    $params[] = "%{$convenient_shift}%";
    $types .= 's';
}

$sql = "SELECT * FROM candidates";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$total_candidates = 0;
$total_flexible = 0;
$total_uk_right = 0;
$total_with_cv = 0;

$statsQuery = "SELECT 
    COUNT(*) as total_candidates,
    SUM(CASE WHEN working_flexible = 'Yes' THEN 1 ELSE 0 END) as total_flexible,
    SUM(CASE WHEN right_to_work_uk = 'Yes' THEN 1 ELSE 0 END) as total_uk_right,
    SUM(CASE WHEN cv_file IS NOT NULL AND cv_file != '' THEN 1 ELSE 0 END) as total_with_cv
FROM candidates";
$statsResult = $conn->query($statsQuery);
if ($statsResult && $statsRow = $statsResult->fetch_assoc()) {
    $total_candidates = $statsRow['total_candidates'] ?? 0;
    $total_flexible = $statsRow['total_flexible'] ?? 0;
    $total_uk_right = $statsRow['total_uk_right'] ?? 0;
    $total_with_cv = $statsRow['total_with_cv'] ?? 0;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Dashboard - Bridgeway Consulting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#2563eb;
            --secondary:#7c3aed;
            --dark:#0f172a;
            --muted:#64748b;
            --soft:#f8fafc;
            --border:rgba(15,23,42,0.08);
        }
        body{
            font-family:'Inter',sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37,99,235,0.10), transparent 25%),
                radial-gradient(circle at top right, rgba(124,58,237,0.08), transparent 25%),
                linear-gradient(135deg,#eef4ff 0%,#f8fafc 55%,#ffffff 100%);
            color:var(--dark);
        }
        .page-wrap{
            padding:32px 0 60px;
        }
        .dashboard-header,
        .card-box,
        .filter-box,
        .table-box{
            background:rgba(255,255,255,0.92);
            border:1px solid var(--border);
            border-radius:24px;
            box-shadow:0 18px 45px rgba(15,23,42,0.08);
        }
        .dashboard-header{
            padding:28px;
            margin-bottom:24px;
        }
        .header-title{
            font-size:clamp(1.8rem, 4vw, 2.8rem);
            font-weight:800;
            margin-bottom:8px;
            letter-spacing:-0.03em;
        }
        .header-text{
            color:var(--muted);
            margin:0;
        }
        .card-box{
            padding:22px;
            height:100%;
        }
        .stat-label{
            color:var(--muted);
            font-size:0.95rem;
            margin-bottom:8px;
            font-weight:600;
        }
        .stat-value{
            font-size:2rem;
            font-weight:800;
            margin:0;
        }
        .filter-box{
            padding:24px;
            margin:24px 0;
        }
        .table-box{
            padding:22px;
        }
        .form-control, .form-select{
            min-height:48px;
            border-radius:14px;
            border:1px solid #d7e2ef;
            box-shadow:none !important;
        }
        .form-control:focus, .form-select:focus{
            border-color:rgba(37,99,235,0.5);
            box-shadow:0 0 0 0.2rem rgba(37,99,235,0.12) !important;
        }
        .btn-primary{
            background:linear-gradient(135deg, var(--primary), var(--secondary));
            border:none;
            border-radius:14px;
            font-weight:700;
            min-height:48px;
            padding:0 22px;
        }
        .btn-outline-secondary,
        .btn-outline-primary,
        .btn-outline-danger{
            border-radius:12px;
            font-weight:600;
        }
        .table thead th{
            border-bottom:1px solid #e5e7eb;
            color:#334155;
            font-size:0.92rem;
            white-space:nowrap;
        }
        .table tbody td{
            vertical-align:middle;
            font-size:0.95rem;
        }
        .badge-soft{
            display:inline-block;
            padding:7px 12px;
            border-radius:999px;
            font-size:0.8rem;
            font-weight:700;
        }
        .badge-yes{
            background:#dcfce7;
            color:#166534;
        }
        .badge-no{
            background:#fee2e2;
            color:#991b1b;
        }
        .badge-shift{
            background:#eff6ff;
            color:#1d4ed8;
            margin:2px;
        }
        .avatar-box{
            width:42px;
            height:42px;
            border-radius:50%;
            background:linear-gradient(135deg, rgba(37,99,235,0.14), rgba(124,58,237,0.14));
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:800;
            color:#1e3a8a;
            flex-shrink:0;
        }
        .candidate-name{
            font-weight:700;
            margin-bottom:2px;
        }
        .candidate-meta{
            color:var(--muted);
            font-size:0.86rem;
        }
        @media (max-width: 991.98px){
            .dashboard-header,.filter-box,.table-box,.card-box{
                border-radius:20px;
            }
        }
    </style>
</head>
<body>
<div class="container page-wrap">

    <div class="dashboard-header">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h1 class="header-title">Candidates Dashboard</h1>
                <p class="header-text">Manage submitted candidate profiles, apply filters, and review each applicant in detail.</p>
            </div>
            
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card-box">
                <div class="stat-label">Total Candidates</div>
                <h3 class="stat-value"><?php echo e($total_candidates); ?></h3>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box">
                <div class="stat-label">Flexible Candidates</div>
                <h3 class="stat-value"><?php echo e($total_flexible); ?></h3>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box">
                <div class="stat-label">Right to Work in UK</div>
                <h3 class="stat-value"><?php echo e($total_uk_right); ?></h3>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box">
                <div class="stat-label">CVs Uploaded</div>
                <h3 class="stat-value"><?php echo e($total_with_cv); ?></h3>
            </div>
        </div>
    </div>

    <div class="filter-box">
        <form method="GET">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, email, phone, location" value="<?php echo e($search); ?>">
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Job Preference</label>
                    <select name="job_preference" class="form-select">
                        <option value="">All Job Preferences</option>
                        <option value="Care Assistant" <?php echo $job_preference === 'Care Assistant' ? 'selected' : ''; ?>>Care Assistant</option>
                        <option value="Warehouse Staff" <?php echo $job_preference === 'Warehouse Staff' ? 'selected' : ''; ?>>Warehouse Staff</option>
                        <option value="Cleaner" <?php echo $job_preference === 'Cleaner' ? 'selected' : ''; ?>>Cleaner</option>
                        <option value="Driver" <?php echo $job_preference === 'Driver' ? 'selected' : ''; ?>>Driver</option>
                        <option value="Office Admin" <?php echo $job_preference === 'Office Admin' ? 'selected' : ''; ?>>Office Admin</option>
                        <option value="Hospitality Staff" <?php echo $job_preference === 'Hospitality Staff' ? 'selected' : ''; ?>>Hospitality Staff</option>
                        <option value="Retail Assistant" <?php echo $job_preference === 'Retail Assistant' ? 'selected' : ''; ?>>Retail Assistant</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Right to Work in UK</label>
                    <select name="right_to_work_uk" class="form-select">
                        <option value="">All</option>
                        <option value="Yes" <?php echo $right_to_work_uk === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo $right_to_work_uk === 'No' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Working Flexible</label>
                    <select name="working_flexible" class="form-select">
                        <option value="">All</option>
                        <option value="Yes" <?php echo $working_flexible === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo $working_flexible === 'No' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Years of Experience</label>
                    <select name="experience_years" class="form-select">
                        <option value="">All Experience</option>
                        <option value="Fresher" <?php echo $experience_years === 'Fresher' ? 'selected' : ''; ?>>Fresher</option>
                        <option value="1 Year" <?php echo $experience_years === '1 Year' ? 'selected' : ''; ?>>1 Year</option>
                        <option value="2 Years" <?php echo $experience_years === '2 Years' ? 'selected' : ''; ?>>2 Years</option>
                        <option value="3 Years" <?php echo $experience_years === '3 Years' ? 'selected' : ''; ?>>3 Years</option>
                        <option value="5+ Years" <?php echo $experience_years === '5+ Years' ? 'selected' : ''; ?>>5+ Years</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Convenient Shift</label>
                    <select name="convenient_shift" class="form-select">
                        <option value="">All Shifts</option>
                        <option value="Morning" <?php echo $convenient_shift === 'Morning' ? 'selected' : ''; ?>>Morning</option>
                        <option value="Evening" <?php echo $convenient_shift === 'Evening' ? 'selected' : ''; ?>>Evening</option>
                        <option value="Night" <?php echo $convenient_shift === 'Night' ? 'selected' : ''; ?>>Night</option>
                        <option value="Weekend" <?php echo $convenient_shift === 'Weekend' ? 'selected' : ''; ?>>Weekend</option>
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-box">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="mb-0 fw-bold">Candidate Records</h4>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job Preference</th>
                        <th>Location</th>
                        <th>Right to Work</th>
                        <th>Flexible</th>
                        <th>Shift</th>
                        <th>Experience</th>
                        <th>CV</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-box">
                                        <?php echo strtoupper(substr($row['first_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="candidate-name"><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                        <div class="candidate-meta"><?php echo e($row['email']); ?></div>
                                        <div class="candidate-meta"><?php echo e($row['phone']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo e($row['job_preference']); ?></td>
                            <td><?php echo e($row['current_location'] ?: '—'); ?></td>
                            <td>
                                <span class="badge-soft <?php echo $row['right_to_work_uk'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                                    <?php echo e($row['right_to_work_uk']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-soft <?php echo $row['working_flexible'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                                    <?php echo e($row['working_flexible']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if (!empty($row['convenient_shift'])) {
                                    $shifts = explode(',', $row['convenient_shift']);
                                    foreach ($shifts as $shift) {
                                        echo '<span class="badge-soft badge-shift">' . e(trim($shift)) . '</span>';
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo e($row['experience_years'] ?: '—'); ?></td>
                            <td>
                                <?php if (!empty($row['cv_file']) && file_exists(__DIR__ . '/uploads/cvs/' . $row['cv_file'])): ?>
                                    <a href="uploads/cvs/<?php echo rawurlencode($row['cv_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Open CV</a>
                                <?php else: ?>
                                    <span class="text-muted">No CV</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e(date('d M Y', strtotime($row['created_at']))); ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="candidate_profile.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="candidate_delete.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</a>
                                    <a href="candidate_share.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-secondary">Share</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">No candidates found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>