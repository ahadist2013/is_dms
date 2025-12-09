<?php
// ফাইল: modules/master_data/add_chamber.php

include '../../includes/header.php'; // Includes session_start, db_connection, and BASE_PATH

// Fetching initial data: Divisions
$divisions_result = $conn->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC");
$divisions = [];
if ($divisions_result) {
    while ($row = $divisions_result->fetch_assoc()) {
        $divisions[] = $row;
    }
    $divisions_result->free();
}

// Status Message Display 
?>

<div class="content-area">
    <h2>🏨 Add New Chamber/Clinic</h2>
    
    <?php if (isset($_SESSION['status_message'])): ?>
        <div class="status-box" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; 
            <?php echo ($_SESSION['status_type'] == 'success') ? 
                'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 
                'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <p style="margin: 0; font-weight: bold;"><?php echo $_SESSION['status_message']; ?></p>
        </div>
        <?php
        unset($_SESSION['status_message']);
        unset($_SESSION['status_type']);
        ?>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <form id="chamber_form" action="<?php echo BASE_PATH; ?>modules/master_data/save_chamber.php" method="POST">
            <div class="form-group">
                <label for="chamber_name">Chamber/Clinic Name *</label>
                <input type="text" id="chamber_name" name="chamber_name" required>
            </div>
            
            <div class="form-group">
                <label for="division_id">Division *</label>
                <select id="division_id" name="division_id" required>
                    <option value="">Select Division</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?php echo $division['division_id']; ?>">
                            <?php echo htmlspecialchars($division['division_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="district_id">District *</label>
                <select id="district_id" name="district_id" required disabled>
                    <option value="">Select District</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="upazila_id">Upazila *</label>
                <select id="upazila_id" name="upazila_id" required disabled>
                    <option value="">Select Upazila</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="full_address">Chamber Address (Street/Road/Area) *</label>
                <input type="text" id="full_address" name="full_address" required>
                <small class="hint">বাজার কিংবা এলাকার নাম লিখুন, এখানে বিভাগ, জেলা এবং উপজেলা লেখার দরকার নেই</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Chamber</button>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        
        // AJAX URL (BASE_PATH variable must be available from header.php or global config)
        var ajaxUrl = '<?php echo BASE_PATH; ?>modules/master_data/ajax_handlers.php';

        // 1. Load Districts (Division সিলেক্ট করলে District লোড হবে)
        function loadDistricts(divisionId) {
            var districtSelect = $('#district_id');
            var upazilaSelect = $('#upazila_id');
            
            districtSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>');
            upazilaSelect.prop('disabled', true).empty().append('<option value="">Select Upazila</option>');
            
            if (!divisionId) {
                districtSelect.empty().append('<option value="">Select District</option>');
                return;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: { action: 'load_districts', division_id: divisionId },
                dataType: 'json',
                success: function(response) {
                    districtSelect.empty().append('<option value="">Select District</option>');
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, district) {
                            districtSelect.append($('<option>', {
                                value: district.district_id,
                                text: district.district_name
                            }));
                        });
                        districtSelect.prop('disabled', false);
                    } else {
                        districtSelect.append('<option value="">No Districts Found</option>');
                    }
                },
                error: function() {
                    districtSelect.empty().append('<option value="">Error loading districts</option>');
                }
            });
        }

        // 2. Load Upazilas (District সিলেক্ট করলে Upazila লোড হবে)
        function loadUpazilas(districtId) {
            var upazilaSelect = $('#upazila_id');
            upazilaSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>');
            
            if (!districtId) {
                upazilaSelect.empty().append('<option value="">Select Upazila</option>');
                return;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: { action: 'load_upazilas', district_id: districtId },
                dataType: 'json',
                success: function(response) {
                    upazilaSelect.empty().append('<option value="">Select Upazila</option>');
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, upazila) {
                            upazilaSelect.append($('<option>', {
                                value: upazila.upazila_id,
                                text: upazila.upazila_name
                            }));
                        });
                        upazilaSelect.prop('disabled', false);
                    } else {
                        upazilaSelect.append('<option value="">No Upazilas Found</option>');
                    }
                },
                error: function() {
                    upazilaSelect.empty().append('<option value="">Error loading upazilas</option>');
                }
            });
        }

        // Event listener for Division change
        $('#division_id').on('change', function() {
            var divisionId = $(this).val();
            loadDistricts(divisionId);
        });

        // Event listener for District change
        $('#district_id').on('change', function() {
            var districtId = $(this).val();
            loadUpazilas(districtId);
        });
    });
</script>

<?php 
include '../../includes/footer.php'; 
?>