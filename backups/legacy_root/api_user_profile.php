<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['userLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$student_id = $_SESSION['student_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT student_id as id, full_name as name, email, phone, department, year_of_study as year FROM users WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($user = $res->fetch_assoc()) {
        echo json_encode($user);
    } else {
        echo json_encode(["error" => "User not found"]);
    }
    $stmt->close();
} 
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data['action']) && $data['action'] === 'updateProfile') {
        if(empty($data['name']) || empty($data['email'])) {
            echo json_encode(["success" => false, "error" => "Name and Email are required."]);
            exit();
        }
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "error" => "Invalid email format."]);
            exit();
        }
        $name = $data['name'];
        $email = $data['email'];
        $phone = isset($data['phone']) ? $data['phone'] : '';
        $dept = $data['department'];
        
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, department=? WHERE student_id=?");
        $stmt->bind_param("sssss", $name, $email, $phone, $dept, $student_id);
        if($stmt->execute()) {
            $_SESSION['student_name'] = $name; // Update session name just in case
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'changePassword') {
        if(empty($data['newPassword']) || strlen($data['newPassword']) < 6) {
            echo json_encode(["success" => false, "error" => "New password must be at least 6 characters."]);
            exit();
        }
        $curr = $data['currentPassword'];
        $new = $data['newPassword'];
        
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($user = $res->fetch_assoc()) {
            if (password_verify($curr, $user['password_hash'])) {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password_hash=? WHERE student_id=?");
                $upd->bind_param("ss", $newHash, $student_id);
                if($upd->execute()) {
                    echo json_encode(["success" => true]);
                } else {
                    echo json_encode(["success" => false, "error" => "Failed to update password"]);
                }
                $upd->close();
            } else {
                 echo json_encode(["success" => false, "error" => "Incorrect current password"]);
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
