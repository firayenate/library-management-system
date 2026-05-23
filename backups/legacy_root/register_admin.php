<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $admin_id = trim($_POST['admin_id']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);
    $role = $_POST['role'];
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

    // Check if admin already exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE admin_id = ? OR email = ?");
    $stmt->bind_param("ss", $admin_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        die("<script>alert('Admin ID or Email already exists.'); window.history.back();</script>");
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO admins (admin_id, full_name, email, phone, position, role, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $admin_id, $full_name, $email, $phone, $position, $role, $password_hash);

    if ($stmt->execute()) {
        echo "<script>alert('Admin Registration successful!'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Error during registration.'); window.history.back();</script>";
    }
    
    $stmt->close();
}
$conn->close();
?>
