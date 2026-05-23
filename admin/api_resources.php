<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['adminLoggedIn']) && !isset($_SESSION['userLoggedIn'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 1. Fetch Digital Resources
    $result = $conn->query("SELECT id, title, author, category as type, format, file_path as path, module FROM digital_resources");
    $resources = [];
    while($row = $result->fetch_assoc()) {
        $ext = strtoupper($row['format']);
        $row['icon'] = 'fa-file-pdf';
        if (in_array($ext, ['PPT', 'PPTX'])) $row['icon'] = 'fa-file-powerpoint';
        if (in_array($ext, ['MP4', 'MKV'])) $row['icon'] = 'fa-circle-play';
        $row['isDigital'] = true;
        $resources[] = $row;
    }

    // 2. Fetch Physical Books and merge them into the list
    $resultBooks = $conn->query("SELECT id, title, author, category as type, isbn, copies, status FROM books");
    while($row = $resultBooks->fetch_assoc()) {
        $row['isDigital'] = false;
        $row['format'] = 'Physical';
        $row['icon'] = 'fa-book';
        $row['path'] = '#'; // No file path for physical books
        $row['module'] = $row['type']; // Use category as module for books
        $resources[] = $row;
    }

    // Sort all by title
    usort($resources, function($a, $b) {
        return strcmp($a['title'], $b['title']);
    });

    echo json_encode($resources);
} 
else if ($method === 'POST') {
    if (!isset($_SESSION['adminLoggedIn'])) {
        echo json_encode(["error" => "Admin only"]);
        exit();
    }
    
    // Check if it's a JSON payload (for delete) or form data (for add)
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        if(isset($data['action']) && $data['action'] === 'delete') {
            $id = $data['id'];
            $stmt = $conn->prepare("DELETE FROM digital_resources WHERE id=?");
            $stmt->bind_param("i", $id);
            if($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "Database error"]);
            }
            $stmt->close();
        }
    } else {
        // Handle multipart/form-data for file upload
        if(isset($_POST['action']) && $_POST['action'] === 'add') {
            $title = $_POST['title'];
            $author = $_POST['author'];
            $category = $_POST['category'];
            $module = $_POST['module'];
            
            // Handle file upload
            if(isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/resources/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['file']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                    $path = 'uploads/resources/' . $fileName; // Relative path for frontend
                    $format = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'PDF';
                    
                    $stmt = $conn->prepare("INSERT INTO digital_resources (title, author, category, format, file_path, module) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $title, $author, $category, $format, $path, $module);
                    
                    if($stmt->execute()) {
                        echo json_encode(["success" => true]);
                    } else {
                        echo json_encode(["success" => false, "error" => "Database error"]);
                    }
                    $stmt->close();
                } else {
                    echo json_encode(["success" => false, "error" => "Failed to move uploaded file."]);
                }
            } else {
                 echo json_encode(["success" => false, "error" => "No file uploaded or upload error."]);
            }
        }
    }
}
$conn->close();
?>
