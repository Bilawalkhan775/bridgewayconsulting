<?php
require_once 'db.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    die("Invalid or missing share token.");
}

$stmt = $conn->prepare("SELECT * FROM candidates WHERE share_token = ? AND share_enabled = 1 LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Shared profile not found or sharing disabled.");
}

$candidate = $result->fetch_assoc();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$shifts = [];
if (!empty($candidate['convenient_shift'])) {
    $shifts = array_map('trim', explode(',', $candidate['convenient_shift']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Candidate Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --dark:#0f172a;
            --muted:#64748b;
            --border:rgba(15,23,42,0.08);
        }
        body{
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#eef4ff 0%,#f8fafc 55%,#ffffff 100%);
            color:var(--dark);
        }
        .page-wrap{ padding:40px 0 60px; }
        .profile-card,.info-card{
            background:rgba(255,255,255,0.94);
            border:1px solid var(--border);
            border-radius:24px;
            box-shadow:0 18px 45px rgba(15,23,42,0.08);
        }
        .btn-primary{
    background:linear-gradient(135deg, #2563eb, #7c3aed);
    border:none;
    border-radius:14px;
    font-weight:700;
    padding:12px 20px;
    color:#fff;
    text-decoration:none;
    display:inline-block;
}
        .profile-card{ padding:30px; margin-bottom:24px; }
        .info-card{ padding:24px; height:100%; }
        .avatar-lg{
            width:84px; height:84px; border-radius:50%;
            background:linear-gradient(135deg, rgba(37,99,235,0.14), rgba(124,58,237,0.18));
            display:flex; align-items:center; justify-content:center;
            font-size:2rem; font-weight:800; color:#1e3a8a;
        }
        .profile-name{ font-size:2rem; font-weight:800; margin-bottom:4px; }
        .profile-sub{ color:var(--muted); margin-bottom:0; }
        .label{ color:var(--muted); font-size:0.9rem; font-weight:600; margin-bottom:6px; }
        .value{ font-weight:700; margin-bottom:0; word-break:break-word; }
        .section-title{ font-size:1.05rem; font-weight:800; margin-bottom:18px; }
        .badge-soft{
            display:inline-block; padding:8px 12px; border-radius:999px;
            font-size:0.82rem; font-weight:700;
        }
        .badge-yes{ background:#dcfce7; color:#166534; }
        .badge-no{ background:#fee2e2; color:#991b1b; }
        .badge-shift{ background:#eff6ff; color:#1d4ed8; margin:4px 6px 0 0; }
    </style>
</head>
<body>
<div class="container page-wrap">
    <div class="profile-card">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-lg"><?php echo strtoupper(substr($candidate['first_name'], 0, 1)); ?></div>
            <div>
                <h1 class="profile-name"><?php echo e($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h1>
                <p class="profile-sub"><?php echo e($candidate['job_preference']); ?> • <?php echo e($candidate['current_location'] ?: 'Location not provided'); ?></p>
            </div>
        </div>

        <div>
            <?php if (!empty($candidate['cv_file']) && file_exists(__DIR__ . '/uploads/cvs/' . $candidate['cv_file'])): ?>
                <a href="../uploads/cvs/<?php echo rawurlencode($candidate['cv_file']); ?>" target="_blank" class="btn btn-primary">
                    View CV
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="info-card">
                <h5 class="section-title">Personal Information</h5>
                <div class="mb-3"><div class="label">First Name</div><p class="value"><?php echo e($candidate['first_name']); ?></p></div>
                <div class="mb-3"><div class="label">Last Name</div><p class="value"><?php echo e($candidate['last_name']); ?></p></div>
                <div class="mb-3"><div class="label">Email Address</div><p class="value"><?php echo e($candidate['email']); ?></p></div>
                <div class="mb-0"><div class="label">Phone Number</div><p class="value"><?php echo e($candidate['phone']); ?></p></div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="info-card">
                <h5 class="section-title">Job Information</h5>
                <div class="mb-3"><div class="label">Job Preference</div><p class="value"><?php echo e($candidate['job_preference']); ?></p></div>
                <div class="mb-3"><div class="label">Current Location</div><p class="value"><?php echo e($candidate['current_location'] ?: '—'); ?></p></div>
                <div class="mb-3"><div class="label">Years of Experience</div><p class="value"><?php echo e($candidate['experience_years'] ?: '—'); ?></p></div>
                <div class="mb-0"><div class="label">Submitted On</div><p class="value"><?php echo e(date('d M Y, h:i A', strtotime($candidate['created_at']))); ?></p></div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="info-card">
                <h5 class="section-title">Work Eligibility & Flexibility</h5>
                <div class="mb-3">
                    <div class="label">Right to Work in UK</div>
                    <span class="badge-soft <?php echo $candidate['right_to_work_uk'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                        <?php echo e($candidate['right_to_work_uk']); ?>
                    </span>
                </div>
                <div class="mb-3"><div class="label">Share Code</div><p class="value"><?php echo e($candidate['share_code'] ?: '—'); ?></p></div>
                <div class="mb-0">
                    <div class="label">Working Flexible</div>
                    <span class="badge-soft <?php echo $candidate['working_flexible'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                        <?php echo e($candidate['working_flexible']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="info-card">
                <h5 class="section-title">Availability</h5>
                <div class="mb-3">
                    <div class="label">Convenient Shift</div>
                    <div>
                        <?php if (!empty($shifts)): ?>
                            <?php foreach ($shifts as $shift): ?>
                                <span class="badge-soft badge-shift"><?php echo e($shift); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="value">—</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-0"><div class="label">CV Status</div><p class="value"><?php echo !empty($candidate['cv_file']) ? 'Uploaded' : 'Not uploaded'; ?></p></div>
            </div>
        </div>

        <div class="col-12">
            <div class="info-card">
                <h5 class="section-title">Additional Notes</h5>
                <p class="value" style="font-weight:500;"><?php echo nl2br(e($candidate['additional_notes'] ?: 'No additional notes provided.')); ?></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>