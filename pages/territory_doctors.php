<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/header.php';

// Security: Enforce Admin role
if (($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: " . BASE_PATH . "index.php");
    exit();
}

// Get parameters from the URL
$territory_id = intval($_GET['territory_id'] ?? 0);
$user_name = htmlspecialchars($_GET['name'] ?? 'N/A User');

// Check if a valid ID is provided
if ($territory_id === 0) {
    // If no ID, redirect back or show an error
    header("Location: " . BASE_PATH . "pages/user_entries.php");
    exit();
}

// Display the user's name in the header
$header_text = "Doctor List for User: " . $user_name;
?>
<style>
/* CSS styles from user_entries.php for consistent look */
.doctor-table th {
    background: #053a72; 
    color: #fff;
    text-transform: uppercase;
    font-size: 9px;
    padding: 8px 6px;
    text-align: left;
    letter-spacing: 0.3px;
    cursor: pointer;
}
.doctor-table th {
    font-size: 10px; /* টেবিল হেডারের ফন্ট সাইজ কমানো হয়েছে */
    padding: 7px 6px;
}
.doctor-table td {
    padding: 5px 6px;
    vertical-align: middle;
}
.doctor-table tr:nth-child(even) { background: #f9fbfd; }
.doctor-table tr:hover { background: #ebf3ff; transition: 0.2s; }
.sort-indicator {
    margin-left: 5px;
    color: rgba(255, 255, 255, 0.7);
}
.active-sort .sort-indicator {
    color: #fff;
}
/* my_doctors.php / territory_doctors.php - বাটন সাইজ ঠিক করা */
.doctor-table td {
  /* ... অন্যান্য স্টাইল ... */
  vertical-align: middle; /* বাটন উল্লম্বভাবে মাঝখানে থাকবে */
}

/* 🎯 বাটন ফিক্স: Edit/Remove/View বাটনের ডিফল্ট প্যাডিং কমিয়ে ছোট করা */
.btn {
  padding: 4px 8px; /* আগের 4px 8px ঠিক আছে, এটি ছোট করে */
  font-size: 11px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  margin-right: 5px; /* বাটনের মধ্যে সামান্য স্পেস */
  /* Remove any excessive width or height settings */
}

/* 🎯 বাটন কন্টেইনার: একাধিক বাটনকে একটি সেলে পাশাপাশি আনার জন্য */
.btn-group {
    display: flex;
    gap: 5px; /* বাটনের মধ্যে দূরত্ব */
    flex-wrap: nowrap; /* বাটন যাতে নিচে না নামে */
    justify-content: flex-start;
}

/* বাটন যদি View/Edit/Remove হয় */
.btn-group .btn {
    flex-shrink: 0; /* বাটন যেন ছোট না হয়ে যায় */
}

/* নিশ্চিত করুন: Edit/View/Remove বাটনগুলো একই সাইজের দেখাবে */
.btn-view, .btn-primary, .btn-danger, .btn-secondary {
    padding: 4px 8px !important; 
}
/* Filters Section Alignment and Styling */
.filters {
  display: flex; /* আইটেমগুলোকে পাশাপাশি রাখবে */
  align-items: center; /* উল্লম্বভাবে মাঝখানে সারিবদ্ধ করবে */
  gap: 15px; /* ফিল্টারগুলির মধ্যে দূরত্ব */
  padding: 15px 20px;
  background-color: #f7f9fc; /* হালকা ব্যাকগ্রাউন্ড */
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Search Box & Dropdown Input Styling */
.filters input[type="text"],
.filters select {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  /* width: 300px; /* সার্চ বক্সের জন্য নির্দিষ্ট করুন */ */
  transition: border-color 0.3s, box-shadow 0.3s;
  box-sizing: border-box; /* Padding যেন উইথের বাইরে না যায় */
}

.filters input[type="text"] {
    width: 300px; 
}
.filters select {
    width: 150px;
}

.filters input[type="text"]:focus, 
.filters select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
  outline: none;
}
</style>

<div class="card doctor-list-card">
  <div class="header-bar">
    <h2>📋 <?php echo $header_text; ?></h2>
    <a href="<?php echo BASE_PATH; ?>pages/user_entries.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to User List
    </a>
  </div>

  <div class="filters">
    <input type="text" id="search-q" placeholder="🔎 Search doctor name or mobile..." data-filter="q">
    <select data-filter="per_page" id="per-page-select">
        <option value="20">20 per page</option>
        <option value="50" selected>50 per page</option>
        <option value="100">100 per page</option>
    </select>
  </div>

  <div class="table-container">
    <table class="doctor-table" id="territory-doctor-table">
      <thead>
        <tr>
          <th style="width: 50px;">SL</th>
          <th style="width: 350px;" data-sort="d.name">Name & Degrees <span class="sort-indicator fas fa-sort-up"></span></th>
          <th style="width: 150px;" data-sort="mdg.designation_name">Designation <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 150px;" data-sort="mi.institute_name">Institute <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 120px;" data-sort="d.mobile_number">Mobile <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 80px;">Type</th>
          <th style="width: 100px;">Actions</th>
        </tr>
      </thead>
      <tbody id="list-body">
        <tr><td colspan="7" style="text-align: center;">Loading data...</td></tr>
      </tbody>
    </table>
  </div>
  
  <div class="pager" id="pager">
    </div>

</div>

<script>
    // Global constants for the JS file
    const apiUrl = '<?php echo BASE_PATH; ?>api/territory_doctor_list.php';
    const baseUrl = '<?php echo BASE_PATH; ?>'; 
    const defaultSortBy = 'd.name';
    const initialTerritoryId = <?php echo $territory_id; ?>; // Pass the ID to JS
</script>
<script src="<?php echo BASE_PATH; ?>assets/js/territory_doctors.js"></script>

<?php 
include __DIR__.'/../includes/footer.php';
?>