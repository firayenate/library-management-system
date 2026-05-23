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
    if (isset($_GET['action']) && $_GET['action'] === 'holds') {
        $stmt = $conn->prepare("
            SELECT h.id, h.book_id, h.status, DATE_FORMAT(h.created_at, '%b %d, %Y') as date,
                   b.title, b.author
            FROM book_holds h
            JOIN books b ON h.book_id = b.id
            WHERE h.student_id = ?
            ORDER BY h.created_at DESC
        ");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $holds = [];
        while($row = $res->fetch_assoc()) {
            $holds[] = $row;
        }
        echo json_encode($holds);
        $stmt->close();
    }
} 
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data['action']) && $data['action'] === 'submitTicket') {
        $subject = $data['subject'];
        $message = $data['message'];
        
        $stmt = $conn->prepare("INSERT INTO support_tickets (student_id, subject, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $student_id, $subject, $message);
        if($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'placeHold') {
        $bookId = $data['bookId'];
        
        $check = $conn->prepare("SELECT id FROM book_holds WHERE student_id=? AND book_id=? AND status != 'Cancelled'");
        $check->bind_param("si", $student_id, $bookId);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(["success" => false, "error" => "You already have a hold on this book."]);
            $check->close();
            exit();
        }
        $check->close();
        
        $stmt = $conn->prepare("INSERT INTO book_holds (student_id, book_id) VALUES (?, ?)");
        $stmt->bind_param("si", $student_id, $bookId);
        if($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'cancelHold') {
        $holdId = $data['holdId'];
        
        $stmt = $conn->prepare("DELETE FROM book_holds WHERE id=? AND student_id=?");
        $stmt->bind_param("is", $holdId, $student_id);
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
