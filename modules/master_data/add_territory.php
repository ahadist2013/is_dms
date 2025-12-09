<?php
require_once __DIR__ . '/../../includes/header.php';

// ✅ শুধুমাত্র Admin Access
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='error-message'>Access Denied! Only Admin can add territory.</div>";
    exit;
}

// ✅ Zone গুলো আনো
$zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_name ASC");
?>

<div class="card">
    <h2>Add New Territory</h2>

    <form id="add_territory_form" method="POST" action="../../modules/master_data/save_territory.php">
        <div class="form-group">
            <label for="zone_id">Select Zone <span class="required">*</span></label>
            <select name="zone_id" id="zone_id" required>
                <option value="">Select Zone</option>
                <?php while ($z = $zones->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($z['zone_id']) ?>">
                        <?= htmlspecialchars($z['zone_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="territory_name">Territory Name <span class="required">*</span></label>
            <input type="text" name="territory_name" id="territory_name" placeholder="Enter Territory Name" required>
        </div>

        <button type="submit" class="btn btn-primary">Save Territory</button>
        <button type="reset" class="btn btn-secondary">Clear</button>
    </form>
</div>

<script>
$("#add_territory_form").on("submit", function(e) {
    e.preventDefault();

    $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                $("#add_territory_form")[0].reset();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message || "Failed to save territory."
                });
            }
        },
        error: function() {
            Swal.fire({ icon: "error", title: "Error", text: "AJAX request failed." });
        }
    });
});
</script>
