<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Clear any existing session variables to prevent session pollution (e.g. admin vs student)
    session_unset();

    $login_type = $_POST['login_type'];
    $login_id = trim($_POST['login_id']);
    $password = $_POST['password'];

    if ($login_type === 'admin') {
        $stmt = $conn->prepare("SELECT id, password_hash, full_name FROM admins WHERE admin_id = ?");
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['adminLoggedIn'] = true;
                $_SESSION['admin_id'] = $login_id;
                $_SESSION['admin_name'] = $admin['full_name'];
                header("Location: admin/Admin.php");
                exit();
            } else {
                echo "<script>alert('Invalid password.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Admin ID not found.'); window.history.back();</script>";
        }
        $stmt->close();
    } else if ($login_type === 'student') {
        $stmt = $conn->prepare("SELECT id, password_hash, full_name FROM users WHERE student_id = ?");
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['userLoggedIn'] = true;
                $_SESSION['student_id'] = $login_id;
                $_SESSION['student_name'] = $user['full_name'];
                header("Location: user/user.php");
                exit();
            } else {
                echo "<script>alert('Invalid password.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Student ID not found.'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid login type.'); window.history.back();</script>";
    }
}
$conn->close();
?>
