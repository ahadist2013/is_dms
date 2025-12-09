<?php
// ফাইল: modules/master_data/add_institute.php

// 1. নিরাপত্তা এবং ডাটাবেস সংযোগ নিশ্চিত করা
include '../../includes/header.php'; // Includes session_start, db_connection, and BASE_PATH

// শুধুমাত্র Admin/EntryOperator-দের জন্য অ্যাক্সেস
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Officer') {
    header("Location: " . BASE_PATH . "index.php");
    exit();
}

// 2. ড্রপডাউনের জন্য প্রয়োজনীয় ডাটা লোড করা
// a. Division Data (divisions table)
$divisions_result = $conn->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC");

// b. Institute Type Data (master_institute_types table)
$institute_types_result = $conn->query("SELECT type_id, type_name FROM master_institute_types ORDER BY type_name ASC");

// c. Message initialization
$message = '';
$message_type = '';

// 3. POST রিকোয়েস্ট হ্যান্ডেলিং (ডাটা সেভ লজিক)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 3.1. ডাটা সংগ্রহ ও স্যানিটাইজ করা
    $institute_name = isset($_POST['institute_name']) ? sanitize_input($conn, $_POST['institute_name']) : '';
    $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0;
    $district_id = isset($_POST['district_id']) ? (int)$_POST['district_id'] : 0;
    $type_id = isset($_POST['type_id']) ? (int)$_POST['type_id'] : 0;
    $created_by = $_SESSION['user_id']; // Session থেকে User ID নেওয়া

    // 3.2. ভ্যালিডেশন
    if (empty($institute_name) || $division_id <= 0 || $district_id <= 0 || $type_id <= 0) {
        $message = "Please fill in all the required fields.";
        $message_type = "error";
    } else {
        // 3.3. ডাটাবেসে INSERT করা
        // institute_id, institute_name, division_id, district_id, created_by, created_at, type_id
        $sql = "INSERT INTO master_institutes (institute_name, division_id, district_id, created_by, type_id) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiii", $institute_name, $division_id, $district_id, $created_by, $type_id);
        
        if ($stmt->execute()) {
            $message = "Institute **'{$institute_name}'** added successfully! ✅";
            $message_type = "success";
            // সফল হলে ইনপুট ফিল্ড খালি করার জন্য
            $_POST = array(); 
        } else {
            // যদি ডাটাবেস এরর হয়
            $message = "Error: Could not save Institute. " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// 4. ডাটাবেস রিসোর্স ফ্রি করা (যদি থাকে)
if (isset($divisions_result) && $divisions_result->num_rows > 0) $divisions_result->data_seek(0);
if (isset($institute_types_result) && $institute_types_result->num_rows > 0) $institute_types_result->data_seek(0);
?>

<div class="content-area">
    <h2>🏥 Add New Institute</h2>
    <p>Use this form to add a new Institute to the master list.</p>
    
    <?php if (!empty($message)): ?>
        <div class="status-message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            
            <div class="form-group">
                <label for="institute_name">Institute Name <span class="required">*</span></label>
                <input type="text" id="institute_name" name="institute_name" 
                        value="<?php echo isset($_POST['institute_name']) ? htmlspecialchars($_POST['institute_name']) : ''; ?>" 
                        required placeholder="E.g., Dhaka Medical College">
            </div>

            <div class="form-group">
                <label for="division_id">Division of Institute <span class="required">*</span></label>
                <select id="division_id" name="division_id" required>
                    <option value="">-- Select Division --</option>
                    <?php 
                    if ($divisions_result && $divisions_result->num_rows > 0) {
                        while ($row = $divisions_result->fetch_assoc()) {
                            $selected = (isset($_POST['division_id']) && $_POST['division_id'] == $row['division_id']) ? 'selected' : '';
                            echo "<option value=\"{$row['division_id']}\" {$selected}>" . htmlspecialchars($row['division_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="district_id">District of Institute <span class="required">*</span></label>
                <select id="district_id" name="district_id" required disabled>
                    <option value="">-- Select District --</option>
                    </select>
            </div>

            <div class="form-group">
                <label for="type_id">Institute Type <span class="required">*</span></label>
                <select id="type_id" name="type_id" required>
                    <option value="">-- Select Institute Type --</option>
                    <?php 
                    if ($institute_types_result && $institute_types_result->num_rows > 0) {
                        while ($row = $institute_types_result->fetch_assoc()) {
                            $selected = (isset($_POST['type_id']) && $_POST['type_id'] == $row['type_id']) ? 'selected' : '';
                            echo "<option value=\"{$row['type_id']}\" {$selected}>" . htmlspecialchars($row['type_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">➕ Add Institute</button>
            <button type="reset" class="btn btn-secondary">Clear Form</button>
        </form>
    </div>
</div>

<?php 
// 5. ডাটাবেস রিসোর্স ফ্রি করা (শেষবার)
if (isset($divisions_result) && $divisions_result->num_rows > 0) $divisions_result->free();
if (isset($institute_types_result) && $institute_types_result->num_rows > 0) $institute_types_result->free();
?>

<script src="../../modules/master_data/institute_form.js"></script>

<?php
include '../../includes/footer.php'; 
?>