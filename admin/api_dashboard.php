<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$stats = [
    "totalBooks" => 0,
    "totalStudents" => 0,
    "issuedBooks" => 0,
    "pendingReturns" => 0
];

// Total Books
$result = $conn->query("SELECT SUM(copies) as count FROM books");
if ($row = $result->fetch_assoc()) $stats['totalBooks'] = (int)$row['count'] ?: 0;

// Total Students
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($row = $result->fetch_assoc()) $stats['totalStudents'] = (int)$row['count'];

// Issued Books (Currently active issues)
$result = $conn->query("SELECT COUNT(*) as count FROM issued_books WHERE status = 'Pending'");
if ($row = $result->fetch_assoc()) $stats['issuedBooks'] = (int)$row['count'];

// Pending Returns
$result = $conn->query("SELECT COUNT(*) as count FROM issued_books WHERE status = 'Pending'");
if ($row = $result->fetch_assoc()) $stats['pendingReturns'] = (int)$row['count'];

// Books by Category
$categoryCounts = [];
$catRes = $conn->query("SELECT category, SUM(copies) as count FROM books GROUP BY category");
while ($row = $catRes->fetch_assoc()) {
    $cat = $row['category'] ? $row['category'] : 'Uncategorized';
    $categoryCounts[$cat] = (int)$row['count'];
}
$stats['categoryCounts'] = $categoryCounts;

echo json_encode($stats);
$conn->close();
?>
