<?php
require_once 'auth_check.php';
require_once 'db.php';

$success = "";
$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($full_name === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "An admin with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO admins (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);

            if ($stmt->execute()) {
                header("Location: add_admin.php?success=1");
                exit;
            } else {
                $error = "Failed to create admin user.";
            }

            $stmt->close();
        }

        $checkStmt->close();
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Admin user created successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bridgeway Consulting - Add Admin</title>
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
            --success-bg:#ecfdf5;
            --success-text:#065f46;
            --danger-bg:#fef2f2;
            --danger-text:#991b1b;
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
            padding:42px 0 56px;
        }

        .hero-card,
        .form-card{
            background:rgba(255,255,255,0.88);
            backdrop-filter:blur(14px);
            border:1px solid var(--card-border);
            border-radius:30px;
            box-shadow:var(--shadow-xl);
            overflow:hidden;
            position:relative;
        }

        .hero-card{
            padding:34px;
            height:100%;
        }

        .form-card{
            padding:36px;
        }

        .hero-card::before{
            content:"";
            position:absolute;
            top:-120px;
            right:-100px;
            width:260px;
            height:260px;
            background:radial-gradient(circle, rgba(37,99,235,0.14), transparent 70%);
            pointer-events:none;
        }

        .hero-card::after{
            content:"";
            position:absolute;
            bottom:-130px;
            left:-100px;
            width:240px;
            height:240px;
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

        .hero-title{
            font-size:clamp(2rem,4vw,3rem);
            line-height:1.05;
            font-weight:900;
            letter-spacing:-0.04em;
            color:var(--dark);
            margin-bottom:14px;
        }

        .hero-text{
            color:var(--muted);
            font-size:1rem;
            line-height:1.75;
            margin-bottom:0;
        }

        .info-list{
            margin-top:24px;
        }

        .info-item{
            display:flex;
            gap:14px;
            align-items:flex-start;
            padding:14px 0;
            border-bottom:1px solid rgba(15,23,42,0.06);
        }

        .info-item:last-child{
            border-bottom:none;
            padding-bottom:0;
        }

        .info-icon{
            width:46px;
            height:46px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg, rgba(37,99,235,0.12), rgba(124,58,237,0.12));
            color:var(--primary);
            font-weight:900;
            flex-shrink:0;
            box-shadow:0 10px 22px rgba(37,99,235,0.08);
        }

        .info-item h6{
            font-weight:800;
            margin-bottom:4px;
            color:var(--dark);
        }

        .info-item p{
            margin:0;
            color:var(--muted);
            font-size:0.93rem;
            line-height:1.6;
        }

        .form-top{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:16px;
            flex-wrap:wrap;
            margin-bottom:24px;
        }

        .form-title{
            margin:0;
            font-size:1.8rem;
            line-height:1.1;
            font-weight:900;
            letter-spacing:-0.03em;
            color:var(--dark);
        }

        .form-subtitle{
            margin:8px 0 0;
            color:var(--muted);
            font-size:0.98rem;
        }

        .top-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:10px 16px;
            border-radius:999px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:#fff;
            font-size:13px;
            font-weight:800;
            box-shadow:0 12px 26px rgba(37,99,235,0.18);
        }

        .alert-custom{
            border:none;
            border-radius:18px;
            padding:16px 18px;
            font-weight:700;
            margin-bottom:20px;
        }

        .alert-success-custom{
            background:var(--success-bg);
            color:var(--success-text);
        }

        .alert-danger-custom{
            background:var(--danger-bg);
            color:var(--danger-text);
        }

        .field-box{
            background:#f8fbff;
            border:1px solid #e8eef7;
            border-radius:22px;
            padding:22px;
        }

        .field-box-title{
            font-size:1.02rem;
            font-weight:900;
            margin-bottom:16px;
            color:var(--dark);
        }

        .form-label{
            font-weight:800;
            font-size:0.93rem;
            margin-bottom:8px;
            color:#334155;
        }

        .form-control{
            min-height:54px;
            border-radius:16px;
            border:1px solid var(--line);
            padding:12px 16px;
            box-shadow:none !important;
            background:#fff;
            font-size:0.96rem;
        }

        .form-control:focus{
            border-color:rgba(37,99,235,0.45);
            box-shadow:0 0 0 0.25rem rgba(37,99,235,0.10) !important;
        }

        .helper-text{
            margin-top:8px;
            color:var(--muted);
            font-size:0.86rem;
        }

        .btn-premium{
            min-height:54px;
            border:none;
            border-radius:16px;
            font-weight:800;
            letter-spacing:0.01em;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            text-decoration:none;
            transition:all 0.2s ease;
            width:100%;
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

        .button-row{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            margin-top:10px;
        }

        .button-row > *{
            flex:1 1 220px;
        }

        @media (max-width:991.98px){
            .page-wrap{
                padding-top:24px;
            }

            .hero-card,
            .form-card{
                padding:24px;
                border-radius:24px;
            }

            .field-box{
                padding:18px;
                border-radius:18px;
            }

            .hero-title{
                font-size:2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container page-wrap">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="brand-pill">Bridgeway Consulting • Secure Admin Access</div>
                        <h1 class="hero-title">Create a new admin account with confidence.</h1>
                        <p class="hero-text">
                            Add trusted team members to your consultancy dashboard with a clear, elegant, and secure interface designed for easy access management.
                        </p>

                        <div class="info-list">
                            <div class="info-item">
                                <div class="info-icon">01</div>
                                <div>
                                    <h6>Professional access control</h6>
                                    <p>Create dashboard access for authorized staff in a more structured and polished way.</p>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">02</div>
                                <div>
                                    <h6>Secure password storage</h6>
                                    <p>Passwords are stored using hashing, helping protect admin credentials and strengthen overall security.</p>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">03</div>
                                <div>
                                    <h6>Clear and readable workflow</h6>
                                    <p>The layout keeps every field easy to understand, making admin setup feel simple and premium.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-card">
                    <div class="form-top">
                        <div>
                            <h2 class="form-title">Add New Admin</h2>
                            <p class="form-subtitle">Create another admin account for dashboard access.</p>
                        </div>
                        <div class="top-badge">Admin Setup</div>
                    </div>

                    <?php if ($success !== ''): ?>
                        <div class="alert-custom alert-success-custom">
                            <?php echo e($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="alert-custom alert-danger-custom">
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="field-box">
                            <div class="field-box-title">Administrator Details</div>

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo e($_POST['full_name'] ?? ''); ?>" placeholder="Enter full name" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo e($_POST['email'] ?? ''); ?>" placeholder="Enter email address" required>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                                <div class="helper-text">Use a strong password to keep the admin account secure.</div>
                            </div>
                        </div>

                        <div class="button-row">
                            <button type="submit" class="btn-premium btn-gradient">Create Admin Account</button>
                            <a href="dashboard.php" class="btn-premium btn-soft">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>