$(document).ready(function() {
    
    // -------------------------------------------------------------------
    // A. LOCATION DEPENDABLE DROPDOWN LOGIC (Division -> District)
    // -------------------------------------------------------------------

    $('#division_id').on('change', function() {
        var divisionId = $(this).val();
        var $districtDropdown = $('#district_id');
        var $upazilaDropdown = $('#upazila_id'); // Upazila ড্রপডাউনও রিসেট করা
        
        // 1. District ড্রপডাউন রিসেট করা
        $districtDropdown.html('<option value="">-- Loading Districts --</option>').prop('disabled', true);
        
        // 2. Upazila ড্রপডাউন রিসেট করা
        $upazilaDropdown.html('<option value="">-- Select Upazila --</option>').prop('disabled', true);

        if (divisionId) {
            // AJAX কল করে জেলাগুলির ডেটা লোড করা
            $.ajax({
                url: 'institute_ajax_handler.php', // নতুন তৈরি করা ফাইল
                type: 'POST',
                data: { action: 'load_districts', division_id: divisionId },
                dataType: 'json',
                success: function(response) {
                    $districtDropdown.html('<option value="">-- Select District --</option>');
                    
                    if (response.success && response.data.length > 0) {
                        // **গুরুত্বপূর্ণ:** response.data থেকে প্রাপ্ত key: `value` এবং `text`
                        $.each(response.data, function(index, district) {
                            $districtDropdown.append(
                                $('<option></option>').val(district.value).text(district.text)
                            );
                        });
                        $districtDropdown.prop('disabled', false);
                    } else {
                        $districtDropdown.html('<option value="">-- No Districts Found --</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error loading districts:", error);
                    $districtDropdown.html('<option value="">-- Error Loading Districts --</option>');
                }
            });
        } else {
            // যদি Division সিলেক্ট না করা হয়
            $districtDropdown.html('<option value="">-- Select District --</option>').prop('disabled', true);
        }
    });


    // -------------------------------------------------------------------
    // B. LOCATION DEPENDABLE DROPDOWN LOGIC (District -> Upazila)
    // -------------------------------------------------------------------

    $('#district_id').on('change', function() {
        var districtId = $(this).val();
        var $upazilaDropdown = $('#upazila_id');
        
        // 1. Upazila ড্রপডাউন রিসেট করা
        $upazilaDropdown.html('<option value="">-- Loading Upazilas --</option>').prop('disabled', true);

        if (districtId) {
            // AJAX কল করে উপজেলাগুলির ডেটা লোড করা
            $.ajax({
                url: 'institute_ajax_handler.php', // নতুন তৈরি করা ফাইল
                type: 'POST',
                data: { action: 'load_upazilas', district_id: districtId },
                dataType: 'json',
                success: function(response) {
                    $upazilaDropdown.html('<option value="">-- Select Upazila --</option>');
                    
                    if (response.success && response.data.length > 0) {
                        // **গুরুত্বপূর্ণ:** response.data থেকে প্রাপ্ত key: `value` এবং `text`
                        $.each(response.data, function(index, upazila) {
                            $upazilaDropdown.append(
                                $('<option></option>').val(upazila.value).text(upazila.text)
                            );
                        });
                        $upazilaDropdown.prop('disabled', false);
                    } else {
                        $upazilaDropdown.html('<option value="">-- No Upazilas Found --</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error loading upazilas:", error);
                    $upazilaDropdown.html('<option value="">-- Error Loading Upazilas --</option>');
                }
            });
        } else {
            // যদি District সিলেক্ট না করা হয়
            $upazilaDropdown.html('<option value="">-- Select Upazila --</option>').prop('disabled', true);
        }
    });

    // -------------------------------------------------------------------
    // C. FORM SUBMISSION/OTHER LOGIC (Optional)
    // -------------------------------------------------------------------
    // Institute Form submission logic would go here if needed
});