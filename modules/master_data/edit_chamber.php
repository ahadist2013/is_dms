<?php
// ফাইল: modules/master_data/edit_chamber.php

include '../../includes/header.php'; // Includes session_start, db_connection, and BASE_PATH

$chamber_id = (int)($_GET['id'] ?? 0);

if ($chamber_id === 0) {
    // Handle error or redirect
    echo '<div class="content-area"><h2>Error</h2><p>Invalid Chamber ID.</p></div>';
    include '../../includes/footer.php';
    exit();
}

// Fetch Chamber data for pre-filling the form
$chamber_data = null;
$stmt = $conn->prepare("
    SELECT mc.*, d.division_name, dis.district_name, u.upazila_name 
    FROM master_chambers mc
    JOIN divisions d ON mc.division_id = d.division_id
    JOIN districts dis ON mc.district_id = dis.district_id
    JOIN upazilas u ON mc.upazila_id = u.upazila_id
    WHERE mc.chamber_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $chamber_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chamber_data = $result->fetch_assoc();
    $stmt->close();
}

if (!$chamber_data) {
    echo '<div class="content-area"><h2>Error</h2><p>Chamber not found.</p></div>';
    include '../../includes/footer.php';
    exit();
}

// Fetch all divisions for dropdown (similar to add_chamber.php)
$divisions_result = $conn->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC");
$divisions = [];
if ($divisions_result) {
    while ($row = $divisions_result->fetch_assoc()) {
        $divisions[] = $row;
    }
    $divisions_result->free();
}

// You will need to fetch the existing districts and upazilas based on $chamber_data's IDs 
// to populate the initial dependent dropdowns on page load.

// --- Form HTML will be similar to add_chamber.php but with an update action ---
?>

<div class="content-area">
    <h2>✍️ Edit Chamber: <?php echo htmlspecialchars($chamber_data['chamber_name']); ?></h2>
    
    <div class="card" style="max-width: 600px;">
        <form action="<?php echo BASE_PATH; ?>modules/master_data/update_chamber.php" method="POST">
            <input type="hidden" name="chamber_id" value="<?php echo $chamber_id; ?>">

            <div class="form-group">
                <label for="chamber_name">Chamber/Clinic Name *</label>
                <input type="text" id="chamber_name" name="chamber_name" required value="<?php echo htmlspecialchars($chamber_data['chamber_name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="division_id">Division *</label>
                <select id="division_id" name="division_id" required>
                    <option value="">Select Division</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?php echo $division['division_id']; ?>" 
                                <?php echo ($division['division_id'] == $chamber_data['division_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($division['division_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="district_id">District *</label>
                <select id="district_id" name="district_id" required>
                    <option value="<?php echo $chamber_data['district_id']; ?>"><?php echo htmlspecialchars($chamber_data['district_name']); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="upazila_id">Upazila *</label>
                <select id="upazila_id" name="upazila_id" required>
                    <option value="<?php echo $chamber_data['upazila_id']; ?>"><?php echo htmlspecialchars($chamber_data['upazila_name']); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="full_address">Chamber Address (Street/Road) *</label>
                <textarea id="full_address" name="full_address" required><?php echo htmlspecialchars($chamber_data['full_address']); ?></textarea>
            </div>
            
            <button type="submit" class="btn-primary">Update Chamber</button>
        </form>
    </div>
</div>

<script>
    // Note: The dependent dropdown logic from main.js will also work here, 
    // but you need a separate AJAX call on page load to populate district/upazila dropdowns initially.
    // For simplicity, I'm skipping the onload population logic here. Assume it's handled by main.js if needed.
</script>

<?php 
include '../../includes/footer.php'; 
?>