<?php 
session_start(); 
if(!isset($_SESSION['adminLoggedIn'])) { 
    header("Location: ../login.html"); 
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
    <link rel="stylesheet" href="Admin.css?v=1.3">
    <script src="Admin.js?v=3"></script>
</head>

<body>
    <div class="container">
        <div class="nav-bar">
            <div class="logo">
                <img src="../img/wku_logo.jpg" alt="WKU Logo" id="img">
                <h2>WKU</h2>
            </div>
            <nav>
                <ul id="mainNav">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#bookedit">Manage Books</a></li>
                    <li><a href="#resources">Resources</a></li>
                    <li><a href="#help">Help</a></li>
                    <li class="mobile-menu-only"><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
                    <li class="mobile-menu-only"><button type="button" onclick="goToLogin()"><i class="fa-solid fa-right-from-bracket" id="btn"></i> Log Out</button></li>
                </ul>
            </nav>

            <div class="nav-profile-container" id="navProfile" onclick="window.location.href='setting.php'" style="cursor: pointer;" title="Go to Settings">
                <div class="admin-avatar">
                    <img src="../img/wku_logo.jpg" alt="Admin" id="navAdminImg">
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
            <h1>Welcome to <span>WKU</span></h1>
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
            <div class="dashboard-card" onclick="window.location.hash = '#bookedit'; setTimeout(() => { document.getElementById('fullLibraryCatalog').scrollIntoView({ behavior: 'smooth' }); }, 150);">
                <i class="fa-solid fa-book"></i>
                <h4>Total Books<br><span id="totalBooksCount">0 Item</span></h4>
            </div>

            <div class="dashboard-card" onclick="window.location.hash = '#bookedit'">
                <i class="fa-solid fa-book-open-reader"></i>
                <h4>Issued Books<br><span id="issuedBooksCount">0 Item</span></h4>
            </div>
            <div class="dashboard-card" onclick="window.location.hash = '#bookedit'; setTimeout(openReturnBookForm, 100);">
                <i class="fa-solid fa-hourglass-half"></i>
                <h4>Pending Returns<br><span id="pendingReturnsCount">0 Item</span></h4>
            </div>
        </div>

        <!-- Books By Subject Breakdown -->
        <div class="category-breakdown" style="padding: 20px; text-align: center; margin-top: 10px;">
            <h3 style="color: #f0a500; margin-bottom: 15px; font-family: 'Syne', sans-serif;"><i class="fa-solid fa-tags"></i> Books by Subject</h3>
            <div id="categoryBreakdownContainer" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <!-- JS will populate category pills here -->
            </div>
        </div>

        <!-- Library Books Showcase Grid -->
        <div class="activity catalog-section" style="background: rgba(15, 23, 42, 0.4); border-radius: 20px; padding: 30px; margin-top: 30px; margin-bottom: 30px;">
            <div class="catalog-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h2 style="color: #f8fafc; margin:0;"><i class="fa-solid fa-book-open" style="color: #f0a500;"></i> Library Book Directory</h2>
                <button id="clearHomeSearchBtn" onclick="clearHomeSearch()" style="display:none; background:rgba(239, 68, 68, 0.15); border:1px solid rgba(239, 68, 68, 0.3); color:#f87171; padding:6px 12px; font-size:12px; border-radius:6px; cursor:pointer; font-weight:600;"><i class="fa-solid fa-xmark"></i> Clear Search</button>
            </div>
            <div class="resource-cards" id="homeBookGrid">
                <!-- Live database books will be rendered here -->
            </div>
        </div>

        <div class="activity catalog-section" style="background: rgba(15, 23, 42, 0.4); border-radius: 20px; padding: 30px;">
            <h2 style="margin-bottom: 20px; color: #f8fafc;"><i class="fa-solid fa-clock-rotate-left" style="color: #f0a500;"></i> Live Borrowing Info</h2>
            <div class="table-container">
                <table class="borrower-table premium-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Book Title</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentActivityBody"></tbody>
                </table>
            </div>
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


                </div>
            </div>
            <div class="action-bar">
                <button class="btn-add-book" type="button" onclick="openAddBookForm()"><i class="fa-solid fa-plus"></i>
                    Add New Book</button>
                <button class="btn-borrow" type="button" onclick="openIssueBookForm()"><i
                        class="fa-solid fa-book-reader"></i> Issue/Borrow Book</button>
                <button class="btn-return" type="button" onclick="openReturnBookForm()"><i
                        class="fa-solid fa-rotate-left"></i> Return Book</button>
                <button class="btn-edit-book" type="button" onclick="document.getElementById('fullLibraryCatalog').scrollIntoView({ behavior: 'smooth' })" style="background-color: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;"><i class="fa-solid fa-pen-to-square"></i> Edit Book</button>
            </div>

            <!-- Borrower Table -->
            <div class="table-container" style="margin-top: 20px; overflow-x: auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 10px; color: #333;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee;">
                            <th style="padding: 12px;">Student ID</th>
                            <th style="padding: 12px;">Name</th>
                            <th style="padding: 12px;">Year</th>
                            <th style="padding: 12px;">Email</th>
                            <th style="padding: 12px;">Status</th>
                            <th style="padding: 12px;">Book Title</th>
                            <th style="padding: 12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="borrowerTableBody">
                        <!-- JS will populate -->
                    </tbody>
                </table>
            </div>

            </div>

            <div class="catalog-section" id="fullLibraryCatalog">
                <div class="catalog-header">
                    <h3><i class="fa-solid fa-book-open"></i> Full Library Catalog</h3>
                </div>
                <div class="resource-cards" id="adminBookGrid">
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
                        <option value="Lecture Notes">Lecture Notes</option>
                        <option value="Past Exams">Past Exams</option>
                        <option value="Tutorials">Tutorials</option>
                        <option value="PDF">PDF</option>
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

    <div class="book-modal" id="editBookModal">
        <div class="book-modal-card">
            <button type="button" class="modal-close" onclick="closeEditBookForm()">&times;</button>
            <h2>Edit Book</h2>
            <form id="editBookForm" onsubmit="saveEditedBook(event)">
                <input type="hidden" id="editBookId">
                <div class="book-form-grid">
                    <input type="text" id="editBookTitle" placeholder="Book Title" required>
                    <input type="text" id="editBookAuthor" placeholder="Author" required>
                    <input type="text" id="editBookSubject" placeholder="Subject" required>
                    <input type="text" id="editBookIsbn" placeholder="ISBN / Book ID" required>
                    <select id="editBookCategory" required>
                        <option value="">Select Category</option>
                        <option value="Textbook">Textbook</option>
                        <option value="Reference">Reference</option>
                        <option value="E-Book">E-Book</option>
                        <option value="Research">Research</option>
                        <option value="Lecture Notes">Lecture Notes</option>
                        <option value="Past Exams">Past Exams</option>
                        <option value="Tutorials">Tutorials</option>
                        <option value="PDF">PDF</option>
                    </select>
                    <input type="number" id="editBookCopies" placeholder="Number of Copies" min="0" required>
                </div>
                <textarea id="editBookDescription" placeholder="Short description or notes about the book"></textarea>
                <div class="book-form-actions">
                    <button type="submit" class="save">Save Changes</button>
                    <button type="button" class="cancel" onclick="closeEditBookForm()">Cancel</button>
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
                    <input type="text" id="issueStudentId" placeholder="Student ID" pattern="^NSR/\d{4}/\d{2}$" title="Student ID must be in format NSR/0000/00" required>
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


    <!-- RESOURCES -->
    <div class="containers" id="resources">

        <div class="resources-hero">
            <div class="resources-hero-overlay">
                <h2>Resources</h2>
                <p>Explore and access a wide range of learning materials</p>
                <div class="resource-search">
                    <div class="search-inner">
                        <select id="resourceTypeSelect" onchange="searchResources()">
                            <option>All Resources</option>
                            <option>E-Books</option>
                            <option>Lecture Notes</option>
                            <option>Past Exams</option>
                            <option>Physical Book</option>
                        </select>
                        <input type="text" id="resourceSearchInput" placeholder="Search resources, e.g., Python, Database, etc." oninput="searchResources()" />
                        <i class="fa fa-search search-icon"></i>
                        <button onclick="searchResources()">Search</button>
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
                <div class="res-cat-card" onclick="filterResourcesByCat('E-Books')" style="cursor:pointer;">
                    <div class="res-cat-icon green-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div class="res-cat-info">
                        <h4>E-Books</h4>
                        <p>Access a wide collection of digital books</p>
                        <span class="res-count green-count" id="countEBooks">— Items</span>
                    </div>
                    <a href="javascript:void(0)" onclick="filterResourcesByCat('E-Books')" class="res-explore-btn green-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>

                <div class="res-cat-card" onclick="filterResourcesByCat('Lecture Notes')" style="cursor:pointer;">
                    <div class="res-cat-icon orange-icon"><i class="fa-solid fa-file-lines"></i></div>
                    <div class="res-cat-info">
                        <h4>Lecture Notes</h4>
                        <p>Download lecture notes and study materials</p>
                        <span class="res-count orange-count" id="countLectureNotes">— Items</span>
                    </div>
                    <a href="javascript:void(0)" onclick="filterResourcesByCat('Lecture Notes')" class="res-explore-btn orange-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>

                <div class="res-cat-card" onclick="filterResourcesByCat('Past Exams')" style="cursor:pointer;">
                    <div class="res-cat-icon blue-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div class="res-cat-info">
                        <h4>Past Exams</h4>
                        <p>Practice with past exam papers and solutions</p>
                        <span class="res-count blue-count" id="countPastExams">— Items</span>
                    </div>
                    <a href="javascript:void(0)" onclick="filterResourcesByCat('Past Exams')" class="res-explore-btn blue-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>

                <div class="res-cat-card" onclick="filterResourcesByCat('Physical Book')" style="cursor:pointer;">
                    <div class="res-cat-icon purple-icon"><i class="fa-solid fa-book"></i></div>
                    <div class="res-cat-info">
                        <h4>Physical Books</h4>
                        <p>Browse all cataloged physical library books</p>
                        <span class="res-count purple-count" id="countPhysicalBooks">— Items</span>
                    </div>
                    <a href="javascript:void(0)" onclick="filterResourcesByCat('Physical Book')" class="res-explore-btn purple-explore">Explore <i class="fa fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="res-section-header" style="margin-top: 30px;">
                <div>
                    <h3 class="res-section-title" id="resourceGridTitle">All Resources</h3>
                    <div class="res-underline"></div>
                </div>
                <button onclick="fetchAdminResources()" class="view-all-btn" style="cursor:pointer; background:white; border:1px solid #ccc;"><i class="fa-solid fa-rotate-right"></i> Show All</button>
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

    <!-- Resource Upload Modal -->
    <div id="resourceUploadModal" class="book-modal">
        <div class="book-modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Upload Digital Resource</h2>
                <button onclick="closeResourceUploader()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="resourceUploadForm" onsubmit="saveNewResource(event)">
                <div class="book-upload-box">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p>Select a file to upload (PDF, PPT, MP4)</p>
                    <label for="resFile">Browse Files</label>
                    <input type="file" id="resFile">
                </div>
                <div class="book-form-grid">
                    <div class="search-group">
                        <label>Resource Title</label>
                        <input type="text" id="resTitle" placeholder="e.g. Advanced Database Systems" required>
                    </div>
                    <div class="search-group">
                        <label>Category</label>
                        <select id="resCategory">
                            <option>E-Books</option>
                            <option>Lecture Notes</option>
                            <option>Past Exams</option>
                            <option>Tutorials</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <label>Author / Dept</label>
                        <input type="text" id="resAuthor" placeholder="e.g. Computer Science Dept" required>
                    </div>
                    <div class="search-group">
                        <label>Module / Subject</label>
                        <input type="text" id="resModule" placeholder="e.g. DBMS" required>
                    </div>
                </div>
                <div class="book-form-actions">
                    <button type="button" class="cancel" onclick="closeResourceUploader()">Cancel</button>
                    <button type="submit" class="save">Upload Resource</button>
                </div>
            </form>
        </div>
    </div>

    <!--Footer-->
    <div class="footer">
        <footer class="res-footer">
            <span>© 2026 WKU. All rights reserved.</span>
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
            window.location.href = "../logout.php";
        }

        function goToHome() {
            window.location.href = "Admin.php";
        }
    </script>
</body>

</html>
