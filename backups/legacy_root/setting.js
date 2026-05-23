let selectedProfileImage = localStorage.getItem('adminProfileImage') || '';

function loadAdminSettings() {
    document.getElementById('settingsAdminId').value = localStorage.getItem('adminId') || 'ADM1234';
    document.getElementById('settingsEmail').value = localStorage.getItem('adminEmail') || '';
    document.getElementById('settingsPhone').value = localStorage.getItem('adminPhone') || '';
    document.getElementById('settingsRole').value = localStorage.getItem('adminRole') || 'Admin';
    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmNewPassword').value = '';

    selectedProfileImage = localStorage.getItem('adminProfileImage') || '';
    document.getElementById('settingsProfilePreview').src = selectedProfileImage || 'img/logo.png';
}

function previewProfileImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function() {
        selectedProfileImage = reader.result;
        document.getElementById('settingsProfilePreview').src = reader.result;
    };
    reader.readAsDataURL(file);
}

function removeProfileImage() {
    selectedProfileImage = '';
    document.getElementById('settingsProfilePreview').src = 'img/logo.png';
}

function saveAdminSettings(event) {
    event.preventDefault();

    const savedPassword = localStorage.getItem('adminPassword');
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmNewPassword = document.getElementById('confirmNewPassword').value;

    if (newPassword || confirmNewPassword) {
        if (savedPassword && currentPassword !== savedPassword) {
            alert('Current password is not correct.');
            return;
        }

        if (newPassword !== confirmNewPassword) {
            alert('New passwords do not match.');
            return;
        }

        if (newPassword.length < 6) {
            alert('New password must be at least 6 characters.');
            return;
        }

        localStorage.setItem('adminPassword', newPassword);
    }

    localStorage.setItem('adminId', document.getElementById('settingsAdminId').value.trim());
    localStorage.setItem('adminEmail', document.getElementById('settingsEmail').value.trim());
    localStorage.setItem('adminPhone', document.getElementById('settingsPhone').value.trim());
    localStorage.setItem('adminRole', document.getElementById('settingsRole').value);

    if (selectedProfileImage) {
        localStorage.setItem('adminProfileImage', selectedProfileImage);
    } else {
        localStorage.removeItem('adminProfileImage');
    }

    alert('Admin settings updated.');
    window.location.href = 'Admin.html';
}

loadAdminSettings();
