<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['userLoggedIn'])) {
    die("Please log in as a student first, then run this script to set up your test data.");
}

$student_id = $_SESSION['student_id'];

// 1. Check if "Atomic Habits" exists in books, if not insert it
$stmt = $conn->prepare("SELECT id FROM books WHERE title = 'Atomic Habits' LIMIT 1");
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $book = $res->fetch_assoc();
    $book_id = $book['id'];
} else {
    $insert_book = $conn->prepare("INSERT INTO books (title, author, subject, isbn, category, copies, status) VALUES ('Atomic Habits', 'James Clear', 'Self-Improvement', '9780735211292', 'Personal Development', 5, 'Available')");
    $insert_book->execute();
    $book_id = $insert_book->insert_id;
    $insert_book->close();
}
$stmt->close();

// 2. Check if this student already has a pending borrow for this book
$stmt = $conn->prepare("SELECT id FROM issued_books WHERE student_id = ? AND book_id = ? AND status = 'Pending' LIMIT 1");
$stmt->bind_param("si", $student_id, $book_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // Issue the book to the logged-in student
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $insert_issue = $conn->prepare("INSERT INTO issued_books (student_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'Pending')");
    $insert_issue->bind_param("siss", $student_id, $book_id, $borrow_date, $due_date);
    $insert_issue->execute();
    $insert_issue->close();
    
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 500px; margin-left: auto; margin-right: auto; background-color: white; border: 1px solid #eef2f5;'>";
    echo "<i class='fa-solid fa-circle-check' style='color: #10b981; font-size: 50px; margin-bottom: 20px;'></i>";
    echo "<h1 style='color: #0f766e;'>Success!</h1>";
    echo "<p style='color: #4b5563; font-size: 16px;'>Dynamic borrow record for <strong>Atomic Habits</strong> (Borrowed Status) successfully set up for student ID <strong>$student_id</strong>!</p>";
    echo "<a href='user.php' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background-color: #0f766e; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Return to Dashboard</a>";
    echo "</div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 500px; margin-left: auto; margin-right: auto; background-color: white; border: 1px solid #eef2f5;'>";
    echo "<h1 style='color: #0f766e;'>Already Set Up</h1>";
    echo "<p style='color: #4b5563; font-size: 16px;'>Student ID <strong>$student_id</strong> is already actively borrowing <strong>Atomic Habits</strong> dynamically in the database!</p>";
    echo "<a href='user.php' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background-color: #0f766e; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Return to Dashboard</a>";
    echo "</div>";
}

$stmt->close();
$conn->close();
?>
