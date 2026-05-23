<?php
session_start();
require_once '../db_connect.php';
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
            echo json_encode(["success" => false, "error" => "Database error occurred. Please contact support."]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'edit') {
        if(empty($data['id']) || empty($data['title']) || empty($data['author'])) {
            echo json_encode(["success" => false, "error" => "ID, Title and Author are required."]);
            exit();
        }
        $id = $data['id'];
        $title = $data['title'];
        $author = $data['author'];
        $subject = $data['subject'];
        $isbn = $data['isbn'];
        $category = $data['category'];
        $copies = $data['copies'];
        
        $currStmt = $conn->prepare("SELECT status FROM books WHERE id=?");
        $currStmt->bind_param("i", $id);
        $currStmt->execute();
        $currRes = $currStmt->get_result();
        $currStatus = 'Available';
        if ($row = $currRes->fetch_assoc()) {
            $currStatus = $row['status'];
        }
        $currStmt->close();

        $newStatus = $currStatus;
        if ($currStatus === 'Available' || $currStatus === 'Unavailable') {
            $newStatus = ($copies > 0) ? 'Available' : 'Unavailable';
        }
        
        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, subject=?, isbn=?, category=?, copies=?, status=? WHERE id=?");
        $stmt->bind_param("sssssisi", $title, $author, $subject, $isbn, $category, $copies, $newStatus, $id);
        if($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Database error occurred."]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'delete') {
        $id = $data['id'];
        
        // 1. Delete associated holds first to satisfy foreign key constraints
        $stmt_holds = $conn->prepare("DELETE FROM book_holds WHERE book_id=?");
        $stmt_holds->bind_param("i", $id);
        $stmt_holds->execute();
        $stmt_holds->close();
        
        // 2. Delete associated borrow records first to satisfy foreign key constraints
        $stmt_borrows = $conn->prepare("DELETE FROM issued_books WHERE book_id=?");
        $stmt_borrows->bind_param("i", $id);
        $stmt_borrows->execute();
        $stmt_borrows->close();
        
        // 3. Delete the parent book row
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
