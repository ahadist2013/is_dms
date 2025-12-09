$(document).ready(function() {
    
    // Global variable to track the number of qualification fields (starts based on loaded data)
    // ✅ FIX 3.1: PHP থেকে আসা সঠিক মানটি নিন।
    var qual_count = window.qual_count_initial || 1; 

   
    // -------------------------------------------------------------------
    // A. Master Data Management (Modals, Select2)
    // -------------------------------------------------------------------
    
    // Institute/Chamber Modal Handling 
    function handleMasterDataSelection(event) {
        var targetId = event.target.id;
        var value = event.params.data.id;

        if (value === 'other_add') {
            event.preventDefault(); 
            $('#' + targetId).val(null).trigger('change'); 
            
            var modalType = targetId === 'institute_id' ? 'institute' : 'chamber';
            showMasterDataModal(modalType);
        }
    }
    
    $('#institute_id').on('select2:select', handleMasterDataSelection);
    $('#chamber_id').on('select2:select', handleMasterDataSelection);

    // Show Modal Function 
    function showMasterDataModal(type) {
        var modal = $('#modal_institute_chamber');
        var modalTitle = $('#modal_title');
        var modalBody = $('#modal_body');
        var titleText = type === 'institute' ? 'Add New Institute' : 'Add New Chamber/Clinic';

        modalTitle.text(titleText);
        modalBody.html('<p>Loading form...</p>');
        modal.show();

        // Load form via AJAX
        $.ajax({
            url: '../doctor/edit_ajax_handlers.php',
            type: 'GET',
            data: { action: 'get_master_data_form', type: type },
            success: function(response) {
                modalBody.html(response);
            },
            error: function() {
                modalBody.html('<p class="text-danger">Error loading form.</p>');
            }
        });
    }

    // Close Modal Handler 
    $('.close-modal').on('click', function() {
        $('#modal_institute_chamber').hide();
    });

    // -------------------------------------------------------------------
    // B. Select2 Initialization for Institute/Chamber
    // -------------------------------------------------------------------
    $('#institute_id').select2({
        placeholder: 'Search Institute...',
        allowClear: true,
        ajax: {
            url: '../doctor/edit_ajax_handlers.php', 
            type: 'GET',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    action: 'search_institutes', 
                    query: params.term || ''
                };
            },
            processResults: function (data) {
                // 'other_add' option ফিল্টার করা হয়েছে, যা PHP হ্যান্ডলারেই যুক্ত হচ্ছে।
                return {
                    results: $.map(data.data, function (item) {
                        return { id: item.id, text: item.name };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });
    
    $('#chamber_id').select2({
        placeholder: 'Search Chamber/Clinic...',
        allowClear: true,
        ajax: {
            url: '../doctor/edit_ajax_handlers.php', 
            type: 'GET',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    action: 'search_chambers',
                    query: params.term || ''
                };
            },
            processResults: function (data) {
                // 'other_add' option ফিল্টার করা হয়েছে, যা PHP হ্যান্ডলারেই যুক্ত হচ্ছে।
                return {
                    results: $.map(data.data, function (item) {
                        return { id: item.id, text: item.name };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });


    // -------------------------------------------------------------------
    // D. Qualification Management (Dynamic Degree/Specialization) - FINAL LOGIC
    // -------------------------------------------------------------------
    
    // ✅ FIX 3.2: Degree ID অনুযায়ী Specialization লোড করার মূল ফাংশন
    function loadSpecializations(degreeId, specializationSelect, selectedVal) {
        specializationSelect.empty();
        
        if (degreeId) {
            $.ajax({
                url: '../doctor/edit_ajax_handlers.php', 
                type: 'GET',
                data: { action: 'get_specializations_by_degree', degree_id: degreeId },
                dataType: 'json',
                success: function(response) {
                    if (Array.isArray(response)) {
                        specializationSelect.append('<option value="">Select Specialization/Discipline</option>');
                        $.each(response, function(i, item) {
                            var isSelected = (item.detail_id == selectedVal) ? 'selected' : '';
                            specializationSelect.append('<option value="' + item.detail_id + '" ' + isSelected + '>' + item.detail_value + '</option>');
                        });
                    } else {
                         specializationSelect.append('<option value="">No Specialization Available</option>');
                    }
                },
                error: function(xhr) {
                     specializationSelect.append('<option value="">Error Loading Specialization</option>');
                     console.error("AJAX Error loading specializations:", xhr.responseText);
                }
            });
        } else {
            specializationSelect.append('<option value="">Select Degree First</option>');
        }
    }
    
    // ✅ FIX 3.3: ইভেন্ট হ্যান্ডলার: যখন একটি ডিগ্রি ড্রপডাউন পরিবর্তন হয় (Event Delegation)
    $(document).on('change', '.degree-dropdown', function() {
        var degreeId = $(this).val();
        var $qualificationRow = $(this).closest('.qualification-row');
        var $specializationSelect = $qualificationRow.find('.specialization-dropdown');
        var selectedVal = $specializationSelect.val(); 
        
        loadSpecializations(degreeId, $specializationSelect, selectedVal);
    });

    // ✅ FIX 3.4: Function to populate Degree dropdowns (Fixes the no-value issue)
    function populateDegreeDropdown(selector) {
        var $sel = $(selector);
        if ($sel.find('option').length <= 1) { 
             $.ajax({
                url: '../doctor/edit_ajax_handlers.php', 
                type: 'GET',
                data: { action: 'get_all_degrees' },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        // Success: Append degrees
                        $.each(response.data, function(i, item) {
                            $sel.append('<option value="' + item.degree_id + '">' + item.degree_name + '</option>');
                        });
                    } else {
                        // Handle empty or error response from PHP
                        $sel.append('<option value="">No Degrees Found/AJAX Failed</option>');
                        console.error("Failed to load master degrees:", response.message || "No data received.");
                    }
                },
                error: function(xhr, status, error) {
                    // ⚠️ FIX: Connection error (Show user-friendly message)
                    $sel.append('<option value="">Error: Failed to Connect to Server</option>');
                    console.error("AJAX connection error for degrees:", status, error, xhr.responseText);
                }
            });
        }
    }

    // ✅ FIX 3.5: 'Add More Degree' বাটনে ক্লিক হ্যান্ডলার
    $('#add_qualification_btn').on('click', function() {
        qual_count++; 

        var new_qual_row = `
            <div class="qualification-item qualification-row" id="qual_${qual_count}">
                <button type="button" class="remove-qual" data-index="${qual_count}">X</button>
                <div class="form-group">
                    <label for="degree_id_${qual_count}">Degree</label>
                    <select id="degree_id_${qual_count}" name="degree_ids[]" data-index="${qual_count}" class="form-control degree-dropdown">
                        <option value="">Select Degree</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="q_specialization_${qual_count}">Specialization/Discipline</label>
                    <select id="q_specialization_${qual_count}" name="detail_ids[]" class="form-control specialization-dropdown">
                        <option value="">Select Degree First</option>
                    </select>
                </div>
            </div>`;

        $('#additional_qualifications').append(new_qual_row);
        
        // নতুন যোগ হওয়া ডিগ্রি ড্রপডাউনে অপশন লোড করা
        populateDegreeDropdown('#degree_id_' + qual_count); 
    });
    
    // ✅ FIX 3.6: রিমুভ করার হ্যান্ডলার
    $(document).on('click', '.remove-qual', function() {
        if ($('.qualification-row').length > 1) {
            $(this).closest('.qualification-item').remove();
        } else {
            alert("At least one degree is required.");
        }
    });

    // -------------------------------------------------------------------
    // E. Initial Load for Existing Qualifications (Page Load)
    // -------------------------------------------------------------------

    // ✅ FIX 3.7: পেজ লোড হওয়ার সাথে সাথে বিদ্যমান স্পেশালাইজেশন লোড করার জন্য ট্রিগার করা
    // এটি সেভ করা Specialization লোড করবে।
    $('.qualification-row').each(function() {
        var $degreeSelect = $(this).find('.degree-dropdown');
        var degreeId = $degreeSelect.val();
        
        if (degreeId) {
            var $specializationSelect = $(this).find('.specialization-dropdown');
            var selectedVal = $specializationSelect.val(); 
            loadSpecializations(degreeId, $specializationSelect, selectedVal);
        }
    });

    // -------------------------------------------------------------------
    // F. Select2 Final Value Setting
    // -------------------------------------------------------------------
    
    setTimeout(function() {
        $('#institute_id').trigger('change');
        $('#chamber_id').trigger('change');
    }, 0);


});