<?php 
include 'includes/header.php'; 
require_once __DIR__ . '/config/db_connection.php';

// ====== Get Doctor Count ======
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'User';
$doctor_count = 0;

// Admin = see all doctors
if ($role === 'Admin') {
    $sql = "SELECT COUNT(DISTINCT doctor_id) AS cnt FROM doctor_assignments";
} else {
    // Officer = see only assigned doctors in his territory
    $sql = "
        SELECT COUNT(DISTINCT da.doctor_id) AS cnt
        FROM doctor_assignments da
        JOIN users u ON da.territory_id = u.territory_id
        WHERE u.user_id = ?
    ";
}

$stmt = $conn->prepare($sql);
if ($role !== 'Admin') {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $doctor_count = $res->fetch_assoc()['cnt'] ?? 0;
}
$stmt->close();

// ====== Get Total Master Doctor Count (Admin-এর জন্য সংরক্ষিত) ======
$total_master_doctors = null;

if ($role === 'Admin') {
    // ✅ ফিক্স: 'doctors' টেবিল থেকে মোট সংখ্যা গণনা করা হচ্ছে
    $total_sql = "SELECT COUNT(doctor_id) AS cnt FROM doctors";
    
    // কোয়েরি এক্সিকিউট করা
    if ($total_res = $conn->query($total_sql)) {
        if ($total_res->num_rows > 0) {
            $total_master_doctors = $total_res->fetch_assoc()['cnt'];
        }
        $total_res->free(); // মেমরি মুক্ত করা
    }
}
?>

<div class="dashboard-home">
  <h1>Dashboard Overview</h1>
  <p>You are logged in as a <b><?php echo $_SESSION['role']; ?></b> 
     under the <b><?php echo $_SESSION['zone_name'] . ' - ' . $_SESSION['territory_name']; ?></b> Territory.</p>

  <div class="summary-cards">
    <!-- ðŸŸ¦ My Assigned Doctors -->
    <div class="summary-card blue-card">
      <div class="card-title">My Assigned Doctors</div>
      <div class="card-count"><?php echo number_format($doctor_count); ?></div>
      <a href="pages/my_doctors_2.php" class="view-link">View List →</a>
    </div>

    <!-- ðŸŸ© Total Doctors -->
    <div class="summary-card green-card">
      <div class="card-title">Total Doctor Entries</div>
      <div class="card-count">
      <?php
            echo is_null($total_master_doctors) ? 'N/A' : number_format($total_master_doctors);
        ?>
      </div>
      <a href="pages/user_entries.php" class="view-link">View All →</a>
    </div>

    <!-- ðŸŸ¨ Today's Visits -->
    <div class="summary-card yellow-card">
      <div class="card-title">Today's Visits</div>
      <div class="card-count"></div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
/* ===== General Layout ===== */
.dashboard-home {
  padding: 25px 30px;
  font-family: "Segoe UI", sans-serif;
}
.dashboard-home h1 {
  font-size: 22px;
  color: #053a72;
  margin-bottom: 5px;
}
.dashboard-home p {
  color: #555;
  margin-bottom: 25px;
  font-size: 13px;
}

/* ===== Card Container ===== */
.summary-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  justify-content: flex-start;
}

/* ===== Each Card ===== */
.summary-card {
  flex: 1 1 28%;
  min-width: 240px;
  background: #ffffff;
  border-radius: 14px;
  padding: 18px 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.07);
  transition: all 0.25s ease;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.summary-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.12);
}

/* Gradient Variants */
.blue-card {
  background: linear-gradient(145deg, #0056d2 0%, #007bff 100%);
  color: #fff;
}
.green-card {
  background: linear-gradient(145deg, #008e3d 0%, #00b95d 100%);
  color: #fff;
}
.yellow-card {
  background: linear-gradient(145deg, #ffb300 0%, #ffc107 100%);
  color: #fff;
}

/* ===== Card Content ===== */
.card-title {
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.3px;
  margin-bottom: 6px;
}
.card-count {
  font-size: 2.5em; /* Bigger count number */
  font-weight: 800;
  margin-bottom: 8px;
  text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.view-link {
  text-decoration: none;
  color: #ffffffd9;
  font-weight: 500;
  font-size: 13px;
  transition: 0.2s;
}
.view-link:hover {
  color: #fff;
  text-decoration: underline;
}

/* Responsive */
@media(max-width: 900px){
  .summary-card { flex: 1 1 45%; }
}
@media(max-width: 600px){
  .summary-card { flex: 1 1 100%; }
}
</style>
