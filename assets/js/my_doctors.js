/**
 * File: is_dms/assets/js/my_doctors.js
 * Purpose: Frontend logic for My Doctors List (DataTables)
 * FINAL CORRECTED VERSION with Client-Side Rendering Fix
 */

$(document).ready(function() {
    // Determine the base path (Must match header.php's BASE_PATH)
    const BASE_PATH = 'https://bdd.ibnsinatrust.com/is_dms/';

    // Initialize DataTables
    const doctorTable = $('#doctorListTable').DataTable({
        // 1. Server-Side Processing
        processing: true,
        serverSide: true,
        pageLength: 300, 
        lengthMenu: [100, 300, 500],
        order: [
            [1, 'asc']
        ], 
        scrollX: true, 

        // 2. AJAX Data Source
        ajax: {
            url: BASE_PATH + 'api/my_doctors_data.php',
            type: 'POST', 
            error: function(xhr, error, code) {
                console.log("DataTables error: ", xhr.responseText, error, code);
                alert("Could not load doctor data. Check console for details.");
            }
        },

        // 3. Column Definitions
        columns: [{
                data: 'SL',
                name: 'SL',
                orderable: false,
                searchable: false,
                width: "3%"
            },
            {
                data: 'doctor_id',
                name: 'd.doctor_id',
                searchable: true,
                width: "5%"
            },
            {
                data: 'formatted_doctor_name',
                name: 'formatted_doctor_name',
                searchable: true
            },
            {
                data: 'mobile_number',
                name: 'd.mobile_number',
                searchable: true,
                width: "10%"
            },
            {
                data: 'institute_name',
                name: 'mi.institute_name',
                searchable: true
            },
            {
                data: 'chamber_name',
                name: 'mc.chamber_name',
                searchable: true
            },
            {
                data: 'specialization_name',
                name: 'md.discipline_name', // ✅ FIXED: Correct column name
                searchable: true
            },
            {
                data: 'actions', // এই কলামের জন্য DataTables এর render ব্যবহার করা হবে
                name: 'actions',
                orderable: false,
                searchable: false,
                width: "15%",
                // ✅ রেন্ডার ফাংশন ব্যবহার করে বাটন তৈরি করা হলো
                render: function (data, type, row) {
                    const editLink = BASE_PATH + 'modules/doctor/edit.php?doctor_id=' + row.doctor_id + '&assignment_id=' + row.assignment_id;
                    
                    const actionsHtml = `
                        <a href="${editLink}" class="btn btn-sm btn-primary" title="Edit Doctor & Assignment" style="margin-right: 5px;">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btn-remove" 
                            data-assignment-id="${row.assignment_id}" 
                            data-doctor-name="${row.doctor_name_raw}" 
                            title="Remove Assignment from Territory">
                            <i class="fa-solid fa-trash"></i> Remove
                        </button>
                    `;
                    // Note: 'row' অবজেক্টে 'doctor_id', 'assignment_id', ও 'doctor_name_raw' PHP থেকে এসেছে।
                    return actionsHtml;
                }
            }
        ],

        // 4. Dom for Search, Filter
        dom: '<"top"lf>rt<"bottom"ip><"clear">', 
    });


    // 5. Remove Assignment Logic (This remains unchanged and will work with the new buttons)
    $('#doctorListTable').on('click', '.btn-remove', function() {
        const assignmentId = $(this).data('assignment-id');
        const doctorName = $(this).data('doctor-name');

        Swal.fire({
            title: `Are you sure?`,
            text: `You are about to remove the assignment for Dr. ${doctorName}. This will only remove the doctor from this territory, NOT the doctor itself.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_PATH + 'api/remove_assignment.php',
                    type: 'POST',
                    data: {
                        assignment_id: assignmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Removed!', 'Assignment has been successfully removed.', 'success');
                            doctorTable.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error!', response.message || 'Could not remove assignment.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'An error occurred during the request.', 'error');
                    }
                });
            }
        });
    });
});