<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$admin_id = $_SESSION['admin_id'];

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(["success" => false, "error" => "No file uploaded."]);
    exit();
}

$file = $_FILES['profile_picture'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];
$fileType = $file['type'];

if ($fileError !== 0) {
    echo json_encode(["success" => false, "error" => "Error during file upload. Code: " . $fileError]);
    exit();
}

// Check file extension
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');

if (!in_array($fileExt, $allowed)) {
    echo json_encode(["success" => false, "error" => "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP allowed."]);
    exit();
}

// Check file size (5MB maximum)
if ($fileSize > 5000000) {
    echo json_encode(["success" => false, "error" => "File is too large. Maximum size is 5MB."]);
    exit();
}

// Target directory is ../img/profiles/
$targetDir = '../img/profiles/';
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Unique filename to prevent browser caching issues
$safe_admin_id = str_replace('/', '_', $admin_id);
$newFileName = "admin_" . $safe_admin_id . "_" . time() . "." . $fileExt;
$destination = $targetDir . $newFileName;
$dbPath = 'img/profiles/' . $newFileName;

if (move_uploaded_file($fileTmpName, $destination)) {
    // Clean up older profile pictures for this admin to avoid server bloat
    $stmt = $conn->prepare("SELECT profile_picture FROM admins WHERE admin_id = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $oldPic = $row['profile_picture'];
        if ($oldPic && file_exists('../' . $oldPic)) {
            @unlink('../' . $oldPic);
        }
    }
    $stmt->close();

    // Update path in database
    $stmt = $conn->prepare("UPDATE admins SET profile_picture = ? WHERE admin_id = ?");
    $stmt->bind_param("ss", $dbPath, $admin_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "profile_picture" => $dbPath]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update profile picture in database."]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Failed to save uploaded file."]);
}

$conn->close();
?>
