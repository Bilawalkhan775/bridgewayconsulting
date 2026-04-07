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
    <title>Bridgeway Consulting - Candidates Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#2563eb;
            --primary-dark:#1d4ed8;
            --secondary:#7c3aed;
            --accent:#0ea5e9;
            --dark:#0f172a;
            --text:#1e293b;
            --muted:#64748b;
            --soft:#f8fafc;
            --line:#e2e8f0;
            --card-border:rgba(15,23,42,0.08);
            --success-bg:#dcfce7;
            --success-text:#166534;
            --danger-bg:#fee2e2;
            --danger-text:#991b1b;
            --blue-soft:#eff6ff;
            --blue-text:#1d4ed8;
            --shadow-xl:0 24px 60px rgba(15,23,42,0.10);
            --shadow-md:0 14px 30px rgba(15,23,42,0.06);
        }

        *{
            box-sizing:border-box;
        }

        body{
            font-family:'Inter',sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at top left, rgba(37,99,235,0.12), transparent 24%),
                radial-gradient(circle at top right, rgba(124,58,237,0.10), transparent 22%),
                linear-gradient(135deg,#edf4ff 0%, #f8fafc 52%, #ffffff 100%);
            min-height:100vh;
        }

        .page-wrap{
            padding:34px 0 56px;
        }

        .hero-shell,
        .stats-card,
        .filter-shell,
        .table-shell{
            background:rgba(255,255,255,0.86);
            backdrop-filter:blur(14px);
            border:1px solid var(--card-border);
            border-radius:28px;
            box-shadow:var(--shadow-xl);
        }

        .hero-shell{
            padding:30px;
            margin-bottom:26px;
            overflow:hidden;
            position:relative;
        }

        .hero-shell::before{
            content:"";
            position:absolute;
            top:-120px;
            right:-120px;
            width:280px;
            height:280px;
            background:radial-gradient(circle, rgba(37,99,235,0.14), transparent 70%);
            pointer-events:none;
        }

        .hero-shell::after{
            content:"";
            position:absolute;
            bottom:-140px;
            left:-120px;
            width:260px;
            height:260px;
            background:radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);
            pointer-events:none;
        }

        .hero-inner{
            position:relative;
            z-index:2;
        }

        .brand-pill{
            display:inline-flex;
            align-items:center;
            gap:10px;
            padding:10px 16px;
            border-radius:999px;
            background:rgba(37,99,235,0.10);
            color:var(--primary);
            font-size:13px;
            font-weight:800;
            letter-spacing:0.02em;
            margin-bottom:18px;
        }

        .hero-title{
            font-size:clamp(2rem,4vw,3.2rem);
            line-height:1.05;
            font-weight:900;
            letter-spacing:-0.04em;
            margin-bottom:12px;
            color:var(--dark);
        }

        .hero-text{
            color:var(--muted);
            font-size:1rem;
            line-height:1.75;
            max-width:780px;
            margin-bottom:0;
        }

        .header-actions{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            justify-content:flex-end;
            align-items:center;
        }

        .admin-chip{
            display:inline-flex;
            align-items:center;
            gap:10px;
            padding:10px 14px;
            background:#ffffff;
            border:1px solid var(--line);
            border-radius:999px;
            font-weight:700;
            color:var(--muted);
            box-shadow:0 8px 20px rgba(15,23,42,0.04);
        }

        .btn-premium{
            min-height:48px;
            padding:0 18px;
            border:none;
            border-radius:14px;
            font-weight:800;
            letter-spacing:0.01em;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            text-decoration:none;
            transition:all 0.2s ease;
        }

        .btn-gradient{
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:#fff;
            box-shadow:0 14px 28px rgba(37,99,235,0.18);
        }

        .btn-gradient:hover{
            transform:translateY(-1px);
            color:#fff;
            box-shadow:0 18px 34px rgba(37,99,235,0.24);
        }

        .btn-soft{
            background:#fff;
            color:var(--dark);
            border:1px solid var(--line);
            box-shadow:0 10px 24px rgba(15,23,42,0.04);
        }

        .btn-soft:hover{
            transform:translateY(-1px);
            color:var(--dark);
        }

        .btn-danger-soft{
            background:#fff;
            color:#b91c1c;
            border:1px solid #fecaca;
            box-shadow:0 10px 24px rgba(15,23,42,0.04);
        }

        .btn-danger-soft:hover{
            background:#fff5f5;
            color:#991b1b;
        }

        .stats-card{
            padding:24px;
            height:100%;
            position:relative;
            overflow:hidden;
        }

        .stats-card::before{
            content:"";
            position:absolute;
            right:-30px;
            top:-30px;
            width:120px;
            height:120px;
            border-radius:50%;
            background:radial-gradient(circle, rgba(37,99,235,0.12), transparent 70%);
        }

        .stats-top{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:12px;
            position:relative;
            z-index:2;
        }

        .stats-label{
            color:var(--muted);
            font-size:0.94rem;
            font-weight:700;
            margin-bottom:12px;
        }

        .stats-value{
            margin:0;
            font-size:2.15rem;
            line-height:1;
            font-weight:900;
            letter-spacing:-0.03em;
            color:var(--dark);
        }

        .stats-icon{
            width:50px;
            height:50px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            font-size:1.05rem;
            color:#fff;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            box-shadow:0 12px 26px rgba(37,99,235,0.18);
        }

        .stats-note{
            margin-top:14px;
            font-size:0.88rem;
            color:var(--muted);
            position:relative;
            z-index:2;
        }

        .filter-shell{
            margin:28px 0;
            padding:28px;
        }

        .section-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            margin-bottom:20px;
        }

        .section-title{
            margin:0;
            font-size:1.25rem;
            font-weight:900;
            letter-spacing:-0.02em;
            color:var(--dark);
        }

        .section-subtitle{
            margin:4px 0 0;
            color:var(--muted);
            font-size:0.95rem;
        }

        .form-label{
            font-weight:800;
            font-size:0.92rem;
            margin-bottom:9px;
            color:#334155;
        }

        .form-control,
        .form-select{
            min-height:52px;
            border-radius:16px;
            border:1px solid #dbe5f1;
            padding:12px 16px;
            background:#ffffff;
            box-shadow:none !important;
            font-size:0.95rem;
        }

        .form-control:focus,
        .form-select:focus{
            border-color:rgba(37,99,235,0.45);
            box-shadow:0 0 0 0.25rem rgba(37,99,235,0.10) !important;
        }

        .filter-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:6px;
        }

        .filter-actions .btn-premium{
            min-height:50px;
            padding:0 20px;
        }

        .table-shell{
            padding:26px;
        }

        .table-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            margin-bottom:18px;
        }

        .table-title{
            margin:0;
            font-size:1.22rem;
            font-weight:900;
            letter-spacing:-0.02em;
            color:var(--dark);
        }

        .table-caption{
            color:var(--muted);
            margin:5px 0 0;
            font-size:0.94rem;
        }

        .table-wrap{
            border:1px solid var(--line);
            border-radius:22px;
            overflow:hidden;
            background:#fff;
        }

        .table{
            margin-bottom:0;
        }

        .table thead th{
            background:#f8fafc;
            color:#334155;
            font-size:0.83rem;
            font-weight:800;
            letter-spacing:0.02em;
            text-transform:uppercase;
            border-bottom:1px solid var(--line);
            white-space:nowrap;
            padding:18px 16px;
        }

        .table tbody td{
            vertical-align:middle;
            padding:18px 16px;
            border-bottom:1px solid #eef2f7;
            font-size:0.94rem;
        }

        .table tbody tr:last-child td{
            border-bottom:none;
        }

        .table tbody tr:hover{
            background:rgba(37,99,235,0.02);
        }

        .candidate-cell{
            display:flex;
            align-items:center;
            gap:14px;
            min-width:240px;
        }

        .avatar-box{
            width:50px;
            height:50px;
            border-radius:18px;
            background:linear-gradient(135deg, rgba(37,99,235,0.16), rgba(124,58,237,0.18));
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            color:#1e3a8a;
            flex-shrink:0;
            box-shadow:0 10px 22px rgba(37,99,235,0.10);
        }

        .candidate-name{
            font-weight:800;
            color:var(--dark);
            margin-bottom:3px;
            font-size:0.98rem;
        }

        .candidate-meta{
            color:var(--muted);
            font-size:0.86rem;
            line-height:1.55;
        }

        .badge-soft{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:8px 12px;
            border-radius:999px;
            font-size:0.78rem;
            font-weight:800;
            white-space:nowrap;
        }

        .badge-yes{
            background:var(--success-bg);
            color:var(--success-text);
        }

        .badge-no{
            background:var(--danger-bg);
            color:var(--danger-text);
        }

        .badge-shift{
            background:var(--blue-soft);
            color:var(--blue-text);
            margin:2px 4px 2px 0;
        }

        .text-soft{
            color:var(--muted);
        }

        .table-btn{
            min-height:38px;
            padding:0 14px;
            border-radius:12px;
            font-size:0.84rem;
            font-weight:800;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid transparent;
            transition:all 0.18s ease;
            white-space:nowrap;
        }

        .table-btn:hover{
            transform:translateY(-1px);
        }

        .btn-view{
            background:#eff6ff;
            color:#1d4ed8;
            border-color:#bfdbfe;
        }

        .btn-view:hover{
            color:#1d4ed8;
            background:#dbeafe;
        }

        .btn-share{
            background:#f5f3ff;
            color:#6d28d9;
            border-color:#ddd6fe;
        }

        .btn-share:hover{
            color:#6d28d9;
            background:#ede9fe;
        }

        .btn-cv{
            background:#ecfeff;
            color:#0f766e;
            border-color:#a5f3fc;
        }

        .btn-cv:hover{
            color:#0f766e;
            background:#cffafe;
        }

        .btn-delete{
            background:#fff1f2;
            color:#be123c;
            border-color:#fecdd3;
        }

        .btn-delete:hover{
            color:#be123c;
            background:#ffe4e6;
        }

        .actions-wrap{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .empty-state{
            padding:52px 20px;
            text-align:center;
            color:var(--muted);
            font-weight:600;
        }

        .candidate-count{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 14px;
            border-radius:999px;
            background:#f8fafc;
            border:1px solid var(--line);
            color:var(--muted);
            font-weight:700;
            font-size:0.9rem;
        }

        @media (max-width:1199.98px){
            .table thead th,
            .table tbody td{
                padding:16px 14px;
            }
        }

        @media (max-width:991.98px){
            .page-wrap{
                padding-top:24px;
            }

            .hero-shell,
            .stats-card,
            .filter-shell,
            .table-shell{
                border-radius:22px;
            }

            .hero-shell,
            .filter-shell,
            .table-shell{
                padding:22px;
            }

            .stats-card{
                padding:20px;
            }

            .hero-title{
                font-size:2.2rem;
            }
        }

        @media (max-width:767.98px){
            .candidate-cell{
                min-width:unset;
            }

            .actions-wrap{
                justify-content:flex-start;
            }

            .table-wrap{
                border-radius:18px;
            }
        }
    </style>
</head>
<body>
<div class="container page-wrap">

    <div class="hero-shell">
        <div class="hero-inner">
            <div class="row g-4 align-items-center">
                <div class="col-xl-8">
                    <div class="brand-pill">Bridgeway Consulting •  Admin Workspace</div>
                    <h1 class="hero-title">Candidates Dashboard</h1>
                    <p class="hero-text">
                        Review profiles, filter applicants, open CVs, share public candidate pages, and manage your recruitment pipeline through a cleaner, more elegant, and highly readable workspace.
                    </p>
                </div>
                <div class="col-xl-4">
                    <div class="header-actions">
                        <span class="admin-chip">
                            👤 <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin'); ?>
                        </span>
    
                        <a href="add_admin.php" class="btn-premium btn-soft">+ Add Admin</a>
                        <a href="logout.php" class="btn-premium btn-danger-soft">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="stats-card">
                <div class="stats-top">
                    <div>
                        <div class="stats-label">Total Candidates</div>
                        <h3 class="stats-value"><?php echo e($total_candidates); ?></h3>
                    </div>
        
                </div>
                <div class="stats-note">All candidates currently stored in your system.</div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stats-card">
                <div class="stats-top">
                    <div>
                        <div class="stats-label">Flexible Candidates</div>
                        <h3 class="stats-value"><?php echo e($total_flexible); ?></h3>
                    </div>
                    
                </div>
                <div class="stats-note">Applicants open to flexible work availability.</div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stats-card">
                <div class="stats-top">
                    <div>
                        <div class="stats-label">UK Work Eligibility</div>
                        <h3 class="stats-value"><?php echo e($total_uk_right); ?></h3>
                    </div>
                    
                </div>
                <div class="stats-note">Candidates who reported right to work in the UK.</div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stats-card">
                <div class="stats-top">
                    <div>
                        <div class="stats-label">CVs Uploaded</div>
                        <h3 class="stats-value"><?php echo e($total_with_cv); ?></h3>
                    </div>
                    
                </div>
                <div class="stats-note">Profiles that include a stored CV document.</div>
            </div>
        </div>
    </div>

    <div class="filter-shell">
        <div class="section-head">
            <div>
                <h2 class="section-title">Smart Candidate Filters</h2>
                <p class="section-subtitle">Narrow your candidate list quickly using search, job type, work eligibility, shift preference, and experience.</p>
            </div>
        </div>

        <form method="GET">
            <div class="row g-4">
                <div class="col-lg-4">
                    <label class="form-label">Search Candidate</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or location" value="<?php echo e($search); ?>">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Job Preference</label>
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
                    <label class="form-label">Right to Work in UK</label>
                    <select name="right_to_work_uk" class="form-select">
                        <option value="">All Candidates</option>
                        <option value="Yes" <?php echo $right_to_work_uk === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo $right_to_work_uk === 'No' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Working Flexible</label>
                    <select name="working_flexible" class="form-select">
                        <option value="">All Candidates</option>
                        <option value="Yes" <?php echo $working_flexible === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo $working_flexible === 'No' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Years of Experience</label>
                    <select name="experience_years" class="form-select">
                        <option value="">All Experience Levels</option>
                        <option value="Fresher" <?php echo $experience_years === 'Fresher' ? 'selected' : ''; ?>>Fresher</option>
                        <option value="1 Year" <?php echo $experience_years === '1 Year' ? 'selected' : ''; ?>>1 Year</option>
                        <option value="2 Years" <?php echo $experience_years === '2 Years' ? 'selected' : ''; ?>>2 Years</option>
                        <option value="3 Years" <?php echo $experience_years === '3 Years' ? 'selected' : ''; ?>>3 Years</option>
                        <option value="5+ Years" <?php echo $experience_years === '5+ Years' ? 'selected' : ''; ?>>5+ Years</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Convenient Shift</label>
                    <select name="convenient_shift" class="form-select">
                        <option value="">All Shift Types</option>
                        <option value="Morning" <?php echo $convenient_shift === 'Morning' ? 'selected' : ''; ?>>Morning</option>
                        <option value="Evening" <?php echo $convenient_shift === 'Evening' ? 'selected' : ''; ?>>Evening</option>
                        <option value="Night" <?php echo $convenient_shift === 'Night' ? 'selected' : ''; ?>>Night</option>
                        <option value="Weekend" <?php echo $convenient_shift === 'Weekend' ? 'selected' : ''; ?>>Weekend</option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="filter-actions">
                        <button type="submit" class="btn-premium btn-gradient">Apply Filters</button>
                        <a href="dashboard.php" class="btn-premium btn-soft">Reset Filters</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="table-shell">
        <div class="table-header">
            <div>
                <h2 class="table-title">Candidate Records</h2>
                <p class="table-caption">Browse, review, and take action on each candidate profile from one central table.</p>
            </div>
            <div class="candidate-count">
                Total Visible: <?php echo e($result->num_rows); ?>
            </div>
        </div>

        <div class="table-wrap">
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
                                    <div class="candidate-cell">
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
                                        echo '<span class="text-soft">—</span>';
                                    }
                                    ?>
                                </td>

                                <td><?php echo e($row['experience_years'] ?: '—'); ?></td>

                                <td>
                                    <?php if (!empty($row['cv_file']) && file_exists(__DIR__ . '/uploads/cvs/' . $row['cv_file'])): ?>
                                        <a href="uploads/cvs/<?php echo rawurlencode($row['cv_file']); ?>" target="_blank" class="table-btn btn-cv">Open CV</a>
                                    <?php else: ?>
                                        <span class="text-soft">No CV</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo e(date('d M Y', strtotime($row['created_at']))); ?></td>

                                <td class="text-end">
                                    <div class="actions-wrap">
                                        <a href="candidate_profile.php?id=<?php echo (int)$row['id']; ?>" class="table-btn btn-view">View</a>
                                        <a href="candidate_share.php?id=<?php echo (int)$row['id']; ?>" class="table-btn btn-share">Share</a>
                                        <a href="candidate_delete.php?id=<?php echo (int)$row['id']; ?>" class="table-btn btn-delete" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    No candidates found for the selected filters.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>