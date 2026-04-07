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
    <title>Add Admin - Bridgeway Consulting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#eef4ff 0%,#f8fafc 55%,#ffffff 100%);
        }
        .page-wrap{
            padding:50px 0;
        }
        .card-box{
            max-width:600px;
            margin:auto;
            background:rgba(255,255,255,0.95);
            border:1px solid rgba(15,23,42,0.08);
            border-radius:24px;
            box-shadow:0 20px 50px rgba(15,23,42,0.08);
            padding:32px;
        }
        .page-title{
            font-size:1.8rem;
            font-weight:800;
            margin-bottom:8px;
        }
        .page-subtitle{
            color:#64748b;
            margin-bottom:24px;
        }
        .form-label{
            font-weight:700;
            margin-bottom:8px;
        }
        .form-control{
            min-height:50px;
            border-radius:14px;
        }
        .btn-primary{
            background:linear-gradient(135deg,#2563eb,#7c3aed);
            border:none;
            border-radius:14px;
            min-height:50px;
            font-weight:700;
        }
        .btn-outline-secondary{
            border-radius:14px;
            min-height:50px;
            font-weight:700;
        }
    </style>
</head>
<body>
    <div class="container page-wrap">
        <div class="card-box">
            <h1 class="page-title">Add New Admin</h1>
            <p class="page-subtitle">Create another admin account for dashboard access.</p>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo e($_POST['full_name'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Create Admin</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary w-100">Back</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>