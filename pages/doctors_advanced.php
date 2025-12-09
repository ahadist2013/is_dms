<?php
// pages/doctors_advanced.php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../includes/header.php'; // BASE_PATH + auth + $conn

$is_admin = ($_SESSION['role'] ?? '') === 'Admin';
?>
<div class="card">
  <h2>Advanced Doctor List (Progressive Filters)</h2>

  <!-- Filters row -->
  <div class="filters" style="display:flex; gap:8px; flex-wrap:wrap; font-size:11px; align-items:flex-end;">
    <div>
      <label style="display:block; font-weight:bold;">Master BDD Zone</label>
      <select id="f_bdd_zone" style="min-width:160px;">
        <option value="">All Zones</option>
      </select>
    </div>

    <div>
      <label style="display:block; font-weight:bold;">Region</label>
      <select id="f_region" style="min-width:160px;" disabled>
        <option value="">All Regions</option>
      </select>
    </div>

    <div>
      <label style="display:block; font-weight:bold;">Unit</label>
      <select id="f_unit" style="min-width:160px;" disabled>
        <option value="">All Units</option>
      </select>
    </div>

    <div>
      <label style="display:block; font-weight:bold;">Zone</label>
      <select id="f_zone" style="min-width:160px;" disabled>
        <option value="">All Zones</option>
      </select>
    </div>

    <div>
      <label style="display:block; font-weight:bold;">Territory</label>
      <select id="f_territory" style="min-width:180px;" disabled>
        <option value="">All Territories</option>
      </select>
    </div>

    <div>
      <label style="display:block; font-weight:bold;">Doctor Type</label>
      <select id="f_doctor_type" style="min-width:140px;">
        <option value="">All</option>
        <option value="in_house">In-House</option>
        <option value="outside">Outside</option>
      </select>
    </div>

    <div>
      <label style="display:block; font-weight:bold;">Search</label>
      <input id="f_q" type="text" placeholder="Name/Mobile/Chamber" style="min-width:220px;">
    </div>

    <div style="margin-left:auto;">
      <button id="btn_reset" class="btn" style="background:#6c757d; color:#fff; padding:6px 10px; border-radius:4px;">Reset</button>
    </div>
  </div>

  <!-- Table -->
  <div style="overflow:auto; margin-top:10px;">
    <table class="doctor-table ultra-slim">
      <thead>
        <tr>
          <th style="width:36px;">SL</th>
          <th style="width:70px;">Doctor ID</th>
          <th style="min-width:320px;">Doctor Name & Degrees</th>
          <th style="width:120px;">Mobile</th>
          <th style="min-width:200px;">Chamber</th>
        </tr>
      </thead>
      <tbody id="doc-body"></tbody>
    </table>
  </div>

  <div id="pager" style="margin-top:8px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;"></div>
</div>

<style>
/* Ultra compact table for dense data */
.doctor-table.ultra-slim {
  width:100%;
  border-collapse:collapse;
  font-size:10px;            /* ⇦ ছোট ফন্ট */
  table-layout: fixed;
}
.doctor-table.ultra-slim th,
.doctor-table.ultra-slim td {
  border-bottom:1px solid #e0e0e0;
  padding:3px 6px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  vertical-align:middle;
}
.doctor-table.ultra-slim th {
  background:#053a72;
  color:#fff;
  font-size:9.5px;
  text-transform:uppercase;
}
.doctor-table.ultra-slim tr:nth-child(even){ background:#fafafa; }
.doctor-table.ultra-slim tr:hover{ background:#eef5ff; }

/* Tiny buttons for pager */
.pbtn { padding:3px 6px; font-size:10px; border:1px solid #cbd5e1; background:#fff; cursor:pointer; border-radius:4px; }
.pbtn[disabled]{ opacity:0.6; cursor:not-allowed; }
.pbtn-primary { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
</style>

<script>
  // Page globals for JS
  window.API_FILTER_OPTIONS = "<?php echo BASE_PATH; ?>api/filter_options.php";
  window.API_FILTER_DATA    = "<?php echo BASE_PATH; ?>api/doctors_filter_data.php";
</script>
<script src="<?php echo BASE_PATH; ?>assets/js/doctors_filter.js"></script>
