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

$full_name = trim(($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? ''));
$initial = strtoupper(substr($candidate['first_name'] ?? 'C', 0, 1));
$cv_exists = !empty($candidate['cv_file']) && file_exists(__DIR__ . '/uploads/cvs/' . $candidate['cv_file']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Candidate Profile - Bridgeway Consulting</title>
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
            --line:#dbe5f1;
            --card-border:rgba(15,23,42,0.08);
            --success-bg:#dcfce7;
            --success-text:#166534;
            --danger-bg:#fee2e2;
            --danger-text:#991b1b;
            --blue-soft:#eff6ff;
            --blue-text:#1d4ed8;
            --violet-soft:#f5f3ff;
            --violet-text:#6d28d9;
            --shadow-xl:0 30px 70px rgba(15,23,42,0.10);
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
        .info-card{
            background:rgba(255,255,255,0.88);
            backdrop-filter:blur(14px);
            border:1px solid var(--card-border);
            border-radius:30px;
            box-shadow:var(--shadow-xl);
        }

        .hero-shell{
            padding:34px;
            margin-bottom:28px;
            position:relative;
            overflow:hidden;
        }

        .hero-shell::before{
            content:"";
            position:absolute;
            top:-120px;
            right:-100px;
            width:270px;
            height:270px;
            background:radial-gradient(circle, rgba(37,99,235,0.14), transparent 70%);
            pointer-events:none;
        }

        .hero-shell::after{
            content:"";
            position:absolute;
            bottom:-130px;
            left:-100px;
            width:250px;
            height:250px;
            background:radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);
            pointer-events:none;
        }

        .hero-content{
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

        .profile-main{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:22px;
            flex-wrap:wrap;
        }

        .profile-left{
            display:flex;
            align-items:center;
            gap:18px;
            min-width:280px;
        }

        .avatar-xl{
            width:96px;
            height:96px;
            border-radius:28px;
            background:linear-gradient(135deg, rgba(37,99,235,0.18), rgba(124,58,237,0.20));
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:2.2rem;
            font-weight:900;
            color:#1e3a8a;
            box-shadow:0 16px 34px rgba(37,99,235,0.14);
            flex-shrink:0;
        }

        .profile-title{
            font-size:clamp(2rem,4vw,3rem);
            line-height:1.05;
            font-weight:900;
            letter-spacing:-0.04em;
            color:var(--dark);
            margin-bottom:8px;
        }

        .profile-sub{
            color:var(--muted);
            font-size:1rem;
            line-height:1.7;
            margin:0;
            max-width:720px;
        }

        .quick-chips{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            margin-top:16px;
        }

        .quick-chip{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:9px 13px;
            border-radius:999px;
            font-size:0.82rem;
            font-weight:800;
            background:#fff;
            border:1px solid var(--line);
            color:var(--muted);
            box-shadow:0 8px 20px rgba(15,23,42,0.04);
        }

        .hero-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:flex-start;
        }

        .btn-premium{
            min-height:48px;
            padding:0 18px;
            border:none;
            border-radius:16px;
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
            color:#fff;
            transform:translateY(-1px);
            box-shadow:0 18px 34px rgba(37,99,235,0.24);
        }

        .btn-soft{
            background:#fff;
            color:var(--dark);
            border:1px solid var(--line);
            box-shadow:0 10px 24px rgba(15,23,42,0.04);
        }

        .btn-soft:hover{
            color:var(--dark);
            transform:translateY(-1px);
        }

        .info-card{
            padding:26px;
            height:100%;
        }

        .section-title{
            font-size:1.08rem;
            font-weight:900;
            letter-spacing:-0.02em;
            margin-bottom:18px;
            color:var(--dark);
        }

        .info-grid{
            display:grid;
            gap:16px;
        }

        .info-item{
            padding:14px 0;
            border-bottom:1px solid #eef2f7;
        }

        .info-item:last-child{
            border-bottom:none;
            padding-bottom:0;
        }

        .label{
            color:var(--muted);
            font-size:0.86rem;
            font-weight:800;
            letter-spacing:0.01em;
            margin-bottom:7px;
            text-transform:uppercase;
        }

        .value{
            font-weight:700;
            color:var(--dark);
            margin:0;
            word-break:break-word;
            line-height:1.65;
        }

        .value-soft{
            color:var(--muted);
            font-weight:600;
        }

        .badge-soft{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:8px 12px;
            border-radius:999px;
            font-size:0.8rem;
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
            margin:4px 6px 0 0;
        }

        .notes-box{
            background:#fbfdff;
            border:1px solid #edf2f8;
            border-radius:20px;
            padding:18px;
            color:var(--text);
            line-height:1.75;
        }

        .footer-note{
            margin-top:22px;
            text-align:center;
            color:var(--muted);
            font-size:0.9rem;
            font-weight:600;
        }

        @media (max-width:991.98px){
            .page-wrap{
                padding-top:24px;
            }

            .hero-shell,
            .info-card{
                border-radius:24px;
            }

            .hero-shell{
                padding:24px;
            }

            .info-card{
                padding:22px;
            }

            .profile-title{
                font-size:2.2rem;
            }

            .avatar-xl{
                width:84px;
                height:84px;
                border-radius:24px;
                font-size:2rem;
            }
        }
    </style>
</head>
<body>
<div class="container page-wrap">
    <div class="hero-shell">
        <div class="hero-content">
            <div class="brand-pill">Bridgeway Consulting • Shared Candidate Profile</div>

            <div class="profile-main">
                <div class="profile-left">
                    <div class="avatar-xl" style="overflow:hidden;">
    <?php if (!empty($candidate['profile_image']) && file_exists(__DIR__ . '/uploads/profile_images/' . $candidate['profile_image'])): ?>
        <img src="uploads/profile_images/<?php echo rawurlencode($candidate['profile_image']); ?>" alt="Profile Picture" style="width:100%; height:100%; object-fit:cover;">
    <?php else: ?>
        <?php echo $initial; ?>
    <?php endif; ?>
</div>
                    <div>
                        <h1 class="profile-title"><?php echo e($full_name); ?></h1>
                        <p class="profile-sub">
                            <?php echo e($candidate['job_preference'] ?: 'Job preference not provided'); ?>
                            •
                            <?php echo e($candidate['current_location'] ?: 'Location not provided'); ?>
                        </p>

                        <div class="quick-chips">
                            <span class="quick-chip"><?php echo e($candidate['experience_years'] ?: 'No experience added'); ?></span>
                            <span class="quick-chip"><?php echo $cv_exists ? 'CV Uploaded' : 'No CV Uploaded'; ?></span>
                            <span class="quick-chip"><?php echo e(date('d M Y', strtotime($candidate['created_at']))); ?></span>
                        </div>
                    </div>
                </div>

                <div class="hero-actions">
                    <?php if ($cv_exists): ?>
                        <a href="uploads/cvs/<?php echo rawurlencode($candidate['cv_file']); ?>" target="_blank" class="btn-premium btn-gradient">View CV</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="info-card">
                <h2 class="section-title">Personal Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">First Name</div>
                        <p class="value"><?php echo e($candidate['first_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Last Name</div>
                        <p class="value"><?php echo e($candidate['last_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Email Address</div>
                        <p class="value"><?php echo e($candidate['email']); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Phone Number</div>
                        <p class="value"><?php echo e($candidate['phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="info-card">
                <h2 class="section-title">Job Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Job Preference</div>
                        <p class="value"><?php echo e($candidate['job_preference'] ?: '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Current Location</div>
                        <p class="value"><?php echo e($candidate['current_location'] ?: '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Years of Experience</div>
                        <p class="value"><?php echo e($candidate['experience_years'] ?: '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Submitted On</div>
                        <p class="value"><?php echo e(date('d M Y, h:i A', strtotime($candidate['created_at']))); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="info-card">
                <h2 class="section-title">Work Eligibility & Flexibility</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Right to Work in UK</div>
                        <span class="badge-soft <?php echo $candidate['right_to_work_uk'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                            <?php echo e($candidate['right_to_work_uk']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <div class="label">Share Code</div>
                        <p class="value"><?php echo e($candidate['share_code'] ?: '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <div class="label">Working Flexible</div>
                        <span class="badge-soft <?php echo $candidate['working_flexible'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                            <?php echo e($candidate['working_flexible']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="info-card">
                <h2 class="section-title">Availability</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Convenient Shift</div>
                        <div>
                            <?php if (!empty($shifts)): ?>
                                <?php foreach ($shifts as $shift): ?>
                                    <span class="badge-soft badge-shift"><?php echo e($shift); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="value value-soft">—</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="label">CV Status</div>
                        <p class="value"><?php echo $cv_exists ? 'Uploaded' : 'Not uploaded'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="info-card">
                <h2 class="section-title">Additional Notes</h2>
                <div class="notes-box">
                    <?php echo nl2br(e($candidate['additional_notes'] ?: 'No additional notes provided.')); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Shared securely through Bridgeway Consulting.
    </div>
</div>
</body>
</html>