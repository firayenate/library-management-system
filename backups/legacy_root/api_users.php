<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query("SELECT student_id as id, full_name as name, email, department, year_of_study as year, 'Active' as status, 0 as borrowed, 0 as returned, 0 as pending FROM users");
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
        $id = $data['id'];
        $name = $data['name'];
        $email = $data['email'];
        $dept = $data['department'];
        $year = $data['year'];
        
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, department=?, year_of_study=? WHERE student_id=?");
        $stmt->bind_param("sssss", $name, $email, $dept, $year, $id);
        if($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'delete') {
        $id = $data['id'];
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
