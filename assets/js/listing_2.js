// assets/js/listing_2.js
$(document).ready(function () {
    let currentPage = 1;
    let perPage     = 350;
    let currentSortBy  = 'name';
    let currentSortDir = 'asc';
    let currentQuery   = '';

    loadDoctorList();

    // Search
    $('#search_box').on('keyup', function () {
        currentQuery = $(this).val();
        currentPage = 1;
        loadDoctorList();
    });

    // Column sort
    $('#myDoctors2Table thead').on('click', 'th.sortable', function () {
    
        $('#myDoctors2Table thead th').removeClass('sorted-asc sorted-desc');
    
        const sortBy = $(this).data('sort');
        if (currentSortBy === sortBy) {
            currentSortDir = (currentSortDir === 'asc') ? 'desc' : 'asc';
        } else {
            currentSortBy = sortBy;
            currentSortDir = 'asc';
        }
    
        if (currentSortDir === 'asc') {
            $(this).addClass('sorted-asc');
        } else {
            $(this).addClass('sorted-desc');
        }
    
        loadDoctorList();
    });


    // Action buttons (event delegation)
    $('#myDoctors2Table tbody')
        .on('click', '.action-edit', function () {
            const doctorId = $(this).data('doctor-id');
            window.location.href = BASE_PATH + 'modules/doctor/edit.php?doctor_id=' + doctorId;
        })
        .on('click', '.action-remove', function () {
            const assignmentId = $(this).data('assignment-id');
            removeAssignment(assignmentId);
        })
        .on('click', '.action-edit-mobile', function () {
            const doctorId  = $(this).data('doctor-id');
            const doctorName = $(this).data('doctor-name');
            const oldMobile  = $(this).data('mobile');

            $('#mobileEditDoctorId').val(doctorId);
            $('#mobileEditDoctorName').text(doctorName);
            $('#mobileEditOldMobile').text(oldMobile);
            $('#mobileEditNewMobile').val('');

            $('#mobileEditModal').modal('show');
        });

    // Modal submit
    $('#mobileEditForm').on('submit', function (e) {
        e.preventDefault();
        submitMobileChange();
    });

    $('#btnExportExcel').on('click', function(){
        window.open(
            BASE_PATH + 'api/doctors_list_2.php?export=excel&q=' 
            + encodeURIComponent(currentQuery)
            + '&sort_by=' + currentSortBy
            + '&sort_dir=' + currentSortDir,
            '_blank'
        );
    });
    
    $('#btnExportPDF').on('click', function(){
        window.open(
            BASE_PATH + 'api/doctors_list_2.php?export=excel&q=' 
            + encodeURIComponent(currentQuery)
            + '&sort_by=' + currentSortBy
            + '&sort_dir=' + currentSortDir,
            '_blank'
        );
    });

    function loadDoctorList() {
        $.ajax({
            url: BASE_PATH + 'api/doctors_list_2.php',
            type: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                per_page: perPage,
                q: currentQuery,
                sort_by: currentSortBy,
                sort_dir: currentSortDir
            },
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Failed to load data');
                    return;
                }
                buildTable(res.data);
                // চাইলে pagination দেখাতে পারো later
            },
            error: function () {
                alert('Error loading data');
            }
        });
    }

    function buildTable(rows) {
        let html = '';
        rows.forEach(function (row) {
            const doctorNameEsc = $('<div>').text(row.name_full).html(); // for data attr

            html += `
                <tr style="font-size: 12px;">
                    <td>${row.sl}</td>
                    <td>${row.doctor_id}</td>
                    <td style="white-space: normal;">${row.name_full}</td>
                    <td>${row.mobile_number || ''}</td>
                    <td style="white-space: normal;">${row.chamber || ''}</td>
                    <td style="white-space: normal;">${row.institution || ''}</td>
                    <td>${row.grade || ''}</td>
                    <td style="text-align:center;">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                &#8942;
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="javascript:void(0)" 
                                    class="dropdown-item action-edit"
                                    data-doctor-id="${row.doctor_id}">Edit</a></li>
                                <li><a href="javascript:void(0)" 
                                    class="dropdown-item action-remove"
                                    data-assignment-id="${row.assignment_id}">Remove</a></li>
                                <li><a href="javascript:void(0)" 
                                    class="dropdown-item action-edit-mobile"
                                    data-doctor-id="${row.doctor_id}"
                                    data-doctor-name="${doctorNameEsc}"
                                    data-mobile="${row.mobile_number || ''}">Edit Number</a></li>
                            </ul>
                        </div>
                    </td>

                </tr>
            `;
        });

        $('#myDoctors2Table tbody').html(html);
    }

    function removeAssignment(assignmentId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This doctor assignment will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove it'
            }).then((result) => {
                if (result.isConfirmed) {
                    doRemove(assignmentId);
                }
            });
        } else {
            if (confirm('Remove this assignment?')) {
                doRemove(assignmentId);
            }
        }
    }

    function doRemove(assignmentId) {
        $.ajax({
            url: BASE_PATH + 'api/doctor_remove_2.php',
            type: 'POST',
            dataType: 'json',
            data: { assignment_id: assignmentId },
            success: function (res) {
                if (res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Removed!', res.message || 'Assignment removed.', 'success');
                    } else {
                        alert('Removed successfully');
                    }
                    loadDoctorList();
                } else {
                    alert(res.message || 'Failed to remove');
                }
            },
            error: function () {
                alert('Error while removing');
            }
        });
    }

    function submitMobileChange() {
        const doctorId = $('#mobileEditDoctorId').val();
        const newMobile = $('#mobileEditNewMobile').val().trim();
        const oldMobile = $('#mobileEditOldMobile').text().trim();

        // basic validation (client)
        const validPrefix = ['013','014','015','016','017','018','019'];
        if (newMobile.length !== 11 || !/^\d{11}$/.test(newMobile)) {
            alert('Mobile must be exactly 11 digits.');
            return;
        }
        if (!validPrefix.some(p => newMobile.startsWith(p))) {
            alert('Mobile must start with 013-019.');
            return;
        }

        $.ajax({
            url: BASE_PATH + 'api/doctor_mobile_submit.php',
            type: 'POST',
            dataType: 'json',
            data: {
                doctor_id: doctorId,
                old_mobile: oldMobile,
                new_mobile: newMobile
            },
            success: function (res) {
                if (res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Submitted!', res.message || 'Mobile change request submitted.', 'success');
                    } else {
                        alert('Submitted');
                    }
                    $('#mobileEditModal').modal('hide');
                } else {
                    alert(res.message || 'Failed to submit');
                }
            },
            error: function () {
                alert('Error submitting change request');
            }
        });
    }
});
