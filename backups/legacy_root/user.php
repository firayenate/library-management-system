<?php 
session_start(); 
if(!isset($_SESSION['userLoggedIn'])) { 
    header("Location: login.html"); 
    exit(); 
} 
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>user page</title>
  <link rel="stylesheet" href="user.css?v=2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="User.js?v=2"></script>
</head>

<body>
  <div class="container">
    <div class="nav-bar">
      <div class="logo">
        <img src="img/logo.png" alt="LibriNet Logo" id="img">
        <h2>LibriNet</h2>
      </div>
      <nav>

        <li><a href="#home">Home</a></li>
        <li><a href="#myprofile">My Profile</a></li>
        <li><a href="#Browse">Browse Catalog</a></li>
        <li><a href="#resource">Resources</a></li>
        <li><a href="#hold">Hold</a></li>
      </nav>
    </div>
  </div>
  <div class="home-page" id="home">
    <div class="upper-home">
      <h1>Welcome to LibriNet</h1>
      <h3>Your digital library,anytime,anywhere.</h3>
      <p>Explore a vast collection of books,resources, and <br> knowledge at your fingertips</p>
      <div class="search-box">
        <select id="searchTypeHome" onchange="searchBook('Home')">
          <option value="title">Title</option>
          <option value="author">Author</option>
          <option value="subject">Subject</option>
        </select>
        <input type="text" id="searchInputHome" placeholder="Search books..." oninput="searchBook('Home')" />

        <button onclick="searchBook('Home')">Search</button>
      </div>
      <div id="homeSearchResults" class="home-results"></div>
    </div>
    <div class="lower-home">
      <h2>Everything You Need,All in one Place</h2>
      <div class="dashboard">
        <div class="dashboard-cards" onclick="navigateToSection('Browse')">

          <i class="fa-solid fa-book"></i>
          <h3>Browse Catalog</h3>
          <p>
            Discover books, journals,<br>
            and more from our <br>
            extensive collection.
          </p>
        </div>

        <div class="dashboard-cards" onclick="navigateToSection('myprofile')">

          <i class="fa-solid fa-user"></i>
          <h3>My Profile</h3>
          <p>
            Manage your account, <br>
            borrowing history, <br>
            and preferences
          </p>
        </div>

        <div class="dashboard-cards" onclick="navigateToSection('resource')">

          <i class="fa-solid fa-folder-open"></i>
          <h3>Resources</h3>
          <p>
            Access e-books, study <br>
            materials, and helpful <br>
            resources.
          </p>
        </div>

        <div class="dashboard-cards" onclick="navigateToSection('hold')">

          <i class="fa-solid fa-bookmark"></i>
          <h3>Hold</h3>
          <p>
            Place holds on books <br>
            and get notified when <br>
            they're available.
          </p>
        </div>
        <div class="dashboard-cards">
          <i class="fa-solid fa-bell"></i>
          <h3>Stay Updated</h3>
          <p>
            Get the latest updates <br>
            and notifications from <br>
            your library.
          </p>
        </div>
      </div>

      <div class="quote-card">
        <div class="quote-content">
          <span class="quote-icon">&ldquo;</span>
          <div class="text-group">
            <p class="quote-text">"A library is not a luxury but one of the necessities of life."</p>
            <p class="author">&ndash; Henry Ward Beecher</p>
          </div>
        </div>
        <div class="illustration">
          <span class="icon-placeholder">📚🪴</span>
        </div>
      </div>

    </div>
  </div>
  </div>

  <div class="profile" id="myprofile">
    <div class="right">
      <div class="sidebar-buttons">
        <button onclick="navigateToSection('myprofile')"><i class="fa-solid fa-user"></i> Profile Overview</button>
        <button onclick="navigateToSection('personal-info')"><i class="fa-solid fa-address-card"></i> Personal
          Information</button>
        <button onclick="navigateToSection('history')"><i class="fa-solid fa-clock-rotate-left"></i> Borrowing
          History</button>
        <button onclick="navigateToSection('hold')"><i class="fa-solid fa-bookmark"></i> Holds</button>
        <button onclick="navigateToSection('favorites')"><i class="fa-solid fa-star"></i> Favorites</button>
        <button onclick="navigateToSection('settings')"><i class="fa-solid fa-gear"></i> Settings</button>
        <button onclick="navigateToSection('notifications')"><i class="fa-solid fa-bell"></i> Notifications</button>
        <button onclick="navigateToSection('password')"><i class="fa-solid fa-key"></i> Change Password</button>
        <button onclick="window.location.href='logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
        <hr>
        <div class="help">
          <h2><i class="fa-solid fa-headset"></i>Need Help?</h2>
          <p>Contact our support team for any assistance</p>
          <button id="btn" onclick="contactSupport()">Contact Support</button>
        </div>
      </div>
    </div>
    <div class="middle">
      <h1>My Profile Dashboard</h1>
      <p>Seamlessly navigate through your library account details.</p>

      <!-- Profile Tab Content Wrapper -->
      <div id="profileTabContent" class="tab-content">
        
        <!-- Tab 1: Profile Overview -->
        <div class="tab-pane active" id="pane-myprofile">
          <div class="profile-card">
            <div class="profile-left">
              <div class="avatar-container">
                <div class="avatar" id="userAvatar">
                  <i class="fa-solid fa-user"></i>
                  <img id="profileImage" src="" alt="" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:50%;">
                </div>
                <div class="camera-badge" onclick="triggerProfileUpload()">
                  <i class="fa-solid fa-camera"></i>
                </div>
                <input type="file" id="profileUpload" accept="image/*" style="display:none;" onchange="handleProfileUpload(event)">
              </div>
              <div class="info">
                <h3 id="userNameDisplay">Firaol Tadesse</h3>
                <p id="userEmailDisplay">firaol.tadesse@example.com</p>
                <div class="meta">
                  <span id="userPhoneDisplay"><i class="fa-solid fa-phone"></i> +251 92 654 478</span>
                  <span><i class="fa-solid fa-calendar"></i> Member since May 2024</span>
                </div>
              </div>
            </div>
            <div class="profile-right">
              <button onclick="navigateToSection('settings')">Edit Profile</button>
            </div>
          </div>

          <div class="tables">
            <div class="leftside">
              <table>
                <tr><th colspan="2">Personal Information</th></tr>
                <tr><td>Full Name</td><td id="infoFullName">Firaol Tadesse</td></tr>
                <tr><td>Email</td><td id="infoEmail">firaol.tadesse@example.com</td></tr>
                <tr><td>Phone</td><td id="infoPhone">+2519356394</td></tr>
                <tr><td>Date of Birth</td><td id="infoDOB">January 15, 2002</td></tr>
                <tr><td>Gender</td><td id="infoGender">Male</td></tr>
                <tr><td>Department</td><td id="infoDept">Computer Science</td></tr>
              </table>
            </div>
            <div class="rightside">
              <table>
                <tr><th colspan="2">Address</th></tr>
                <tr><td>Street</td><td id="infoStreet">Bole Road, Near Edna Mall</td></tr>
                <tr><td>City</td><td id="infoCity">Addis Ababa</td></tr>
                <tr><td>State/Region</td><td id="infoRegion">Addis Ababa</td></tr>
                <tr><td>Country</td><td id="infoCountry">Ethiopia</td></tr>
                <tr><td>Postal Code</td><td id="infoPostal">1000</td></tr>
              </table>
            </div>
          </div>
        </div>

        <!-- Tab: Personal Information (Detail) -->
        <div class="tab-pane" id="pane-personal-info">
            <div class="pane-header">
                <h2><i class="fa-solid fa-address-card"></i> Personal Records</h2>
                <p>Detailed view of your academic profile.</p>
            </div>
            <div class="info-grid-modern">
                <div class="info-card-item">
                    <label>Full Name</label>
                    <p>Firaol Tadesse</p>
                </div>
                <div class="info-card-item">
                    <label>Department</label>
                    <p id="infoDeptDetail">Computer Science</p>
                </div>
                <div class="info-card-item">
                    <label>Enrollment Status</label>
                    <p><span class="status-badge active">Active</span></p>
                </div>
            </div>
        </div>

        <!-- Tab: Borrowing History -->
        <div class="tab-pane" id="pane-history">
          <div class="pane-header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Borrowing Activity</h2>
          </div>
          <table class="activity-table">
            <thead>
              <tr>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Return Due</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="fullHistoryBody">
              <tr>
                <td>Atomic Habits</td>
                <td>May 10, 2026</td>
                <td>May 24, 2026</td>
                <td><span class="activity-badge borrowed">Borrowed</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tab: Holds -->
        <div class="tab-pane" id="pane-hold">
          <div class="pane-header">
            <h2><i class="fa-solid fa-bookmark"></i> My Holds</h2>
          </div>
          <table class="activity-table">
            <thead>
              <tr>
                <th>Book</th>
                <th>Request Date</th>
                <th>Availability</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="holdsBody">
              <tr>
                <td>The Alchemist</td>
                <td>May 08, 2026</td>
                <td>May 20, 2026</td>
                <td><span class="activity-badge hold">On Hold</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tab: Settings -->
        <div class="tab-pane" id="pane-settings">
          <div class="pane-header">
            <h2><i class="fa-solid fa-user-gear"></i> Account Settings</h2>
            <p>Update your personal profile and contact information.</p>
          </div>
          <form class="settings-form" onsubmit="saveUserSettings(event)">
            
            <div class="form-section-title">Personal Information</div>
            <div class="form-grid">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="setDisplayName" required>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="setEmail" required>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="setPhone" required>
              </div>
              <div class="form-group">
                <label>Date of Birth</label>
                <input type="text" id="setDOB">
              </div>
              <div class="form-group">
                <label>Gender</label>
                <select id="setGender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
              </div>
              <div class="form-group">
                <label>Department</label>
                <input type="text" id="setDept">
              </div>
            </div>

            <div class="form-section-title">Address Information</div>
            <div class="form-grid">
              <div class="form-group">
                <label>Street</label>
                <input type="text" id="setStreet">
              </div>
              <div class="form-group">
                <label>City</label>
                <input type="text" id="setCity">
              </div>
              <div class="form-group">
                <label>Region</label>
                <input type="text" id="setRegion">
              </div>
              <div class="form-group">
                <label>Country</label>
                <input type="text" id="setCountry">
              </div>
              <div class="form-group">
                <label>Postal Code</label>
                <input type="text" id="setPostal">
              </div>
            </div>

            <button type="submit" class="save-btn">Update Profile & Address</button>
          </form>
        </div>

        <!-- Tab: Notifications -->
        <div class="tab-pane" id="pane-notifications">
          <div class="pane-header">
            <h2><i class="fa-solid fa-bell"></i> Notifications</h2>
          </div>
          <div class="notification-list">
            <div class="notification-item unread">
              <i class="fa-solid fa-circle-info"></i>
              <div class="notif-content">
                <p>Welcome to LibriNet!</p>
                <span>Recently</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab: Password -->
        <div class="tab-pane" id="pane-password">
          <div class="pane-header">
            <h2><i class="fa-solid fa-key"></i> Security</h2>
          </div>
          <div class="password-card">
            <form onsubmit="changeUserPassword(event)">
              <div class="form-group">
                <label>Current Password</label>
                <input type="password" id="currPass" required>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input type="password" id="newPass" required minlength="6">
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" id="confirmPass" required minlength="6">
              </div>
              <button type="submit" class="password-btn">Update Password</button>
            </form>
          </div>
        </div>

        <!-- Tab: Favorites -->
        <div class="tab-pane" id="pane-favorites">
            <div class="pane-header">
              <h2><i class="fa-solid fa-star"></i> My Favorites</h2>
            </div>
            <div id="favoritesGrid" class="resource-cards">
                <p style="padding: 20px; color: #666;">No favorites yet.</p>
            </div>
        </div>

      </div>
    </div>

    <div class="side-panel">
      <div class="left">
        <h2><i class="fa-solid fa-shield-halved"></i>Library Member</h2>
        <button id="memberStatusBtn">Active Member</button>
        <p>Member ID</p>
        <p id="userIdDisplay">LIB123456789</p>

        <h3>Membership Type</h3>
        <h3 id="membershipType">Regular Member</h3>
      </div>
      <div class="lower">
        <div class="at-glance-container">
          <div class="at-glance-header">
            <i class="fa-solid fa-bolt"></i>
            <h3>At a Glance</h3>
          </div>
          <div class="glance-card">
            <div class="glance-icon"><i class="fa-solid fa-book-open"></i></div>
            <div class="glance-info">
              <span class="count" id="statBorrowed">7</span>
              <p>Books Borrowed</p>
            </div>
          </div>
          <div class="glance-card">
            <div class="glance-icon"><i class="fa-solid fa-bookmark"></i></div>
            <div class="glance-info">
              <span class="count" id="statPending">2</span>
              <p>Books on Hold</p>
            </div>
          </div>
          <div class="glance-card">
            <div class="glance-icon"><i class="fa-solid fa-heart"></i></div>
            <div class="glance-info">
              <span class="count" id="statReturned">5</span>
              <p>Favorite Books</p>
            </div>
          </div>
          <div class="glance-card">
            <div class="glance-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="glance-info">
              <span class="count">3</span>
              <p>Recent Searches</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab content will be managed in the middle section -->

  <div class="quote-card">
    <div class="quote-content">
      <span class="quote-icon">&ldquo;</span>
      <div class="text-group">
        <p class="quote-text">"A library is not a luxury but one of the necessities of life."</p>
        <p class="author">&ndash; Henry Ward Beecher</p>
      </div>
    </div>
    <div class="illustration">
      <span class="icon-placeholder">📚🪴</span>
    </div>
  </div>
  <div class="browse" id="Browse">
    <h1><i class="fa-solid fa-book-open"></i>Browse Catalog</h1>
    <p>Search and discover books from our library collection.</p>

    <div class="search-container">
      <div class="search-grid">
        <div class="search-group">
          <label for="searchInputBooks">Search</label>
          <div class="input-with-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInputBooks" placeholder="Search books by title, author..." />
          </div>
        </div>
        <div class="search-group">
          <label for="categories">Category</label>
          <select id="categories">
            <option value="">All Categories</option>
            <option value="DAA">DAA (Algorithms)</option>
            <option value="Web Development">Web Development</option>
            <option value="Computer Graphics">Computer Graphics</option>
          </select>
        </div>
        <div class="search-group">
          <label for="formats">Format</label>
          <select id="formats">
            <option value="">All Formats</option>
            <option value="Hardcover">Hardcover</option>
            <option value="Paperback">Paperback</option>
            <option value="Digital">Digital</option>
          </select>
        </div>
        <div class="search-group">
          <label for="availability">Availability</label>
          <select id="availability">
            <option value="">All Availability</option>
            <option value="Available">Available Now</option>
            <option value="Borrowed">Borrowed</option>
            <option value="Hold">On Hold</option>
          </select>
        </div>
        <div class="search-group search-action">
          <button id="btnSearchCatalog" class="primary-search-btn" onclick="searchBook('Catalog')">Search</button>
        </div>
      </div>

      <div class="quick-filters">
        <span class="filter-label">Quick Filters:</span>
        <div class="filter-buttons">
          <button class="qf-btn active" onclick="filterCatalog('all')"><i class="fa-solid fa-layer-group"></i> All Books</button>
          <button class="qf-btn" onclick="filterCatalog('DAA')"><i class="fa-solid fa-code-branch"></i> DAA</button>
          <button class="qf-btn" onclick="filterCatalog('Web Development')"><i class="fa-solid fa-code"></i> Web Dev</button>
          <button class="qf-btn" onclick="filterCatalog('Computer Graphics')"><i class="fa-solid fa-palette"></i> Graphics</button>
          <button class="qf-btn" onclick="filterCatalog('available')"><i class="fa-solid fa-circle-check"></i> Available</button>
        </div>
      </div>
    </div>

    <!-- Results Section -->
    <div id="bookGrid" class="resource-cards"></div>

    <div class="catego">
      <h2>Browse by category</h2>

      <div class="dashboards">

        <div class="dashboard" onclick="filterCatalog('DAA')">
          <i class="fa-solid fa-code-branch"></i>
          <h3>DAA</h3>
          <h4>Algorithms</h4>
        </div>
        <div class="dashboard" onclick="filterCatalog('Web Development')">
          <i class="fa-solid fa-code"></i>
          <h3>Web Dev</h3>
          <h4>HTML/CSS/JS</h4>
        </div>
        <div class="dashboard" onclick="filterCatalog('Computer Graphics')">
          <i class="fa-solid fa-palette"></i>
          <h3>Graphics</h3>
          <h4>OpenGL/VR</h4>
        </div>
      </div>
    </div>
    <div class="recent-activity">
      <h2>Recent Activity</h2>
      <table class="activity-table">
        <thead>
          <tr>
            <th></th>
            <th>Book</th>
            <th>Date</th>
            <th>Due Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="recentActivityBody">
          <!-- JS will populate this -->
        </tbody>
      </table>
    </div>
  </div>
  <div class="resources-page" id="resource">

    <!-- Header -->
    <h1><i class="fa-solid fa-folder-open"></i> Resources</h1>
    <p>Access e-books, study materials, and helpful resources.</p>

    <!-- Search Bar -->
    <div class="resource-search-bar">
      <div class="resource-search-group">
        <label>Search Resources</label>
        <div class="resource-input-wrap">
          <input type="text" id="resourceSearchInput" placeholder="Search by title, author, or keyword..." oninput="searchResources()" />
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
      </div>
      <div class="resource-search-group">
        <label>Folder / Module</label>
        <select id="resourceTypeSelect" onchange="searchResources()">
          <option value="All">All Folders</option>
          <option value="DAA">DAA (Algorithms)</option>
          <option value="Web Development">Web Development</option>
          <option value="Computer Graphics">Computer Graphics</option>
        </select>
      </div>
      <div class="resource-search-group">
        <label>Format</label>
        <select id="resourceFormatSelect" onchange="searchResources()">
          <option value="All">All Formats</option>
          <option value="PDF">PDF</option>
          <option value="Video">Video</option>
          <option value="Website">Website</option>
        </select>
      </div>
      <button class="resource-search-btn" onclick="searchResources()">Search</button>
    </div>

    <!-- Quick Filters -->
    <div class="resource-filters">
      <span class="filter-label">Quick Filters:</span>
      <button class="rf-btn active" onclick="filterResources('All')"><i class="fa-solid fa-layer-group"></i> All Books</button>
      <button class="rf-btn" onclick="filterResources('DAA')"><i class="fa-solid fa-code-branch"></i> DAA</button>
      <button class="rf-btn" onclick="filterResources('Web Development')"><i class="fa-solid fa-code"></i> Web Dev</button>
      <button class="rf-btn" onclick="filterResources('Computer Graphics')"><i class="fa-solid fa-palette"></i> Graphics</button>
      <button class="rf-btn" onclick="filterResources('Available')"><i class="fa-solid fa-circle-check"></i> Available</button>
    </div>

    <div class="featured-resources">
      <h2>Featured Subjects</h2>
      <div class="featured-grid">

        <div class="featured-card" onclick="filterResources('Study Material')">
          <div class="featured-icon" style="background: #e0f2fe; color: #0369a1;">
            <i class="fa-solid fa-code-branch"></i>
          </div>
          <div class="featured-info">
            <h3>DAA Modules</h3>
            <p>Design & Analysis of Algorithms</p>
          </div>
        </div>

        <div class="featured-card" onclick="filterResources('Study Material')">
          <div class="featured-icon" style="background: #f0fdf4; color: #15803d;">
            <i class="fa-solid fa-code"></i>
          </div>
          <div class="featured-info">
            <h3>Web Development</h3>
            <p>HTML, CSS & JavaScript</p>
          </div>
        </div>

        <div class="featured-card" onclick="filterResources('E-Book')">
          <div class="featured-icon" style="background: #fefce8; color: #a16207;">
            <i class="fa-solid fa-book"></i>
          </div>
          <div class="featured-info">
            <h3>Course Outlines</h3>
            <p>Curriculum & Syllabi</p>
          </div>
        </div>

        <div class="featured-card" onclick="filterResources('Computer Graphics')">
          <div class="featured-icon" style="background: #fff1f2; color: #e11d48;">
            <i class="fa-solid fa-palette"></i>
          </div>
          <div class="featured-info">
            <h3>Computer Graphics</h3>
            <p>OpenGL & Visualization</p>
          </div>
        </div>

      </div>
    </div>

    <!-- All Resources -->
    <div class="all-resources">
      <div class="all-resources-header">
        <h2>All Resources</h2>
        <div class="sort-wrap">
          <label>Sort by:</label>
          <select id="resourceSortSelect" onchange="searchResources()">
            <option value="newest">Newest Added</option>
            <option value="popular">Most Popular</option>
          </select>
          <button class="view-btn active"><i class="fa-solid fa-table-cells-large"></i></button>
          <button class="view-btn"><i class="fa-solid fa-list"></i></button>
        </div>
      </div>

      <div id="allResourcesGrid" class="resource-cards">
        <!-- JS will populate this -->
      </div>

      <!-- Pagination -->
      <div class="resource-pagination">
        <button class="page-arrow"><i class="fa-solid fa-arrow-left"></i></button>
        <button class="page-num active">1</button>
        <button class="page-num">2</button>
        <button class="page-num">3</button>
        <button class="page-num">4</button>
        <button class="page-num">5</button>
        <span>...</span>
        <button class="page-num">20</button>
        <button class="page-arrow"><i class="fa-solid fa-arrow-right"></i></button>
      </div>

    </div>
  </div>

  <!-- Hold Page -->
  <div class="hold-page" id="hold">
    <h1><i class="fa-solid fa-bookmark"></i> My Library Holds</h1>
    <p>Manage your pending book requests and track availability.</p>

    <div class="hold-container">
      <div class="hold-main">
        <div class="hold-header">
          <h3>Active Holds (<span id="holdCountHeader">0</span>)</h3>
          <div class="hold-actions">
            <button class="hold-refresh-btn" onclick="updateHoldDisplay()"><i class="fa-solid fa-rotate"></i> Refresh</button>
          </div>
        </div>
        <div id="activeHoldsGrid" class="hold-grid">
          <!-- Populated by JS -->
        </div>
      </div>

      <div class="hold-sidebar">
        <div class="hold-info-card">
          <h4><i class="fa-solid fa-circle-info"></i> Hold Information</h4>
          <ul>
            <li>Items are held for <strong>3 days</strong> after they become available.</li>
            <li>You will receive a notification when your item is ready.</li>
            <li>You can have a maximum of <strong>5 active holds</strong>.</li>
          </ul>
        </div>
        <div class="hold-stats-card">
          <div class="hold-stat-item">
            <span>Ready for Pickup</span>
            <span class="count" id="holdsReadyCount">0</span>
          </div>
          <div class="hold-stat-item">
            <span>In Queue</span>
            <span class="count" id="holdsQueueCount">0</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Support Ticket Modal -->
  <div id="supportModal" class="support-modal">
    <div class="support-modal-content">
      <div class="support-header">
        <h2><i class="fa-solid fa-paper-plane"></i> Contact Support</h2>
        <span class="close-support" onclick="closeSupportModal()">&times;</span>
      </div>
      <p>Please describe your issue or suggestion below. Our admin team will review it shortly.</p>
      <form onsubmit="submitSupportTicket(event)">
        <div class="support-form-group">
          <label>Subject</label>
          <input type="text" id="supportSubject" placeholder="Brief summary of your issue" required>
        </div>
        <div class="support-form-group">
          <label>Message / Comment</label>
          <textarea id="supportMessage" rows="5" placeholder="Type your message here..." required></textarea>
        </div>
        <div class="support-actions">
          <button type="button" class="cancel-btn" onclick="closeSupportModal()">Cancel</button>
          <button type="submit" class="send-btn">Send to Admin</button>
        </div>
      </form>
    </div>
  </div>

</body>

</html>