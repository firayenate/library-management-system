<?php
session_start();
if (!isset($_SESSION['adminLoggedIn'])) {
    header("Location: ../login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="setting.css">
</head>
<body>
    <header class="settings-nav">
        <div class="brand">
            <img src="../img/wku_logo.jpg" alt="WKU logo">
            <h2>WKU</h2>
        </div>
        <a href="Admin.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </header>

    <main class="settings-page">
        <section class="settings-hero">
            <i class="fa-solid fa-gear"></i>
            <h1>Admin Settings</h1>
            <p>Update your admin profile, personal details, and password security.</p>
        </section>

        <section class="settings-card">
            <h3><i class="fa-solid fa-user-shield"></i> Profile Information</h3>
            <form id="settingsForm" onsubmit="saveAdminSettings(event)">
                <div class="profile-image-row">
                    <img src="../img/wku_logo.jpg" alt="Admin profile preview" id="settingsProfilePreview">
                    <div>
                        <label for="settingsProfileImage" class="image-btn"><i class="fa-solid fa-image"></i> Change Profile Image</label>
                        <input type="file" id="settingsProfileImage" accept="image/*" onchange="uploadProfileImage(event)" style="display:none;">
                        <button type="button" class="secondary-btn" onclick="removeProfileImage()">Remove Image</button>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="settingsFullName">Full Name</label>
                        <input type="text" id="settingsFullName" required placeholder="John Doe">
                    </div>
                    <div class="field">
                        <label for="settingsAdminId">Admin ID</label>
                        <input type="text" id="settingsAdminId" disabled required style="background: #f1f5f9; cursor: not-allowed; border-color: #cbd5e1;">
                    </div>
                    <div class="field">
                        <label for="settingsEmail">Email Address</label>
                        <input type="email" id="settingsEmail" required placeholder="admin@email.com">
                    </div>
                    <div class="field">
                        <label for="settingsPhone">Phone Number</label>
                        <input type="tel" id="settingsPhone" placeholder="0912345678">
                    </div>
                    <div class="field">
                        <label for="settingsPosition">Position</label>
                        <input type="text" id="settingsPosition" placeholder="Librarian / Director">
                    </div>
                    <div class="field">
                        <label for="settingsRole">System Role</label>
                        <select id="settingsRole">
                            <option value="admin">Admin</option>
                            <option value="super-admin">Super Admin</option>
                        </select>
                    </div>
                </div>

                <h3><i class="fa-solid fa-lock"></i> Security</h3>
                <div class="form-grid">
                    <div class="field">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" placeholder="Required only when changing password">
                    </div>
                    <div class="field">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" minlength="6" placeholder="At least 6 characters">
                    </div>
                    <div class="field">
                        <label for="confirmNewPassword">Confirm New Password</label>
                        <input type="password" id="confirmNewPassword" minlength="6">
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="save-btn"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
                    <button type="button" class="secondary-btn" onclick="loadAdminSettings()">Cancel</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Sleek Toast Notification Container -->
    <div id="settingsToastContainer" style="position: fixed; top: 25px; right: 25px; z-index: 10000; pointer-events: none;"></div>

    <script src="setting.js"></script>
</body>
</html>
