<?php
// Include the shared header file (handles session, security, and layout)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/header.php';

// Security: Enforce Admin role
if (($_SESSION['role'] ?? '') !== 'Admin') {
    // If not Admin, redirect to the home page
    header("Location: " . BASE_PATH . "index.php");
    exit();
}

$is_admin = ($_SESSION['role'] ?? '') === 'Admin';
?>
<style>
/* Improved Table UI based on your successful doctor-table style */
.doctor-list-card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-radius: 10px;
}
.doctor-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    table-layout: fixed;
}
.doctor-table th {
    background: #053a72;
    color: #fff;
    text-transform: uppercase;
    font-size: 11px;
    padding: 8px 6px;
    text-align: left;
    letter-spacing: 0.3px;
    cursor: pointer;
    position: relative;
}
.doctor-table th:hover {
    background: #004c9c;
}
.doctor-table th.active-sort {
    background: #0056b3;
}
.doctor-table td {
    border-bottom: 1px solid #e9ecef;
    padding: 6px 6px;
    vertical-align: middle;
}
.doctor-table tr:nth-child(even) { background: #f9fbfd; }
.doctor-table tr:hover { background: #ebf3ff; transition: 0.2s; }

/* Sorting Icons */
.sort-indicator {
    margin-left: 5px;
    color: rgba(255, 255, 255, 0.7);
}
.active-sort .sort-indicator {
    color: #fff;
}

/* Search Box Style */
.filters input[type="text"] {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 300px;
    margin-bottom: 15px;
    transition: border-color 0.3s;
}
.filters input[type="text"]:focus {
    border-color: #007bff;
    outline: none;
}
</style>

<div class="card doctor-list-card">
  <div class="header-bar">
    <h2>User Entry Analysis List</h2>
  </div>

  <div class="filters">
    <input type="text" placeholder="Search User Name or ID..." data-filter="q">
    </div>

  <div class="table-container">
    <table class="doctor-table" id="user-entry-table">
      <thead>
        <tr>
          <th style="width: 50px;">SL</th>
          <th style="width: 250px;" data-sort="name">Name of User <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 100px;" data-sort="login_id">ID No <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 150px;" data-sort="zone_name">Zone <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 150px;" data-sort="territory_name">Territory <span class="sort-indicator fas fa-sort"></span></th>
          <th style="width: 100px;" data-sort="total_entry" class="active-sort" data-order="DESC">Total Entry <span class="sort-indicator fas fa-sort-down"></span></th>
          <th style="width: 100px;">View</th>
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
    // Define the API URL for the JS file
    const apiUrl = '<?php echo BASE_PATH; ?>api/user_entry_list.php';
    const baseUrl = '<?php echo BASE_PATH; ?>'; 
    const defaultSortBy = 'total_entry';
</script>
<script src="<?php echo BASE_PATH; ?>assets/js/user_entries.js?v=20251101"></script> 


<?php 
// Include the shared footer file
include __DIR__.'/../includes/footer.php';
?>