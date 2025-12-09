$(document).ready(function() {
    
    // Global variable to track the number of qualification fields (starts at 1 for new additions, 0 is the main one)
    var qual_count = 1; 

    // -------------------------------------------------------------------
    // A. DUPLICATE PREVENTION LOGIC (Mobile Check) - (EXISTING)
    // -------------------------------------------------------------------

   $('#mobile_number').on('input', function() {
        var mobile = $(this).val().trim();
        $('#existing_doctor_id').val(''); 
        $('#name_suggestion_box').empty();

        // ১১ ডিজিট এবং valid pattern হলে চেক করা হবে
        if (mobile.length === 11 && mobile.match(/^01[3-9]\d{8}$/)) {
            
            // AJAX call to check doctor
            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'POST',
                data: { action: 'check_doctor_by_mobile', mobile_number: mobile },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        // 🟡 পুরোনো ডাক্তার (already exists)
                        $('#name').val(response.doctor.name).prop('readonly', true);
                        $('#existing_doctor_id').val(response.doctor.doctor_id);
                        $('#mobile_suggestion_box').html(
                            '<div class="suggestion-found">' + response.doctor.name + '</div>'
                        );

                        // Doctor create অংশ হাইড
                        $('#new_doctor_fields').slideUp(); 
                        $('#new_doctor_fields :input').prop('disabled', true);

                        // Assignment অংশ দেখাও
                        $('#assignment_section').slideDown();  
                        $('#assignment_section :input').prop('disabled', false);

                    } else {
                        // 🟢 নতুন ডাক্তার (not found)
                        $('#name').val('').prop('readonly', false);
                        $('#mobile_suggestion_box').empty();

                        // Doctor create অংশ দেখাও
                        $('#new_doctor_fields').slideDown();
                        $('#new_doctor_fields :input').prop('disabled', false);

                        // Assignment অংশ সবসময় visible রাখো
                        $('#assignment_section').slideDown();  
                        $('#assignment_section :input').prop('disabled', false);
                    }
                    updatePreview(); 
                }
            });

        } else {
            // ❗ যখন এখনো ১১ ডিজিট হয়নি → কিছুই হাইড কোরো না!
            $('#mobile_suggestion_box').html('<span ‍ class="error-message">Please enter full 11-digit mobile number ‍start with 013-019.</span>');
            
            // Doctor name ফিল্ড writable রাখো
            $('#name').val('').prop('readonly', false);

            // Doctor form visible রাখো
            $('#new_doctor_fields').slideDown(); 
            $('#new_doctor_fields :input').prop('disabled', false);

            // ✅ Assignment অংশ visible রাখো (এইটাই fix!)
            $('#assignment_section').slideDown();  
            $('#assignment_section :input').prop('disabled', false);
        }
    });

    // main.js - Assignment Type Logic
    function toggleAssignmentFields() {
        var doctorType = $('input[name="doctor_type"]:checked').val();
        var inhouseFields = $('#inhouse_fields');
        var visitContainer = $('#visit_type_container');

        // লুকাও সব
        inhouseFields.hide().find('select, input').prop('required', false);
        visitContainer.hide().find('select').prop('required', false);

        if (doctorType === 'in_house') {
            // ✅ In-House হলে Unit ও Room Number দেখাও
            inhouseFields.show().find('select, input').prop('required', true);
        } else if (doctorType === 'outside') {
            // ✅ Outside হলে Visit Type দেখাও
            visitContainer.show().find('select').prop('required', true);
        }
    }



    // Doctor Type radio buttons এর পরিবর্তন হলে ফাংশনটি কল করুন
    $('input[name="doctor_type"]').on('change', toggleAssignmentFields);

    // পেজ লোড হওয়ার পরেও এটি চলতে পারে, যদি কোনো ডেটা প্রি-লোড করা হয়
    // toggleAssignmentFields();

    // -------------------------------------------------------------------
    // B. REAL-TIME PREVIEW & UPDATE (FIXED: Sticky and Update)
    // -------------------------------------------------------------------

    function updatePreview() {
        var name = $('#name').val() || 'Doctor Name';
        var mobile = $('#mobile_number').val() || 'N/A';
        var designation = $('#designation').val() || 'N/A';
        
        // Get name from input field, since suggestion is used to set the hidden field
        var specializationName = $('#specialization_search').val() || 'N/A';
        //var instituteName = $('#institute_search').val() || 'N/A';
       var instituteName = (function(){
            var v = $('#institute_id').val();
            if (v && v !== 'other_add') {
                var $opt = $('#institute_id option:selected');
                return $opt.length ? $opt.text() : 'N/A';
            }
            return 'N/A';
        })();

        var chamberName = (function(){
            var v = $('#chamber_id').val();
            if (v && v !== 'other_add') {
                var $opt = $('#chamber_id option:selected');
                return $opt.length ? $opt.text() : 'N/A';
            }
            return 'N/A';
        })();

        $('#preview_name').text(name);
        $('#preview_mobile').text('Mobile: ' + mobile);
        $('#preview_designation').text('Designation: ' + designation);
        $('#preview_institute').text('Working at: ' + instituteName);
        $('#preview_chamber').text('Chamber: ' + chamberName);
    }
    
    // Attach updatePreview to all relevant input changes
    $(document).on('input change', '#name, #mobile_number, #designation, #specialization_search, #institute_id, #chamber_id', updatePreview);
    
    updatePreview(); // Initial load
    
    // -------------------------------------------------------------------
    // C. DYNAMIC DROPDOWNS & AUTO-SUGGESTION LOGIC (FIXED & NEW)
    // -------------------------------------------------------------------
    
    // --- C1. Master Data Search Suggestions (Specialization, Institute, Chamber) ---
    function setupMasterSearch(inputId, hiddenId, suggestionBoxId, type) {
        var input = $(inputId);
        var hiddenField = $(hiddenId);
        var suggestionBox = $(suggestionBoxId);
        
        // Set initial value of hidden field if input is empty (e.g., if required)
        if (!input.val()) hiddenField.val('');

        input.on('input', function() {
            var query = $(this).val();
            suggestionBox.empty();
            hiddenField.val(''); // Clear hidden ID on new input
            
            if (query.length < 2) {
                return;
            }

            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'POST',
                data: { action: 'search_master_data', type: type, query: query },
                dataType: 'json',
                success: function(response) {
                    suggestionBox.empty();
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            $('<div class="suggestion-item" data-id="' + item.id + '">' + item.name + '</div>').appendTo(suggestionBox);
                        });
                        
                        // Add the "Add New" option for Institute and Chamber only
                        if (type === 'institute' || type === 'chamber') {
                             $('<div class="suggestion-item add-new-option" data-id="other_add">➕ Add New ' + type.charAt(0).toUpperCase() + type.slice(1) + ' (and Select)</div>').appendTo(suggestionBox);
                        }
                        
                    } else if (query.length >= 2) {
                        suggestionBox.html('<div class="suggestion-item">No results found for "' + query + '".</div>');
                        // Add the "Add New" option if no match is found
                        if (type === 'institute' || type === 'chamber') {
                             $('<div class="suggestion-item add-new-option" data-id="other_add">➕ Add New ' + type.charAt(0).toUpperCase() + type.slice(1) + ' (and Select)</div>').appendTo(suggestionBox);
                        }
                    }
                }
            });
        });
        
        // Handle suggestion item click
        suggestionBox.on('click', '.suggestion-item', function() {
            var id = $(this).data('id');
            var name = $(this).text();
            
            if (id === 'other_add') {
                // Trigger Modal/Add New Logic (Section F)
                // Use the hidden field's ID to know which modal to open
                $(hiddenId).val('other_add').trigger('change'); 
            } else {
                // Select the item and update the hidden field
                input.val(name);
                hiddenField.val(id).trigger('change');
            }
            suggestionBox.empty();
            updatePreview();
        });
        
        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest(suggestionBoxId).length && !$(e.target).is(inputId)) {
                suggestionBox.empty();
            }
        });
    }

    // Initialize search suggestions
    setupMasterSearch('#specialization_search', '#specialization_id', '#specialization_suggestion_box', 'discipline');
    //setupMasterSearch('#institute_search', '#institute_id', '#institute_suggestion_box', 'institute');
    //setupMasterSearch('#chamber_search', '#chamber_id', '#chamber_suggestion_box', 'chamber');
        populateInstituteDropdown();
        populateChamberDropdown();
    
    // --- C2. Officer Assignment (Zone -> Territory) FIX ---
    
    $('#zone_id').on('change', function() {
        var zoneId = $(this).val();
        var territorySelect = $('#territory_id');
        var officerInfo = $('#officer_info');
        
        territorySelect.html('<option value="">Loading Territories...</option>');
        officerInfo.text('Officer: N/A | ID: N/A');

        if (zoneId) {
            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'GET',
                data: { action: 'get_territories_by_zone', zone_id: zoneId },
                dataType: 'json',
                success: function(response) {
                    territorySelect.empty().append('<option value="">Select Territory</option>');
                    if (response.length > 0) {
                        $.each(response, function(i, item) {
                            // Ensure user_name and login_id are properly encoded/escaped if they contain special characters
                            var userName = item.user_name ? item.user_name : 'N/A';
                            var loginId = item.login_id ? item.login_id : 'N/A';
                            territorySelect.append('<option value="' + item.territory_id + '" data-user="' + userName + '" data-login="' + loginId + '">' + item.territory_name + '</option>');
                        });
                    } else {
                        territorySelect.append('<option value="">No Territories Assigned</option>');
                    }
                }
            });
        }
    });

    $('#territory_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var userName = selectedOption.data('user') || 'N/A';
        var loginId = selectedOption.data('login') || 'N/A';
        $('#officer_info').text('Officer: ' + userName + ' | ID: ' + loginId);
    });


    // --- F1. Doctor Type Logic (Unit vs. Visit Type) ---
    $('input[name="doctor_type"]').on('change', function() {
        var doctorType = $(this).val();
        var selectedRadio = $(this); // যে রেডিও বাটনটি ক্লিক করা হয়েছে
        
        var unitContainer = $('#unit_container');
        var visitTypeContainer = $('#visit_type_container');
        var unitField = $('#unit_id');
        var visitTypeField = $('#visit_type');

        // Reset: সব কন্টেইনার লুকিয়ে ফেলুন এবং required অ্যাট্রিবিউট সরান
        unitContainer.hide();
        unitField.prop('required', false);
        visitTypeContainer.hide();
        visitTypeField.prop('required', false);
        
        // Logic for In-House (Value is 'in_house')
        if (doctorType === 'in_house') { 
            // 💡 FIX: Unit কন্টেইনারটিকে In-House রেডিও বাটনের লেবেলের ঠিক পরে নিয়ে আসা
            unitContainer.insertAfter(selectedRadio.parent()).show();
            unitField.prop('required', true);
        } 
        
        // Logic for Outside (Value is 'outside')
        else if (doctorType === 'outside') {
            // 💡 FIX: Visit Type কন্টেইনারটিকে Outside রেডিও বাটনের লেবেলের ঠিক পরে নিয়ে আসা
            visitTypeContainer.insertAfter(selectedRadio.parent()).show();
            visitTypeField.prop('required', true);
        }
    });
    // -------------------------------------------------------------------
    // E. QUALIFICATION LOGIC (Dynamic Add & Degree->Specialization) (FIXED & NEW)
    // -------------------------------------------------------------------
   if (!window.IS_DOCTOR_EDIT) {
    
        // --- E1. Add More Qualification ---
        $('#add_qualification_btn').on('click', function() {
    
            // Fetch current degree list from degree_id_0
            var degreeOptions = $('#degree_id_0').html();
    
            var newQualHtml = `
                <div class="qualification-item" id="qual_${qual_count}">
                    <button type="button" class="remove-qual" data-index="${qual_count}">X</button>
    
                    <div class="form-group">
                        <label for="degree_id_${qual_count}">Degree</label>
                        <select id="degree_id_${qual_count}" name="degree_ids[]" data-index="${qual_count}">
                            ${degreeOptions}
                        </select>
                    </div>
    
                    <div class="form-group">
                        <label for="q_specialization_${qual_count}">Specialization/Discipline</label>
                        <select id="q_specialization_${qual_count}" name="q_specializations[]">
                            <option value="">Select Degree First</option>
                        </select>
                    </div>
                </div>
            `;
    
            $('#additional_qualifications').append(newQualHtml);
            qual_count++;
        });
    
        // --- E2. Remove Qualification ---
        $(document).on('click', '.remove-qual', function() {
            var index = $(this).data('index');
            $('#qual_' + index).remove();
        });
    
        // --- E3. Degree -> Specialization (Dynamic Loader) ---
        $(document).on('change', 'select[id^="degree_id_"]', function() {
            var degreeId = $(this).val();
            var index = $(this).data('index');
            var specializationSelect = $('#q_specialization_' + index);
    
            specializationSelect.html('<option value="">Loading Disciplines...</option>');
    
            if (degreeId) {
    
                $.ajax({
                    url: '../../modules/master_data/ajax_handlers.php',
                    type: 'GET',
                    data: { action: 'get_specializations_by_degree', degree_id: degreeId },
                    dataType: 'json',
    
                    success: function(response) {
                        specializationSelect.empty()
                            .append('<option value="">Select Specialization (Optional)</option>');
    
                        if (response.success && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
    
                                specializationSelect.append(
                                    '<option value="' + item.value + '">' + item.text + '</option>'
                                );
                            });
                        } else {
                            specializationSelect.append('<option value="">No Specializations found</option>');
                        }
                    },
    
                    error: function(xhr, status, error) {
                        specializationSelect.empty()
                            .append('<option value="">Error Loading Data</option>');
                        console.error("AJAX Error:", status, error);
                    }
                });
    
            } else {
                specializationSelect.html('<option value="">Select Degree First</option>');
            }
        });
    
    } // ← THIS closing bracket was missing earlier (NOW FIXED)
    // ... (শেষের কোড)

    // -------------------------------------------------------------------
    // F. MODAL LOGIC (Institute/Chamber) - (FIXED)
    // -------------------------------------------------------------------

    // --- Master Data Load Functions ---

    // Function to load Districts based on Division
    function loadDistricts(divisionId, districtSelectId) {
        var districtSelect = $(districtSelectId);
        districtSelect.html('<option value="">Loading Districts...</option>').prop('disabled', true);
        
        if (divisionId) {
            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'POST',
                data: { action: 'load_modal_form', type: 'districts', division_id: divisionId },
                dataType: 'json',
                success: function(response) {
                    districtSelect.empty().append('<option value="">Select District</option>');
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            districtSelect.append('<option value="' + item.district_id + '">' + item.district_name + '</option>');
                        });
                        districtSelect.prop('disabled', false);
                    } else {
                        districtSelect.append('<option value="">No Districts Found</option>');
                    }
                },
                error: function() {
                    districtSelect.html('<option value="">Error loading districts</option>');
                }
            });
        } else {
            districtSelect.html('<option value="">Select Division First</option>').prop('disabled', true);
        }
    }
    
    // Function to load Upazilas based on District (for chamber form)
    function loadUpazilas(districtId, upazilaSelectId) {
        var upazilaSelect = $(upazilaSelectId);
        upazilaSelect.html('<option value="">Loading Upazilas...</option>').prop('disabled', true);
        
        if (districtId) {
            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'POST',
                data: { action: 'load_modal_form', type: 'upazilas', district_id: districtId },
                dataType: 'json',
                success: function(response) {
                    upazilaSelect.empty().append('<option value="">Select Upazila/Thana</option>');
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            upazilaSelect.append('<option value="' + item.upazila_id + '">' + item.upazila_name + '</option>');
                        });
                        upazilaSelect.prop('disabled', false);
                    } else {
                        upazilaSelect.append('<option value="">No Upazilas Found</option>');
                    }
                },
                error: function() {
                    upazilaSelect.html('<option value="">Error loading upazilas</option>');
                }
            });
        } else {
            upazilaSelect.html('<option value="">Select District First</option>').prop('disabled', true);
        }
    }

    // --- Modal Control Logic ---
    
    // Global function to open modal and load form
    function openMasterModal(type) {
        var modal = $('#modal_institute_chamber');
        var modalTitle = $('#modal_title');
        var modalBody = $('#modal_body');
        
        modalTitle.text('Add New ' + type.charAt(0).toUpperCase() + type.slice(1));
        modalBody.html('Loading form...');
        modal.show();

        $.ajax({
            url: '../../modules/master_data/ajax_handlers.php',
            type: 'POST',
            data: { action: 'load_modal_form', type: type },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.html);
                    
                    // Attach dynamic listeners based on form type
                    if (type === 'institute') {
                        $('#division_id_m').on('change', function() {
                            loadDistricts($(this).val(), '#district_id_m');
                        });
                        handleInstituteSubmission();
                    } else if (type === 'chamber') {
                        $('#division_id_c').on('change', function() {
                            loadDistricts($(this).val(), '#district_id_c');
                            $('#upazila_id_c').html('<option value="">Select District First</option>').prop('disabled', true); // Reset upazila
                        });
                        $('#district_id_c').on('change', function() {
                            loadUpazilas($(this).val(), '#upazila_id_c');
                        });
                        handleChamberSubmission();
                    }
                } else {
                    modalBody.html('<span class="error-message">Error loading form.</span>');
                }
            },
            error: function() {
                modalBody.html('<span class="error-message">An error occurred.</span>');
            }
        });
    }

    // --- Modal Submission Handlers ---

    // Function to handle institute form submission
    function handleInstituteSubmission() {
        $('#institute_add_form').off('submit').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize() + '&action=add_institute';
            var messageBox = $('#modal_message_m');
            messageBox.html('<span class="warning-message">Saving...</span>');

            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        messageBox.html('<span class="success-message">' + response.message + '</span>');
                        
                        // Update the main form's Institute input field
                        var $sel = $('#institute_id');
                        var id = response.institute_id, name = response.institute_name;

                        if ($sel.find('option[value="'+id+'"]').length === 0) {
                            var $add = $sel.find('option[value="other_add"]');
                            var $newOpt = $('<option/>', { value: id, text: name });
                            $add.length ? $newOpt.insertBefore($add) : $sel.append($newOpt);
                        }
                        $sel.val(id).trigger('change');
                        setTimeout(function(){ $('#modal_institute_chamber').hide(); updatePreview(); }, 500);
                        
                        // Close modal after a short delay
                        setTimeout(function() {
                            $('#modal_institute_chamber').hide();
                            updatePreview();
                        }, 1000);
                        
                    } else {
                        messageBox.html('<span class="error-message">' + response.message + '</span>');
                    }
                }
            });
        });
    }

    // Function to handle chamber form submission
    function handleChamberSubmission() {
        $('#chamber_add_form').off('submit').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize() + '&action=add_chamber';
            var messageBox = $('#modal_message_c');
            messageBox.html('<span class="warning-message">Saving...</span>');

            $.ajax({
                url: '../../modules/master_data/ajax_handlers.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        messageBox.html('<span class="success-message">' + response.message + '</span>');
                        
                        // Update the main form's Chamber input field
                        var $sel = $('#chamber_id');
                        var id = response.chamber_id, name = response.chamber_name;

                        if ($sel.find('option[value="'+id+'"]').length === 0) {
                            var $add = $sel.find('option[value="other_add"]');
                            var $newOpt = $('<option/>', { value: id, text: name });
                            $add.length ? $newOpt.insertBefore($add) : $sel.append($newOpt);
                        }
                        $sel.val(id).trigger('change');
                        setTimeout(function(){ $('#modal_institute_chamber').hide(); updatePreview(); }, 500);

                        
                        // Close modal after a short delay
                        setTimeout(function() {
                            $('#modal_institute_chamber').hide();
                            updatePreview();
                        }, 1000);
                        
                    } else {
                        messageBox.html('<span class="error-message">' + response.message + '</span>');
                    }
                }
            });
        });
    }

    // --- Modal Trigger Listener ---

    // Listener for when the hidden ID field is set to 'other_add'
    $('#institute_id').on('change', function() {
        if ($(this).val() === 'other_add') {
            openMasterModal('institute');
        }
    });

    $('#chamber_id').on('change', function() {
        if ($(this).val() === 'other_add') {
            openMasterModal('chamber');
        }
    });
    
    // Close button logic for modal
    $('.modal .close-btn').on('click', function() {
        $('#modal_institute_chamber').hide();
        // Reset the form field if modal is closed without saving (optional, but good practice)
        // You might want to clear the search input to force re-selection if they close the modal
        // Example: $('#institute_search').val(''); $('#institute_id').val('');
    });
    
    // Close modal when clicking outside of the content area
    $(window).on('click', function(event) {
        var modal = $('#modal_institute_chamber');
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    
    
});

function populateInstituteDropdown() {
    var $sel = $('#institute_id');
    if ($sel.length === 0) return;

    // "other_add" থাকলে ধরে রাখি, বাকিগুলো রিফ্রেশ
    var $add = $sel.find('option[value="other_add"]');
    $sel.find('option').not('[value=""], [value="other_add"]').remove();

    $.ajax({
        url: '../../modules/master_data/ajax_handlers.php',
        type: 'GET',
        data: { action: 'get_institutes' },
        dataType: 'json',
        success: function(res) {
            if (res.success && Array.isArray(res.data)) {
                res.data.forEach(function(item){
                    $sel.find('option[value="'+item.id+'"]').length ||
                        $sel.append('<option value="'+item.id+'">'+item.name+'</option>');
                });
            }
        }
    });
}

function populateChamberDropdown() {
    var $sel = $('#chamber_id');
    if ($sel.length === 0) return;

    var $add = $sel.find('option[value="other_add"]');
    $sel.find('option').not('[value=""], [value="other_add"]').remove();

    $.ajax({
        url: '../../modules/master_data/ajax_handlers.php',
        type: 'GET',
        data: { action: 'get_chambers' },
        dataType: 'json',
        success: function(res) {
            if (res.success && Array.isArray(res.data)) {
                res.data.forEach(function(item){
                    $sel.find('option[value="'+item.id+'"]').length ||
                        $sel.append('<option value="'+item.id+'">'+item.name+'</option>');
                });
            }
        }
    });
}
