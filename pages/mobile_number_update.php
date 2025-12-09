<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die('Access denied');
}

include '../includes/header.php';
?>

<!-- Bootstrap CSS (if not already loaded globally) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
/* PAGE WRAPPER */
.doctor-list-wrapper {
    background: #ffffff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0px 2px 10px rgba(0,0,0,0.08);
    margin-top: 20px;
}

/* HEADER TITLE */
.doctor-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 22px;
    color: #053A72;
}

/* TABLE */
.table-mobile-update {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
}

/* HEADER */
.table-mobile-update thead th {
    background: #053A72 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    padding: 10px;
    font-size: 12px;
    border-bottom: none !important;
}

/* BODY ROWS */
.table-mobile-update tbody tr {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0px 1px 5px rgba(0,0,0,0.08);
    transition: all 0.15s ease;
}
.table-mobile-update tbody tr:hover {
    background: #F3F7FF;
}

/* CELLS */
.table-mobile-update td {
    padding: 10px;
    font-size: 12px;
    vertical-align: top;
}

/* OLD/NEW MOBILE BLOCKS */
.mobile-box {
    font-size: 13px;
    font-weight: 600;
    color: #053A72;
}
.mobile-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #6b7280;
}
.info-small {
    font-size: 11px;
    color: #4b5563;
}

/* ACTION BUTTON */
.btn-action-main {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 6px;
}
</style>

<div class="container-fluid">
    <div class="doctor-list-wrapper">
        <div class="doctor-title">Mobile Number Update (Admin Only)</div>

        <div class="table-responsive">
            <table class="table table-borderless table-mobile-update" id="mobileUpdateTable">
                <thead>
                    <tr>
                        <th style="width:50px;">SL</th>
                        <th style="width:35%;">Old Mobile & Current Doctor</th>
                        <th style="width:35%;">New Mobile & Target Doctor</th>
                        <th style="width:20%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- rows will be loaded by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer_without_mainjs.php'; ?>

<script>
    const BASE_PATH = "<?php echo BASE_PATH; ?>";
</script>

<!-- Bootstrap JS bundle (for modal/dropdown if needed) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo BASE_PATH; ?>assets/js/mobile_number_update.js"></script>
