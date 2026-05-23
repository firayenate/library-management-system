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
    if (isset($_SESSION['userLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
        // Student only sees their own
        $stmt = $conn->prepare("
            SELECT ib.id, ib.student_id, u.full_name as studentName, u.email, 
                   ib.borrow_date as borrowDate, ib.due_date as dueDate, ib.status, 
                   b.title as bookTitle 
            FROM issued_books ib 
            JOIN users u ON ib.student_id = u.student_id
            JOIN books b ON ib.book_id = b.id
            WHERE ib.student_id = ?
            ORDER BY ib.borrow_date DESC
        ");
        $stmt->bind_param("s", $_SESSION['student_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $issued[] = $row;
        }
        $stmt->close();
    } else {
        // Admin sees all
        $result = $conn->query("
            SELECT ib.id, ib.student_id, u.full_name as studentName, u.email, 
                   ib.borrow_date as borrowDate, ib.due_date as dueDate, ib.status, 
                   b.title as bookTitle 
            FROM issued_books ib 
            JOIN users u ON ib.student_id = u.student_id
            JOIN books b ON ib.book_id = b.id
            ORDER BY ib.borrow_date DESC
        ");
        while($row = $result->fetch_assoc()) {
            $issued[] = $row;
        }
    }
    echo json_encode($issued);
} 
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data['action']) && $data['action'] === 'issue') {
        if(empty($data['studentId']) || empty($data['bookId'])) {
            echo json_encode(["success" => false, "error" => "Student ID and Book ID are required."]);
            exit();
        }
        $studentId = $data['studentId'];
        $bookId = $data['bookId']; // Assuming frontend now sends bookId instead of title
        $borrowDate = $data['borrowDate'];
        $dueDate = $data['dueDate'];
        
        // Ensure book is available
        $check = $conn->prepare("SELECT status FROM books WHERE id = ?");
        $check->bind_param("i", $bookId);
        $check->execute();
        $res = $check->get_result();
        if($row = $res->fetch_assoc()) {
            if($row['status'] === 'Borrowed') {
                echo json_encode(["success" => false, "error" => "Book is already borrowed"]);
                exit();
            }
        } else {
            echo json_encode(["success" => false, "error" => "Book not found"]);
            exit();
        }
        $check->close();

        // Issue book
        $stmt = $conn->prepare("INSERT INTO issued_books (student_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $studentId, $bookId, $borrowDate, $dueDate);
        
        if($stmt->execute()) {
            // Update book status
            $upd = $conn->prepare("UPDATE books SET status = 'Borrowed' WHERE id = ?");
            $upd->bind_param("i", $bookId);
            $upd->execute();
            $upd->close();
            
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $stmt->close();
    }
    else if(isset($data['action']) && $data['action'] === 'return') {
        $issueId = $data['issueId'];
        
        // Find the book ID linked to this issue
        $check = $conn->prepare("SELECT book_id FROM issued_books WHERE id = ?");
        $check->bind_param("i", $issueId);
        $check->execute();
        $res = $check->get_result();
        $bookId = 0;
        if($row = $res->fetch_assoc()) {
            $bookId = $row['book_id'];
        }
        $check->close();

        if ($bookId > 0) {
            // Update issue status
            $stmt = $conn->prepare("UPDATE issued_books SET status = 'Returned' WHERE id = ?");
            $stmt->bind_param("i", $issueId);
            if($stmt->execute()) {
                // Update book status back to Available
                $upd = $conn->prepare("UPDATE books SET status = 'Available' WHERE id = ?");
                $upd->bind_param("i", $bookId);
                $upd->execute();
                $upd->close();
                
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "error" => "Issue record not found"]);
        }
    }
}
$conn->close();
?>
