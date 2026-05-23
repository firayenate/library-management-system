// --- Navigation & Routing ---
function goToLogin() { window.location.href = "logout.php"; }
function goToHome() { window.location.href = "Admin.php"; }

function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', () => {
    handleRouting();
    window.addEventListener('hashchange', handleRouting);
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

    if (hash === '#home') { updateDashboardStats(); renderActivityTable(); }
    if (hash === '#bookedit') { renderBorrowerTable(); renderAdminCatalog(); }
    if (hash === '#useredit') renderRegisteredStudents();
    if (hash === '#resources') renderAdminResources();
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

        tbody.innerHTML = issuedBooks.slice(0, 10).map(issue => `
            <tr>
                <td>${issue.student_id}</td>
                <td>${issue.bookTitle}</td>
                <td>${issue.borrowDate}</td>
                <td>${issue.dueDate}</td>
                <td><span style="color: ${issue.status === 'Returned' ? '#16a34a' : '#d97706'}">${issue.status}</span></td>
            </tr>
        `).join('');
    } catch(err) {
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

        tbody.innerHTML = activeBorrows.map(issue => `
            <tr>
                <td>${issue.student_id}</td>
                <td>${issue.studentName}</td>
                <td>Year N/A</td> <!-- Could fetch year from user data -->
                <td>${issue.email}</td>
                <td><span style="color:#d97706; font-weight:bold;">${issue.status}</span></td>
                <td>${issue.bookTitle}</td>
                <td>
                    <button onclick="acceptReturn('${issue.id}')" style="background:#16a34a; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">Accept Return</button>
                </td>
            </tr>
        `).join('');
    } catch(err) {
        console.error(err);
    }
}

// --- Add Book Logic ---
function openAddBookForm() { document.getElementById('addBookModal').style.display = 'flex'; }
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
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Book added successfully!');
            closeAddBookForm();
            event.target.reset();
            renderAdminCatalog();
            updateDashboardStats();
        } else {
            alert('Error: ' + data.error);
        }
    } catch(err) {
        console.error(err);
    }
}

// --- Issue Book Logic ---
function openIssueBookForm() { document.getElementById('issueBookModal').style.display = 'flex'; }
function closeIssueBookForm() { document.getElementById('issueBookModal').style.display = 'none'; }

async function saveIssuedBook(event) {
    event.preventDefault();
    // Assuming user inputs ISBN, we need api_borrow to handle it.
    // For simplicity, we just pass what they typed in bookId field.
    const payload = {
        action: 'issue',
        studentId: document.getElementById('issueStudentId').value,
        bookId: document.getElementById('issueBookId').value, // This expects a numeric ID now based on our API
        borrowDate: document.getElementById('issueBorrowDate').value,
        dueDate: document.getElementById('issueDueDate').value
    };

    try {
        const res = await fetch('api_borrow.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Book issued successfully!');
            closeIssueBookForm();
            event.target.reset();
            renderBorrowerTable();
            updateDashboardStats();
            renderAdminCatalog();
        } else {
            alert('Error issuing book: ' + data.error);
        }
    } catch(err) {
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
    } catch(err) {
        console.error(err);
    }
}

async function acceptReturn(issueId) {
    try {
        const res = await fetch('api_borrow.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'return', issueId: issueId }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Return accepted successfully!');
            renderBorrowerTable();
            renderActivityTable();
            updateDashboardStats();
            renderReturnList();
            renderAdminCatalog();
        } else {
            alert('Error: ' + data.error);
        }
    } catch(err) {
        console.error(err);
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
    } catch(err) {
        console.error(err);
    }
}

function selectStudent(index) {
    const student = globalUsers[index];
    if(!student) return;
    document.getElementById('selectedStudentIndex').value = index;
    document.getElementById('editStudentId').value = student.id;
    document.getElementById('editStudentName').value = student.name;
    document.getElementById('editStudentEmail').value = student.email;
    document.getElementById('editStudentPhone').value = ''; // Phone not in db currently
    document.getElementById('editStudentDepartment').value = student.department;
    document.getElementById('editStudentYear').value = student.year;
    
    const hint = document.getElementById('selectedStudentHint');
    if(hint) hint.textContent = 'Editing: ' + student.id;
}

async function saveStudentInfo(event) {
    event.preventDefault();
    const index = document.getElementById('selectedStudentIndex').value;
    if (index === '') {
        alert('Please select a student first.');
        return;
    }

    const payload = {
        action: 'update',
        id: document.getElementById('editStudentId').value,
        name: document.getElementById('editStudentName').value,
        email: document.getElementById('editStudentEmail').value,
        department: document.getElementById('editStudentDepartment').value,
        year: document.getElementById('editStudentYear').value
    };

    try {
        const res = await fetch('api_users.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Student information updated.');
            renderRegisteredStudents();
        } else {
            alert('Error: ' + data.error);
        }
    } catch(err) {
        console.error(err);
    }
}

function clearStudentForm() {
    document.getElementById('studentEditForm').reset();
    document.getElementById('selectedStudentIndex').value = '';
    const hint = document.getElementById('selectedStudentHint');
    if(hint) hint.textContent = 'Choose a student from the table first.';
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
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if(data.success) {
            alert('Student deleted.');
            clearStudentForm();
            renderRegisteredStudents();
            updateDashboardStats();
        } else {
            alert('Error: ' + data.error);
        }
    } catch(err) {
        console.error(err);
    }
}

// --- Admin Dynamic Grids ---
let globalBooks = [];
async function renderAdminCatalog() {
    const grid = document.getElementById('adminBookGrid');
    if (!grid) return;
    
    try {
        const res = await fetch('api_books.php');
        globalBooks = await res.json();
        
        grid.innerHTML = globalBooks.map(book => `
            <div class="resource-card">
                <div class="resource-cover">
                    <span class="resource-tag">Physical Book ID: ${book.id}</span>
                    <div class="cover-placeholder">
                        <i class="fa-solid fa-book"></i>
                    </div>
                </div>
                <div class="resource-card-info">
                    <h4>${book.title}</h4>
                    <p class="resource-author">${book.author}</p>
                    <p class="resource-meta">
                        <i class="fa-solid ${book.status === 'Available' ? 'fa-circle-check' : 'fa-circle-xmark'}" 
                           style="color: ${book.status === 'Available' ? '#16a34a' : '#dc2626'}"></i>
                        ${book.status} (Copies: ${book.copies})
                    </p>
                    <button class="resource-action-btn btn-return" onclick="deleteBook(${book.id})" style="border:1px solid #dc2626; color:#dc2626; padding:5px 10px; border-radius:5px; background:white; cursor:pointer;">
                        <i class="fa-solid fa-trash"></i> Delete Book
                    </button>
                </div>
            </div>
        `).join('');
    } catch(err) {
        console.error(err);
    }
}

async function deleteBook(id) {
    if (confirm("Are you sure you want to delete this book?")) {
        try {
            const res = await fetch('api_books.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'delete', id: id }),
                headers: {'Content-Type': 'application/json'}
            });
            const data = await res.json();
            if(data.success) {
                renderAdminCatalog();
                updateDashboardStats();
            } else {
                alert("Error: " + data.error);
            }
        } catch(err) {
            console.error(err);
        }
    }
}

function renderAdminResources() {
    // Left empty/stubbed out as per implementation plan to keep resources hardcoded or static for now, 
    // or just let the HTML be static for the "Resources" section.
}

function searchBook(context) {
    alert('Search function triggered. Searching currently not wired to API in this demo.');
}