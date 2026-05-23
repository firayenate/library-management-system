<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

// Self-healing database check: create support_tickets table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('Pending', 'Resolved') DEFAULT 'Pending',
        admin_reply TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch all support tickets
    $result = $conn->query("
        SELECT st.id, st.student_id, st.subject, st.message, st.status, st.admin_reply,
               DATE_FORMAT(st.created_at, '%b %d, %Y %h:%i %p') as date,
               COALESCE(u.full_name, CONCAT('Student (', st.student_id, ')')) as studentName
        FROM support_tickets st
        LEFT JOIN users u ON st.student_id = u.student_id
        ORDER BY st.created_at DESC
    ");
    
    $tickets = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
    }
    echo json_encode($tickets);
} 
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action']) && $data['action'] === 'reply') {
        $ticketId = $data['ticketId'];
        $reply = $data['reply'];
        
        $stmt = $conn->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'Resolved' WHERE id = ?");
        $stmt->bind_param("si", $reply, $ticketId);
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
}
$conn->close();
?>
