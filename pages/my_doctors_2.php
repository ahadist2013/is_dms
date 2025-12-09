<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/header.php';

?>

<style>
/* PAGE WRAPPER */
.doctor-list-wrapper {
    background: #ffffff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0px 3px 12px rgba(0,0,0,0.08);
    margin-top: 20px;
}

/* HEADER TITLE */
.doctor-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 24px;
    color: #053A72;
}

/* SEARCH BAR */
#search_box {
    max-width: 330px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    font-size: 13px;
    padding: 7px 11px;
    transition: 0.2s ease;
}
#search_box:focus {
    border-color: #053A72;
    box-shadow: 0 0 0 3px rgba(5,58,114,0.15);
}

/* EXPORT BUTTONS */
.btn-export {
    padding: 7px 15px;
    font-size: 12px;
    border-radius: 6px;
    margin-left: 6px;
    background: #053A72;
    color: #fff;
    border: none;
    transition: 0.2s;
}
.btn-export:hover {
    background: #064a93;
    box-shadow: 0 3px 8px rgba(5,58,114,0.25);
    transform: translateY(-1px);
}

/* TABLE */
.table-modern {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
}

/* TABLE HEADER */
.table-modern thead th {
    background: #053A72 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    padding: 12px 14px;
    font-size: 13px;
    border-bottom: none !important;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    border-radius: 6px;
}

/* SORT ARROWS */
th.sortable {
    position: relative;
    cursor: pointer;
    padding-right: 20px !important;
}
th.sortable::after {
    content: "\f0dc"; 
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.7;
    font-size: 11px;
}
th.sortable.sorted-asc::after {
    content: "\f0de";
    opacity: 1;
}
th.sortable.sorted-desc::after {
    content: "\f0dd";
    opacity: 1;
}

/* TABLE ROWS */
.table-modern tbody tr {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0px 2px 8px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
}

/* ROW HOVER */
.table-modern tbody tr:hover {
    background: #F1F7FF !important;
    transform: scale(1.008);
    box-shadow: 0px 3px 12px rgba(0,0,0,0.09);
}

/* TABLE CELLS */
.table-modern td {
    padding: 12px;
    font-size: 12px;
    vertical-align: middle !important;
    white-space: normal;
    color: #333;
}

/* ACTION BUTTON */
.action-btn {
    background: #e2e8f0;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 6px 10px;
    cursor: pointer;
    transition: 0.2s ease;
    color: #1e293b;
    font-size: 16px;
}
.action-btn:hover {
    background: #d8e1ed;
    transform: scale(1.07);
}

/* DROPDOWN MENU */
.dropdown-menu.action-menu {
    border-radius: 8px;
    font-size: 13px;
    padding: 8px 0;
    min-width: 170px;
    box-shadow: 0px 4px 14px rgba(0,0,0,0.15);
    animation: dropdownFadeIn 0.18s ease-out;
}

/* DROPDOWN HOVER EFFECT */
.dropdown-menu.action-menu .dropdown-item {
    padding: 9px 16px;
    transition: 0.15s ease;
    color: #1e293b;
}
.dropdown-menu.action-menu .dropdown-item:hover {
    background: #e9f2ff;
    color: #053A72 !important;
    padding-left: 22px;
}

/* DROPDOWN ANIMATION */

/* MODAL */
.modal-content {
    border-radius: 12px;
    padding: 15px;
}
.modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #053A72;
}

</style>
<!-- Add Bootstrap 5 CSS (required for dropdown & modal UI) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<div class="doctor-list-wrapper">

    <div class="doctor-title">My Doctor List (New)</div>

    <!-- SEARCH + EXPORT ROW -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <input type="text" id="search_box" 
        class="form-control form-control-sm"
        placeholder="Search Name, Mobile, Chamber, Institution...">

        <div>
            <button id="btnExportExcel" class="btn btn-success btn-export">Excel</button>
            <button id="btnExportPDF" class="btn btn-danger btn-export">PDF</button>
        </div>
    </div>

    <!-- TABLE AREA -->
    <div class="table-responsive">
        <table id="myDoctors2Table" class="table table-borderless table-modern">
            <thead>
                <tr>
                    <th class="sortable" data-sort="sl">SL</th>
                    <th class="sortable" data-sort="doctor_id">Doctor ID</th>
                    <th class="sortable" data-sort="name">Name</th>
                    <th class="sortable" data-sort="mobile">Mobile</th>
                    <th class="sortable" data-sort="chamber">Chamber</th>
                    <th class="sortable" data-sort="institution">Institution</th>
                    <th class="sortable" data-sort="grade">Grade</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


<!-- MOBILE EDIT MODAL -->
<div class="modal fade" id="mobileEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="mobileEditForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Mobile Number</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="font-size: 13px;">
        <input type="hidden" id="mobileEditDoctorId">

        <p><strong>Doctor:</strong> <span id="mobileEditDoctorName"></span></p>
        <p><strong>Current Mobile:</strong> <span id="mobileEditOldMobile"></span></p>

        <div class="mb-2">
            <label for="mobileEditNewMobile" class="form-label">New Mobile Number</label>
            <input type="text" class="form-control form-control-sm" id="mobileEditNewMobile" maxlength="11">
            <small class="text-muted">Must be 11 digits and start with 013–019</small>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-sm btn-primary">Submit Request</button>
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </form>
  </div>
</div>


<?php include '../includes/footer_without_mainjs.php'; ?>

<script>
    const BASE_PATH = "<?php echo BASE_PATH; ?>";
</script>

<!-- FIX: Bootstrap JS needed for dropdown + modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo BASE_PATH; ?>assets/js/listing_2.js"></script>

