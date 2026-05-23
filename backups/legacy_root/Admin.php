<?php 
session_start(); 
if(!isset($_SESSION['adminLoggedIn'])) { 
    header("Location: login.html"); 
    exit(); 
} 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Managment System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Admin.css">
</head>

<body>
    <div class="container">
        <div class="nav-bar">
            <div class="logo">
                <img src="img/logo.png" alt="LibriNet Logo" id="img">
                <h2>LibriNet</h2>
            </div>
            <nav>
                <ul id="mainNav">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#bookedit">Manage Books</a></li>
                    <li><a href="#useredit">Edit User Info</a></li>
                    <li><a href="#resources">Resources</a></li>
                    <li><a href="#help">Help</a></li>
                    <li class="mobile-menu-only"><a href="setting.html"><i class="fa-solid fa-gear"></i> Settings</a></li>
                    <li class="mobile-menu-only"><button type="button" onclick="goToLogin()"><i class="fa-solid fa-right-from-bracket" id="btn"></i> Log Out</button></li>

                </ul>
            </nav>

            <div class="nav-profile-container" id="navProfile">
                <div class="admin-avatar">
                    <img src="img/logo.png" alt="Admin" id="navAdminImg">
                </div>
                <span id="navAdminId" class="glitch-text">ADM1234</span>
            </div>



            <!-- <a href="setting.html" class="desktop-settings-link hide-on-mobile" title="Settings">
                <i class="fa-solid fa-gear"></i>
                <span class="settings-text">Settings</span>
            </a> -->


            <button type="button" class="hamburger" id="hamburger" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <!-- HOME PAGE -->
    <div class="HomePage" id="home">
        <div class="homee">
            <h1>Welcome to <span>LibriNet</span></h1>
            <p>Manage, Explore and Discover the world of Knowledge.</p>

            <div class="search-box">
                <select id="searchTypeHome">
                    <option value="title">Title</option>
                    <option value="author">Author</option>
                    <option value="subject">Subject</option>
                </select>
                <input type="text" id="searchInputHome" placeholder="Search books..." />
                <button onclick="searchBook('Home')">Search</button>
            </div>
        </div>
        <div class="dashboard">
            <h2>DashBoard Cards</h2>
            <div class="dashboard-card">
                <i class="fa-solid fa-book"></i>
                <h4>Total Books<br><span id="totalBooksCount">0 Item</span></h4>
            </div>
            <div class="dashboard-card">
                <i class="fa-solid fa-user-graduate"></i>
                <h4>Total Students<br><span id="totalStudentsCount">0 Item</span></h4>
            </div>
            <div class="dashboard-card">
                <i class="fa-solid fa-book-open-reader"></i>
                <h4>Issued Books<br><span id="issuedBooksCount">0 Item</span></h4>
            </div>
            <div class="dashboard-card">
                <i class="fa-solid fa-hourglass-half"></i>
                <h4>Pending Returns<br><span id="pendingReturnsCount">0 Item</span></h4>
            </div>
        </div>

        <div class="activity">
            <h2>Recent Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Borrower ID</th>
                        <th>Book Title</th>
                        <th>Borrowed Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="recentActivityBody"></tbody>
            </table>
        </div>
    </div>

    <div class="activity-modal" id="activityModal">
        <div class="activity-modal-card">
            <button type="button" class="modal-close" onclick="closeActivityDetails()">&times;</button>
            <h2>Borrowing Details</h2>
            <div class="activity-detail-grid" id="activityDetailGrid"></div>
            <div class="activity-notes">
                <h3>Admin Notes</h3>
                <p id="activityNotes"></p>
            </div>
            <div class="activity-actions" id="activityActions"></div>
        </div>
    </div>

    <!-- MANAGE BOOKS -->
    <div class="Book-edit" id="bookedit">
        <div class="heading">
            <div class="edit">
                <div class="searchinfo">
                    <h3>Search and Manage Borrowers</h3>
                    <h3>Status</h3>
                    <h3>Batch</h3>
                </div>
                <div class="infotik">
                    <div class="search-Box">
                        <select id="searchTypeBooks">
                            <option value="title">Title</option>
                            <option value="author">Author</option>
                            <option value="subject">Subject</option>
                        </select>
                        <input type="text" id="searchInputBooks" placeholder="Search books..." />
                        <button onclick="searchBook('Books')">Search</button>
                    </div>

                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="returned">Returned</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>

                    <select id="batchFilter">
                        <option value="">All Batches</option>
                        <option value="year1">Year 1</option>
                        <option value="year2">Year 2</option>
                        <option value="year3">Year 3</option>
                        <option value="year4">Year 4</option>
                    </select>
                </div>
            </div>
            <div class="action-bar">
                <button class="btn-add-book" type="button" onclick="openAddBookForm()"><i class="fa-solid fa-plus"></i>
                    Add New Book</button>
                <button class="btn-borrow" type="button" onclick="openIssueBookForm()"><i
                        class="fa-solid fa-book-reader"></i> Issue/Borrow Book</button>
                <button class="btn-return" type="button" onclick="openReturnBookForm()"><i
                        class="fa-solid fa-rotate-left"></i> Return Book</button>
            </div>

            <div class="table-container">
                <table class="borrower-table">
                    <thead>
                        <tr>
                            <th>Borrower ID</th>
                            <th>Full Name</th>
                            <th>Batch</th>
                            <th>Contact (Email)</th>
                            <th>Status</th>
                            <th>Books Issued</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="borrowerTableBody"></tbody>
                </table>
            </div>

            <!-- Dynamic Admin Catalog Grid -->
            <div style="margin-top: 40px;">
                <div class="searchinfo" style="margin-bottom: 20px;">
                    <h3><i class="fa-solid fa-book-open"></i> Full Library Catalog</h3>
                </div>
                <div class="resource-cards" id="adminBookGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px;">
                    <!-- JS will populate -->
                </div>
            </div>
        </div>
    </div>

    <div class="book-modal" id="addBookModal">
        <div class="book-modal-card">
            <button type="button" class="modal-close" onclick="closeAddBookForm()">&times;</button>
            <h2>Add New Book</h2>
            <form id="addBookForm" onsubmit="saveNewBook(event)">
                <div class="book-upload-box">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <label for="bookFile">Choose Book File</label>
                    <input type="file" id="bookFile" accept=".pdf,.doc,.docx,.epub,.txt,image/*"
                        onchange="showSelectedBookFile()">
                    <p id="selectedBookFileName">No file selected</p>
                </div>

                <div class="book-form-grid">
                    <input type="text" id="bookTitle" placeholder="Book Title" required>
                    <input type="text" id="bookAuthor" placeholder="Author" required>
                    <input type="text" id="bookSubject" placeholder="Subject" required>
                    <input type="text" id="bookIsbn" placeholder="ISBN / Book ID" required>
                    <select id="bookCategory" required>
                        <option value="">Select Category</option>
                        <option value="Textbook">Textbook</option>
                        <option value="Reference">Reference</option>
                        <option value="E-Book">E-Book</option>
                        <option value="Research">Research</option>
                    </select>
                    <input type="number" id="bookCopies" placeholder="Number of Copies" min="1" value="1" required>
                </div>

                <textarea id="bookDescription" placeholder="Short description or notes about the book"></textarea>

                <div class="book-form-actions">
                    <button type="submit" class="save">Save Book</button>
                    <button type="button" class="cancel" onclick="closeAddBookForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="book-modal" id="issueBookModal">
        <div class="book-modal-card">
            <button type="button" class="modal-close" onclick="closeIssueBookForm()">&times;</button>
            <h2>Issue / Borrow Book</h2>
            <form id="issueBookForm" onsubmit="saveIssuedBook(event)">
                <input type="hidden" id="issueEditIndex">
                <div class="book-form-grid">
                    <input type="text" id="issueStudentId" placeholder="Student ID" required>
                    <input type="text" id="issueStudentName" placeholder="Student Full Name" required>
                    <input type="email" id="issueStudentEmail" placeholder="Student Email">
                    <input type="text" id="issueStudentPhone" placeholder="Phone Number">
                    <select id="issueStudentBatch" required>
                        <option value="">Select Batch</option>
                        <option value="Year 1">Year 1</option>
                        <option value="Year 2">Year 2</option>
                        <option value="Year 3">Year 3</option>
                        <option value="Year 4">Year 4</option>
                    </select>
                    <input type="text" id="issueBookTitle" placeholder="Book Title" required>
                    <input type="text" id="issueBookId" placeholder="Book ID / ISBN" required>
                    <input type="date" id="issueBorrowDate" required>
                    <input type="date" id="issueDueDate" required>
                    <input type="time" id="issueBorrowTime" required>
                    <select id="issueBookCondition" required>
                        <option value="">Book Condition</option>
                        <option value="New">New</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Damaged">Damaged</option>
                    </select>
                </div>

                <textarea id="issueNotes" placeholder="Borrowing notes, rules, or special condition"></textarea>

                <div class="book-form-actions">
                    <button type="submit" class="save">Issue Book</button>
                    <button type="button" class="cancel" onclick="closeIssueBookForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="book-modal" id="returnBookModal">
        <div class="book-modal-card">
            <button type="button" class="modal-close" onclick="closeReturnBookForm()">&times;</button>
            <h2>Return Book Requests</h2>
            <p class="return-helper">Select a pending borrowed book and accept the return after checking the book
                condition.</p>
            <div id="returnRequestList" class="return-request-list"></div>
        </div>
    </div>

    <!-- USER EDIT -->
    <div class="userEdit" id="useredit">
        <div class="userinffo">
            <h1>Registered Students</h1>
            <p>Select a student from the list, then update their information</p>
        </div>
        <div class="containerr">
            <div class="left">
                <div class="card student-table-card">
                    <h3>All Registered Students</h3>
                    <div class="student-filter">
                        <input type="text" id="studentSearchInput"
                            placeholder="Search by name, ID, email, or department" oninput="renderRegisteredStudents()">
                    </div>
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="registeredStudentBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="right">
                <div class="card student-edit-card">
                    <h3 id="profileSummaryTitle">Edit Selected Student</h3>
                    <p id="selectedStudentHint">Choose a student from the table first.</p>
                    <form id="studentEditForm" onsubmit="saveStudentInfo(event)">
                        <input type="hidden" id="selectedStudentIndex">
                        <input type="text" id="editStudentId" placeholder="Student ID" required>
                        <input type="text" id="editStudentName" placeholder="Full Name" required>
                        <input type="email" id="editStudentEmail" placeholder="Email" required>
                        <input type="text" id="editStudentPhone" placeholder="Phone Number" required>
                        <input type="text" id="editStudentDepartment" placeholder="Department" required>
                        <select id="editStudentYear" required>
                            <option value="">Select Year</option>
                            <option value="Year 1">Year 1</option>
                            <option value="Year 2">Year 2</option>
                            <option value="Year 3">Year 3</option>
                            <option value="Year 4">Year 4</option>
                        </select>
                        <select id="editStudentStatus" required>
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                        <div class="buttons student-buttons">
                            <button class="save" type="submit">Update Student</button>
                            <button class="cancel" type="button" onclick="clearStudentForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3 id="activityTitle">Activity</h3>
                    <p id="studentBooksBorrowed">Books Borrowed: -</p>
                    <p id="studentBooksReturned">Books Returned: -</p>
                    <p id="studentPendingBooks">Pending: -</p>
                </div>

                <div class="card danger">
                    <h3>Account Actions</h3>
                    <button class="delete" type="button" onclick="deleteSelectedStudent()">Delete Selected
                        Student</button>
                </div>
            </div>
        </div>
    </div>

    <!-- RESOURCES -->
    <div class="containers" id="resources">

        <div class="resources-hero">
            <div class="resources-hero-overlay">
                <h2>Resources</h2>
                <p>Explore and access a wide range of learning materials</p>
                <div class="resource-search">
                    <div class="search-inner">
                        <select>
                            <option>All Resources</option>
                            <option>E-Books</option>
                            <option>Lecture Notes</option>
                            <option>Past Exams</option>
                            <option>Tutorials</option>
                        </select>
                        <input type="text" placeholder="Search resources, e.g., Python, Database, etc." />
                        <i class="fa fa-search search-icon"></i>
                        <button>Search</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="res-body">
            <div class="res-section-header">
                <div>
                    <h3 class="res-section-title">Resource Categories</h3>
                    <div class="res-underline"></div>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button onclick="openFileUploader()" style="background: #16a34a; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Resource</button>
                    <a href="#" class="view-all-btn"><i class="fa-solid fa-border-all"></i> View All Categories</a>
                </div>
            </div>

            <div class="res-categories">
                <div class="res-cat-card">
                    <div class="res-cat-icon green-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div class="res-cat-info">
                        <h4>E-Books</h4>
                        <p>Access a wide collection of digital books</p>
                        <span class="res-count green-count">120 Items</span>
                    </div>
                    <a href="#" class="res-explore-btn green-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>

                <div class="res-cat-card">
                    <div class="res-cat-icon orange-icon"><i class="fa-solid fa-file-lines"></i></div>
                    <div class="res-cat-info">
                        <h4>Lecture Notes</h4>
                        <p>Download lecture notes and study materials</p>
                        <span class="res-count orange-count">85 Items</span>
                    </div>
                    <a href="#" class="res-explore-btn orange-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>

                <div class="res-cat-card">
                    <div class="res-cat-icon blue-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div class="res-cat-info">
                        <h4>Past Exams</h4>
                        <p>Practice with past exam papers and solutions</p>
                        <span class="res-count blue-count">64 Items</span>
                    </div>
                    <a href="#" class="res-explore-btn blue-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>

                <div class="res-cat-card">
                    <div class="res-cat-icon purple-icon"><i class="fa-solid fa-video"></i></div>
                    <div class="res-cat-info">
                        <h4>Tutorials</h4>
                        <p>Watch videos and learn new concepts</p>
                        <span class="res-count purple-count">42 Items</span>
                    </div>
                    <a href="#" class="res-explore-btn purple-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="res-section-header" style="margin-top: 30px;">
                <div>
                    <h3 class="res-section-title">Recently Added Resources</h3>
                    <div class="res-underline"></div>
                </div>
                <a href="#" class="view-all-btn"><i class="fa-solid fa-border-all"></i> View All Resources</a>
            </div>

            <div class="res-recent" id="adminResourceGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px;">
                <!-- JS will populate with real files -->
            </div>

            <div class="res-why">
                <h3 class="res-section-title" style="margin-bottom: 6px;">Why use our resources?</h3>
                <div class="res-why-cards">
                    <div class="res-why-card">
                        <div class="res-why-icon green-why"><i class="fa-solid fa-award"></i></div>
                        <div>
                            <h4>Trusted Content</h4>
                            <p>All resources are verified and curated by experts.</p>
                        </div>
                    </div>
                    <div class="res-why-card">
                        <div class="res-why-icon orange-why"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <h4>Save Time</h4>
                            <p>Quick access to quality study materials.</p>
                        </div>
                    </div>
                    <div class="res-why-card">
                        <div class="res-why-icon blue-why"><i class="fa-solid fa-chart-line"></i></div>
                        <div>
                            <h4>Improve Performance</h4>
                            <p>Practice more and achieve better results.</p>
                        </div>
                    </div>
                    <div class="res-why-card">
                        <div class="res-why-icon purple-why"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <h4>Learn Anytime</h4>
                            <p>Access resources anytime, anywhere.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- ===== HELP / FAQ ===== -->
    <div class="help-section" id="help">
        <div class="help">
            <h2>Admin Help Center</h2>
            <p>Everything you need to manage the LibriNet library system</p>
            <div class="faq-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="faqSearch" placeholder="Search admin help..." oninput="filterFAQ()" />
            </div>
        </div>
        <div class="faq-tabs">
            <button class="faq-tab active" onclick="setTab('all',this)">All</button>
            <button class="faq-tab" onclick="setTab('books',this)">Managing Books</button>
            <button class="faq-tab" onclick="setTab('users',this)">Managing Users</button>
            <button class="faq-tab" onclick="setTab('borrowing',this)">Borrowing</button>
            <button class="faq-tab" onclick="setTab('system',this)">System</button>
        </div>
        <div class="faq-list" id="faqList"></div>
        <div class="faq-contact">
            <i class="fa-solid fa-shield-halved"></i>
            <p>Need technical support? Contact the system developer at <strong>librinet@support.com</strong></p>
        </div>
    </div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        function goToLogin() { window.location.href = "logout.php"; }

        function changeAdminProfileImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function () {
                document.getElementById('navAdminImg').src = reader.result;
                document.getElementById('adminProfilePreview').src = reader.result;
                localStorage.setItem('adminProfileImage', reader.result);
            };
            reader.readAsDataURL(file);
        }

        let registeredStudents = JSON.parse(localStorage.getItem('registeredStudents')) || [
            {
                id: 'NSR/0324/16',
                name: 'Sara J.',
                email: 'sara.j@example.com',
                phone: '0912345678',
                department: 'Computer Science',
                year: 'Year 1',
                status: 'Pending',
                borrowed: 2,
                returned: 4,
                pending: 1
            },
            {
                id: 'NSR/0451/16',
                name: 'David L.',
                email: 'david.l@example.com',
                phone: '0923456789',
                department: 'Information Technology',
                year: 'Year 2',
                status: 'Active',
                borrowed: 0,
                returned: 10,
                pending: 0
            },
            {
                id: 'NSR/0198/16',
                name: 'Marta A.',
                email: 'marta.a@example.com',
                phone: '0934567890',
                department: 'Software Engineering',
                year: 'Year 3',
                status: 'Active',
                borrowed: 3,
                returned: 7,
                pending: 2
            }
        ];

        function saveRegisteredStudents() {
            localStorage.setItem('registeredStudents', JSON.stringify(registeredStudents));
        }

        function renderRegisteredStudents() {
            const body = document.getElementById('registeredStudentBody');
            if (!body) return;

            const query = document.getElementById('studentSearchInput').value.toLowerCase();
            const filtered = registeredStudents
                .map((student, index) => ({ student, index }))
                .filter(({ student }) => {
                    return student.id.toLowerCase().includes(query) ||
                        student.name.toLowerCase().includes(query) ||
                        student.email.toLowerCase().includes(query) ||
                        student.department.toLowerCase().includes(query);
                });

            if (!filtered.length) {
                body.innerHTML = '<tr><td colspan="6">No registered students found.</td></tr>';
                return;
            }

            body.innerHTML = filtered.map(({ student, index }) => `
        <tr>
            <td>${student.id}</td>
            <td>${student.name}</td>
            <td>${student.department}</td>
            <td>${student.year}</td>
            <td>${student.email}</td>
            <td><button class="edit-btn" type="button" onclick="selectStudent(${index})">Edit</button></td>
        </tr>
    `).join('');
        }

        function selectStudent(index) {
            const student = registeredStudents[index];
            document.getElementById('selectedStudentIndex').value = index;
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editStudentName').value = student.name;
            document.getElementById('editStudentEmail').value = student.email;
            document.getElementById('editStudentPhone').value = student.phone;
            document.getElementById('editStudentDepartment').value = student.department;
            document.getElementById('editStudentYear').value = student.year;
            document.getElementById('editStudentStatus').value = student.status;
            document.getElementById('selectedStudentHint').textContent = 'Editing: ' + student.id;
            document.getElementById('studentBooksBorrowed').textContent = 'Books Borrowed: ' + student.borrowed;
            document.getElementById('studentBooksReturned').textContent = 'Books Returned: ' + student.returned;
            document.getElementById('studentPendingBooks').textContent = 'Pending: ' + student.pending;
        }

        function saveStudentInfo(event) {
            event.preventDefault();

            const index = document.getElementById('selectedStudentIndex').value;
            if (index === '') {
                alert('Please select a student first.');
                return;
            }

            registeredStudents[index] = {
                ...registeredStudents[index],
                id: document.getElementById('editStudentId').value.trim(),
                name: document.getElementById('editStudentName').value.trim(),
                email: document.getElementById('editStudentEmail').value.trim(),
                phone: document.getElementById('editStudentPhone').value.trim(),
                department: document.getElementById('editStudentDepartment').value.trim(),
                year: document.getElementById('editStudentYear').value,
                status: document.getElementById('editStudentStatus').value
            };

            saveRegisteredStudents();
            renderRegisteredStudents();
            selectStudent(Number(index));
            alert('Student information updated.');
        }

        function clearStudentForm() {
            document.getElementById('studentEditForm').reset();
            document.getElementById('selectedStudentIndex').value = '';
            document.getElementById('selectedStudentHint').textContent = 'Choose a student from the table first.';
            document.getElementById('studentBooksBorrowed').textContent = 'Books Borrowed: -';
            document.getElementById('studentBooksReturned').textContent = 'Books Returned: -';
            document.getElementById('studentPendingBooks').textContent = 'Pending: -';
        }

        function deleteSelectedStudent() {
            const index = document.getElementById('selectedStudentIndex').value;
            if (index === '') {
                alert('Please select a student first.');
                return;
            }

            const student = registeredStudents[index];
            if (!confirm('Delete student ' + student.id + '?')) return;

            registeredStudents.splice(index, 1);
            saveRegisteredStudents();
            clearStudentForm();
            renderRegisteredStudents();
        }

        function loadAdminProfile() {
            const adminId = localStorage.getItem('adminId') || 'ADM1234';
            const adminImage = localStorage.getItem('adminProfileImage');

            document.getElementById('navAdminId').textContent = adminId;

            if (adminImage) {
                document.getElementById('navAdminImg').src = adminImage;
            }
        }

        function toggleMenu() {
            const mainNav = document.getElementById('mainNav');
            mainNav.classList.toggle('open');
            const hamburger = document.getElementById('hamburger');
            hamburger.classList.toggle('active');
        }

        function closeMenu() {
            document.getElementById('mainNav').classList.remove('open');
            document.getElementById('hamburger').classList.remove('active');
        }

        function searchBook(area) {
            const type = document.getElementById('searchType' + area).value;
            const query = document.getElementById('searchInput' + area).value.trim();
            if (!query) return alert('Please enter a search term.');
            alert('Searching by ' + type + ': "' + query + '"');
        }

        function searchBorrower() {
            const type = document.getElementById('searchTypeBorrower').value;
            const query = document.getElementById('searchInputBorrower').value.trim();
            if (!query) return alert('Please enter a search term.');
            alert('Searching borrower by ' + type + ': "' + query + '"');
        }

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

        let dashboardStats = JSON.parse(localStorage.getItem('dashboardStatsV2')) || {
            totalBooks: 0,
            totalStudents: 0,
            issuedBooks: 0,
            pendingReturns: 0
        };

        let libraryBooks = JSON.parse(localStorage.getItem('libraryBooks')) || [];
        let borrowedBooks = JSON.parse(localStorage.getItem('borrowedBooks')) || [];
        const RETURN_ROW_VISIBLE_TIME = 30000;

        const sampleBorrowerRows = [
            {
                borrowerId: '#NSR/0324/16',
                borrowerName: 'Sara J.',
                batch: 'Year 1',
                email: 'sara.j@example.com',
                status: 'Pending',
                booksIssued: 2
            },
            {
                borrowerId: '#NSR/0451/16',
                borrowerName: 'David L.',
                batch: 'Year 2',
                email: 'david.l@example.com',
                status: 'Active',
                booksIssued: 0
            }
        ];

        function saveDashboardStats() {
            localStorage.setItem('dashboardStatsV2', JSON.stringify(dashboardStats));
        }

        function renderDashboardStats() {
            document.getElementById('totalBooksCount').textContent = formatItemCount(dashboardStats.totalBooks);
            document.getElementById('totalStudentsCount').textContent = formatItemCount(dashboardStats.totalStudents);
            document.getElementById('issuedBooksCount').textContent = formatItemCount(dashboardStats.issuedBooks);
            document.getElementById('pendingReturnsCount').textContent = formatItemCount(dashboardStats.pendingReturns);
        }

        function formatItemCount(count) {
            return count + (count === 1 ? ' Item' : ' Items');
        }

        function openAddBookForm() {
            document.getElementById('addBookModal').classList.add('open');
        }

        function closeAddBookForm() {
            document.getElementById('addBookModal').classList.remove('open');
            document.getElementById('addBookForm').reset();
            document.getElementById('selectedBookFileName').textContent = 'No file selected';
        }

        function showSelectedBookFile() {
            const file = document.getElementById('bookFile').files[0];
            document.getElementById('selectedBookFileName').textContent = file ? file.name : 'No file selected';
        }

        function getVisibleBorrowedBooks() {
            const now = Date.now();
            const visibleBooks = borrowedBooks.filter(book => {
                return book.status !== 'Returned' || !book.returnedAt || now - book.returnedAt < RETURN_ROW_VISIBLE_TIME;
            });

            if (visibleBooks.length !== borrowedBooks.length) {
                borrowedBooks = visibleBooks;
                localStorage.setItem('borrowedBooks', JSON.stringify(borrowedBooks));
            }

            return visibleBooks;
        }

        function renderBorrowerTable() {
            const body = document.getElementById('borrowerTableBody');
            if (!body) return;

            const borrowedRows = getVisibleBorrowedBooks().map((book, index) => ({
                borrowerId: book.borrowerId,
                borrowerName: book.borrowerName,
                batch: book.batch || 'Not selected',
                email: book.email || '-',
                status: book.status,
                booksIssued: book.status === 'Pending' ? 1 : 0,
                borrowedIndex: index
            }));

            const rows = [...sampleBorrowerRows, ...borrowedRows];

            body.innerHTML = rows.map(row => {
                const badgeClass = row.status === 'Pending' ? 'pending' : row.status === 'Returned' ? 'returned' : 'active';
                const actions = row.borrowedIndex !== undefined
                    ? `<button class="info-btn" type="button" onclick="showBorrowerInfo(${row.borrowedIndex})">Info</button>`
                    : `<button class="info-btn" type="button" onclick="showSampleBorrowerInfo('${row.borrowerId}')">Info</button>`;

                return `
            <tr>
                <td>${row.borrowerId}</td>
                <td><strong>${row.borrowerName}</strong></td>
                <td>${row.batch}</td>
                <td>${row.email}</td>
                <td><span class="badge ${badgeClass}">${row.status}</span></td>
                <td>${row.booksIssued}</td>
                <td>${actions}</td>
            </tr>
        `;
            }).join('');
        }

        function removeBorrowerRow(index) {
            const removedBook = borrowedBooks[index];
            if (!removedBook) return;

            if (!confirm('Delete this borrowed book record?')) return;

            borrowedBooks.splice(index, 1);
            localStorage.setItem('borrowedBooks', JSON.stringify(borrowedBooks));

            if (removedBook.status === 'Pending') {
                returnBookToDashboard();
            }

            renderBorrowerTable();
            renderRecentActivity();
            renderReturnRequests();
        }

        function showBorrowerInfo(index) {
            const activityIndex = getAllRecentActivities().findIndex(activity => {
                return activity.source === 'borrowedBooks' && activity.borrowerId === borrowedBooks[index].borrowerId && activity.bookId === borrowedBooks[index].bookId;
            });

            if (activityIndex >= 0) {
                showActivityDetails(activityIndex);
            }
        }

        function showSampleBorrowerInfo(borrowerId) {
            const cleanId = borrowerId.replace('#', '');
            const activityIndex = getAllRecentActivities().findIndex(activity => activity.borrowerId === cleanId);

            if (activityIndex >= 0) {
                showActivityDetails(activityIndex);
            } else {
                alert('No detailed information found for this sample row.');
            }
        }

        function saveNewBook(event) {
            event.preventDefault();

            const file = document.getElementById('bookFile').files[0];
            const copies = Number(document.getElementById('bookCopies').value) || 1;
            const book = {
                id: Date.now(), // needed for resources mapping
                title: document.getElementById('bookTitle').value.trim(),
                author: document.getElementById('bookAuthor').value.trim(),
                subject: document.getElementById('bookSubject').value.trim(),
                isbn: document.getElementById('bookIsbn').value.trim(),
                category: document.getElementById('bookCategory').value,
                status: 'Available', // needed for resources mapping
                copies: copies,
                description: document.getElementById('bookDescription').value.trim(),
                fileName: file ? file.name : 'No file attached',
                addedAt: new Date().toLocaleString()
            };

            libraryBooks.push(book);
            localStorage.setItem('libraryBooks', JSON.stringify(libraryBooks));
            dashboardStats.totalBooks += copies;
            saveDashboardStats();
            renderDashboardStats();
            closeAddBookForm();
            if(typeof renderAdminResources === 'function') renderAdminResources();
            alert('Book added successfully.');
        }

        function renderAdminResources() {
            const grid = document.getElementById('adminResourceGrid');
            if (!grid) return;
            
            // Real resources from the user side
            const resources = [
                // DAA Category
                { id: 1, title: 'Introduction to DAA', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '327 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/1 Intoduction (2).ppt', module: 'DAA' },
                { id: 2, title: 'Analysis of Algorithms', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '624 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/2 Analysis of algorithm (2).ppt', module: 'DAA' },
                { id: 3, title: 'Graph Theory', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '438 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/3 Graph.ppt', module: 'DAA' },
                { id: 4, title: 'Divide & Conquer', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '335 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/4 Divide Conquer.ppt', module: 'DAA' },
                { id: 5, title: 'Greedy Algorithms', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '175 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/5 Greedy.ppt', module: 'DAA' },
                { id: 6, title: 'Dynamic Programming', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '225 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/6 Dynamic programming.ppt', module: 'DAA' },
                { id: 7, title: 'Backtracking', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '209 KB', icon: 'fa-file-powerpoint', path: 'files/DAA/7 BackTrack.ppt', module: 'DAA' },
                
                // Web Category
                { id: 8, title: 'Web Development Ch 1', author: 'Web Dept', type: 'Study Material', format: 'PPTX', size: '915 KB', icon: 'fa-file-powerpoint', path: 'files/web/Chapter 1.pptx', module: 'Web Development' },
                { id: 9, title: 'HTML Essentials', author: 'Web Dept', type: 'Study Material', format: 'PPTX', size: '745 KB', icon: 'fa-file-powerpoint', path: 'files/web/Ch 2 HTML.pptx', module: 'Web Development' },
                { id: 10, title: 'CSS Styling', author: 'Web Dept', type: 'Study Material', format: 'PPTX', size: '877 KB', icon: 'fa-file-powerpoint', path: 'files/web/Ch 3 CSS.pptx', module: 'Web Development' },
                { id: 11, title: 'JavaScript Logic', author: 'Web Dept', type: 'Study Material', format: 'PPT', size: '5.3 MB', icon: 'fa-file-powerpoint', path: 'files/web/Ch 4 JavaScript.ppt', module: 'Web Development' },
                { id: 12, title: 'Web Development Ch 5', author: 'Web Dept', type: 'Study Material', format: 'PPT', size: '1.8 MB', icon: 'fa-file-powerpoint', path: 'files/web/Chapter Five (2).ppt', module: 'Web Development' },
                { id: 13, title: 'Web Development Ch 6', author: 'Web Dept', type: 'Study Material', format: 'PPTX', size: '1.9 MB', icon: 'fa-file-powerpoint', path: 'files/web/Chapter 6 (2).pptx', module: 'Web Development' },
                { id: 14, title: 'Course Outline', author: 'Library Admin', type: 'E-Book', format: 'PDF', size: '185 KB', icon: 'fa-file-pdf', path: 'files/web/Course Outline.pdf', module: 'Web Development' },
                
                // Graphics Category
                { id: 15, title: 'Computer Graphics Ch 1', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '560 KB', icon: 'fa-file-powerpoint', path: 'files/graphics/chap1 (9).ppt', module: 'Computer Graphics' },
                { id: 16, title: 'Computer Graphics Ch 2', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '363 KB', icon: 'fa-file-powerpoint', path: 'files/graphics/chap2 (9).ppt', module: 'Computer Graphics' },
                { id: 17, title: 'Computer Graphics Ch 3', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '571 KB', icon: 'fa-file-powerpoint', path: 'files/graphics/chap3 (6).ppt', module: 'Computer Graphics' },
                { id: 18, title: 'Computer Graphics Ch 4', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '550 KB', icon: 'fa-file-powerpoint', path: 'files/graphics/chap4 (5).ppt', module: 'Computer Graphics' },
                { id: 19, title: 'Computer Graphics Ch 6', author: 'Course Material', type: 'Study Material', format: 'PPT', size: '454 KB', icon: 'fa-file-powerpoint', path: 'files/graphics/chap 6 (2).ppt', module: 'Computer Graphics' },
                { id: 20, title: 'OpenGL Programming Guide', author: 'Khronos Group', type: 'E-Book', format: 'PDF', size: '35.4 MB', icon: 'fa-file-pdf', path: 'files/graphics/opengl book (2).pdf', module: 'Computer Graphics' },
                { id: 21, title: 'Computer Graphics Tutorials', author: 'YouTube Series', type: 'Video', format: 'Video', size: 'Playlist', icon: 'fa-circle-play', path: 'https://www.youtube.com/watch?v=Jv52kWUUCng&list=PLzfVsIhtvVY2dCi7RK4VGhSbrFHzSPz6A&index=23', module: 'Computer Graphics' },
                { id: 22, title: 'Visual Studio Community', author: 'Microsoft', type: 'Software', format: 'ZIP', size: '1.2 MB', icon: 'fa-file-zipper', path: 'files/graphics/visual-studio-community-16-8-30907 (2).zip', module: 'Computer Graphics' }
            ];

            // Map physical books from localStorage to look like resources
            let currentBooks = JSON.parse(localStorage.getItem('libraryBooks')) || [];
            let mappedBooks = currentBooks.map(b => ({
                id: b.id || Math.random(),
                title: b.title,
                author: b.author,
                type: 'Physical Book',
                format: 'Book',
                size: b.status || 'Available',
                icon: 'fa-book',
                path: null,
                module: b.category,
                isCatalogBook: true,
                status: b.status || 'Available'
            }));

            let combinedResources = [...mappedBooks, ...resources];

            grid.innerHTML = combinedResources.map(res => `
                <div class="resource-card">
                    <div class="resource-cover">
                        <span class="resource-tag">${res.type || res.module}</span>
                        <div class="cover-placeholder" style="cursor:pointer;" onclick="${res.path ? \`window.open('\${res.path}', '_blank')\` : \`alert('Viewing \${res.title}')\`}">
                            <i class="fa-solid ${res.icon || 'fa-file'}"></i>
                        </div>
                    </div>
                    <div class="resource-card-info">
                        <h4>${res.title}</h4>
                        <p class="resource-author">${res.author}</p>
                        <p class="resource-meta">
                            <i class="fa-solid ${res.isCatalogBook ? (res.status === 'Available' ? 'fa-circle-check' : 'fa-circle-xmark') : 'fa-file-lines'}"></i> 
                            ${res.format} • ${res.size}
                        </p>
                        ${res.path ? \`<button class="resource-action-btn btn-view" onclick="window.open('\${res.path}', '_blank')">
                            <i class="fa-solid fa-eye"></i> View File
                        </button>\` : ''}
                    </div>
                </div>
            `).join('');
        }

        function openIssueBookForm() {
            if (dashboardStats.totalBooks <= dashboardStats.issuedBooks) {
                alert('No available books to issue. Add a book first.');
                return;
            }

            document.getElementById('issueEditIndex').value = '';
            document.getElementById('issueBookModal').classList.add('open');
            const today = new Date();
            const dueDate = new Date();
            dueDate.setDate(today.getDate() + 7);
            document.getElementById('issueBorrowDate').value = today.toISOString().slice(0, 10);
            document.getElementById('issueDueDate').value = dueDate.toISOString().slice(0, 10);
            document.getElementById('issueBorrowTime').value = today.toTimeString().slice(0, 5);
        }

        function closeIssueBookForm() {
            document.getElementById('issueBookModal').classList.remove('open');
            document.getElementById('issueBookForm').reset();
            document.getElementById('issueEditIndex').value = '';
        }

        function editBorrowerRow(index) {
            const book = borrowedBooks[index];
            if (!book) return;

            document.getElementById('issueEditIndex').value = index;
            document.getElementById('issueStudentId').value = book.borrowerId;
            document.getElementById('issueStudentName').value = book.borrowerName;
            document.getElementById('issueStudentEmail').value = book.email || '';
            document.getElementById('issueStudentPhone').value = book.phone || '';
            document.getElementById('issueStudentBatch').value = book.batch || '';
            document.getElementById('issueBookTitle').value = book.bookTitle;
            document.getElementById('issueBookId').value = book.bookId;
            document.getElementById('issueBorrowDate').value = book.borrowedDate;
            document.getElementById('issueDueDate').value = book.dueDate;
            document.getElementById('issueBorrowTime').value = book.borrowedTime;
            document.getElementById('issueBookCondition').value = book.condition;
            document.getElementById('issueNotes').value = book.notes || '';
            document.getElementById('issueBookModal').classList.add('open');
        }

        function openReturnBookForm() {
            renderReturnRequests();
            document.getElementById('returnBookModal').classList.add('open');
        }

        function closeReturnBookForm() {
            document.getElementById('returnBookModal').classList.remove('open');
        }

        function renderReturnRequests() {
            const list = document.getElementById('returnRequestList');
            const pendingBooks = borrowedBooks
                .map((book, index) => ({ book, index }))
                .filter(({ book }) => book.status === 'Pending');

            if (!pendingBooks.length) {
                list.innerHTML = '<div class="empty-return">No pending return requests right now.</div>';
                return;
            }

            list.innerHTML = pendingBooks.map(({ book, index }) => `
        <div class="return-request-card">
            <div>
                <h3>${book.bookTitle}</h3>
                <p><strong>Student:</strong> ${book.borrowerName} (${book.borrowerId})</p>
                <p><strong>Borrowed:</strong> ${book.borrowedDate} at ${book.borrowedTime}</p>
                <p><strong>Due Date:</strong> ${book.dueDate}</p>
                <p><strong>Condition Before:</strong> ${book.condition}</p>
            </div>
            <button type="button" onclick="acceptReturnRequest(${index})">Accept Return</button>
        </div>
    `).join('');
        }

        function acceptReturnRequest(index) {
            const now = new Date();
            borrowedBooks[index] = {
                ...borrowedBooks[index],
                status: 'Returned',
                returnedDate: now.toISOString().slice(0, 10),
                returnedTime: now.toTimeString().slice(0, 5),
                returnedAt: now.getTime(),
                penalty: 'No penalty',
                penaltyAmount: '0 ETB'
            };

            localStorage.setItem('borrowedBooks', JSON.stringify(borrowedBooks));
            returnBookToDashboard();
            renderBorrowerTable();
            renderRecentActivity();
            renderReturnRequests();
            alert('Return accepted.');
        }

        function saveIssuedBook(event) {
            event.preventDefault();

            const editIndex = document.getElementById('issueEditIndex').value;

            if (editIndex === '' && dashboardStats.totalBooks <= dashboardStats.issuedBooks) {
                alert('No available books to issue. Add a book first.');
                return;
            }

            const issuedBook = {
                borrowerId: document.getElementById('issueStudentId').value.trim(),
                borrowerName: document.getElementById('issueStudentName').value.trim(),
                email: document.getElementById('issueStudentEmail').value.trim(),
                phone: document.getElementById('issueStudentPhone').value.trim(),
                batch: document.getElementById('issueStudentBatch').value,
                bookTitle: document.getElementById('issueBookTitle').value.trim(),
                bookId: document.getElementById('issueBookId').value.trim(),
                borrowedDate: document.getElementById('issueBorrowDate').value,
                borrowedTime: document.getElementById('issueBorrowTime').value,
                dueDate: document.getElementById('issueDueDate').value,
                condition: document.getElementById('issueBookCondition').value,
                notes: document.getElementById('issueNotes').value.trim(),
                status: 'Pending',
                handledBy: localStorage.getItem('adminId') || 'ADM1234'
            };

            if (editIndex === '') {
                borrowedBooks.push(issuedBook);
                dashboardStats.issuedBooks += 1;
                dashboardStats.pendingReturns += 1;
            } else {
                borrowedBooks[Number(editIndex)] = {
                    ...borrowedBooks[Number(editIndex)],
                    ...issuedBook
                };
            }

            localStorage.setItem('borrowedBooks', JSON.stringify(borrowedBooks));
            saveDashboardStats();
            renderDashboardStats();
            renderRecentActivity();
            renderBorrowerTable();
            renderReturnRequests();
            closeIssueBookForm();
            alert(editIndex === '' ? 'Book issued successfully.' : 'Borrowed book record updated.');
        }

        function returnBookToDashboard() {
            if (dashboardStats.issuedBooks > 0) {
                dashboardStats.issuedBooks -= 1;
            }

            if (dashboardStats.pendingReturns > 0) {
                dashboardStats.pendingReturns -= 1;
            }

            saveDashboardStats();
            renderDashboardStats();
        }

        function markBorrowedBookReturned(index) {
            const now = new Date();
            borrowedBooks[index] = {
                ...borrowedBooks[index],
                status: 'Returned',
                returnedDate: now.toISOString().slice(0, 10),
                returnedTime: now.toTimeString().slice(0, 5),
                returnedAt: now.getTime(),
                penalty: 'No penalty',
                penaltyAmount: '0 ETB'
            };

            localStorage.setItem('borrowedBooks', JSON.stringify(borrowedBooks));
            returnBookToDashboard();
            renderBorrowerTable();
            renderRecentActivity();
            showActivityDetails(index);
            alert('Book marked as returned.');
        }

        const recentActivities = [
            {
                borrowerId: 'NSR/0324/16',
                borrowerName: 'Sara J.',
                department: 'Computer Science',
                batch: 'Year 1',
                email: 'sara.j@example.com',
                phone: '0912345678',
                bookTitle: 'Database System',
                bookId: 'BOOK-DB-014',
                author: 'Thomas Connolly',
                borrowedDate: '2026-04-20',
                borrowedTime: '09:30 AM',
                dueDate: '2026-04-27',
                returnedDate: '2026-04-27',
                returnedTime: '02:15 PM',
                status: 'Returned',
                penalty: 'No penalty',
                penaltyAmount: '0 ETB',
                bookCondition: 'Good',
                handledBy: 'ADM1234',
                notes: 'Returned on the due date. Book condition checked and accepted.'
            },
            {
                borrowerId: 'NSR/0451/16',
                borrowerName: 'David L.',
                department: 'Information Technology',
                batch: 'Year 2',
                email: 'david.l@example.com',
                phone: '0923456789',
                bookTitle: 'Java Programming',
                bookId: 'BOOK-JAVA-021',
                author: 'Herbert Schildt',
                borrowedDate: '2026-04-22',
                borrowedTime: '11:05 AM',
                dueDate: '2026-04-29',
                returnedDate: 'Not returned yet',
                returnedTime: '-',
                status: 'Pending',
                penalty: 'No penalty yet',
                penaltyAmount: '0 ETB',
                bookCondition: 'Not checked',
                handledBy: 'ADM1234',
                notes: 'Book is still with the student. Follow up if it passes the due date.'
            }
        ];

        function getAllRecentActivities() {
            const issuedActivities = borrowedBooks.map(book => ({
                source: 'borrowedBooks',
                borrowerId: book.borrowerId,
                borrowerName: book.borrowerName,
                department: 'Not selected',
                batch: 'Not selected',
                email: book.email || '-',
                phone: book.phone || '-',
                bookTitle: book.bookTitle,
                bookId: book.bookId,
                author: 'Not entered',
                borrowedDate: book.borrowedDate,
                borrowedTime: book.borrowedTime,
                dueDate: book.dueDate,
                returnedDate: book.returnedDate || 'Not returned yet',
                returnedTime: book.returnedTime || '-',
                status: book.status,
                penalty: book.penalty || 'No penalty yet',
                penaltyAmount: book.penaltyAmount || '0 ETB',
                bookCondition: book.condition,
                handledBy: book.handledBy,
                notes: book.notes || 'Book issued by admin and waiting for return.'
            }));

            const defaultActivities = recentActivities.map(activity => ({
                ...activity,
                source: 'default'
            }));

            return [...issuedActivities, ...defaultActivities];
        }

        function renderRecentActivity() {
            const body = document.getElementById('recentActivityBody');
            const allActivities = getAllRecentActivities();

            body.innerHTML = allActivities.map((activity, index) => `
        <tr onclick="showActivityDetails(${index})">
            <td>${activity.borrowerId}</td>
            <td>${activity.bookTitle}</td>
            <td>${activity.borrowedDate}</td>
            <td>${activity.returnedDate === 'Not returned yet' ? '-' : activity.returnedDate}</td>
            <td class="color"><button type="button">${activity.status}</button></td>
        </tr>
    `).join('');
        }

        function showActivityDetails(index) {
            const item = getAllRecentActivities()[index];
            const adminId = localStorage.getItem('adminId') || item.handledBy;
            const detailGrid = document.getElementById('activityDetailGrid');

            detailGrid.innerHTML = `
        <div><span>Borrower ID</span><strong>${item.borrowerId}</strong></div>
        <div><span>Borrower Name</span><strong>${item.borrowerName}</strong></div>
        <div><span>Department</span><strong>${item.department}</strong></div>
        <div><span>Batch</span><strong>${item.batch}</strong></div>
        <div><span>Email</span><strong>${item.email}</strong></div>
        <div><span>Phone</span><strong>${item.phone}</strong></div>
        <div><span>Book Title</span><strong>${item.bookTitle}</strong></div>
        <div><span>Book ID</span><strong>${item.bookId}</strong></div>
        <div><span>Author</span><strong>${item.author}</strong></div>
        <div><span>Borrowed Date</span><strong>${item.borrowedDate}</strong></div>
        <div><span>Borrowed Time</span><strong>${item.borrowedTime}</strong></div>
        <div><span>Due Date</span><strong>${item.dueDate}</strong></div>
        <div><span>Returned Date</span><strong>${item.returnedDate}</strong></div>
        <div><span>Returned Time</span><strong>${item.returnedTime}</strong></div>
        <div><span>Status</span><strong>${item.status}</strong></div>
        <div><span>Penalty</span><strong>${item.penalty}</strong></div>
        <div><span>Penalty Amount</span><strong>${item.penaltyAmount}</strong></div>
        <div><span>Book Condition</span><strong>${item.bookCondition}</strong></div>
        <div><span>Handled By</span><strong>${adminId}</strong></div>
    `;

            document.getElementById('activityNotes').textContent = item.notes;
            document.getElementById('activityActions').innerHTML = item.source === 'borrowedBooks' && item.status === 'Pending'
                ? `<button type="button" class="return-book-btn" onclick="markBorrowedBookReturned(${index})">Mark as Returned</button>`
                : `<span class="return-status">No return action needed.</span>`;
            document.getElementById('activityModal').classList.add('open');
        }

        function closeActivityDetails() {
            document.getElementById('activityModal').classList.remove('open');
        }

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
            const query = document.getElementById('faqSearch').value.toLowerCase();
            const list = document.getElementById('faqList');
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

        loadAdminProfile();
        renderDashboardStats();
        renderRecentActivity();
        renderBorrowerTable();
        renderRegisteredStudents();
        renderFAQ();
        
        if (typeof renderAdminResources === 'function') renderAdminResources();
        if (typeof renderAdminCatalog === 'function') renderAdminCatalog();
        
        setInterval(renderBorrowerTable, 5000);
    </script>



    <!--Footer-->
    <div class="footer">
        <footer class="res-footer">
            <span>© 2025 LibriNet. All rights reserved.</span>
            <div class="res-footer-links">
                <a href="#">Privacy Policy</a>
                <span>|</span>
                <a href="#">Terms of Use</a>
                <span>|</span>
                <a href="#">Contact Us</a>
            </div>
            <div class="res-footer-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </footer>
    </div>
    <script>
        function goToLogin() {
            window.location.href = "login.html";
        }

        function goToHome() {
            window.location.href = "Admin.html";
        }
    </script>
</body>

</html>