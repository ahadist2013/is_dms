<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../includes/header.php';

$is_admin = ($_SESSION['role'] ?? '') === 'Admin';
?>
<div class="card doctor-list-card">
  <div class="header-bar">
    <h2>👨‍⚕️ My Doctor List</h2>
    <a href="<?php echo BASE_PATH; ?>modules/doctor/create.php" class="btn add-btn">
      <i class="fa-solid fa-user-plus"></i> Add New Doctor
    </a>
  </div>

  <!-- 🔍 Filters -->
  <div class="filters">
    <input type="text" placeholder="🔎 Search doctor name or mobile..." data-filter="q">
    <select data-filter="doctor_type">
      <option value="">Doctor Type</option>
      <option value="in_house">In-House</option>
      <option value="outside">Outside</option>
    </select>
  </div>

  <!-- 📋 Doctor List Table -->
  <div class="table-container">
    <table class="doctor-table">
      <thead>
        <tr>
          <th style="width: 50px;">SL</th>
          <th style="width: 80px;">Doctor ID</th>
          <th style="width: 300px;">Name</th>
          <th style="width: 130px;">Mobile</th>
          <th style="width: 160px;">Designation</th>
          <th style="width: 180px;">Chamber</th>
          <th style="width: 130px;">Actions</th>
        </tr>
      </thead>
      <tbody id="list-body">
        <tr><td colspan="7" style="text-align:center; color:#999;">Loading...</td></tr>
      </tbody>
    </table>
  </div>

  <div id="pager" class="pager"></div>
</div>

<script>
  window.LIST_SCOPE = 'my';
  window.API_URL = '/is_dms/api/doctors_list.php';
  window.IS_ADMIN = <?php echo $is_admin ? 'true' : 'false'; ?>;
  window.PER_PAGE = 300;
</script>
<script src="/is_dms/assets/js/listing.js"></script>

<style>
/* 🌟 Layout Container */
.doctor-list-card {
  background: #fff;
  padding: 18px 22px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  font-family: "Segoe UI", sans-serif;
  font-size: 13px;
  color: #333;
}

/* 🔹 Header bar */
.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.header-bar h2 {
  color: #053a72;
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

/* 🎨 Add New Doctor Button */
.add-btn {
  background: linear-gradient(135deg, #0048b3, #007bff);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.25s ease-in-out;
  box-shadow: 0 3px 6px rgba(0, 91, 187, 0.25);
}
.add-btn:hover {
  background: linear-gradient(135deg, #005ce6, #0090ff);
  transform: translateY(-1px);
  box-shadow: 0 5px 12px rgba(0, 123, 255, 0.35);
}

/* 🔍 Filters */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 12px;
}
.filters input, .filters select {
  padding: 8px 10px;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  background: #f8fafc;
  font-size: 13px;
  min-width: 220px;
  transition: all 0.2s ease;
}
.filters input:focus, .filters select:focus {
  border-color: #007bff;
  background: #fff;
  outline: none;
}

/* 📋 Table Container */
.table-container {
  overflow-x: auto;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
}

/* 🧾 Table Styling */
.doctor-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
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
}
.doctor-table td {
  border-bottom: 1px solid #e9ecef;
  padding: 6px 6px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  vertical-align: middle;
}
.doctor-table tr:nth-child(even) { background: #f9fbfd; }
.doctor-table tr:hover { background: #ebf3ff; transition: 0.2s; }

/* 🎯 Buttons */
.btn {
  padding: 4px 8px;
  font-size: 11px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
}
.btn-primary {
  background: #007bff;
  color: #fff;
}
.btn-primary:hover { background: #0056b3; }
.btn-danger {
  background: #e74c3c;
  color: #fff;
}
.btn-danger:hover { background: #c0392b; }
.btn-outline-danger {
  background: #fff;
  border: 1px solid #e74c3c;
  color: #e74c3c;
}
.btn-outline-danger:hover {
  background: #e74c3c;
  color: #fff;
}

/* 📄 Pagination */
.pager {
  margin-top: 12px;
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;
}
.pager button {
  padding: 4px 10px;
  font-size: 11px;
  border: 1px solid #007bff;
  background: #fff;
  color: #007bff;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}
.pager button:hover {
  background: #007bff;
  color: #fff;
}
</style>
