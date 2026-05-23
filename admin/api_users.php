<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query("SELECT student_id as id, full_name as name, email, phone, department, year_of_study as year, 'Active' as status, 0 as borrowed, 0 as returned, 0 as pending FROM users");
    $users = [];
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
} 
else if ($method === 'POST') {
    // Update user (using POST since PUT can be tricky with some shared hosting/browsers)
    $data = json_decode(file_get_contents("php://input"), true);
    if(isset($data['action']) && $data['action'] === 'update') {
        if(empty($data['name']) || empty($data['email'])) {
            echo json_encode(["success" => false, "error" => "Name and Email are required."]);
            exit();
        }
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "error" => "Invalid email format."]);
            exit();
        }
        $id   = $data['id'];
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'] ?? '';
        $dept = $data['department'] ?? '';
        $year = $data['year'] ?? '';

        // Check if admin wants to reset the password
        $newPassword = isset($data['password']) ? trim($data['password']) : '';

        if ($newPassword !== '') {
            // Validate minimum length server-side too
            if (strlen($newPassword) < 6) {
                echo json_encode(["success" => false, "error" => "Password must be at least 6 characters."]);
                exit();
            }
            $hashedPwd = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, department=?, year_of_study=?, password_hash=? WHERE student_id=?");
            $stmt->bind_param("sssssss", $name, $email, $phone, $dept, $year, $hashedPwd, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, department=?, year_of_study=? WHERE student_id=?");
            $stmt->bind_param("ssssss", $name, $email, $phone, $dept, $year, $id);
        }

        if($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Database error occurred. Please contact support."]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'delete') {
        $id = $data['id'];
        
        // 1. Delete associated support tickets first to satisfy constraints
        $stmt_tickets = $conn->prepare("DELETE FROM support_tickets WHERE student_id=?");
        $stmt_tickets->bind_param("s", $id);
        $stmt_tickets->execute();
        $stmt_tickets->close();
        
        // 2. Delete associated book holds first to satisfy constraints
        $stmt_holds = $conn->prepare("DELETE FROM book_holds WHERE student_id=?");
        $stmt_holds->bind_param("s", $id);
        $stmt_holds->execute();
        $stmt_holds->close();
        
        // 3. Delete associated borrow records first to satisfy constraints
        $stmt_borrows = $conn->prepare("DELETE FROM issued_books WHERE student_id=?");
        $stmt_borrows->bind_param("s", $id);
        $stmt_borrows->execute();
        $stmt_borrows->close();
        
        // 4. Delete the parent student account row
        $stmt = $conn->prepare("DELETE FROM users WHERE student_id=?");
        $stmt->bind_param("s", $id);
        if($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
}
$conn->close();
?>
