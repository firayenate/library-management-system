// --- Navigation & Routing ---
function goToLogin() { window.location.href = "../logout.php"; }
function goToHome() { window.location.href = "Admin.php"; }

function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', () => {
    loadAdminProfile();
    handleRouting();
    window.addEventListener('hashchange', handleRouting);
    setupIssueBookAutocomplete();
});

function handleRouting() {
    const hash = window.location.hash || '#home';
    const sections = ['#home', '#bookedit', '#useredit', '#resources', '#help'];

    sections.forEach(sec => {
        const el = document.querySelector(sec);
        if (el) el.style.display = (sec === hash) ? 'block' : 'none';
    });

    const links = document.querySelectorAll('#mainNav a');
    links.forEach(link => {
        if (link.getAttribute('href') === hash) link.style.color = '#f0a500';
        else link.style.color = '';
    });

    if (hash === '#home') { updateDashboardStats(); renderActivityTable(); renderHomeBookGrid(); }
    if (hash === '#bookedit') { renderBorrowerTable(); renderAdminCatalog(); }
    if (hash === '#useredit') renderRegisteredStudents();
    if (hash === '#resources') renderAdminResources();
    if (hash === '#help') renderFAQ();
}

// --- Dashboard Logic ---
async function updateDashboardStats() {
    try {
        const res = await fetch('api_dashboard.php');
        const stats = await res.json();

        if (document.getElementById('totalStudentsCount')) document.getElementById('totalStudentsCount').textContent = `${stats.totalStudents} Student(s)`;
        if (document.getElementById('totalBooksCount')) document.getElementById('totalBooksCount').textContent = `${stats.totalBooks} Book(s)`;
        if (document.getElementById('issuedBooksCount')) document.getElementById('issuedBooksCount').textContent = `${stats.issuedBooks} Book(s)`;
        if (document.getElementById('pendingReturnsCount')) document.getElementById('pendingReturnsCount').textContent = `${stats.pendingReturns} Pending`;

        const catContainer = document.getElementById('categoryBreakdownContainer');
        if (catContainer && stats.categoryCounts) {
            let catHtml = '';
            for (const [cat, count] of Object.entries(stats.categoryCounts)) {
                catHtml += `
                    <div style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); padding: 10px 20px; border-radius: 50px; color: white; display: flex; align-items: center; gap: 10px;">
                        <span style="font-weight: bold;">${cat}</span>
                        <span style="background: #f0a500; color: #111; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 800;">${count}</span>
                    </div>
                `;
            }
            if (catHtml === '') catHtml = '<div style="color: #999;">No books categorized yet.</div>';
            catContainer.innerHTML = catHtml;
        }
    } catch (err) {
        console.error("Error fetching stats:", err);
    }
}

// --- Activity Table ---
async function renderActivityTable() {
    const tbody = document.getElementById('recentActivityBody');
    if (!tbody) return;

    try {
        const res = await fetch('api_borrow.php');
        const issuedBooks = await res.json();

        if (issuedBooks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No recent activity</td></tr>';
            return;
        }

        tbody.innerHTML = issuedBooks.slice(0, 15).map(issue => `
            <tr>
                <td>${issue.student_id}</td>
                <td>${issue.studentName || 'Unknown Student'}</td>
                <td>${issue.bookTitle}</td>
                <td>${issue.borrowDate}</td>
                <td>${issue.dueDate}</td>
                <td><span class="status-badge ${issue.status.toLowerCase()}" style="color: ${issue.status === 'Returned' ? '#10b981' : '#f0a500'}; font-weight:bold;">${issue.status}</span></td>
            </tr>
        `).join('');
    } catch (err) {
        console.error(err);
    }
}

// --- Borrower / Manage Books Table ---
async function renderBorrowerTable() {
    const tbody = document.getElementById('borrowerTableBody');
    if (!tbody) return;

    try {
        const res = await fetch('api_borrow.php');
        const issuedBooks = await res.json();
        const activeBorrows = issuedBooks.filter(i => i.status === 'Pending');

        if (activeBorrows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No active borrowers found.</td></tr>';
            return;
        }

        const usersRes = await fetch('api_users.php');
        const users = await usersRes.json();

        tbody.innerHTML = activeBorrows.map(issue => {
            const student = users.find(u => u.id === issue.student_id);
            const year = student && student.year ? student.year : 'N/A';
            return `
            <tr>
                <td>${issue.student_id}</td>
                <td>${issue.studentName}</td>
                <td>${year}</td> <!-- Real year from user data -->
                <td>${issue.email}</td>
                <td><span style="color:#d97706; font-weight:bold;">${issue.status}</span></td>
                <td>${issue.bookTitle}</td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button onclick="acceptReturn('${issue.id}')" style="background:#16a34a; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">Accept Return</button>
                        <button onclick="openEditBookForm('${issue.book_id}')" style="background:#3b82f6; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                    </div>
                </td>
            </tr>
        `}).join('');
    } catch (err) {
        console.error(err);
    }
}

// --- Add Book Logic ---
function openAddBookForm() { 
    document.getElementById('addBookModal').style.display = 'flex'; 
    // Auto-generate a unique 6-digit Book ID / ISBN
    const generatedId = 'LBN-' + Math.floor(100000 + Math.random() * 900000);
    const isbnInput = document.getElementById('bookIsbn');
    if (isbnInput) {
        isbnInput.value = generatedId;
    }
}
function closeAddBookForm() { document.getElementById('addBookModal').style.display = 'none'; }
function showSelectedBookFile() {
    const input = document.getElementById('bookFile');
    const display = document.getElementById('selectedBookFileName');
    display.textContent = input.files.length > 0 ? input.files[0].name : 'No file selected';
}

async function saveNewBook(event) {
    event.preventDefault();
    const payload = {
        action: 'add',
        title: document.getElementById('bookTitle').value,
        author: document.getElementById('bookAuthor').value,
        subject: document.getElementById('bookSubject').value,
        isbn: document.getElementById('bookIsbn').value,
        category: document.getElementById('bookCategory').value,
        copies: document.getElementById('bookCopies').value
    };

    try {
        const res = await fetch('api_books.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            alert('Book added successfully!');
            closeAddBookForm();
            event.target.reset();
            renderAdminCatalog();
            updateDashboardStats();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

// --- Issue Book Logic ---
function openIssueBookForm() { 
    document.getElementById('issueBookModal').style.display = 'flex'; 
    
    // Auto-prefill borrowing date parameters
    const today = new Date();
    const formattedToday = today.toISOString().split('T')[0];
    
    const hours = String(today.getHours()).padStart(2, '0');
    const minutes = String(today.getMinutes()).padStart(2, '0');
    const formattedTime = `${hours}:${minutes}`;
    
    const dueDate = new Date();
    dueDate.setDate(today.getDate() + 14);
    const formattedDueDate = dueDate.toISOString().split('T')[0];
    
    if (document.getElementById('issueBorrowDate')) document.getElementById('issueBorrowDate').value = formattedToday;
    if (document.getElementById('issueBorrowTime')) document.getElementById('issueBorrowTime').value = formattedTime;
    if (document.getElementById('issueDueDate')) document.getElementById('issueDueDate').value = formattedDueDate;
    if (document.getElementById('issueBookCondition')) document.getElementById('issueBookCondition').value = 'Good';
}

function closeIssueBookForm() { 
    document.getElementById('issueBookModal').style.display = 'none'; 
    // Reset highlights
    if (document.getElementById('issueStudentId')) document.getElementById('issueStudentId').style.borderColor = '';
    if (document.getElementById('issueBookId')) document.getElementById('issueBookId').style.borderColor = '';
}

async function saveIssuedBook(event) {
    event.preventDefault();
    
    let targetBookId = document.getElementById('issueBookId').value;
    
    // Resolve ISBN or ID string to true database numeric primary key
    try {
        const res = await fetch('api_books.php');
        const books = await res.json();
        const foundBook = books.find(b => 
            b.id.toString() === targetBookId.trim() || 
            b.isbn.toLowerCase() === targetBookId.trim().toLowerCase()
        );
        if (foundBook) {
            targetBookId = foundBook.id;
        }
    } catch(e) {}

    const payload = {
        action: 'issue',
        studentId: document.getElementById('issueStudentId').value,
        bookId: targetBookId,
        borrowDate: document.getElementById('issueBorrowDate').value,
        dueDate: document.getElementById('issueDueDate').value
    };

    try {
        const res = await fetch('api_borrow.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            alert('Book issued successfully!');
            closeIssueBookForm();
            event.target.reset();
            renderBorrowerTable();
            updateDashboardStats();
            renderAdminCatalog();
        } else {
            alert('Error issuing book: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

// --- Return Book Logic ---
function openReturnBookForm() { document.getElementById('returnBookModal').style.display = 'flex'; renderReturnList(); }
function closeReturnBookForm() { document.getElementById('returnBookModal').style.display = 'none'; }

async function renderReturnList() {
    const container = document.getElementById('returnRequestList');
    try {
        const res = await fetch('api_borrow.php');
        const issuedBooks = await res.json();
        const activeBorrows = issuedBooks.filter(i => i.status === 'Pending');

        if (activeBorrows.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#666;">No pending returns.</p>';
            return;
        }

        container.innerHTML = activeBorrows.map(issue => `
            <div style="background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:10px; border:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>${issue.bookTitle}</strong><br>
                    <span style="font-size:12px; color:#666;">Borrowed by ${issue.studentName} (${issue.student_id})</span>
                </div>
                <button onclick="acceptReturn('${issue.id}')" style="background:#16a34a; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;">Accept</button>
            </div>
        `).join('');
    } catch (err) {
        console.error(err);
    }
}

async function acceptReturn(issueId) {
    try {
        const res = await fetch('api_borrow.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'return', issueId: issueId }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            alert('Return accepted successfully!');
            renderBorrowerTable();
            renderActivityTable();
            updateDashboardStats();
            renderReturnList();
            renderAdminCatalog();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

// --- Password Toggle Helper ---
function togglePwdVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    }
}

// --- User Edit Logic ---
let globalUsers = [];
async function renderRegisteredStudents() {
    const tbody = document.getElementById('registeredStudentBody');
    if (!tbody) return;

    try {
        const res = await fetch('api_users.php');
        globalUsers = await res.json();
        const query = (document.getElementById('studentSearchInput')?.value || '').toLowerCase();
        const students = globalUsers.filter(u => u.name?.toLowerCase().includes(query) || u.email?.toLowerCase().includes(query) || u.id?.toLowerCase().includes(query));

        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No students found</td></tr>';
            return;
        }

        tbody.innerHTML = students.map((student, idx) => `
            <tr>
                <td>${student.id || 'N/A'}</td>
                <td>${student.name}</td>
                <td>${student.department || 'N/A'}</td>
                <td>${student.year || 'N/A'}</td>
                <td>${student.email}</td>
                <td><button class="edit-btn" type="button" onclick="selectStudent(${idx})" style="background:#2563eb; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">Edit</button></td>
            </tr>
        `).join('');
    } catch (err) {
        console.error(err);
    }
}

function selectStudent(index) {
    const student = globalUsers[index];
    if (!student) return;
    document.getElementById('selectedStudentIndex').value = index;
    document.getElementById('editStudentId').value = student.id;
    document.getElementById('editStudentName').value = student.name;
    document.getElementById('editStudentEmail').value = student.email;
    document.getElementById('editStudentPhone').value = student.phone || '';
    document.getElementById('editStudentDepartment').value = student.department;
    document.getElementById('editStudentYear').value = student.year;

    const hint = document.getElementById('selectedStudentHint');
    if (hint) hint.textContent = 'Editing: ' + student.id;
}

async function saveStudentInfo(event) {
    event.preventDefault();
    const index = document.getElementById('selectedStudentIndex').value;
    if (index === '') {
        alert('Please select a student first.');
        return;
    }

    // --- Password validation ---
    const newPwd = document.getElementById('editStudentPassword')?.value || '';
    const confirmPwd = document.getElementById('editStudentPasswordConfirm')?.value || '';
    const pwdMsg = document.getElementById('pwdMatchMsg');

    if (newPwd || confirmPwd) {
        if (newPwd.length < 6) {
            if (pwdMsg) { pwdMsg.textContent = '⚠ Password must be at least 6 characters.'; pwdMsg.style.display = 'block'; pwdMsg.style.color = '#dc2626'; }
            return;
        }
        if (newPwd !== confirmPwd) {
            if (pwdMsg) { pwdMsg.textContent = '⚠ Passwords do not match.'; pwdMsg.style.display = 'block'; pwdMsg.style.color = '#dc2626'; }
            return;
        }
    }
    if (pwdMsg) pwdMsg.style.display = 'none';
    // --------------------------

    const payload = {
        action: 'update',
        id: document.getElementById('editStudentId').value,
        name: document.getElementById('editStudentName').value,
        email: document.getElementById('editStudentEmail').value,
        phone: document.getElementById('editStudentPhone').value,
        department: document.getElementById('editStudentDepartment').value,
        year: document.getElementById('editStudentYear').value
    };

    // Only include password in payload if admin typed one
    if (newPwd) payload.password = newPwd;

    try {
        const res = await fetch('api_users.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            // Clear password fields after successful save
            if (document.getElementById('editStudentPassword')) document.getElementById('editStudentPassword').value = '';
            if (document.getElementById('editStudentPasswordConfirm')) document.getElementById('editStudentPasswordConfirm').value = '';
            alert('Student information updated successfully.');
            renderRegisteredStudents();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

function clearStudentForm() {
    document.getElementById('studentEditForm').reset();
    document.getElementById('selectedStudentIndex').value = '';
    const hint = document.getElementById('selectedStudentHint');
    if (hint) hint.textContent = 'Choose a student from the table first.';
    // Reset password fields & message
    if (document.getElementById('editStudentPassword')) document.getElementById('editStudentPassword').value = '';
    if (document.getElementById('editStudentPasswordConfirm')) document.getElementById('editStudentPasswordConfirm').value = '';
    const pwdMsg = document.getElementById('pwdMatchMsg');
    if (pwdMsg) pwdMsg.style.display = 'none';
}

async function deleteSelectedStudent() {
    const index = document.getElementById('selectedStudentIndex').value;
    if (index === '') {
        alert('Please select a student first.');
        return;
    }

    const student = globalUsers[index];
    if (!confirm('Delete student ' + student.id + '?')) return;

    try {
        const res = await fetch('api_users.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete', id: student.id }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            alert('Student deleted.');
            clearStudentForm();
            renderRegisteredStudents();
            updateDashboardStats();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

// --- Admin Dynamic Grids ---
let globalBooks = [];
let resources = [];

function updateResourceCounts(data) {
    const ebooks = data.filter(r => r.type === 'E-Books').length;
    const lectureNotes = data.filter(r => r.type === 'Lecture Notes').length;
    const pastExams = data.filter(r => r.type === 'Past Exams').length;
    
    let physicalBooks = 0;
    data.forEach(r => {
        if (!r.isDigital || r.type === 'Physical Book') {
            physicalBooks += r.copies ? parseInt(r.copies) : 1;
        }
    });

    if(document.getElementById('countEBooks')) document.getElementById('countEBooks').textContent = `${ebooks} Items`;
    if(document.getElementById('countLectureNotes')) document.getElementById('countLectureNotes').textContent = `${lectureNotes} Items`;
    if(document.getElementById('countPastExams')) document.getElementById('countPastExams').textContent = `${pastExams} Items`;
    if(document.getElementById('countPhysicalBooks')) document.getElementById('countPhysicalBooks').textContent = `${physicalBooks} Items`;
}

async function fetchAdminResources() {
    try {
        const res = await fetch('api_resources.php');
        resources = await res.json();
        updateResourceCounts(resources);
        
        // Reset search/filter to 'All Resources' on fresh fetch
        const typeSelect = document.querySelector('.search-inner select');
        const searchInput = document.querySelector('.search-inner input');
        if (typeSelect) typeSelect.value = 'All Resources';
        if (searchInput) searchInput.value = '';
        if (document.getElementById('resourceGridTitle')) document.getElementById('resourceGridTitle').textContent = 'All Resources';

        renderAdminResources();
    } catch (err) {
        console.error("Error fetching resources:", err);
    }
}
fetchAdminResources();
async function renderAdminCatalog() {
    const grid = document.getElementById('adminBookGrid');
    if (!grid) return;

    try {
        const res = await fetch('api_books.php');
        globalBooks = await res.json();

        if (globalBooks.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; color:#64748b;">No physical books in catalog. Click "Add New Book" to start.</div>';
            return;
        }

        grid.innerHTML = globalBooks.map(book => `
            <div class="resource-card">
                <div class="resource-cover">
                    <span class="resource-tag">${book.category || 'Physical Book'}</span>
                    <div class="cover-placeholder">
                        <i class="fa-solid fa-book"></i>
                    </div>
                </div>
                <div class="resource-card-info">
                    <h4>${book.title}</h4>
                    <p class="resource-author">by ${book.author}</p>
                    <p class="resource-meta">
                        <i class="fa-solid ${book.status === 'Available' ? 'fa-circle-check' : 'fa-circle-xmark'}" 
                           style="color: ${book.status === 'Available' ? '#16a34a' : '#dc2626'}"></i>
                        ${book.status} (${book.copies} copies)
                    </p>
                    <div style="display:flex; gap:8px; margin-top:12px;">
                        <button class="resource-action-btn" onclick="openEditBookForm(${book.id})" style="border-color:#3b82f6; color:#3b82f6; flex:1;">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                        <button class="resource-action-btn" onclick="deleteBook(${book.id})" style="border-color:#dc2626; color:#dc2626; flex:1;">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (err) {
        console.error(err);
    }
}

async function deleteBook(id) {
    if (confirm("Are you sure you want to delete this book?")) {
        try {
            const res = await fetch('api_books.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'delete', id: id }),
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                renderAdminCatalog();
                updateDashboardStats();
            } else {
                alert("Error: " + data.error);
            }
        } catch (err) {
            console.error(err);
        }
    }
}

function renderAdminResources(data = resources) {
    const grid = document.getElementById('adminResourceGrid');
    if (!grid) return;
    
    if (data.length === 0) {
        grid.innerHTML = '<div class="no-resources" style="grid-column: 1/-1; text-align:center; padding:40px;">No items found. Add books or digital resources to see them here.</div>';
        return;
    }

    grid.innerHTML = data.map(res => {
        const isDigital = res.isDigital;
        return `
            <div class="resource-card">
                <div class="resource-cover">
                    <span class="resource-tag">${isDigital ? res.type : 'Physical Book'}</span>
                    <div class="cover-placeholder">
                        <i class="fa-solid ${res.icon || 'fa-file-pdf'}"></i>
                    </div>
                </div>
                <div class="resource-card-info">
                    <h4>${res.title}</h4>
                    <p class="resource-author">${res.author}</p>
                    <p class="resource-meta">
                        ${isDigital ? `<i class="fa-solid fa-file"></i> ${res.format}` : `<i class="fa-solid fa-bookmark"></i> ${res.status || 'Available'}`}
                    </p>
                    <div style="display:flex; gap:8px; margin-top:12px;">
                        ${isDigital ? `
                            <button class="resource-action-btn" onclick="window.open('${res.path}', '_blank')" style="border-color:#16a34a; color:#16a34a; flex:1;">
                                <i class="fa-solid fa-eye"></i> View
                            </button>
                        ` : `
                            <button class="resource-action-btn" disabled style="border-color:#64748b; color:#64748b; flex:1; opacity:0.7;">
                                <i class="fa-solid fa-book"></i> Physical
                            </button>
                        `}
                        ${isDigital ? `
                            <button class="resource-action-btn" onclick="deleteResource(${res.id})" style="border-color:#dc2626; color:#dc2626; width:40px; display:flex; justify-content:center; align-items:center;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function deleteResource(id) {
    if (confirm('Are you sure you want to delete this resource?')) {
        try {
            const res = await fetch('api_resources.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'delete', id: id }),
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                alert('Resource deleted successfully!');
                fetchAdminResources();
            } else alert('Error deleting resource: ' + data.error);
        } catch (err) { console.error(err); }
    }
}

function filterResourcesByCat(cat) {
    const typeSelect = document.querySelector('.search-inner select');
    if (typeSelect) typeSelect.value = cat;
    
    // Also clear the text input when clicking a category filter
    const searchInput = document.querySelector('.search-inner input');
    if (searchInput) searchInput.value = '';
    
    searchResources();
    
    // Smooth scroll to grid
    const grid = document.getElementById('adminResourceGrid');
    if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function searchResources() {
    const query = document.querySelector('.search-inner input')?.value.toLowerCase() || '';
    const type = document.querySelector('.search-inner select')?.value || 'All Resources';
    
    // Update the section title based on filter
    const titleEl = document.getElementById('resourceGridTitle');
    if (titleEl) {
        if (query && type !== 'All Resources') {
            titleEl.textContent = `Search results for "${query}" in ${type}`;
        } else if (query) {
            titleEl.textContent = `Search results for "${query}"`;
        } else {
            titleEl.textContent = type === 'All Resources' ? 'All Resources' : `${type}`;
        }
    }

    const filtered = resources.filter(r => {
        const matchesQuery = r.title.toLowerCase().includes(query) || r.author.toLowerCase().includes(query);
        let matchesType = false;
        if (type === 'All Resources') {
            matchesType = true;
        } else if (type === 'Physical Book') {
            matchesType = !r.isDigital;
        } else {
            matchesType = r.type === type || r.module === type;
        }
        return matchesQuery && matchesType;
    });
    renderAdminResources(filtered);
}

async function searchBook(context) {
    if (context === 'Home') {
        const type = document.getElementById('searchTypeHome').value;
        const query = document.getElementById('searchInputHome').value.trim();
        const clearBtn = document.getElementById('clearHomeSearchBtn');
        
        if (!query) {
            clearHomeSearch();
            return;
        }

        try {
            const res = await fetch('api_books.php');
            const books = await res.json();
            
            const filtered = books.filter(book => {
                const val = book[type] ? book[type].toString().toLowerCase() : '';
                return val.includes(query.toLowerCase());
            });

            if (clearBtn) clearBtn.style.display = 'block';
            renderHomeBookGrid(filtered);
        } catch (err) {
            console.error(err);
        }
    } else if (context === 'Books') {
        const type = document.getElementById('searchTypeBooks').value;
        const query = document.getElementById('searchInputBooks').value.trim().toLowerCase();
        const grid = document.getElementById('adminBookGrid');
        if (!grid) return;

        try {
            const res = await fetch('api_books.php');
            const books = await res.json();
            
            const filtered = books.filter(book => {
                const val = book[type] ? book[type].toString().toLowerCase() : '';
                return val.includes(query);
            });

            if (filtered.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; color:#64748b;">No matching physical books found.</div>';
                return;
            }

            grid.innerHTML = filtered.map(book => `
                <div class="resource-card">
                    <div class="resource-cover">
                        <span class="resource-tag">${book.category || 'Physical Book'}</span>
                        <div class="cover-placeholder">
                            <i class="fa-solid fa-book"></i>
                        </div>
                    </div>
                    <div class="resource-card-info">
                        <h4>${book.title}</h4>
                        <p class="resource-author">by ${book.author}</p>
                        <p class="resource-meta">
                            <i class="fa-solid ${parseInt(book.copies) > 0 ? 'fa-circle-check' : 'fa-circle-xmark'}" 
                               style="color: ${parseInt(book.copies) > 0 ? '#16a34a' : '#dc2626'}"></i>
                            ${parseInt(book.copies) > 0 ? 'Available' : 'Out of Stock'} (${book.copies} copies)
                        </p>
                        <div style="display:flex; gap:8px; margin-top:12px;">
                            <button class="resource-action-btn" onclick="openEditBookForm(${book.id})" style="border-color:#3b82f6; color:#3b82f6; flex:1;">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <button class="resource-action-btn" onclick="deleteBook(${book.id})" style="border-color:#dc2626; color:#dc2626; flex:1;">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (err) {
            console.error(err);
        }
    }
}

function clearHomeSearch() {
    const input = document.getElementById('searchInputHome');
    if (input) input.value = '';
    const clearBtn = document.getElementById('clearHomeSearchBtn');
    if (clearBtn) clearBtn.style.display = 'none';
    renderHomeBookGrid(null);
}

function openFileUploader() {
    document.getElementById('resourceUploadModal').classList.add('open');
}

function closeResourceUploader() {
    document.getElementById('resourceUploadModal').classList.remove('open');
}

async function saveNewResource(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('resFile');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert("Please select a file to upload.");
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('title', document.getElementById('resTitle').value);
    formData.append('author', document.getElementById('resAuthor').value);
    formData.append('category', document.getElementById('resCategory').value);
    formData.append('module', document.getElementById('resModule').value);
    formData.append('file', fileInput.files[0]);

    try {
        const res = await fetch('api_resources.php', {
            method: 'POST',
            body: formData
            // Do not set Content-Type header when sending FormData!
        });
        const data = await res.json();
        if (data.success) {
            alert('Resource uploaded and added to catalog successfully!');
            fetchAdminResources();
            closeResourceUploader();
            event.target.reset();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) { console.error(err); }
}
function filterResourcesByCat(cat) {
    const select = document.getElementById('resourceTypeSelect');
    if (select) {
        select.value = cat;
        searchResources();
    }
}

// --- Admin Profile display ---
async function loadAdminProfile() {
    try {
        const res = await fetch('api_admin_profile.php');
        const data = await res.json();
        
        if (data.error) return;

        const navIdEl = document.getElementById('navAdminId');
        if (navIdEl) navIdEl.textContent = data.admin_id || 'ADM1234';

        const navImgEl = document.getElementById('navAdminImg');
        if (navImgEl) {
            if (data.profile_picture) {
                navImgEl.src = '../' + data.profile_picture;
            } else {
                navImgEl.src = '../img/wku_logo.jpg';
            }
        }
    } catch (err) {
        console.error("Error loading admin profile:", err);
    }
}

// --- FAQ FAQ FAQ logic ---
const faqData = [
    { q: "How do I add a new book to the library?", a: "Go to Manage Books, click Add New Book. Fill in the title, author, ISBN, subject, and copies, then submit.", cat: "books" },
    { q: "How do I delete a book from the system?", a: "In the Manage Books table, find the book and click Delete. You cannot delete a book currently borrowed.", cat: "books" },
    { q: "How do I edit book information?", a: "Click Edit next to the book in the Manage Books table. Update the fields and click Save.", cat: "books" },
    { q: "How do I search for a specific book?", a: "Use the search bar in Manage Books. Select Title, Author, or Subject, enter your keyword and click Search.", cat: "books" },
    { q: "How do I filter books by status or batch?", a: "Use the Status and Batch dropdowns in Manage Books to narrow down the table.", cat: "books" },
    { q: "How do I view all registered students?", a: "The full student list is in the borrower table under Manage Books. Filter by batch or status to find specific students.", cat: "users" },
    { q: "How do I edit a student's information?", a: "Find the student in Manage Books and click Edit. Update their details and save.", cat: "users" },
    { q: "How do I delete a student account?", a: "Click Delete next to the student. This permanently removes the record. Ensure all books are returned first.", cat: "users" },
    { q: "How do I update admin profile info?", a: "Go to Edit User Info from the nav bar. Update your name, email, phone, department, and password.", cat: "users" },
    { q: "How do I change the admin password?", a: "In Edit User Info, go to Security Settings. Enter your current password, new password, confirm it and save.", cat: "users" },
    { q: "How do I issue a book to a student?", a: "In Manage Books, click Issue/Borrow Book. Enter the student ID, name, select the book and batch, then submit.", cat: "borrowing" },
    { q: "How do I mark a book as returned?", a: "Find the borrower in Manage Books, click Edit, change status to Returned and save.", cat: "borrowing" },
    { q: "How do I identify overdue books?", a: "Use the Status filter and select Overdue. Contact the student using the email shown in the table.", cat: "borrowing" },
    { q: "What is the default borrowing period?", a: "7 days from the issue date. After that the status automatically changes to Overdue.", cat: "borrowing" },
    { q: "Can a student borrow more than one book?", a: "Yes, up to 5 books at a time. The Books Issued column shows how many they currently have.", cat: "borrowing" },
    { q: "How do I log out of the admin panel?", a: "Click the Log Out button in the top right of the navigation bar.", cat: "system" },
    { q: "How do the dashboard cards update?", a: "They update automatically based on data stored in the system.", cat: "system" },
    { q: "How do I access the Resources section?", a: "Click Resources in the nav bar to manage E-Books, Lecture Notes, Past Exams, and Tutorials.", cat: "system" },
    { q: "What if the page is not loading correctly?", a: "Refresh with Ctrl+R or Cmd+R. If it persists, clear your browser cache or contact the developer.", cat: "system" },
];

let activeTab = 'all';

function setTab(cat, el) {
    activeTab = cat;
    document.querySelectorAll('.faq-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    renderFAQ();
}

function filterFAQ() { renderFAQ(); }

function toggleFAQ(i) {
    document.querySelectorAll('.faq-item').forEach((item, idx) => {
        if (idx === i) item.classList.toggle('open');
        else item.classList.remove('open');
    });
}

function renderFAQ() {
    const searchInput = document.getElementById('faqSearch');
    const query = searchInput ? searchInput.value.toLowerCase() : '';
    const list = document.getElementById('faqList');
    if (!list) return;

    const filtered = faqData.filter(f => {
        const matchCat = activeTab === 'all' || f.cat === activeTab;
        const matchQ = f.q.toLowerCase().includes(query) || f.a.toLowerCase().includes(query);
        return matchCat && matchQ;
    });

    if (!filtered.length) {
        list.innerHTML = '<div class="faq-empty">No results found. Try a different keyword or category.</div>';
        return;
    }

    list.innerHTML = filtered.map((f, i) => `
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(${i})">
                <div class="faq-question-left">
                    <span class="faq-badge ${f.cat}">${f.cat}</span>
                    <span class="q-text">${f.q}</span>
                </div>
                <span class="faq-toggle">+</span>
            </div>
            <div class="faq-answer">${f.a}</div>
        </div>
    `).join('');
}

// --- Issue Book Autocomplete & Real-Time Lookup ---
async function lookupStudent(studentId) {
    if (!studentId || studentId.trim().length < 3) return;
    try {
        const res = await fetch('api_users.php');
        const students = await res.json();
        const student = students.find(s => s.id.toLowerCase() === studentId.trim().toLowerCase());
        if (student) {
            if (document.getElementById('issueStudentName')) document.getElementById('issueStudentName').value = student.name || '';
            if (document.getElementById('issueStudentEmail')) document.getElementById('issueStudentEmail').value = student.email || '';
            if (document.getElementById('issueStudentPhone')) document.getElementById('issueStudentPhone').value = student.phone || '';
            if (document.getElementById('issueStudentBatch')) document.getElementById('issueStudentBatch').value = student.year || 'Year 1';
            
            // Visual success indicator: highlight input border in green
            document.getElementById('issueStudentId').style.borderColor = '#10b981';
        } else {
            document.getElementById('issueStudentId').style.borderColor = '';
        }
    } catch (err) {
        console.error(err);
    }
}

async function lookupBook(bookQuery) {
    if (!bookQuery || bookQuery.trim().length < 1) return;
    try {
        const res = await fetch('api_books.php');
        const books = await res.json();
        const book = books.find(b => 
            b.id.toString() === bookQuery.trim() || 
            b.isbn.toLowerCase() === bookQuery.trim().toLowerCase()
        );
        if (book) {
            if (document.getElementById('issueBookTitle')) document.getElementById('issueBookTitle').value = book.title || '';
            
            // Visual success indicator: highlight input border in green
            document.getElementById('issueBookId').style.borderColor = '#10b981';
        } else {
            document.getElementById('issueBookId').style.borderColor = '';
        }
    } catch (err) {
        console.error(err);
    }
}

function setupIssueBookAutocomplete() {
    const studentIdInput = document.getElementById('issueStudentId');
    if (studentIdInput) {
        studentIdInput.addEventListener('input', (e) => lookupStudent(e.target.value));
        studentIdInput.addEventListener('blur', (e) => lookupStudent(e.target.value));
    }
    
    const bookIdInput = document.getElementById('issueBookId');
    if (bookIdInput) {
        bookIdInput.addEventListener('input', (e) => lookupBook(e.target.value));
        bookIdInput.addEventListener('blur', (e) => lookupBook(e.target.value));
    }
}

// --- Home Library Showcase Grid Renderer ---
async function renderHomeBookGrid(filteredBooks = null) {
    const grid = document.getElementById('homeBookGrid');
    if (!grid) return;

    try {
        let books = filteredBooks;
        if (!books) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding:50px 20px; color:#94a3b8; background: rgba(30, 41, 59, 0.2); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.05);">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 36px; color: #f0a500; margin-bottom: 15px; display: block; filter: drop-shadow(0 0 8px rgba(240,165,0,0.3));"></i>
                    <span style="font-size: 16px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Discover Library Books</span>
                    <p style="margin: 0; font-size: 13px; color: #64748b;">Use the search bar above to query books by title, author, or subject.</p>
                </div>
            `;
            return;
        }

        if (books.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding:40px; color:#64748b;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; color: #ef4444; margin-bottom: 12px; display: block;"></i>
                    No matching physical books found in the library.
                </div>
            `;
            return;
        }

        grid.innerHTML = books.map(book => {
            const isAvailable = parseInt(book.copies) > 0;
            const statusClass = isAvailable ? 'status-available' : 'status-borrowed';
            const statusText = isAvailable ? 'Available' : 'Out of Stock';
            return `
                <div class="resource-card">
                    <div class="resource-cover">
                        <span class="resource-tag">${book.category || 'Physical Book'}</span>
                        <div class="cover-placeholder">
                            <i class="fa-solid fa-book"></i>
                        </div>
                    </div>
                    <div class="resource-card-info">
                        <h4>${book.title}</h4>
                        <p class="resource-author">by ${book.author}</p>
                        <p class="resource-meta">
                            <i class="fa-solid ${isAvailable ? 'fa-circle-check' : 'fa-circle-xmark'}" 
                               style="color: ${isAvailable ? '#16a34a' : '#dc2626'}"></i>
                            ${statusText} (${book.copies} copies)
                        </p>
                        <div style="display:flex; gap:4px; margin-top:12px; font-size:12px; color:#94a3b8; flex-direction:column;">
                            <span><strong>Subject:</strong> ${book.subject}</span>
                            <span><strong>ISBN:</strong> ${book.isbn}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error(err);
    }
}



// --- Edit Book Form ---
function openEditBookForm(bookId) {
    const book = globalBooks.find(b => parseInt(b.id) === parseInt(bookId));
    if (!book) return;
    
    document.getElementById('editBookId').value = book.id;
    document.getElementById('editBookTitle').value = book.title || '';
    document.getElementById('editBookAuthor').value = book.author || '';
    document.getElementById('editBookSubject').value = book.subject || book.category || '';
    document.getElementById('editBookIsbn').value = book.isbn || '';
    document.getElementById('editBookCategory').value = book.category || '';
    document.getElementById('editBookCopies').value = book.copies || '';
    document.getElementById('editBookDescription').value = book.description || '';
    
    document.getElementById('editBookModal').style.display = 'flex';
}

function closeEditBookForm() {
    document.getElementById('editBookModal').style.display = 'none';
}

async function saveEditedBook(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const data = {
        action: 'edit',
        id: document.getElementById('editBookId').value,
        title: document.getElementById('editBookTitle').value,
        author: document.getElementById('editBookAuthor').value,
        subject: document.getElementById('editBookSubject').value,
        isbn: document.getElementById('editBookIsbn').value,
        category: document.getElementById('editBookCategory').value,
        copies: document.getElementById('editBookCopies').value,
        description: document.getElementById('editBookDescription').value
    };

    try {
        const res = await fetch('api_books.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            closeEditBookForm();
            renderAdminCatalog(); // refresh catalog
            if (document.getElementById('searchInputBooks').value.trim()) {
                searchBook('Books'); // refresh search
            }
        } else {
            alert('Failed to update book: ' + (result.error || 'Unknown error'));
        }
    } catch (e) {
        console.error(e);
        alert('Error updating book');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Changes';
    }
}

