<?php
require_once __DIR__ . '/../../includes/header.php';

// ✅ Access control: only Admin
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='error-message'>Access Denied! Only Admins can assign users.</div>";
    exit;
}

// ✅ Load Zones for dropdown
$zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_name ASC");
?>

<div class="card">
    <h2>User Assignment (Add New User)</h2>

    <form id="user_assignment_form" method="POST" action="../../modules/master_data/save_user_assignment.php">
        <div class="form-group">
            <label for="login_id">Login ID <span class="required">*</span></label>
            <input type="text" name="login_id" id="login_id" placeholder="Enter unique login ID" required>
        </div>

        <div class="form-group">
            <label for="name">Full Name <span class="required">*</span></label>
            <input type="text" name="name" id="name" placeholder="Enter user full name" required>
        </div>

        <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <input type="password" name="password" id="password" placeholder="Enter password" required>
        </div>

        <div class="form-group">
            <label for="role">Role <span class="required">*</span></label>
            <select name="role" id="role" required>
                <option value="">Select Role</option>
                <option value="Admin">Admin</option>
                <option value="Officer">Officer</option>
            </select>
        </div>

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
            <label for="territory_id">Select Territory <span class="required">*</span></label>
            <select name="territory_id" id="territory_id" required>
                <option value="">Select Zone First</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save User</button>
        <button type="reset" class="btn btn-secondary">Clear</button>
    </form>
</div>

<script>
$(document).ready(function () {
    // ✅ Load territories dynamically when Zone changes
    $('#zone_id').on('change', function() {
    const zoneId = $(this).val();
    const territorySelect = $('#territory_id');
    territorySelect.html('<option>Loading...</option>');

    if (zoneId) {
        $.ajax({
            url: '../../modules/master_data/ajax_handlers_user_assignment.php',
            type: 'POST',
            data: { action: 'get_territories_by_zone', zone_id: zoneId },
            dataType: 'json',
            success: function(response) {
                territorySelect.empty().append('<option value="">Select Territory</option>');
                if (response.success && response.data.length > 0) {
                    $.each(response.data, function(i, item) {
                        territorySelect.append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                } else {
                    territorySelect.html('<option value="">No territories found</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                console.log("Response:", xhr.responseText);
                territorySelect.html('<option value="">Error loading territories</option>');
            }
        });
    } else {
        territorySelect.html('<option value="">Select Zone First</option>');
    }
});

    // ✅ Save new user via AJAX
    $('#user_assignment_form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#user_assignment_form')[0].reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to save user.'
                    });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'AJAX request failed.' });
            }
        });
    });
});
</script>
