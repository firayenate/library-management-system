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
    $issued = [];
    $isStudent = (isset($_GET['role']) && $_GET['role'] === 'student') || (isset($_SESSION['userLoggedIn']) && !isset($_SESSION['adminLoggedIn']));
    if ($isStudent && isset($_SESSION['student_id'])) {
        // Student only sees their own
        $stmt = $conn->prepare("
            SELECT ib.id, ib.student_id, u.full_name as studentName, u.email, 
                   ib.borrow_date as borrowDate, ib.due_date as dueDate, ib.status, 
                   b.title as bookTitle, b.id as book_id 
            FROM issued_books ib 
            JOIN users u ON ib.student_id = u.student_id
            JOIN books b ON ib.book_id = b.id
            WHERE ib.student_id = ? 
              AND (ib.status != 'Returned' OR ib.return_timestamp IS NULL OR ib.return_timestamp >= NOW() - INTERVAL 10 MINUTE)
            ORDER BY (ib.status = 'Returned') ASC, ib.borrow_date DESC
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
                   b.title as bookTitle, b.id as book_id 
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
        
        // Ensure book is available and has copies
        $check = $conn->prepare("SELECT status, copies FROM books WHERE id = ?");
        $check->bind_param("i", $bookId);
        $check->execute();
        $res = $check->get_result();
        $currentCopies = 0;
        if($row = $res->fetch_assoc()) {
            if($row['copies'] <= 0 || $row['status'] === 'Borrowed' || $row['status'] === 'Unavailable') {
                echo json_encode(["success" => false, "error" => "Book is out of stock or currently unavailable"]);
                exit();
            }
            $currentCopies = (int)$row['copies'];
        } else {
            echo json_encode(["success" => false, "error" => "Book not found"]);
            exit();
        }
        $check->close();

        // Issue book
        $stmt = $conn->prepare("INSERT INTO issued_books (student_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $studentId, $bookId, $borrowDate, $dueDate);
        
        if($stmt->execute()) {
            // Decrement copies and update status if it hits 0
            $newCopies = $currentCopies - 1;
            $newStatus = ($newCopies > 0) ? 'Available' : 'Unavailable';
            
            $upd = $conn->prepare("UPDATE books SET copies = ?, status = ? WHERE id = ?");
            $upd->bind_param("isi", $newCopies, $newStatus, $bookId);
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
            $stmt = $conn->prepare("UPDATE issued_books SET status = 'Returned', return_timestamp = NOW() WHERE id = ?");
            $stmt->bind_param("i", $issueId);
            if($stmt->execute()) {
                // Find current copies to increment
                $chk = $conn->prepare("SELECT copies FROM books WHERE id = ?");
                $chk->bind_param("i", $bookId);
                $chk->execute();
                $res = $chk->get_result();
                $currentCopies = 0;
                if ($r = $res->fetch_assoc()) $currentCopies = (int)$r['copies'];
                $chk->close();
                
                $newCopies = $currentCopies + 1;

                // Check if there is a hold request for this book
                $holdCheck = $conn->prepare("SELECT id, student_id FROM book_holds WHERE book_id = ? AND status = 'In Queue' ORDER BY created_at ASC LIMIT 1");
                $holdCheck->bind_param("i", $bookId);
                $holdCheck->execute();
                $holdRes = $holdCheck->get_result();

                if ($holdRow = $holdRes->fetch_assoc()) {
                    $holdId = $holdRow['id'];
                    // Update this hold's status to 'Ready'
                    $updHold = $conn->prepare("UPDATE book_holds SET status = 'Ready' WHERE id = ?");
                    $updHold->bind_param("i", $holdId);
                    $updHold->execute();
                    $updHold->close();

                    // Update book: increment copies, but set status to 'Reserved' so other students can't claim it
                    $upd = $conn->prepare("UPDATE books SET status = 'Reserved', copies = ? WHERE id = ?");
                    $upd->bind_param("ii", $newCopies, $bookId);
                    $upd->execute();
                    $upd->close();
                } else {
                    // Update book status back to Available and increment copies normally
                    $upd = $conn->prepare("UPDATE books SET status = 'Available', copies = ? WHERE id = ?");
                    $upd->bind_param("ii", $newCopies, $bookId);
                    $upd->execute();
                    $upd->close();
                }
                
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
