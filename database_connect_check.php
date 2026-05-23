<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

$host = "localhost";
$username = "root";
$password = "";
$database = "librinet_db";

$conn_status = "PENDING";
$conn_msg = "";
$db_created = false;
$conn = null;

// Step 1: Connect to MySQL Server
try {
    $temp_conn = new mysqli($host, $username, $password);
    if ($temp_conn->connect_error) {
        $conn_status = "FAILED";
        $conn_msg = "Could not connect to MySQL server. Please make sure MySQL is started in your XAMPP Control Panel. Error: " . $temp_conn->connect_error;
    } else {
        // Step 2: Create Database if not exists
        if ($temp_conn->query("CREATE DATABASE IF NOT EXISTS $database")) {
            $db_created = true;
        }
        $temp_conn->close();

        // Step 3: Connect to the Specific Database
        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            $conn_status = "FAILED";
            $conn_msg = "Created database but failed connection: " . $conn->connect_error;
        } else {
            $conn_status = "SUCCESS";
            $conn_msg = "Connected successfully to MySQL and database '$database' is fully active!";
        }
    }
} catch (Exception $e) {
    $conn_status = "FAILED";
    $conn_msg = "Database Exception: " . $e->getMessage();
}

$schema_results = [];
if ($conn_status === "SUCCESS") {
    // Step 4: Run SQL schema files in order
    $sql_files = [
        'database/database_setup.sql' => 'Core Account System (Users/Admins)',
        'database/database_update.sql' => 'Library Catalog & Issue Records',
        'database/database_resources.sql' => 'Digital Library Resources Grid',
        'database/database_user_updates.sql' => 'Waitlists & Support Tickets'
    ];

    foreach ($sql_files as $file_path => $description) {
        if (file_exists($file_path)) {
            $queries = file_get_contents($file_path);
            
            // Execute multi query
            if ($conn->multi_query($queries)) {
                do {
                    if ($res = $conn->store_result()) {
                        $res->free();
                    }
                } while ($conn->next_result());
                $schema_results[$description] = [
                    "status" => "SUCCESS",
                    "msg" => "Table schemas loaded and synchronized successfully."
                ];
            } else {
                $schema_results[$description] = [
                    "status" => "WARNING",
                    "msg" => "Partial import warning or duplicate check: " . $conn->error
                ];
            }
        } else {
            $schema_results[$description] = [
                "status" => "FAILED",
                "msg" => "SQL file not found at '$file_path'."
            ];
        }
    }

    // Step 5: Automatically check and patch missing columns (e.g. phone, profile_picture)
    // Check & patch phone
    $check_phone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($check_phone && $check_phone->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
        $schema_results["Users Profile Patch (Phone Column)"] = [
            "status" => "SUCCESS",
            "msg" => "Added missing phone column to users table."
        ];
    }

    // Check & patch profile_picture
    $check_pic = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($check_pic && $check_pic->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER created_at");
        $schema_results["Users Profile Patch (Profile Image Column)"] = [
            "status" => "SUCCESS",
            "msg" => "Added missing profile_picture column to users table."
        ];
    }

    // Check & patch profile_picture for admins
    $check_admin_pic = $conn->query("SHOW COLUMNS FROM admins LIKE 'profile_picture'");
    if ($check_admin_pic && $check_admin_pic->num_rows == 0) {
        $conn->query("ALTER TABLE admins ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER created_at");
        $schema_results["Admins Profile Patch (Profile Image Column)"] = [
            "status" => "SUCCESS",
            "msg" => "Added missing profile_picture column to admins table."
        ];
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connector - LibriNet</title>
    <script src="https://kit.fontawesome.com/93f4cc41cc.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 50% 50%, #0f172a 0%, #020617 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .connector-card {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 40px;
            width: 100%;
            max-width: 650px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 40px rgba(15, 118, 110, 0.1);
            text-align: center;
            animation: fadeInScale 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        .status-header {
            margin-bottom: 30px;
        }
        .pulse-shield {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            position: relative;
        }
        .pulse-shield.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.2);
            animation: pulseEmerald 2s infinite;
        }
        .pulse-shield.failed {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.2);
            animation: pulseRed 2s infinite;
        }
        @keyframes pulseEmerald {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        @keyframes pulseRed {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p.subtitle {
            font-size: 15px;
            color: #94a3b8;
            margin-bottom: 25px;
            font-family: 'Inter', sans-serif;
        }
        .connection-summary {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
            font-family: 'Inter', sans-serif;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.08);
            padding-top: 10px;
        }
        .label {
            color: #64748b;
            font-weight: 500;
        }
        .value {
            color: #e2e8f0;
            font-weight: 600;
        }
        .value.active {
            color: #10b981;
        }
        .value.error {
            color: #ef4444;
        }
        .schema-check-list {
            text-align: left;
            margin-bottom: 30px;
            font-family: 'Inter', sans-serif;
        }
        .schema-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f766e;
            font-weight: 700;
            margin-bottom: 12px;
            border-bottom: 1px solid rgba(15, 118, 110, 0.2);
            padding-bottom: 6px;
        }
        .schema-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .schema-item:last-child {
            border-bottom: none;
        }
        .schema-icon {
            font-size: 16px;
            margin-top: 2px;
        }
        .schema-icon.success { color: #10b981; }
        .schema-icon.warning { color: #f59e0b; }
        .schema-icon.failed { color: #ef4444; }
        .schema-details {
            flex: 1;
        }
        .schema-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 2px;
        }
        .schema-details p {
            font-size: 12px;
            color: #64748b;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        .btn {
            flex: 1;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            border: 1px solid #14b8a6;
            color: white;
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 118, 110, 0.4);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .error-log-box {
            background: rgba(239, 68, 68, 0.06);
            border: 1px dashed rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            padding: 15px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
            text-align: left;
            margin-bottom: 25px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="connector-card">
        <div class="status-header">
            <?php if ($conn_status === "SUCCESS"): ?>
                <div class="pulse-shield success">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1>Database Synced Perfectly</h1>
                <p class="subtitle">Your LibriNet digital library is online and fully configured.</p>
            <?php else: ?>
                <div class="pulse-shield failed">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h1>Connection Interrupted</h1>
                <p class="subtitle">Unable to interface with the local database schema.</p>
            <?php endif; ?>
        </div>

        <div class="connection-summary">
            <div class="summary-row">
                <span class="label">Database Host</span>
                <span class="value"><?php echo htmlspecialchars($host); ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Database Name</span>
                <span class="value"><?php echo htmlspecialchars($database); ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Connection Status</span>
                <span class="value <?php echo $conn_status === "SUCCESS" ? "active" : "error"; ?>">
                    <?php echo $conn_status === "SUCCESS" ? "ACTIVE" : "OFFLINE"; ?>
                </span>
            </div>
        </div>

        <?php if ($conn_status === "FAILED"): ?>
            <div class="error-log-box">
                <strong>[Diagnostic Log]</strong><br>
                <?php echo htmlspecialchars($conn_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($schema_results)): ?>
            <div class="schema-check-list">
                <div class="schema-title">Database Provisioning Modules</div>
                <?php foreach ($schema_results as $desc => $res): ?>
                    <div class="schema-item">
                        <div class="schema-icon <?php echo strtolower($res['status']); ?>">
                            <?php if ($res['status'] === "SUCCESS"): ?>
                                <i class="fa-solid fa-circle-check"></i>
                            <?php elseif ($res['status'] === "WARNING"): ?>
                                <i class="fa-solid fa-circle-exclamation"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle-xmark"></i>
                            <?php endif; ?>
                        </div>
                        <div class="schema-details">
                            <h4><?php echo htmlspecialchars($desc); ?></h4>
                            <p><?php echo htmlspecialchars($res['msg']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="login.html" class="btn btn-primary">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Proceed to Login
            </a>
            <a href="javascript:location.reload();" class="btn btn-secondary">
                <i class="fa-solid fa-rotate"></i> Retest Connection
            </a>
        </div>
    </div>
</body>
</html>
