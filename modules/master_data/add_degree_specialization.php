<?php
session_start();
require_once __DIR__ . '/../../includes/header.php'; // তোমার common header + sidebar
require_once __DIR__ . '/../../config/db_connection.php';

$message = "";

// ✅ 1️⃣ Fetch existing degrees for dropdown
$degrees = $conn->query("SELECT degree_id, degree_name FROM master_degrees ORDER BY degree_name ASC");

// ✅ 2️⃣ Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $degree_id = intval($_POST['degree_id'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');

    if ($degree_id <= 0) {
        $message = '<div class="error">⚠️ Please select a valid degree.</div>';
    } elseif ($specialization === '') {
        $message = '<div class="error">⚠️ Please enter a specialization name.</div>';
    } else {
        // Check if same specialization already exists for that degree
        $check = $conn->prepare("SELECT detail_id FROM master_degree_details WHERE degree_id = ? AND detail_value = ?");
        $check->bind_param("is", $degree_id, $specialization);
        $check->execute();
        $exists = $check->get_result();

        if ($exists->num_rows > 0) {
            $message = '<div class="error">⚠️ This specialization already exists under the selected degree.</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO master_degree_details (degree_id, detail_value) VALUES (?, ?)");
            $stmt->bind_param("is", $degree_id, $specialization);
            if ($stmt->execute()) {
                $message = '<div class="success">✅ Specialization successfully added.</div>';
            } else {
                $message = '<div class="error">❌ Failed to insert specialization.</div>';
            }
            $stmt->close();
        }
    }
}
?>

<div class="card">
  <h2>Add New Specialization</h2>

  <?php if ($message) echo $message; ?>

  <form method="POST" style="max-width: 600px; margin-top: 10px;">
    <div class="form-group">
        <label for="degree_id">Select Degree <span class="required">*</span></label>
        <select id="degree_id" name="degree_id" required>
            <option value="">-- Select Degree --</option>
            <?php while($deg = $degrees->fetch_assoc()): ?>
                <option value="<?php echo $deg['degree_id']; ?>">
                    <?php echo htmlspecialchars($deg['degree_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="specialization">Name of Specialization <span class="required">*</span></label>
        <input type="text" id="specialization" name="specialization" placeholder="e.g. Medicine, Surgery, Paediatrics" required>
    </div>

    <button type="submit" class="btn btn-primary">💾 Save Specialization</button>
  </form>
</div>

<style>
.card {
    background:#fff;
    padding:20px 25px;
    border-radius:8px;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
    max-width:700px;
    margin:auto;
    margin-top:20px;
    font-family:'Segoe UI',sans-serif;
}
.card h2 {
    margin-top:0;
    font-size:18px;
    color:#053a72;
    text-align:center;
}
.form-group {
    margin-bottom:15px;
}
label {
    display:block;
    font-weight:bold;
    color:#333;
    margin-bottom:5px;
}
select, input[type=text] {
    width:100%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:4px;
    font-size:13px;
}
.btn-primary {
    width:100%;
    padding:10px;
    background:#053a72;
    color:white;
    border:none;
    border-radius:4px;
    font-size:14px;
    cursor:pointer;
}
.btn-primary:hover { background:#0757a1; }
.success {
    background:#d4edda;
    color:#155724;
    padding:10px;
    border-radius:4px;
    text-align:center;
    margin-bottom:10px;
}
.error {
    background:#f8d7da;
    color:#721c24;
    padding:10px;
    border-radius:4px;
    text-align:center;
    margin-bottom:10px;
}
</style>
