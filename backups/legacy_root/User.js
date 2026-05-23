// --- Global State ---
let currentUser = {};
let books = [];
let issuedBooks = [];
let userHolds = [];
let activityLog = [];

// Hardcoded Resources for Digital Content
let resources = [
    // DAA Category
    { id: 1001, title: 'Introduction to DAA', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '327 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/1 Intoduction (2).ppt', module: 'DAA' },
    { id: 1002, title: 'Analysis of Algorithms', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '624 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/2 Analysis of algorithm (2).ppt', module: 'DAA' },
    { id: 1003, title: 'Graph Theory', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '438 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/3 Graph.ppt', module: 'DAA' },
    // Web Category
    { id: 1004, title: 'Web Development Ch 1', author: 'Web Dept', type: 'Study Material', format: 'PPTX', size: '915 KB', icon: 'fa-file-powerpoint', path: 'files/web/Chapter 1.pptx', module: 'Web Development' },
    { id: 1005, title: 'HTML Essentials', author: 'Web Dept', type: 'Study Material', format: 'PPTX', size: '745 KB', icon: 'fa-file-powerpoint', path: 'files/web/Ch 2 HTML.pptx', module: 'Web Development' },
    // Graphics Category
    { id: 1006, title: 'Computer Graphics Tutorials', author: 'YouTube Series', type: 'Video', format: 'Video', size: 'Playlist', icon: 'fa-circle-play', path: 'https://www.youtube.com/watch?v=Jv52kWUUCng&list=PLzfVsIhtvVY2dCi7RK4VGhSbrFHzSPz6A&index=23', module: 'Computer Graphics' }
];

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
        res = await fetch('api_books.php');
        books = await res.json();
        
        // Fetch User's Borrow History
        res = await fetch('api_borrow.php');
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
        activityLog.sort((a,b) => new Date(b.date) - new Date(a.date));

        // Setup Stats
        currentUser.borrowed = issuedBooks.filter(ib => ib.status === 'Pending').length;
        currentUser.favorites = issuedBooks.filter(ib => ib.status === 'Returned').length;
        currentUser.holds = userHolds.length;

    } catch (e) {
        console.error("Failed to fetch dashboard data:", e);
    }
}

function refreshAllUI() {
    loadUserProfile();
    updateStats();
    searchBook('Catalog'); // Default view
    searchResources(); 
    updateHoldDisplay();
    renderRecentActivity();
}

function loadUserProfile() {
    if (currentUser.error) return; // Not logged in
    
    const safeSet = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };

    safeSet('userNameDisplay', currentUser.name);
    safeSet('userIdDisplay', currentUser.id);

    safeSet('infoFullName', currentUser.name);
    safeSet('infoEmail', currentUser.email);
    safeSet('infoPhone', currentUser.phone || 'N/A');
    safeSet('infoDept', currentUser.department || 'N/A');

    // Pre-fill settings form
    const ids = {
        'setDisplayName': currentUser.name,
        'setEmail': currentUser.email,
        'setPhone': currentUser.phone,
        'setDept': currentUser.department
    };
    for (let id in ids) {
        const el = document.getElementById(id);
        if (el) el.value = ids[id] || '';
    }
}

async function saveUserSettings(event) {
    event.preventDefault();
    const payload = {
        action: 'updateProfile',
        name: document.getElementById('setDisplayName').value.trim(),
        email: document.getElementById('setEmail').value.trim(),
        phone: document.getElementById('setPhone').value.trim(),
        department: document.getElementById('setDept').value.trim()
    };

    try {
        const res = await fetch('api_user_profile.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Profile updated successfully!');
            await fetchDashboardData();
            loadUserProfile();
        } else alert('Error: ' + data.error);
    } catch(e) { console.error(e); }
}

async function changeUserPassword(event) {
    event.preventDefault();
    const payload = {
        action: 'changePassword',
        currentPassword: document.getElementById('currPass').value,
        newPassword: document.getElementById('newPass').value
    };
    
    if (document.getElementById('newPass').value !== document.getElementById('confirmPass').value) {
        alert("Passwords do not match!");
        return;
    }

    try {
        const res = await fetch('api_user_profile.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Password changed successfully!');
            event.target.reset();
        } else alert('Error: ' + data.error);
    } catch(e) { console.error(e); }
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
        const isAvailable = currentStatus === 'Available';

        return `
        <div class="resource-card">
            <div class="resource-cover">
                <span class="resource-tag">${isDigital ? 'Study Material' : 'Physical Book'}</span>
                <div class="cover-placeholder">
                    <i class="fa-solid ${isDigital ? 'fa-file-pdf' : 'fa-book'}"></i>
                </div>
                ${!isDigital && currentStatus === 'Borrowed' ? `<div class="borrowed-badge">Borrowed</div>` : ''}
            </div>
            <div class="resource-card-info">
                <h4>${book.title}</h4>
                <p class="resource-author">${isDigital ? 'Course Material' : 'by ' + book.author}</p>
                <p class="resource-meta">
                    <i class="fa-solid ${isDigital ? 'fa-file-powerpoint' : (isAvailable ? 'fa-circle-check' : 'fa-circle-xmark')}"
                       style="color: ${isDigital ? '' : (isAvailable ? '#16a34a' : '#dc2626')}"></i>
                    ${isDigital ? book.format : currentStatus}
                </p>
                ${isDigital ? `
                    <button class="resource-action-btn" onclick="viewResource(${book.id})" style="border-color: #8b5cf6; color: #7c3aed;">
                        <i class="fa-solid fa-eye" style="color: #8b5cf6;"></i> View File
                    </button>
                ` : isAvailable ? `
                    <button class="resource-action-btn btn-borrow" onclick="borrowBook(${book.id})">
                        <i class="fa-solid fa-book-reader"></i> Request Borrow
                    </button>
                ` : `
                    <button class="resource-action-btn btn-hold" onclick="placeHold(${book.id})">
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

    const query = (inputEl.value || '').toLowerCase();
    const type = typeEl.value; 

    let bookResults = books.filter(b => {
        const matchesQuery = b.title.toLowerCase().includes(query) || b.author.toLowerCase().includes(query);
        const matchesCategory = !type || type === 'all' || type === 'All' || b.category === type;
        return matchesQuery && matchesCategory;
    });

    let resResults = resources.filter(res => {
        const matchesQuery = res.title.toLowerCase().includes(query) || res.author.toLowerCase().includes(query);
        const matchesModule = !type || type === 'all' || type === 'All' || res.module === type;
        return matchesQuery && matchesModule;
    }).map(res => ({...res, isDigitalResource: true}));

    let combined = [...bookResults, ...resResults];
    
    if (window._catalogFilterAvailable && context === 'Catalog') {
        combined = combined.filter(item => item.isDigitalResource || item.status === 'Available');
    }

    if (context === 'Home') renderHomeResults(combined);
    else renderBooks(combined);
}

function renderHomeResults(results) {
    const container = document.getElementById('homeSearchResults');
    if (!container) return;
    const query = document.getElementById('searchInputHome').value.trim();
    container.style.display = 'block';

    if (results.length === 0) {
        container.innerHTML = `<div style="padding:20px;background:#7f1d1d;color:white;text-align:center;">No match for "${query}"</div>`;
        return;
    }

    container.innerHTML = `
        <div style="background:rgba(0,0,0,0.7); padding:25px; border-radius:15px;">
            <h3 style="color:white;margin-bottom:10px;">Results (${results.length})</h3>
            <div style="display:flex; flex-direction:column; gap:10px;">
                ${results.map(book => `
                    <div style="background:white; padding:10px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h4 style="margin:0;">${book.title}</h4>
                            <small>${book.isDigitalResource ? 'Digital' : 'Physical'} - ${book.status || 'Available'}</small>
                        </div>
                        <button onclick="borrowBook(${book.id})" ${book.status !== 'Available' && !book.isDigitalResource ? 'disabled' : ''} style="padding:5px 10px; background:#16a34a; color:white; border:none; border-radius:5px;">${book.status === 'Available' ? 'Borrow' : 'View'}</button>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

async function borrowBook(bookId) {
    const isDigital = resources.some(r => r.id === bookId);
    if(isDigital) {
        viewResource(bookId);
        return;
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
        const res = await fetch('api_borrow.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            showToast('Borrow request successful!', 'success');
            await fetchDashboardData();
            refreshAllUI();
        } else {
            showToast('Error: ' + data.error, 'warning');
        }
    } catch(e) { console.error(e); }
}

async function placeHold(bookId) {
    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'placeHold', bookId: bookId }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            showToast('Hold placed successfully!', 'success');
            await fetchDashboardData();
            refreshAllUI();
        } else {
            showToast('Error: ' + data.error, 'warning');
        }
    } catch(e) { console.error(e); }
}

async function cancelHold(holdId) {
    if (!confirm('Are you sure you want to cancel this hold?')) return;
    try {
        const res = await fetch('api_user_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'cancelHold', holdId: holdId }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            showToast('Hold cancelled.', 'info');
            await fetchDashboardData();
            refreshAllUI();
        } else {
            showToast('Error: ' + data.error, 'warning');
        }
    } catch(e) { console.error(e); }
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
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Your message has been sent to the admin team!');
            document.getElementById('supportModal').style.display = 'none';
            event.target.reset();
        } else alert('Error: ' + data.error);
    } catch(e) { console.error(e); }
}

// --- UI Helpers ---
function filterCatalog(filterType) {
    const categorySelect = document.getElementById('categories');
    if (filterType === 'available') {
        window._catalogFilterAvailable = true;
        if (categorySelect) categorySelect.value = 'all';
    } else if (filterType === 'all') {
        window._catalogFilterAvailable = false;
        if (categorySelect) categorySelect.value = 'all';
    } else {
        window._catalogFilterAvailable = false;
        if (categorySelect) categorySelect.value = filterType;
    }
    searchBook('Catalog');
}

function searchResources() {
    // Just map over the hardcoded resources array since it's digital content
    const query = (document.getElementById('resourceSearchInput')?.value || '').toLowerCase();
    const combined = resources.filter(r => r.title.toLowerCase().includes(query));
    renderResources(combined);
}

function renderResources(data) {
    const grid = document.getElementById('allResourcesGrid');
    if (!grid) return;
    grid.innerHTML = data.map(res => `
        <div class="resource-card">
            <div class="resource-cover"><span class="resource-tag">Study Material</span><i class="fa-solid fa-file-pdf"></i></div>
            <div class="resource-card-info">
                <h4>${res.title}</h4><p>Course Material</p>
                <button class="resource-action-btn" onclick="viewResource(${res.id})" style="border-color:#8b5cf6; color:#7c3aed;">
                    <i class="fa-solid fa-eye"></i> View File
                </button>
            </div>
        </div>
    `).join('');
}

function viewResource(id) {
    const res = resources.find(r => r.id === id);
    if (res && res.path) window.open(res.path, '_blank');
    else alert('Viewing resource...');
}

function updateHoldDisplay() {
    const grid = document.getElementById('activeHoldsGrid');
    if (!grid) return;
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
    panes.forEach(pane => {
        pane.classList.remove('active');
        if (pane.id === `pane-${sectionId}`) pane.classList.add('active');
    });
}

function contactSupport() { document.getElementById('supportModal').style.display = 'flex'; }
function closeSupportModal() { document.getElementById('supportModal').style.display = 'none'; }
function toggleMenu() { document.getElementById('sidebar').classList.toggle('active'); }

function showToast(message, type = 'success') {
    alert(message); // Simplified for integration script
}
