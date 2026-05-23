<?php
session_start();
require_once '../db_connect.php';
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
    } else if (isset($_GET['action']) && $_GET['action'] === 'notifications') {
        $stmt = $conn->prepare("
            SELECT h.id as hold_id, h.book_id, b.title, b.author
            FROM book_holds h
            JOIN books b ON h.book_id = b.id
            WHERE h.student_id = ? AND h.status = 'Ready'
            ORDER BY h.created_at DESC
        ");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $notifs = [];
        while($row = $res->fetch_assoc()) {
            $notifs[] = $row;
        }
        echo json_encode($notifs);
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
    else if(isset($data['action']) && $data['action'] === 'borrowHold') {
        $holdId = $data['holdId'];
        
        // 1. Verify this hold belongs to the logged-in student and is status 'Ready'
        $stmt = $conn->prepare("SELECT book_id FROM book_holds WHERE id = ? AND student_id = ? AND status = 'Ready'");
        $stmt->bind_param("is", $holdId, $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($holdRow = $res->fetch_assoc()) {
            $bookId = $holdRow['book_id'];
            $stmt->close();
            
            // 2. Fetch current copies of the book
            $chk = $conn->prepare("SELECT copies FROM books WHERE id = ?");
            $chk->bind_param("i", $bookId);
            $chk->execute();
            $chkRes = $chk->get_result();
            $copies = 0;
            if ($r = $chkRes->fetch_assoc()) {
                $copies = (int)$r['copies'];
            }
            $chk->close();
            
            if ($copies <= 0) {
                echo json_encode(["success" => false, "error" => "This book is currently out of stock."]);
                exit();
            }
            
            // 3. Delete the hold record since it has been fulfilled
            $del = $conn->prepare("DELETE FROM book_holds WHERE id = ?");
            $del->bind_param("i", $holdId);
            $del->execute();
            $del->close();
            
            // 4. Create borrowing record in issued_books
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days'));
            $ins = $conn->prepare("INSERT INTO issued_books (student_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'Pending')");
            $ins->bind_param("siss", $student_id, $bookId, $borrowDate, $dueDate);
            
            if ($ins->execute()) {
                $newCopies = $copies - 1;
                
                // 5. Update book copies and status
                if ($newCopies > 0) {
                    // Check if there is ANOTHER hold in queue
                    $nextCheck = $conn->prepare("SELECT id FROM book_holds WHERE book_id = ? AND status = 'In Queue' ORDER BY created_at ASC LIMIT 1");
                    $nextCheck->bind_param("i", $bookId);
                    $nextCheck->execute();
                    $nextRes = $nextCheck->get_result();
                    
                    if ($nextRow = $nextRes->fetch_assoc()) {
                        // There's another hold, so make it Ready and keep the book Reserved
                        $nextHoldId = $nextRow['id'];
                        $updNext = $conn->prepare("UPDATE book_holds SET status = 'Ready' WHERE id = ?");
                        $updNext->bind_param("i", $nextHoldId);
                        $updNext->execute();
                        $updNext->close();
                        
                        $updBook = $conn->prepare("UPDATE books SET copies = ?, status = 'Reserved' WHERE id = ?");
                        $updBook->bind_param("ii", $newCopies, $bookId);
                        $updBook->execute();
                        $updBook->close();
                    } else {
                        // No other holds, make it Available
                        $updBook = $conn->prepare("UPDATE books SET copies = ?, status = 'Available' WHERE id = ?");
                        $updBook->bind_param("ii", $newCopies, $bookId);
                        $updBook->execute();
                        $updBook->close();
                    }
                    $nextCheck->close();
                } else {
                    // Out of stock
                    $updBook = $conn->prepare("UPDATE books SET copies = ?, status = 'Borrowed' WHERE id = ?");
                    $updBook->bind_param("ii", $newCopies, $bookId);
                    $updBook->execute();
                    $updBook->close();
                }
                
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to create borrow record."]);
            }
            $ins->close();
        } else {
            echo json_encode(["success" => false, "error" => "Hold not found or not ready yet."]);
            $stmt->close();
        }
    }
}
$conn->close();
?>
