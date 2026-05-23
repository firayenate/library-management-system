<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn']) && !isset($_SESSION['userLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query("SELECT id, title, author, subject, isbn, category, copies, status FROM books");
    $books = [];
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    echo json_encode($books);
} 
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data['action']) && $data['action'] === 'add') {
        if(empty($data['title']) || empty($data['author'])) {
            echo json_encode(["success" => false, "error" => "Title and Author are required fields."]);
            exit();
        }
        $title = $data['title'];
        $author = $data['author'];
        $subject = $data['subject'];
        $isbn = $data['isbn'];
        $category = $data['category'];
        $copies = $data['copies'];
        
        $stmt = $conn->prepare("INSERT INTO books (title, author, subject, isbn, category, copies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $author, $subject, $isbn, $category, $copies);
        if($stmt->execute()) {
            echo json_encode(["success" => true, "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'delete') {
        $id = $data['id'];
        $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
        $stmt->bind_param("i", $id);
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
