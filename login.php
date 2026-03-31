<?php
session_start();
require_once 'db.php';

/*
|--------------------------------------------------------------------------
| DEBUG MODE
|--------------------------------------------------------------------------
| Set to false after testing is complete
*/
$debug_mode = true;
$debug_messages = [];

/*
|--------------------------------------------------------------------------
| DEBUG HELPER
|--------------------------------------------------------------------------
*/
function add_debug(&$debug_messages, $message)
{
    $debug_messages[] = $message;
}

/*
|--------------------------------------------------------------------------
| CHECK DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check db.php");
} else {
    add_debug($debug_messages, "Database connection object is available.");
}

/*
|--------------------------------------------------------------------------
| REDIRECT IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['admin_id'])) {
    add_debug($debug_messages, "Admin session already exists. Redirecting to dashboard.");
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| HANDLE LOGIN
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    add_debug($debug_messages, "Form submitted.");
    add_debug($debug_messages, "Entered email: " . htmlspecialchars($email));

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
        add_debug($debug_messages, "Validation failed: email or password is empty.");
    } else {
        $sql = "SELECT id, full_name, email, password FROM admins WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = 'Database query preparation failed.';
            add_debug($debug_messages, "Prepare failed: " . $conn->error);
        } else {
            add_debug($debug_messages, "SQL statement prepared successfully.");

            $stmt->bind_param("s", $email);

            if (!$stmt->execute()) {
                $error = 'Failed to execute login query.';
                add_debug($debug_messages, "Execute failed: " . $stmt->error);
            } else {
                add_debug($debug_messages, "SQL statement executed successfully.");

                $result = $stmt->get_result();

                if (!$result) {
                    $error = 'Failed to fetch login result.';
                    add_debug($debug_messages, "get_result() failed. This may happen if mysqlnd is missing.");
                } else {
                    add_debug($debug_messages, "Rows found: " . $result->num_rows);

                    if ($result->num_rows === 1) {
                        $admin = $result->fetch_assoc();

                        add_debug($debug_messages, "Admin record found.");
                        add_debug($debug_messages, "Admin ID: " . $admin['id']);
                        add_debug($debug_messages, "Admin Email from DB: " . htmlspecialchars($admin['email']));
                        add_debug($debug_messages, "Password hash found in DB.");

                        if (password_verify($password, $admin['password'])) {
                            add_debug($debug_messages, "Password verified successfully.");

                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_name'] = $admin['full_name'];
                            $_SESSION['admin_email'] = $admin['email'];

                            $success = 'Login successful. Redirecting to dashboard...';
                            add_debug($debug_messages, "Session variables set successfully.");

                            header('refresh:2;url=dashboard.php');
                        } else {
                            $error = 'Invalid email or password.';
                            add_debug($debug_messages, "Password verification failed.");
                            add_debug($debug_messages, "Entered password did not match stored hash.");
                        }
                    } else {
                        $error = 'Invalid email or password.';
                        add_debug($debug_messages, "No matching admin found for this email.");
                    }
                }
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bridgeway Consulting</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --dark-color: #0f172a;
            --muted-color: #64748b;
            --soft-bg: #f8fafc;
            --card-border: rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.16), transparent 32%),
                radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.14), transparent 28%),
                linear-gradient(135deg, #eef4ff 0%, #f8fbff 45%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1120px;
        }

        .login-card {
            border: 1px solid var(--card-border);
            border-radius: 28px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        .left-panel {
            background: linear-gradient(160deg, #0d6efd 0%, #155eef 45%, #0b57d0 100%);
            color: #fff;
            padding: 52px 42px;
            height: 100%;
            position: relative;
        }

        .left-panel::before,
        .left-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.10);
        }

        .left-panel::before {
            width: 220px;
            height: 220px;
            top: -70px;
            right: -70px;
        }

        .left-panel::after {
            width: 160px;
            height: 160px;
            bottom: -50px;
            left: -50px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.20);
            color: #fff;
            border-radius: 999px;
            padding: 9px 16px;
            font-size: 0.92rem;
            font-weight: 600;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .left-panel h1 {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }

        .left-panel p {
            color: rgba(255,255,255,0.92);
            font-size: 1rem;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        .info-box {
            margin-top: 28px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 18px;
            padding: 16px 18px;
            position: relative;
            z-index: 1;
        }

        .info-box h6 {
            font-weight: 700;
            margin-bottom: 6px;
        }

        .info-box p {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .right-panel {
            padding: 52px 42px;
            background: #fff;
        }

        .login-heading {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .login-subtext {
            color: var(--muted-color);
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .input-group-text {
            background: #fff;
            border-right: 0;
            border-radius: 14px 0 0 14px;
            border-color: #dbe4f0;
            color: #64748b;
            min-height: 54px;
        }

        .form-control {
            min-height: 54px;
            border-radius: 0 14px 14px 0;
            border-left: 0;
            border-color: #dbe4f0;
            padding: 12px 14px;
            box-shadow: none !important;
        }

        .form-control:focus,
        .input-group:focus-within .input-group-text {
            border-color: rgba(13, 110, 253, 0.55);
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.10) !important;
        }

        .form-check-label {
            color: var(--muted-color);
            font-size: 0.94rem;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.94rem;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            min-height: 56px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 12px 28px rgba(13, 110, 253, 0.22);
        }

        .security-note {
            margin-top: 22px;
            font-size: 0.92rem;
            color: var(--muted-color);
            text-align: center;
        }

        .alert {
            border-radius: 16px;
        }

        .debug-box {
            background: #fff8e1;
            border: 1px solid #ffe08a;
            color: #664d03;
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 20px;
            font-size: 0.93rem;
        }

        .debug-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .debug-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .debug-box li {
            margin-bottom: 6px;
            word-break: break-word;
        }

        @media (max-width: 991.98px) {
            .left-panel,
            .right-panel {
                padding: 34px 26px;
            }

            .left-panel h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-card">
            <div class="row g-0">
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="left-panel">
                        <div class="brand-badge">
                            <i class="bi bi-briefcase-fill"></i>
                            Bridgeway Consulting
                        </div>

                        <h1>Welcome back to the admin portal.</h1>
                        <p>
                            Access your consultancy dashboard to manage employer relationships,
                            review candidate records, and control daily operations from one secure place.
                        </p>

                        <div class="info-box">
                            <h6><i class="bi bi-shield-lock me-2"></i>Secure Access</h6>
                            <p>Protected login for authorized administrators only.</p>
                        </div>

                        <div class="info-box">
                            <h6><i class="bi bi-people-fill me-2"></i>Candidate Management</h6>
                            <p>Organize applications and prepare profiles for employers.</p>
                        </div>

                        <div class="info-box">
                            <h6><i class="bi bi-bar-chart-line-fill me-2"></i>Built to Grow</h6>
                            <p>Ready to expand with jobs, reports, and employer modules later.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="right-panel">
                        <h2 class="login-heading">Admin Login</h2>
                        <p class="login-subtext">Please sign in to continue to your dashboard.</p>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($debug_mode && !empty($debug_messages)): ?>
                            <div class="debug-box">
                                <strong>Debug Information</strong>
                                <ul>
                                    <?php foreach ($debug_messages as $message): ?>
                                        <li><?php echo htmlspecialchars($message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        placeholder="Enter your email"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        placeholder="Enter your password"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                                <a href="#" class="forgot-link">Forgot password?</a>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Login to Dashboard
                            </button>
                        </form>

                        <div class="security-note">
                            Secure admin access for Bridgeway Consulting management system.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>