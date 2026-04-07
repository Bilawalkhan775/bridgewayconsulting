<?php
require_once 'auth_check.php';
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Invalid candidate ID.");
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, share_token, share_enabled FROM candidates WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Candidate not found.");
}

$candidate = $result->fetch_assoc();

if (empty($candidate['share_token'])) {
    $share_token = bin2hex(random_bytes(16));

    $updateStmt = $conn->prepare("UPDATE candidates SET share_token = ?, share_enabled = 1 WHERE id = ?");
    $updateStmt->bind_param("si", $share_token, $id);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    $share_token = $candidate['share_token'];

    if ((int)$candidate['share_enabled'] !== 1) {
        $enableStmt = $conn->prepare("UPDATE candidates SET share_enabled = 1 WHERE id = ?");
        $enableStmt->bind_param("i", $id);
        $enableStmt->execute();
        $enableStmt->close();
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$share_link = $scheme . '://' . $host . $basePath . '/candidate_public_profile.php?token=' . urlencode($share_token);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Candidate Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; }
        .card-box {
            max-width: 760px;
            margin: 60px auto;
            background: white;
            border-radius: 22px;
            box-shadow: 0 20px 50px rgba(15,23,42,0.08);
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-box">
            <h2 class="fw-bold mb-2">Share Candidate Profile</h2>
            <p class="text-muted mb-4">
                Public link for <?php echo e($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
            </p>

            <label class="form-label fw-semibold">Shareable Link</label>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="shareLink" value="<?php echo e($share_link); ?>" readonly>
                <button class="btn btn-primary" type="button" onclick="copyLink()">Copy</button>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="candidate_profile.php?id=<?php echo (int)$id; ?>" class="btn btn-outline-primary">Back to Profile</a>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                <a href="candidate_disable_share.php?id=<?php echo (int)$id; ?>" class="btn btn-outline-danger" onclick="return confirm('Disable public sharing for this candidate?');">Disable Share</a>
            </div>

            <p class="text-muted mt-4 mb-0">
                Anyone with this link can view this candidate profile until sharing is disabled.
            </p>
        </div>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById('shareLink');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                alert('Link copied successfully');
            });
        }
    </script>
</body>
</html>