// --- Global State ---
let currentUser = {};
let books = [];
let issuedBooks = [];
let userHolds = [];
let activityLog = [];
let userFavorites = [];

// Hardcoded Resources for Digital Content (Now Dynamic)
let resources = [];

async function fetchUserResources() {
    try {
        const res = await fetch('../admin/api_resources.php');
        resources = await res.json();
        renderResources(resources);
    } catch (err) {
        console.error("Error fetching resources:", err);
    }
}
fetchUserResources();

document.addEventListener('DOMContentLoaded', async () => {
    await fetchDashboardData();
    refreshAllUI();
});

async function fetchDashboardData() {
    try {
        // Fetch Profile
        let res = await fetch('api_user_profile.php');
        currentUser = await res.json();

        // Fetch Books Catalog
        res = await fetch('../admin/api_books.php');
        books = await res.json();

        // Fetch User's Borrow History
        res = await fetch('../admin/api_borrow.php?role=student');
        issuedBooks = await res.json();

        // Fetch User's Holds
        res = await fetch('api_user_actions.php?action=holds');
        userHolds = await res.json();

        // Generate Activity Log
        activityLog = [];
        issuedBooks.forEach(ib => {
            activityLog.push({
                type: ib.status === 'Pending' ? 'Borrowed' : 'Returned',
                book: ib.bookTitle,
                author: 'Catalog Book',
                date: ib.borrowDate,
                due: ib.status === 'Pending' ? ib.dueDate : '—',
                icon: ib.status === 'Pending' ? 'fa-book-open' : 'fa-rotate-left',
                statusClass: ib.status === 'Pending' ? 'borrowed' : 'returned'
            });
        });
        userHolds.forEach(uh => {
            activityLog.push({
                type: 'On Hold',
                book: uh.title,
                author: uh.author,
                date: uh.date,
                due: '—',
                icon: 'fa-bookmark',
                statusClass: 'hold'
            });
        });

        // Sort activity log by date (simple string sort for demo, should be proper date parsing)
        activityLog.sort((a, b) => new Date(b.date) - new Date(a.date));

        // Load favorites from localStorage
        if (currentUser && currentUser.id) {
            const favKey = `librinet_favs_${currentUser.id}`;
            let rawFavs = JSON.parse(localStorage.getItem(favKey) || '[]');
            // Migrate legacy numeric IDs to physical book prefix "p_", and keep string IDs
            userFavorites = rawFavs.map(id => {
                if (typeof id === 'number' || (!isNaN(id) && !id.toString().includes('_'))) {
                    return 'p_' + id;
                }
                return id;
            }).filter(id => typeof id === 'string' && (id.startsWith('p_') || id.startsWith('d_')));
            // Save migrated back
            localStorage.setItem(favKey, JSON.stringify(userFavorites));
        } else {
            userFavorites = [];
        }

        // Setup Stats
        currentUser.borrowed = issuedBooks.filter(ib => ib.status === 'Pending').length;
        currentUser.favorites = userFavorites.length;
        currentUser.holds = userHolds.length;

    } catch (e) {
        console.error("Failed to fetch dashboard data:", e);
    }
}

function refreshAllUI() {
    loadUserProfile();
    updateStats();
    renderCatalogQuickFilters();
    renderResourceQuickFilters();
    searchBook('Catalog'); // Default view
    searchResources();
    updateHoldDisplay();
    renderRecentActivity();
    renderBooksBySubject();
    renderFavoritesGrid();
    renderFullHistory();
    fetchHoldsNotifications(); // Fetch and render priority hold notifications
}

async function fetchHoldsNotifications() {
    try {
        const res = await fetch('api_user_actions.php?action=notifications');
        const notifs = await res.json();
        renderHoldsNotifications(notifs);
    } catch (err) {
        console.error("Error fetching hold notifications:", err);
    }
}

function renderHoldsNotifications(notifs) {
    // 1. Manage Dynamic Badge Indicators in Navbar/Sidebar
    const sidebarBadge = document.getElementById('sidebarNotifBadge');
    const cardBadge = document.getElementById('cardNotifBadge');
    
    const count = (notifs && notifs.length) || 0;
    
    if (count > 0) {
        if (sidebarBadge) {
            sidebarBadge.textContent = count;
            sidebarBadge.style.display = 'inline-flex';
        }
        if (cardBadge) {
            cardBadge.textContent = count;
            cardBadge.style.display = 'flex';
        }
    } else {
        if (sidebarBadge) sidebarBadge.style.display = 'none';
        if (cardBadge) cardBadge.style.display = 'none';
    }

    // 2. Manage Dynamic Profile Notifications Tab list
    const profileNotifList = document.getElementById('profileNotificationsList');
    if (profileNotifList) {
        let listHtml = '';
        if (count > 0) {
            listHtml += notifs.map(n => `
                <div class="notification-item unread" style="display: flex; justify-content: space-between; align-items: center; gap: 15px; border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.03); padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: flex-start; gap: 15px; width: 100%;">
                        <i class="fa-solid fa-bell" style="color: #ef4444; font-size: 20px; margin-top: 2px;"></i>
                        <div class="notif-content" style="flex: 1;">
                            <p style="font-weight: 700; font-size: 14px; margin-bottom: 2px; color: #f8fafc;">Book Hold Ready: "${n.title}"</p>
                            <span style="color: #94a3b8; font-size: 13px; display: block; margin-bottom: 10px;">The book by ${n.author} is now available. Claim your copy now.</span>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="borrowHoldBook(${n.hold_id}, ${n.book_id}, this)" style="background: #0f766e; color: #ffffff; border: none; padding: 8px 16px; border-radius: 30px; font-weight: 600; cursor: pointer; font-size: 12px; transition: all 0.2s;">
                                    <i class="fa-solid fa-book-reader"></i> Borrow Now
                                </button>
                                <button onclick="cancelHoldBook(${n.hold_id}, this)" style="background: transparent; color: #94a3b8; border: 1px solid rgba(255,255,255,0.15); padding: 7px 16px; border-radius: 30px; cursor: pointer; font-size: 12px; transition: all 0.2s;">
                                    Cancel Hold
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Add welcome notification
        listHtml += `
            <div class="notification-item" style="display: flex; align-items: flex-start; gap: 15px; padding: 15px; border-radius: 8px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 10px;">
                <i class="fa-solid fa-circle-info" style="color: #3b82f6; font-size: 20px; margin-top: 2px;"></i>
                <div class="notif-content">
                    <p style="font-weight: 600; font-size: 14px; color: #f8fafc; margin-bottom: 2px;">Welcome to WKU!</p>
                    <span style="color: #94a3b8; font-size: 13px;">Your dynamic student library account is fully active. Place holds, borrow catalog titles, and manage support tickets instantly.</span>
                </div>
            </div>
        `;
        
        profileNotifList.innerHTML = listHtml;
    }

    // 3. Manage Floating Header Alert Banner
    const container = document.getElementById('notificationAlertBanner');
    if (!container) return;

    if (count === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = notifs.map(n => `
        <div class="notification-alert-card" id="hold-notif-card-${n.hold_id}">
            <div class="notification-alert-left">
                <div class="notification-alert-icon">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div class="notification-alert-info">
                    <h4>Book Hold Available!</h4>
                    <p>The book <strong>"${n.title}"</strong> by ${n.author} is now ready for you! Claim your checkout below.</p>
                </div>
            </div>
            <div class="notification-alert-actions">
                <button class="notification-btn-borrow" onclick="borrowHoldBook(${n.hold_id}, ${n.book_id}, this)">
                    <i class="fa-solid fa-book-reader"></i> Borrow Now
                </button>
                <button class="notification-btn-cancel" onclick="cancelHoldBook(${n.hold_id}, this)">
                    Cancel Hold
                </button>
            </div>
        </div>
    `).join('');
}

async function borrowHoldBook(holdId, bookId, btnEl) {
    if (btnEl) btnEl.disabled = true;
    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'borrowHold', holdId: holdId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Book borrowed successfully! Pick it up at the library counter.', 'success');
            
            // Animate card slide out
            const card = document.getElementById(`hold-notif-card-${holdId}`);
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.transform = 'translateY(-20px)';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 400);
            }
            
            // Reload all dashboard stats, histories, lists, catalogs
            await fetchDashboardData();
            refreshAllUI();
        } else {
            showToast(data.error || 'Failed to borrow book.', 'error');
            if (btnEl) btnEl.disabled = false;
        }
    } catch (err) {
        console.error(err);
        showToast('Error processing borrow request.', 'error');
        if (btnEl) btnEl.disabled = false;
    }
}

async function cancelHoldBook(holdId, btnEl) {
    if (btnEl) btnEl.disabled = true;
    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cancelHold', holdId: holdId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Hold cancelled successfully.', 'success');
            
            const card = document.getElementById(`hold-notif-card-${holdId}`);
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.transform = 'translateY(-20px)';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 400);
            }
            
            await fetchDashboardData();
            refreshAllUI();
        } else {
            showToast(data.error || 'Failed to cancel hold.', 'error');
            if (btnEl) btnEl.disabled = false;
        }
    } catch (err) {
        console.error(err);
        showToast('Error processing cancel hold request.', 'error');
        if (btnEl) btnEl.disabled = false;
    }
}

function renderBooksBySubject() {
    const catContainer = document.getElementById('categoryBreakdownContainer');
    if (!catContainer) return;

    let categoryCounts = {};
    books.forEach(b => {
        const cat = b.category || 'Uncategorized';
        const copies = parseInt(b.copies) || 1;
        categoryCounts[cat] = (categoryCounts[cat] || 0) + copies;
    });

    let catHtml = '';
    for (const [cat, count] of Object.entries(categoryCounts)) {
        catHtml += `
            <div class="category-pill" onclick="filterCatalog('${cat}')" style="cursor: pointer; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); padding: 10px 20px; border-radius: 50px; color: white; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.transform='scale(1)'">
                <span style="font-weight: bold;">${cat}</span>
                <span style="background: #f0a500; color: #111; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 800;">${count}</span>
            </div>
        `;
    }
    if (catHtml === '') catHtml = '<div style="color: #999;">No books categorized yet.</div>';
    catContainer.innerHTML = catHtml;
}

function loadUserProfile() {
    if (currentUser.error) return; // Not logged in

    const safeSet = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };

    safeSet('userNameDisplay', currentUser.name);
    
    // Extract first name dynamically for the premium homepage greeting
    const firstName = (currentUser.name || 'User').trim().split(' ')[0];
    safeSet('welcomeUserName', firstName);
    safeSet('userEmailDisplay', currentUser.email);
    safeSet('userIdDisplay', currentUser.id || 'N/A');
    safeSet('membershipType', 'Student Member');

    const memberStatus = document.getElementById('memberStatusBtn');
    if (memberStatus) {
        memberStatus.textContent = 'Active Member';
    }

    // Update top-level card phone number dynamically
    const cardPhone = document.getElementById('userPhoneDisplay');
    if (cardPhone) {
        cardPhone.innerHTML = `<i class="fa-solid fa-phone"></i> ${currentUser.phone || 'N/A'}`;
    }

    // Format and update membership join date dynamically
    const joinDateEl = document.getElementById('userJoinDate');
    if (joinDateEl && currentUser.created_at) {
        try {
            const date = new Date(currentUser.created_at.replace(/-/g, "/"));
            const options = { month: 'long', year: 'numeric' };
            const formatted = date.toLocaleDateString('en-US', options);
            joinDateEl.innerHTML = `<i class="fa-solid fa-calendar"></i> Member since ${formatted}`;
        } catch (e) {
            joinDateEl.innerHTML = `<i class="fa-solid fa-calendar"></i> Member since May 2024`;
        }
    }

    // Profile Overview Table
    safeSet('infoFullName', currentUser.name);
    safeSet('infoStudentId', currentUser.id);
    safeSet('infoEmail', currentUser.email);
    safeSet('infoPhone', currentUser.phone || 'N/A');
    safeSet('infoDept', currentUser.department || 'N/A');
    safeSet('infoYear', currentUser.year || 'N/A');

    // Personal Records Detail Pane
    safeSet('detailFullName', currentUser.name);
    safeSet('detailStudentId', currentUser.id);
    safeSet('detailEmail', currentUser.email);
    safeSet('detailDept', currentUser.department || 'N/A');
    safeSet('detailYear', currentUser.year || 'N/A');
    safeSet('detailPhone', currentUser.phone || 'N/A');

    // Pre-fill settings form
    const ids = {
        'setDisplayName': currentUser.name,
        'setEmail': currentUser.email,
        'setPhone': currentUser.phone,
        'setDept': currentUser.department,
        'setYear': currentUser.year,
        'setStudentId': currentUser.id
    };
    for (let id in ids) {
        const el = document.getElementById(id);
        if (el) el.value = ids[id] || '';
    }


    // Profile Pictures & Navbar Elements (Main Profile & Header)
    const profImg = document.getElementById('profileImage');
    const mainIcon = document.querySelector('.avatar .fa-user');
    
    const navName = document.getElementById('navUserName');
    const navEmail = document.getElementById('navUserEmail');
    const navImg = document.getElementById('navProfileImg');

    if (navName) navName.textContent = currentUser.name || 'User';
    if (navEmail) navEmail.textContent = currentUser.email || 'user@wku.edu';

    if (currentUser.profile_picture && currentUser.profile_picture !== '') {
        const imgPath = '../' + currentUser.profile_picture;

        if (profImg) {
            profImg.src = imgPath;
            profImg.style.display = 'block';
            if (mainIcon) mainIcon.style.display = 'none';
        }
        if (navImg) {
            navImg.src = imgPath;
        }
    } else {
        if (profImg) {
            profImg.src = '';
            profImg.style.display = 'none';
        }
        if (mainIcon) mainIcon.style.display = 'block';
        if (navImg) {
            navImg.src = '../img/wku_logo.jpg'; // Fallback to branded WKU logo
        }
    }
}

async function saveUserSettings(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    const originalText = btn ? btn.innerHTML : "Update Profile Information";

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin" style="font-size:12px; padding:0; background:none; border-radius:0;"></i> Updating...`;
        btn.style.opacity = '0.75';
    }

    const payload = {
        action: 'updateProfile',
        name: document.getElementById('setDisplayName').value.trim(),
        email: document.getElementById('setEmail').value.trim(),
        phone: document.getElementById('setPhone').value.trim(),
        department: document.getElementById('setDept').value.trim(),
        year: document.getElementById('setYear').value
    };

    try {
        const res = await fetch('api_user_profile.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            if (btn) {
                btn.innerHTML = `<i class="fa-solid fa-circle-check" style="font-size:12px; padding:0; background:none; border-radius:0;"></i> Updated!`;
                btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                btn.style.borderColor = '#34d399';
            }
            showToast('Your academic and contact credentials have been updated.', 'success');
            await fetchDashboardData();
            loadUserProfile();
        } else {
            if (btn) {
                btn.innerHTML = `<i class="fa-solid fa-circle-xmark" style="font-size:12px; padding:0; background:none; border-radius:0;"></i> Failed`;
                btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            }
            showToast('Error: ' + data.error, 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('An unexpected network error occurred.', 'error');
    } finally {
        setTimeout(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.style.opacity = '1';
                btn.style.background = '';
                btn.style.borderColor = '';
            }
        }, 2000);
    }
}

async function changeUserPassword(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    const originalText = btn ? btn.innerHTML : "Change Password";

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin" style="font-size:12px; padding:0; background:none; border-radius:0;"></i> Changing...`;
        btn.style.opacity = '0.75';
    }

    const payload = {
        action: 'changePassword',
        currentPassword: document.getElementById('currPass').value,
        newPassword: document.getElementById('newPass').value
    };

    if (document.getElementById('newPass').value !== document.getElementById('confirmPass').value) {
        showToast("New passwords do not match!", 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.opacity = '1';
        }
        return;
    }

    try {
        const res = await fetch('api_user_profile.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            if (btn) {
                btn.innerHTML = `<i class="fa-solid fa-circle-check" style="font-size:12px; padding:0; background:none; border-radius:0;"></i> Password Changed!`;
                btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            }
            showToast('Your account password has been updated securely.', 'success');
            event.target.reset();
        } else {
            if (btn) {
                btn.innerHTML = `<i class="fa-solid fa-circle-xmark" style="font-size:12px; padding:0; background:none; border-radius:0;"></i> Failed`;
                btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            }
            showToast(data.error, 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Network error while changing password.', 'error');
    } finally {
        setTimeout(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.style.opacity = '1';
                btn.style.background = '';
            }
        }, 2000);
    }
}

function updateStats() {
    const safeSet = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };
    safeSet('statBorrowed', currentUser.borrowed);
    safeSet('statPending', currentUser.holds);
    safeSet('statReturned', currentUser.favorites);
}

function renderBooks(booksToRender) {
    const grid = document.getElementById('bookGrid');
    if (!grid) return;

    if (booksToRender.length === 0) {
        grid.innerHTML = '<div style="padding: 40px; text-align: center; color: #666;">No books found.</div>';
        return;
    }

    grid.innerHTML = booksToRender.map(book => {
        const isDigital = book.isDigitalResource;
        const currentStatus = isDigital ? book.status : (book.status || 'Available');
        const isAvailable = isDigital || (currentStatus === 'Available' && parseInt(book.copies) > 0);

        const isCurrentlyBorrowing = !isDigital && issuedBooks.some(ib => ib.bookTitle === book.title && ib.status === 'Pending');
        const isOnHold = !isDigital && userHolds.some(h => h.title === book.title);

        return `
        <div class="resource-card" style="position: relative; overflow: hidden; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
            ${getFavButtonHtml(book.id, isDigital)}
            <div style="position: relative;">
                ${getBookCoverHtml(book.title, book.author, book.category || book.module, isDigital)}
                ${!isDigital && !isAvailable ? `<div class="borrowed-badge" style="position: absolute; top: 12px; left: 12px; background: #dc2626; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; z-index: 5; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);">Out of Stock</div>` : ''}
            </div>
            <div class="resource-card-info" style="padding: 16px; background: #ffffff;">
                <h4 style="font-size: 15px; font-weight: 700; margin: 0 0 6px 0; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${book.title}</h4>
                <p class="resource-author" style="font-size: 13px; color: #64748b; margin: 0 0 12px 0;">${isDigital ? 'Course Material' : 'by ' + book.author}</p>
                <p class="resource-meta" style="font-size: 12px; display: flex; align-items: center; gap: 6px; margin: 0 0 16px 0; color: #475569;">
                    <i class="fa-solid ${isDigital ? 'fa-file-powerpoint' : (isAvailable ? 'fa-circle-check' : 'fa-circle-xmark')}"
                       style="color: ${isDigital ? '#059669' : (isAvailable ? '#10b981' : '#ef4444')}"></i>
                    ${isDigital ? book.format : (isAvailable ? 'Available' : 'Out of Stock')}
                </p>
                ${isDigital ? `
                    <button class="resource-action-btn" onclick="viewResource(${book.id})" style="border-color: #8b5cf6; color: #7c3aed; transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-eye" style="color: #8b5cf6;"></i> View File
                    </button>
                ` : isCurrentlyBorrowing ? `
                    <button class="resource-action-btn" disabled style="background: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1; cursor: not-allowed; transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-clock"></i> Currently Borrowing
                    </button>
                ` : isOnHold ? `
                    <button class="resource-action-btn" disabled style="background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; cursor: not-allowed; transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-bookmark"></i> On Waitlist
                    </button>
                ` : isAvailable ? `
                    <button id="btn-borrow-${book.id}" class="resource-action-btn btn-borrow" onclick="borrowBook(${book.id}, this)" style="transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-book-reader"></i> Request Borrow
                    </button>
                ` : `
                    <button id="btn-hold-${book.id}" class="resource-action-btn btn-hold" onclick="placeHold(${book.id}, this)" style="transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-bookmark"></i> Place Hold
                    </button>
                `}
            </div>
        </div>
    `}).join('');
}

function searchBook(context) {
    const typeEl = document.getElementById(context === 'Home' ? 'searchTypeHome' : 'categories');
    const inputEl = document.getElementById(context === 'Home' ? 'searchInputHome' : 'searchInputBooks');
    if (!typeEl || !inputEl) return;

    const query = (inputEl.value || '').trim().toLowerCase();
    const type = typeEl.value;

    let bookResults = [];
    let resResults = [];

    if (context === 'Home') {
        // Physical books search
        bookResults = books.filter(b => {
            if (!query) return false;
            let val = '';
            if (type === 'title') val = b.title || '';
            else if (type === 'author') val = b.author || '';
            else if (type === 'subject') val = b.category || b.subject || '';
            return val.toLowerCase().includes(query);
        });

        // Digital resources search
        resResults = resources.filter(res => {
            if (!query) return false;
            let val = '';
            if (type === 'title') val = res.title || '';
            else if (type === 'author') val = res.author || '';
            else if (type === 'subject') val = res.module || '';
            return val.toLowerCase().includes(query);
        }).map(res => ({ ...res, isDigitalResource: res.isDigital === true }));
    } else {
        // Catalog search
        bookResults = books.filter(b => {
            const matchesQuery = b.title.toLowerCase().includes(query) || b.author.toLowerCase().includes(query);
            const matchesCategory = !type || type === 'all' || type === 'All' || b.category === type;
            return matchesQuery && matchesCategory;
        });

        resResults = resources.filter(res => {
            const matchesQuery = res.title.toLowerCase().includes(query) || res.author.toLowerCase().includes(query);
            const matchesCategory = !type || type === 'all' || type === 'All' || res.type === type;
            return matchesQuery && matchesCategory;
        }).map(res => ({ ...res, isDigitalResource: res.isDigital === true }));
    }

    let combined = [...bookResults, ...resResults];

    // Filter duplicates by Title & Media Type (physical vs digital)
    const seen = new Set();
    combined = combined.filter(item => {
        const isDig = !!item.isDigitalResource;
        const key = `${isDig ? 'digital' : 'physical'}_${item.title.toLowerCase()}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });

    if (window._catalogFilterAvailable && context === 'Catalog') {
        combined = combined.filter(item => item.isDigitalResource || item.status === 'Available');
    }

    if (context === 'Catalog') {
        const availFilter = document.getElementById('availability') ? document.getElementById('availability').value : '';
        if (availFilter) {
            combined = combined.filter(item => {
                const isDig = !!item.isDigitalResource;
                const isAvail = isDig || (item.status === 'Available' && parseInt(item.copies) > 0);
                const isCurrentlyBorrowing = !isDig && issuedBooks.some(ib => ib.bookTitle === item.title && ib.status === 'Pending');
                if (availFilter === 'Available') return isAvail;
                if (availFilter === 'Borrowed') return isCurrentlyBorrowing;
                if (availFilter === 'Hold') return !isDig && userHolds.some(h => h.title === item.title);
                return true;
            });
        }
    }

    if (context === 'Home') renderHomeResults(combined);
    else renderBooks(combined);
}

function renderHomeResults(results) {
    const container = document.getElementById('homeSearchResults');
    if (!container) return;
    const query = document.getElementById('searchInputHome').value.trim();

    if (!query) {
        container.innerHTML = '';
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';

    if (results.length === 0) {
        container.innerHTML = `
            <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 25px; border-radius: 16px; text-align: center; margin-top: 15px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.05); font-family: 'Inter', sans-serif; animation: fadeInSlideUp 0.3s ease forwards;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 28px; margin-bottom: 10px; display: block; color: #ef4444; background: none; padding: 0;"></i>
                <span style="font-weight: 700; font-size: 15px; display: block; margin-bottom: 4px;">No Results Found</span>
                <span style="font-size: 13px; color: #64748b;">We couldn't find any books matching "${query}". Try checking your spelling or using different keywords.</span>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="home-search-results-panel" style="margin-top: 20px; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); border: 1px solid rgba(15, 118, 110, 0.15); border-radius: 20px; padding: 25px; box-shadow: 0 20px 40px rgba(15, 118, 110, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03); animation: fadeInSlideUp 0.3s ease forwards; max-height: 550px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(15, 118, 110, 0.2) transparent;">
            <style>
                .home-search-results-panel::-webkit-scrollbar {
                    width: 6px;
                }
                .home-search-results-panel::-webkit-scrollbar-track {
                    background: transparent;
                }
                .home-search-results-panel::-webkit-scrollbar-thumb {
                    background-color: rgba(15, 118, 110, 0.2);
                    border-radius: 10px;
                }
            </style>
            <div class="search-results-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(15, 118, 110, 0.1);">
                <h3 style="color: #0f766e; margin: 0; font-size:18px; font-weight:700; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-magnifying-glass-chart" style="color: #0f766e; font-size: 16px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> Search Results (${results.length})</h3>
                <button onclick="clearHomeSearch()" 
                        style="background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.18); color: #ef4444; padding: 6px 14px; border-radius: 50px; cursor: pointer; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.2s;" 
                        onmouseover="this.style.background='rgba(239, 68, 68, 0.12)'; this.style.transform='scale(1.03)';" 
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.06)'; this.style.transform='scale(1)';"
                >
                    <i class="fa-solid fa-xmark" style="color: #ef4444; font-size: 12px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> 
                    Clear
                </button>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                ${results.map(book => {
        const isDigital = !!book.isDigitalResource;
        const isAvailable = isDigital || book.status === 'Available' || parseInt(book.copies) > 0;

        const isCurrentlyBorrowing = !isDigital && issuedBooks.some(ib => ib.bookTitle === book.title && ib.status === 'Pending');
        const isOnHold = !isDigital && userHolds.some(h => h.title === book.title);

        const statusText = isAvailable ? 'Available' : 'Out of Stock';
        const statusStyles = isAvailable
            ? 'background: rgba(22, 163, 74, 0.08); color: #16a34a; border: 1px solid rgba(22, 163, 74, 0.15);'
            : 'background: rgba(220, 38, 38, 0.06); color: #dc2626; border: 1px solid rgba(220, 38, 38, 0.12);';

        const subjectVal = book.category || book.subject || 'Course Resource';

        return `
                        <div class="search-result-card" 
                             style="background: #ffffff; border: 1px solid rgba(15, 118, 110, 0.1); border-radius: 14px; padding: 18px; display: flex; gap: 14px; position: relative; transition: all 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);"
                             onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(15, 118, 110, 0.06)'; this.style.borderColor='rgba(15, 118, 110, 0.25)';" 
                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.02)'; this.style.borderColor='rgba(15, 118, 110, 0.1)';"
                        >
                            <span class="card-badge" style="position: absolute; top: 14px; right: 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 6px; ${statusStyles}">
                                ${statusText}
                            </span>
                            ${getFavButtonHtmlForHome(book.id, isDigital)}
                            <div class="card-icon" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(15, 118, 110, 0.06); border: 1px solid rgba(15, 118, 110, 0.15); color: #0f766e; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                <i class="fa-solid ${isDigital ? 'fa-laptop-code' : 'fa-book'}" style="color: #0f766e; font-size: 18px; padding: 0; background: none;"></i>
                            </div>
                            <div class="card-details" style="flex: 1; display: flex; flex-direction: column; gap: 4px; font-family: 'Inter', sans-serif;">
                                <h4 style="margin: 0 135px 0 0; font-size: 15px; font-weight: 700; color: #0f766e; line-height: 1.3; word-break: break-word; overflow-wrap: break-word;">${book.title}</h4>
                                <p style="margin: 0; font-size: 13px; color: #475569; font-weight: 500;">by ${book.author}</p>
                                <div style="display:flex; flex-direction:column; gap:4px; font-size: 11px; color: #64748b; margin-top: 8px; border-top: 1px dashed rgba(15, 118, 110, 0.1); padding-top: 8px;">
                                    <span><strong style="color: #0f766e;">Format:</strong> ${isDigital ? 'Digital Content' : 'Physical Library Copy'}</span>
                                    <span><strong style="color: #0f766e;">Subject:</strong> ${subjectVal}</span>
                                    ${book.isbn ? `<span><strong style="color: #0f766e;">ISBN:</strong> ${book.isbn}</span>` : ''}
                                </div>
                                <div style="margin-top: 12px; display: flex; gap: 6px;">
                                    ${isDigital ? `
                                        <button onclick="viewResource(${book.id})" style="flex:1; padding: 8px; background: #0f766e; border: 1px solid #0f766e; color: #ffffff; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#0d9488'; this.style.borderColor='#0d9488'" onmouseout="this.style.background='#0f766e'; this.style.borderColor='#0f766e'"><i class="fa-solid fa-file-arrow-down" style="font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block; color: inherit;"></i> View / Download</button>
                                    ` : isCurrentlyBorrowing ? `
                                        <button disabled style="flex:1; padding: 8px; background: #e2e8f0; border: 1px solid #cbd5e1; color: #64748b; border-radius: 8px; cursor: not-allowed; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px;"><i class="fa-solid fa-clock" style="font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block; color: inherit;"></i> Borrowing</button>
                                    ` : isOnHold ? `
                                        <button disabled style="flex:1; padding: 8px; background: #fffbeb; border: 1px solid #fcd34d; color: #d97706; border-radius: 8px; cursor: not-allowed; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px;"><i class="fa-solid fa-bookmark" style="font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block; color: inherit;"></i> On Waitlist</button>
                                    ` : isAvailable ? `
                                        <button onclick="borrowBook(${book.id}, this)" style="flex:1; padding: 8px; background: #0f766e; border: 1px solid #0f766e; color: #ffffff; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#0d9488'; this.style.borderColor='#0d9488'" onmouseout="this.style.background='#0f766e'; this.style.borderColor='#0f766e'"><i class="fa-solid fa-hand-holding-hand" style="font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block; color: inherit;"></i> Borrow Book</button>
                                    ` : `
                                        <button onclick="placeHold(${book.id}, this)" style="flex:1; padding: 8px; background: #d97706; border: 1px solid #d97706; color: #ffffff; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#b45309'; this.style.borderColor='#b45309'" onmouseout="this.style.background='#d97706'; this.style.borderColor='#d97706'"><i class="fa-solid fa-bookmark" style="font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block; color: inherit;"></i> Place Hold</button>
                                    `}
                                </div>
                            </div>
                        </div>
                    `;
    }).join('')}
            </div>
        </div>
    `;
}

function clearHomeSearch() {
    const input = document.getElementById('searchInputHome');
    if (input) input.value = '';
    const container = document.getElementById('homeSearchResults');
    if (container) {
        container.innerHTML = '';
        container.style.display = 'none';
    }
}

async function borrowBook(bookId, btn) {
    const isDigital = resources.some(r => r.id === bookId);
    if (isDigital) {
        viewResource(bookId);
        return;
    }

    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        btn.disabled = true;
        btn.style.opacity = '0.7';
    }

    // Attempt to borrow physical book via API
    const borrowDate = new Date().toISOString().split('T')[0];
    const dueDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    const payload = {
        action: 'issue',
        studentId: currentUser.id,
        bookId: bookId,
        borrowDate: borrowDate,
        dueDate: dueDate
    };

    try {
        const res = await fetch('../admin/api_borrow.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Requested';
                btn.style.background = '#16a34a';
                btn.style.color = 'white';
                btn.style.borderColor = '#16a34a';
            }
            showToast('Borrow request successful!', 'success');
            setTimeout(async () => {
                await fetchDashboardData();
                refreshAllUI();
            }, 1000);
        } else {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-book-reader"></i> Request Borrow';
                btn.disabled = false;
                btn.style.opacity = '1';
            }
            showToast('Error: ' + data.error, 'warning');
        }
    } catch (e) {
        console.error(e);
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-book-reader"></i> Request Borrow';
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
}

async function placeHold(bookId, btn) {
    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        btn.disabled = true;
        btn.style.opacity = '0.7';
    }

    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'placeHold', bookId: bookId }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Waitlisted';
                btn.style.background = '#d97706';
                btn.style.color = 'white';
                btn.style.borderColor = '#d97706';
            }
            showToast('Hold placed successfully!', 'success');
            setTimeout(async () => {
                await fetchDashboardData();
                refreshAllUI();
            }, 1000);
        } else {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-bookmark"></i> Place Hold';
                btn.disabled = false;
                btn.style.opacity = '1';
            }
            showToast('Error: ' + data.error, 'warning');
        }
    } catch (e) {
        console.error(e);
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-bookmark"></i> Place Hold';
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
}

async function cancelHold(holdId) {
    if (!confirm('Are you sure you want to cancel this hold?')) return;
    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'cancelHold', holdId: holdId }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showToast('Hold cancelled.', 'info');
            await fetchDashboardData();
            refreshAllUI();
        } else {
            showToast('Error: ' + data.error, 'warning');
        }
    } catch (e) { console.error(e); }
}

async function submitSupportTicket(event) {
    event.preventDefault();
    const payload = {
        action: 'submitTicket',
        subject: document.getElementById('supportSubject').value,
        message: document.getElementById('supportMessage').value
    };

    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            alert('Your message has been sent to the admin team!');
            document.getElementById('supportModal').style.display = 'none';
            event.target.reset();
        } else alert('Error: ' + data.error);
    } catch (e) { console.error(e); }
}

// --- UI Helpers ---
function renderCatalogQuickFilters(activeFilter = 'all') {
    const container = document.getElementById('catalogQuickFilters');
    if (!container) return;

    // Collect unique categories from books
    const categories = [...new Set(books.map(b => b.category).filter(Boolean))].sort();

    // Icon map for well-known categories
    const iconMap = {
        'DAA': 'fa-code-branch',
        'Web Development': 'fa-code',
        'Computer Graphics': 'fa-palette',
        'DBMS': 'fa-database',
        'Networking': 'fa-network-wired',
        'Mathematics': 'fa-square-root-variable',
        'Physics': 'fa-atom',
        'Self Help': 'fa-brain',
        'Reference': 'fa-book-bookmark',
        'Textbook': 'fa-book-open',
        'E-Book': 'fa-book-open-reader',
        'Research': 'fa-microscope',
        'Lecture Notes': 'fa-chalkboard-teacher',
        'Past Exams': 'fa-file-circle-check',
        'Tutorials': 'fa-play-circle',
        'PDF': 'fa-file-pdf'
    };

    const allBtn = `<button class="qf-btn ${activeFilter === 'all' ? 'active' : ''}" onclick="filterCatalog('all')"><i class="fa-solid fa-layer-group"></i> All Books</button>`;
    const availBtn = `<button class="qf-btn ${activeFilter === 'available' ? 'active' : ''}" onclick="filterCatalog('available')"><i class="fa-solid fa-circle-check"></i> Available</button>`;

    const catBtns = categories.map(cat => {
        const icon = iconMap[cat] || 'fa-tag';
        const isActive = activeFilter === cat ? 'active' : '';
        // Shorten long names for display
        const label = cat.length > 14 ? cat.slice(0, 13) + '…' : cat;
        return `<button class="qf-btn ${isActive}" onclick="filterCatalog('${cat}')"><i class="fa-solid ${icon}"></i> ${label}</button>`;
    }).join('');

    container.innerHTML = allBtn + catBtns + availBtn;
}

function filterCatalog(filterType) {
    const categorySelect = document.getElementById('categories');
    if (filterType === 'available') {
        window._catalogFilterAvailable = true;
        window._catalogActiveFilter = 'available';
        if (categorySelect) categorySelect.value = 'all';
    } else if (filterType === 'all') {
        window._catalogFilterAvailable = false;
        window._catalogActiveFilter = 'all';
        if (categorySelect) categorySelect.value = 'all';
    } else {
        window._catalogFilterAvailable = false;
        window._catalogActiveFilter = filterType;
        if (categorySelect) categorySelect.value = filterType;
    }
    renderCatalogQuickFilters(window._catalogActiveFilter || 'all');
    searchBook('Catalog');
}

function filterResources(filterType) {
    window._resourceActiveFilter = filterType;
    renderResourceQuickFilters(filterType);
    searchResources();
}

function renderResourceQuickFilters(activeFilter = 'All') {
    const container = document.getElementById('resourceQuickFilters');
    if (!container) return;

    // Dynamically collect unique categories from available resources
    const categories = [...new Set(resources.map(r => r.type).filter(Boolean))].sort();

    const iconMap = {
        'DAA': 'fa-code-branch',
        'Web Development': 'fa-code',
        'Computer Graphics': 'fa-palette',
        'DBMS': 'fa-database',
        'Networking': 'fa-network-wired',
        'Mathematics': 'fa-square-root-variable',
        'Physics': 'fa-atom',
        'Self Help': 'fa-brain',
        'Reference': 'fa-book-bookmark',
        'Textbook': 'fa-book-open',
        'E-Book': 'fa-book-open-reader',
        'Research': 'fa-microscope',
        'Lecture Notes': 'fa-chalkboard-teacher',
        'Past Exams': 'fa-file-circle-check',
        'Tutorials': 'fa-play-circle',
        'PDF': 'fa-file-pdf'
    };

    const allBtn = `<button class="rf-btn ${activeFilter === 'All' ? 'active' : ''}" onclick="filterResources('All')"><i class="fa-solid fa-layer-group"></i> All Resources</button>`;

    const catBtns = categories.map(cat => {
        const icon = iconMap[cat] || 'fa-folder';
        const isActive = activeFilter === cat ? 'active' : '';
        const label = cat.length > 14 ? cat.slice(0, 13) + '…' : cat;
        return `<button class="rf-btn ${isActive}" onclick="filterResources('${cat}')"><i class="fa-solid ${icon}"></i> ${label}</button>`;
    }).join('');

    container.innerHTML = `<span class="filter-label">Quick Filters:</span>` + allBtn + catBtns;
}

function searchResources() {
    const query = (document.getElementById('resourceSearchInput')?.value || '').toLowerCase();
    const activeFilter = window._resourceActiveFilter || 'All';
    const dropdownFilter = document.getElementById('resourceTypeSelect')?.value || 'All';
    
    // Respect whichever filter is more specific (quick filter or dropdown)
    const catFilter = activeFilter !== 'All' ? activeFilter : dropdownFilter;

    const combined = resources.filter(r => {
        const matchesQuery = !query || r.title.toLowerCase().includes(query) || (r.author && r.author.toLowerCase().includes(query));
        const matchesCat = catFilter === 'All' || r.type === catFilter;
        return matchesQuery && matchesCat;
    });
    renderResources(combined);
}


window._resourceViewMode = 'grid';

function renderResources(data) {
    const grid = document.getElementById('allResourcesGrid');
    if (!grid) return;

    // Apply the active view class persistently
    if (window._resourceViewMode === 'list') {
        grid.classList.add('list-view');
    } else {
        grid.classList.remove('list-view');
    }

    if (data.length === 0) {
        grid.innerHTML = '<div style="padding: 40px; text-align: center; color: #666;">No resources found.</div>';
        return;
    }

    grid.innerHTML = data.map(res => {
        const isDigital = res.isDigital === true;

        // For physical books, evaluate availability, borrowing, and waitlist state
        const currentStatus = isDigital ? 'Available' : (res.status || 'Available');
        const isAvailable = !isDigital && currentStatus === 'Available' && parseInt(res.copies) > 0;

        const isCurrentlyBorrowing = !isDigital && issuedBooks.some(ib => ib.bookTitle === res.title && ib.status === 'Pending');
        const isOnHold = !isDigital && userHolds.some(h => h.title === res.title);

        const badgeText = isDigital ? (res.type || 'Study Material') : 'Physical Book';
        const coverIcon = isDigital ? (res.icon || 'fa-file-pdf') : 'fa-book';
        const authorText = isDigital ? 'Course Material' : `by ${res.author}`;
        const formatMeta = isDigital ? res.format : (isAvailable ? 'Available' : 'Out of Stock');
        const metaIcon = isDigital ? 'fa-file-powerpoint' : (isAvailable ? 'fa-circle-check' : 'fa-circle-xmark');
        const metaIconColor = isDigital ? '' : (isAvailable ? '#16a34a' : '#dc2626');

        return `
        <div class="resource-card" style="position: relative;">
            ${getFavButtonHtml(res.id, isDigital)}
            <div class="resource-cover">
                <span class="resource-tag">${badgeText}</span>
                <div class="cover-placeholder">
                    <i class="fa-solid ${coverIcon}"></i>
                </div>
                ${!isDigital && currentStatus === 'Borrowed' ? `<div class="borrowed-badge">Out of Stock</div>` : ''}
            </div>
            <div class="resource-card-info">
                <h4>${res.title}</h4>
                <p class="resource-author">${authorText}</p>
                <p class="resource-meta">
                    <i class="fa-solid ${metaIcon}" style="color: ${metaIconColor}; font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i>
                    ${formatMeta}
                </p>
                ${isDigital ? `
                    <button class="resource-action-btn" onclick="viewResource(${res.id})" style="border-color: #8b5cf6; color: #7c3aed; transition: all 0.3s;">
                        <i class="fa-solid fa-eye" style="color: inherit; font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> View File
                    </button>
                ` : isCurrentlyBorrowing ? `
                    <button class="resource-action-btn" disabled style="background: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1; cursor: not-allowed; transition: all 0.3s;">
                        <i class="fa-solid fa-clock" style="color: inherit; font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> Currently Borrowing
                    </button>
                ` : isOnHold ? `
                    <button class="resource-action-btn" disabled style="background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; cursor: not-allowed; transition: all 0.3s;">
                        <i class="fa-solid fa-bookmark" style="color: inherit; font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> On Waitlist
                    </button>
                ` : isAvailable ? `
                    <button id="res-btn-borrow-${res.id}" class="resource-action-btn btn-borrow" onclick="borrowBook(${res.id}, this)" style="transition: all 0.3s;">
                        <i class="fa-solid fa-book-reader" style="color: inherit; font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> Request Borrow
                    </button>
                ` : `
                    <button id="res-btn-hold-${res.id}" class="resource-action-btn btn-hold" onclick="placeHold(${res.id}, this)" style="transition: all 0.3s;">
                        <i class="fa-solid fa-bookmark" style="color: inherit; font-size: 11px; padding: 0; margin: 0; background: none; border-radius: 0; display: inline-block;"></i> Place Hold
                    </button>
                `}
            </div>
        </div>
        `;
    }).join('');
}

function switchResourceView(mode) {
    window._resourceViewMode = mode;
    
    // Toggle active state on header buttons
    const gridBtn = document.getElementById('viewGridBtn');
    const listBtn = document.getElementById('viewListBtn');
    if (gridBtn) gridBtn.classList.toggle('active', mode === 'grid');
    if (listBtn) listBtn.classList.toggle('active', mode === 'list');
    
    // Toggle list-view layout class on container
    const container = document.getElementById('allResourcesGrid');
    if (container) {
        if (mode === 'list') {
            container.classList.add('list-view');
        } else {
            container.classList.remove('list-view');
        }
    }
}

function viewResource(id) {
    // Use loose == to handle string/number type mismatch from JSON vs onclick
    const res = resources.find(r => r.isDigital === true && r.id == id);

    if (!res || !res.path || res.path === '#') {
        showToast('No file available for this resource.', 'warning');
        return;
    }

    // Extract filename from path for the download attribute
    const filename = res.path.split('/').pop() || res.title || 'download';

    // Create a hidden anchor and trigger download
    const a = document.createElement('a');
    a.href = res.path;
    a.download = filename;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    showToast(`Downloading "${res.title}"...`, 'success');
}


function updateHoldDisplay() {
    const grid = document.getElementById('activeHoldsGrid');
    if (grid) {
        if (document.getElementById('holdCountHeader')) document.getElementById('holdCountHeader').textContent = userHolds.length;

        if (userHolds.length === 0) {
            grid.innerHTML = '<div style="padding:40px;text-align:center;">You have no active holds.</div>';
        } else {
            grid.innerHTML = userHolds.map(hold => `
                <div class="hold-item-card">
                    <div class="hold-item-info">
                        <span class="hold-status-tag ${hold.status === 'Ready' ? 'ready' : 'queue'}">${hold.status}</span>
                        <h4>${hold.title}</h4>
                        <button class="hold-cancel-btn" onclick="cancelHold('${hold.id}')">Cancel Hold</button>
                    </div>
                </div>
            `).join('');
        }

        if (document.getElementById('holdsReadyCount')) {
            document.getElementById('holdsReadyCount').textContent = userHolds.filter(h => h.status === 'Ready').length;
        }
        if (document.getElementById('holdsQueueCount')) {
            document.getElementById('holdsQueueCount').textContent = userHolds.filter(h => h.status !== 'Ready').length;
        }
    }

    // Synchronize Holds tab table in profile
    const tbody = document.getElementById('holdsBody');
    if (tbody) {
        if (userHolds.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px; color: #666;">No active holds.</td></tr>';
        } else {
            tbody.innerHTML = userHolds.map(hold => `
                <tr>
                    <td><strong>${hold.title}</strong><br><span style="font-size:11px; color:#666;">by ${hold.author}</span></td>
                    <td>${hold.date}</td>
                    <td>${hold.status === 'Ready' ? 'Ready for Pickup' : 'In Queue'}</td>
                    <td><span class="activity-badge ${hold.status === 'Ready' ? 'returned' : 'hold'}">${hold.status}</span></td>
                </tr>
            `).join('');
        }
    }
}

function renderRecentActivity() {
    const tbody = document.getElementById('recentActivityBody');
    if (!tbody) return;
    tbody.innerHTML = activityLog.map(act => `
        <tr>
            <td><i class="fa-solid ${act.icon}"></i></td>
            <td><div>${act.book}</div></td>
            <td>${act.date}</td>
            <td>${act.due}</td>
            <td><span class="activity-badge ${act.statusClass}">${act.type}</span></td>
        </tr>
    `).join('');
}

function navigateToSection(sectionId) {
    const panes = document.querySelectorAll('.tab-pane');
    let hasPane = false;

    panes.forEach(pane => {
        pane.classList.remove('active');
        if (pane.id === `pane-${sectionId}`) {
            pane.classList.add('active');
            hasPane = true;
        }
    });

    // Sync active state on the profile sidebar nav buttons
    const navButtons = document.querySelectorAll('.sidebar-buttons button');
    navButtons.forEach(btn => {
        btn.classList.remove('active-nav');
        const onclickAttr = btn.getAttribute('onclick') || '';
        if (onclickAttr.includes(`'${sectionId}'`)) {
            btn.classList.add('active-nav');
        }
    });

    // Smoothly scroll or redirect to the correct top-level container
    if (sectionId === 'Browse' || sectionId === 'resource' || sectionId === 'hold') {
        window.location.href = `#${sectionId}`;
    } else if (hasPane || sectionId === 'myprofile') {
        window.location.href = '#myprofile';
    } else {
        window.location.href = `#${sectionId}`;
    }
}

function contactSupport() { document.getElementById('supportModal').style.display = 'flex'; }
function closeSupportModal() { document.getElementById('supportModal').style.display = 'none'; }
function toggleMenu() { document.getElementById('sidebar').classList.toggle('active'); }

function triggerProfileUpload() {
    const fileInput = document.getElementById('profileUpload');
    if (fileInput) fileInput.click();
}

async function handleProfileUpload(event) {
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
            showToast('Your profile picture has been updated successfully.', 'success');
            await fetchDashboardData();
            loadUserProfile();
        } else {
            showToast(data.error, 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('An error occurred while uploading your profile picture.', 'error');
    }
}

function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;

    const title = type === 'success' ? 'Operation Success' : 'Error Alert';
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';

    toast.innerHTML = `
        <div class="toast-icon ${type}"><i class="fa-solid ${icon}"></i></div>
        <div class="toast-content">
            <h5>${title}</h5>
            <p>${message}</p>
        </div>
        <div class="toast-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></div>
    `;

    container.appendChild(toast);

    // Trigger animate-in
    setTimeout(() => toast.classList.add('show'), 50);

    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// --- Favorites and History Dynamic Enhancements ---

function getBookCoverHtml(title, author, category, isDigital) {
    let gradient = 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)';
    const cat = (category || '').toLowerCase();
    const tit = (title || '').toLowerCase();

    if (isDigital) {
        gradient = 'linear-gradient(135deg, #059669 0%, #064e3b 100%)';
    } else if (cat.includes('self') || tit.includes('habit') || tit.includes('atomic')) {
        gradient = 'linear-gradient(135deg, #b45309 0%, #78350f 100%)';
    } else if (cat.includes('computer') || cat.includes('tech') || tit.includes('program') || tit.includes('dbms') || tit.includes('data')) {
        gradient = 'linear-gradient(135deg, #0284c7 0%, #0c4a6e 100%)';
    } else if (cat.includes('reference') || tit.includes('alchemist')) {
        gradient = 'linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%)';
    } else if (cat.includes('research') || cat.includes('science')) {
        gradient = 'linear-gradient(135deg, #4f46e5 0%, #312e81 100%)';
    } else {
        let hash = 0;
        for (let i = 0; i < tit.length; i++) {
            hash = tit.charCodeAt(i) + ((hash << 5) - hash);
        }
        const hue = Math.abs(hash % 360);
        gradient = `linear-gradient(135deg, hsl(${hue}, 60%, 40%) 0%, hsl(${hue}, 70%, 15%) 100%)`;
    }

    return `
        <div class="book-spine-cover" style="background: ${gradient}; width: 100%; height: 180px; position: relative; border-radius: 12px 12px 0 0; display: flex; flex-direction: column; justify-content: space-between; padding: 16px; box-sizing: border-box; overflow: hidden; border-bottom: 3px solid rgba(255,255,255,0.1); box-shadow: inset 0 0 40px rgba(0,0,0,0.3);">
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 50%); pointer-events: none;"></div>
            <div style="position: absolute; top: 0; left: 0; bottom: 0; width: 6px; background: rgba(0,0,0,0.2); border-right: 1px solid rgba(255,255,255,0.1); pointer-events: none;"></div>
            
            <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid ${isDigital ? 'fa-file-pdf' : 'fa-book-open'}"></i>
                ${isDigital ? 'E-Resource' : (category || 'Library Book')}
            </div>
            
            <div style="margin-top: 15px; margin-bottom: auto; text-align: left;">
                <h4 style="margin: 0; color: white; font-family: 'Syne', 'Inter', sans-serif; font-size: 15px; font-weight: 700; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; text-shadow: 0 2px 4px rgba(0,0,0,0.4); white-space: normal;">${title}</h4>
            </div>
            
            <div style="font-size: 11px; font-style: italic; color: rgba(255,255,255,0.85); text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                ${isDigital ? 'WKU Digital' : 'by ' + author}
            </div>
        </div>
    `;
}

function getFavButtonHtml(bookId, isDigital) {
    const id = parseInt(bookId) || bookId;
    const favId = (isDigital ? 'd_' : 'p_') + id;
    const isFav = userFavorites.includes(favId);
    return `
        <button class="fav-toggle-btn" onclick="toggleFavorite(${id}, ${isDigital ? 'true' : 'false'}); event.stopPropagation();" 
                style="position: absolute; top: 12px; right: 12px; background: rgba(255, 255, 255, 0.95); border: none; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: ${isFav ? '#ef4444' : '#94a3b8'}; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.2s ease; z-index: 10;" 
                onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';"
        >
            <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart" style="font-size: 15px; color: inherit; padding:0; background:none;"></i>
        </button>
    `;
}

function getFavButtonHtmlForHome(bookId, isDigital) {
    const id = parseInt(bookId) || bookId;
    const favId = (isDigital ? 'd_' : 'p_') + id;
    const isFav = userFavorites.includes(favId);
    return `
        <button class="fav-toggle-btn" onclick="toggleFavorite(${id}, ${isDigital ? 'true' : 'false'}); event.stopPropagation();" 
                style="position: absolute; top: 14px; right: 90px; background: rgba(248, 250, 252, 0.95); border: 1px solid rgba(15, 118, 110, 0.15); border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: ${isFav ? '#ef4444' : '#94a3b8'}; transition: all 0.2s ease; z-index: 10;" 
                onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';"
        >
            <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart" style="font-size: 13px; color: inherit; padding:0; background:none;"></i>
        </button>
    `;
}

function toggleFavorite(bookId, isDigital) {
    const id = parseInt(bookId) || bookId;
    if (!currentUser.id) {
        showToast('Please log in to use favorites.', 'error');
        return;
    }
    const favKey = `librinet_favs_${currentUser.id}`;
    const favId = (isDigital ? 'd_' : 'p_') + id;

    const index = userFavorites.indexOf(favId);
    if (index === -1) {
        userFavorites.push(favId);
        showToast('Added to Favorites', 'success');
    } else {
        userFavorites.splice(index, 1);
        showToast('Removed from Favorites', 'info');
    }
    localStorage.setItem(favKey, JSON.stringify(userFavorites));

    currentUser.favorites = userFavorites.length;
    updateStats();

    // Refresh display in all views
    searchBook('Catalog');
    searchResources();
    renderFavoritesGrid();
}

function renderFavoritesGrid() {
    const grid = document.getElementById('favoritesGrid');
    if (!grid) return;

    if (userFavorites.length === 0) {
        grid.innerHTML = '<p style="padding: 20px; color: #666; text-align: center; width: 100%;">No favorites yet.</p>';
        return;
    }

    const favBooks = books.filter(b => userFavorites.includes("p_" + b.id));
    const favRes = resources.filter(r => userFavorites.includes("d_" + r.id)).map(r => ({ ...r, isDigitalResource: true }));

    const combined = [...favBooks, ...favRes];

    if (combined.length === 0) {
        grid.innerHTML = '<p style="padding: 20px; color: #666; text-align: center; width: 100%;">No favorites found.</p>';
        return;
    }

    grid.innerHTML = combined.map(book => {
        const isDigital = book.isDigitalResource;
        const currentStatus = isDigital ? book.status : (book.status || 'Available');
        const isAvailable = isDigital || (currentStatus === 'Available' && parseInt(book.copies) > 0);

        const isCurrentlyBorrowing = !isDigital && issuedBooks.some(ib => ib.bookTitle === book.title && ib.status === 'Pending');
        const isOnHold = !isDigital && userHolds.some(h => h.title === book.title);

        return `
        <div class="resource-card" style="position: relative; overflow: hidden; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
            ${getFavButtonHtml(book.id, isDigital)}
            <div style="position: relative;">
                ${getBookCoverHtml(book.title, book.author, book.category || book.module, isDigital)}
                ${!isDigital && !isAvailable ? `<div class="borrowed-badge" style="position: absolute; top: 12px; left: 12px; background: #dc2626; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; z-index: 5; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);">Out of Stock</div>` : ''}
            </div>
            <div class="resource-card-info" style="padding: 16px; background: #ffffff;">
                <h4 style="font-size: 15px; font-weight: 700; margin: 0 0 6px 0; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${book.title}</h4>
                <p class="resource-author" style="font-size: 13px; color: #64748b; margin: 0 0 12px 0;">${isDigital ? 'Course Material' : 'by ' + book.author}</p>
                <p class="resource-meta" style="font-size: 12px; display: flex; align-items: center; gap: 6px; margin: 0 0 16px 0; color: #475569;">
                    <i class="fa-solid ${isDigital ? 'fa-file-powerpoint' : (isAvailable ? 'fa-circle-check' : 'fa-circle-xmark')}"
                       style="color: ${isDigital ? '#059669' : (isAvailable ? '#10b981' : '#ef4444')}"></i>
                    ${isDigital ? book.format : (isAvailable ? 'Available' : 'Out of Stock')}
                </p>
                ${isDigital ? `
                    <button class="resource-action-btn" onclick="viewResource(${book.id})" style="border-color: #8b5cf6; color: #7c3aed; transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-eye" style="color: #8b5cf6;"></i> View File
                    </button>
                ` : isCurrentlyBorrowing ? `
                    <button class="resource-action-btn" disabled style="background: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1; cursor: not-allowed; transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-clock"></i> Currently Borrowing
                    </button>
                ` : isOnHold ? `
                    <button class="resource-action-btn" disabled style="background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; cursor: not-allowed; transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-bookmark"></i> On Waitlist
                    </button>
                ` : isAvailable ? `
                    <button id="fav-btn-borrow-${book.id}" class="resource-action-btn btn-borrow" onclick="borrowBook(${book.id}, this)" style="transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-book-reader"></i> Request Borrow
                    </button>
                ` : `
                    <button id="fav-btn-hold-${book.id}" class="resource-action-btn btn-hold" onclick="placeHold(${book.id}, this)" style="transition: all 0.3s; width: 100%;">
                        <i class="fa-solid fa-bookmark"></i> Place Hold
                    </button>
                `}
            </div>
        </div>
    `;
    }).join('');
}

function renderFullHistory() {
    const tbody = document.getElementById('fullHistoryBody');
    if (!tbody) return;

    if (issuedBooks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px; color: #666;">No borrowing activity yet.</td></tr>';
        return;
    }

    tbody.innerHTML = issuedBooks.map(ib => {
        const statusClass = ib.status === 'Pending' ? 'borrowed' : (ib.status === 'Returned' ? 'returned' : 'overdue');
        const displayStatus = ib.status === 'Pending' ? 'Borrowed' : ib.status;
        return `
            <tr>
                <td><strong>${ib.bookTitle}</strong></td>
                <td>${ib.borrowDate}</td>
                <td>${ib.status === 'Returned' ? '—' : ib.dueDate}</td>
                <td><span class="activity-badge ${statusClass}">${displayStatus}</span></td>
            </tr>
        `;
    }).join('');
}
