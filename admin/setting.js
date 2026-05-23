async function loadAdminSettings() {
    try {
        const res = await fetch('api_admin_profile.php');
        const data = await res.json();
        
        if (data.error) {
            showSettingsToast(data.error, 'error');
            return;
        }

        document.getElementById('settingsFullName').value = data.full_name || '';
        document.getElementById('settingsAdminId').value = data.admin_id || '';
        document.getElementById('settingsEmail').value = data.email || '';
        document.getElementById('settingsPhone').value = data.phone || '';
        document.getElementById('settingsPosition').value = data.position || '';
        document.getElementById('settingsRole').value = data.role || 'admin';
        
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmNewPassword').value = '';

        const previewImg = document.getElementById('settingsProfilePreview');
        if (data.profile_picture) {
            previewImg.src = '../' + data.profile_picture;
        } else {
            previewImg.src = '../img/wku_logo.jpg';
        }
    } catch (err) {
        console.error("Error loading settings:", err);
        showSettingsToast('Failed to connect to the backend server.', 'error');
    }
}

async function uploadProfileImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('profile_picture', file);

    try {
        const res = await fetch('upload_profile.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            showSettingsToast('Profile photo uploaded and processed successfully!', 'success');
            document.getElementById('settingsProfilePreview').src = '../' + data.profile_picture;
        } else {
            showSettingsToast(data.error || 'Failed to upload photo.', 'error');
        }
    } catch (err) {
        console.error("Error uploading image:", err);
        showSettingsToast('Network error during image upload.', 'error');
    }
}

async function removeProfileImage() {
    try {
        const res = await fetch('api_admin_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'removePhoto' })
        });
        const data = await res.json();
        
        if (data.success) {
            showSettingsToast('Profile image cleared successfully.', 'success');
            document.getElementById('settingsProfilePreview').src = '../img/wku_logo.jpg';
        } else {
            showSettingsToast(data.error || 'Failed to clear profile image.', 'error');
        }
    } catch (err) {
        console.error("Error removing image:", err);
        showSettingsToast('Network error during deletion.', 'error');
    }
}

async function saveAdminSettings(event) {
    event.preventDefault();

    const fullName = document.getElementById('settingsFullName').value.trim();
    const email = document.getElementById('settingsEmail').value.trim();
    const phone = document.getElementById('settingsPhone').value.trim();
    const position = document.getElementById('settingsPosition').value.trim();
    const role = document.getElementById('settingsRole').value;

    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmNewPassword = document.getElementById('confirmNewPassword').value;

    // 1. Password change validation & processing
    if (newPassword || confirmNewPassword || currentPassword) {
        if (!currentPassword) {
            showSettingsToast('Current password is required to update security credentials.', 'error');
            return;
        }
        if (newPassword !== confirmNewPassword) {
            showSettingsToast('New passwords do not match.', 'error');
            return;
        }
        if (newPassword.length < 6) {
            showSettingsToast('New password must contain at least 6 characters.', 'error');
            return;
        }

        try {
            const passRes = await fetch('api_admin_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'changePassword',
                    currentPassword: currentPassword,
                    newPassword: newPassword
                })
            });
            const passData = await passRes.json();
            if (!passData.success) {
                showSettingsToast(passData.error || 'Failed to change password.', 'error');
                return;
            }
        } catch (err) {
            console.error("Error updating password:", err);
            showSettingsToast('Password update encountered a connection error.', 'error');
            return;
        }
    }

    // 2. Profile Details update
    try {
        const res = await fetch('api_admin_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'updateProfile',
                fullName: fullName,
                email: email,
                phone: phone,
                position: position,
                role: role
            })
        });
        const data = await res.json();
        
        if (data.success) {
            showSettingsToast('Admin profile database settings updated!', 'success');
            // Graceful redirection to dashboard
            setTimeout(() => {
                window.location.href = 'Admin.php';
            }, 1500);
        } else {
            showSettingsToast(data.error || 'Failed to update profile details.', 'error');
        }
    } catch (err) {
        console.error("Error saving settings:", err);
        showSettingsToast('Connection error while saving administrative records.', 'error');
    }
}

function showSettingsToast(message, type = 'success') {
    const container = document.getElementById('settingsToastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${type === 'success' ? 'rgba(6, 78, 59, 0.95)' : 'rgba(127, 29, 29, 0.95)'};
        backdrop-filter: blur(8px);
        border-left: 4px solid ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        border-radius: 12px;
        padding: 16px 20px;
        width: 320px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 13.5px;
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: auto;
    `;
    
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const iconColor = type === 'success' ? '#34d399' : '#fca5a5';
    
    toast.innerHTML = `
        <i class="fa-solid ${icon}" style="color: ${iconColor}; font-size: 18px;"></i>
        <div style="flex: 1;">
            <strong>${type === 'success' ? 'Success' : 'Error'}</strong>
            <div style="margin-top: 2px; font-size: 12px; color: rgba(255,255,255,0.8);">${message}</div>
        </div>
    `;
    
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 50);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Kickstart settings load
loadAdminSettings();
