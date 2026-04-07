<?php
require_once 'auth_check.php';
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT cv_file FROM candidates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (!empty($row['cv_file'])) {
            $filePath = __DIR__ . '/uploads/cvs/' . $row['cv_file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    $stmt->close();

    $deleteStmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
    $deleteStmt->close();
}

header("Location: dashboard.php");
exit;