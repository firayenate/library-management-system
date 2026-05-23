<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT admin_id, full_name, email, phone, position, role, profile_picture FROM admins WHERE admin_id = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "Admin not found"]);
    }
    $stmt->close();
} 
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action']) && $data['action'] === 'updateProfile') {
        $fullName = trim($data['fullName']);
        $email = trim($data['email']);
        $phone = trim($data['phone']);
        $position = trim($data['position']);
        $role = trim($data['role']);
        
        if (empty($fullName) || empty($email)) {
            echo json_encode(["success" => false, "error" => "Name and Email are required."]);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ?, phone = ?, position = ?, role = ? WHERE admin_id = ?");
        $stmt->bind_param("ssssss", $fullName, $email, $phone, $position, $role, $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_name'] = $fullName; // update session display name
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if (isset($data['action']) && $data['action'] === 'changePassword') {
        $currentPassword = $data['currentPassword'];
        $newPassword = $data['newPassword'];
        
        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(["success" => false, "error" => "Both current and new passwords are required."]);
            exit();
        }
        
        // Fetch current password hash
        $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE admin_id = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $hash = $row['password_hash'];
            $stmt->close();
            
            if (!password_verify($currentPassword, $hash)) {
                echo json_encode(["success" => false, "error" => "Current password is incorrect."]);
                exit();
            }
            
            if (strlen($newPassword) < 6) {
                echo json_encode(["success" => false, "error" => "New password must be at least 6 characters."]);
                exit();
            }
            
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admins SET password_hash = ? WHERE admin_id = ?");
            $upd->bind_param("ss", $newHash, $admin_id);
            
            if ($upd->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => $conn->error]);
            }
            $upd->close();
        } else {
            echo json_encode(["success" => false, "error" => "Admin record lookup failed."]);
            $stmt->close();
        }
    }
    else if (isset($data['action']) && $data['action'] === 'removePhoto') {
        // Fetch and unlink old file
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
        
        $upd = $conn->prepare("UPDATE admins SET profile_picture = NULL WHERE admin_id = ?");
        $upd->bind_param("s", $admin_id);
        
        if ($upd->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $upd->close();
    }
}
$conn->close();
?>
