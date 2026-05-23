<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $student_id = trim($_POST['student_id']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $year_of_study = $_POST['year_of_study'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Invalid email format.'); window.history.back();</script>");
    }
    if (strlen($password) < 6) {
        die("<script>alert('Password must be at least 6 characters long.'); window.history.back();</script>");
    }
    if ($password !== $confirm_password) {
        die("<script>alert('Passwords do not match.'); window.history.back();</script>");
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
    $stmt->bind_param("ss", $student_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        die("<script>alert('Student ID or Email already exists.'); window.history.back();</script>");
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, email, department, year_of_study, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $student_id, $full_name, $email, $department, $year_of_study, $password_hash);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='../login.html';</script>";
    } else {
        echo "<script>alert('Error during registration.'); window.history.back();</script>";
    }
    
    $stmt->close();
}
$conn->close();
?>
