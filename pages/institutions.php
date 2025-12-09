<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../includes/header.php';
?>
<div class="card">
  <div class="institute-header">
    <h2>🏫 Institution List</h2>
    <a href="<?php echo BASE_PATH; ?>modules/master_data/add_institute.php" class="btn btn-primary">➕ Add New Institute</a>
  </div>

  <!-- 🔍 Filters -->
  <div class="filters">
    <input type="text" placeholder="Search institute name..." data-filter="q">
    <select data-filter="division_id" id="f_division"><option value="">Division</option></select>
    <select data-filter="district_id" id="f_district"><option value="">District</option></select>
    <select data-filter="type" id="f_type"><option value="">Type</option></select>
  </div>

  <!-- 📋 Table -->
  <div class="table-container">
    <table class="institute-table">
      <thead>
        <tr>
          <th style="width:40px;">SL</th>
          <th style="width:60px;">ID</th>
          <th style="width:380px;">Institute Name</th>
          <th style="width:130px;">Type</th>
          <th style="width:130px;">Division</th>
          <th style="width:130px;">District</th>
        </tr>
      </thead>
      <tbody id="list-body">
        <tr><td colspan="6" style="text-align:center; color:#999;">Loading...</td></tr>
      </tbody>
    </table>
  </div>

  <div id="pager" class="pager"></div>
</div>

<script>
  window.API_URL = '<?php echo BASE_PATH; ?>api/institutions_list.php';
  window.API_GEO = '<?php echo BASE_PATH; ?>api/geo_options.php';
  window.API_TYPES = '<?php echo BASE_PATH; ?>api/get_institute_types.php';
</script>
<script src="<?php echo BASE_PATH; ?>assets/js/institutions.js"></script>

<style>
/* ===== Institution List Styles ===== */
.card {
  background:#fff;
  padding:16px 20px;
  border-radius:8px;
  box-shadow:0 2px 6px rgba(0,0,0,0.05);
  font-family:'Segoe UI',sans-serif;
  font-size:12px;
}
.institute-header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:10px;
}
.institute-header h2 {
  font-size:16px;
  color:#053a72;
  margin:0;
}
/* Filters */
.filters {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-bottom:10px;
}
.filters input,
.filters select {
  padding:6px 8px;
  border:1px solid #ccc;
  border-radius:4px;
  font-size:12px;
  min-width:150px;
  background:#f9f9f9;
  transition:border .2s, background .2s;
}
.filters input:focus,
.filters select:focus {
  border-color:#0252a7;
  background:#fff;
  outline:none;
}
/* Table */
.table-container {
  overflow-x:auto;
  border:1px solid #e5e5e5;
  border-radius:6px;
}
.institute-table {
  width:100%;
  border-collapse:collapse;
  font-size:11.5px;
  table-layout:fixed;
}
.institute-table th,
.institute-table td {
  padding:6px 6px;
  border-bottom:1px solid #eee;
  white-space:nowrap;
  text-overflow:ellipsis;
  overflow:hidden;
  vertical-align:middle;
}
.institute-table th {
  background:#053a72;
  color:#fff;
  font-weight:600;
  text-transform:uppercase;
  font-size:10px;
  letter-spacing:0.3px;
}
.institute-table tr:nth-child(even){background:#f9fafb;}
.institute-table tr:hover{background:#edf4ff;transition:background .2s;}
.institute-table th:nth-child(1),
.institute-table td:nth-child(1){text-align:center;}
.institute-table th:nth-child(2),
.institute-table td:nth-child(2){text-align:center;}
/* Pager */
.pager {
  margin-top:8px;
  display:flex;
  gap:6px;
  flex-wrap:wrap;
  align-items:center;
  font-size:11px;
}
.pager button {
  padding:3px 8px;
  font-size:11px;
  border:1px solid #0252a7;
  background:#fff;
  color:#0252a7;
  border-radius:4px;
  cursor:pointer;
}
.pager button:hover {
  background:#0252a7;
  color:#fff;
}
.btn-primary {
  background:#0252a7;
  color:#fff;
  border:none;
  padding:6px 10px;
  font-size:12px;
  border-radius:4px;
}
.btn-primary:hover { background:#0360c3; }
</style>
