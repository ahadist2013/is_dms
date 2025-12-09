// assets/js/mobile_number_update.js
$(document).ready(function () {

    loadRequests();

    function loadRequests() {
        $.ajax({
            url: BASE_PATH + 'api/mobile_change_list.php',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Failed to load data');
                    return;
                }
                buildTable(res.data);
            },
            error: function () {
                alert('Error loading data');
            }
        });
    }

    function buildTable(rows) {
        const $tbody = $('#mobileUpdateTable tbody');
        $tbody.empty();

        if (!rows || rows.length === 0) {
            $tbody.append('<tr><td colspan="4" class="text-center">No pending requests.</td></tr>');
            return;
        }

        rows.forEach(function (r) {
            // Old doctor info block
            let oldInfo = `
                <div class="mobile-label">Old Mobile</div>
                <div class="mobile-box">${r.old_mobile || ''}</div>
                <div class="mt-1"><strong>${r.old_name_full || ''}</strong></div>
            `;

            if (r.old_institute) {
                oldInfo += `<div class="info-small">${r.old_institute}</div>`;
            }
            if (r.old_designation) {
                oldInfo += `<div class="info-small">${r.old_designation}</div>`;
            }

            if (r.submitted_by && r.submitted_by.name) {
                oldInfo += `
                    <div class="info-small mt-1">
                        Submitted by: ${r.submitted_by.name} (${r.submitted_by.login_id || ''})<br>
                        ${r.submitted_by.zone || ''} / ${r.submitted_by.territory || ''}
                    </div>
                `;
            }

            // New doctor / assignments block
            let newInfo = `
                <div class="mobile-label">New Mobile</div>
                <div class="mobile-box">${r.new_mobile || ''}</div>
            `;

            if (r.new_doctor && r.new_doctor.name_full) {
                newInfo += `<div class="mt-1"><strong>${r.new_doctor.name_full}</strong></div>`;
            } else {
                newInfo += `<div class="mt-1 info-small text-muted">No doctor found with this number.</div>`;
            }

            if (r.assignments && r.assignments.length > 0) {
                newInfo += `<div class="mt-1 info-small">Assignments:</div>`;
                r.assignments.forEach(function (a) {
                    newInfo += `
                        <div class="info-small">
                            Officer: ${a.officer_name || ''} (${a.login_id || ''})<br>
                            ${a.zone_name || ''} / ${a.territory_name || ''}
                        </div>
                    `;
                });
            } else {
                newInfo += `<div class="mt-1 info-small text-muted">No record available.</div>`;
            }

            // Decide action button type
            let actionBtnHtml = '';

            if (!r.new_doctor || !r.new_doctor.doctor_id) {
                // No doctor found with new mobile => Update Number
                actionBtnHtml = `
                    <button class="btn btn-primary btn-action-main btn-update-number"
                        data-request-id="${r.request_id}"
                        data-old-doctor-id="${r.old_doctor_id}"
                        data-new-mobile="${r.new_mobile}">
                        Update Number
                    </button>
                `;
            } else {
                // Doctor exists with new mobile => Add user (transfer assignments)
                actionBtnHtml = `
                    <button class="btn btn-success btn-action-main btn-add-user"
                        data-request-id="${r.request_id}"
                        data-old-doctor-id="${r.old_doctor_id}"
                        data-new-doctor-id="${r.new_doctor.doctor_id}"
                        data-new-mobile="${r.new_mobile}">
                        Add User
                    </button>
                `;
            }

            const rowHtml = `
                <tr>
                    <td>${r.sl}</td>
                    <td>${oldInfo}</td>
                    <td>${newInfo}</td>
                    <td>${actionBtnHtml}</td>
                </tr>
            `;

            $tbody.append(rowHtml);
        });
    }

    // Update Number
    $('#mobileUpdateTable').on('click', '.btn-update-number', function () {
        const requestId   = $(this).data('request-id');
        const oldDoctorId = $(this).data('old-doctor-id');
        const newMobile   = $(this).data('new-mobile');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm update',
                text: 'Update this doctor\'s mobile number to ' + newMobile + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, update'
            }).then((result) => {
                if (result.isConfirmed) {
                    applyAction('update_number', requestId, oldDoctorId, 0, newMobile);
                }
            });
        } else {
            if (confirm('Update mobile to ' + newMobile + '?')) {
                applyAction('update_number', requestId, oldDoctorId, 0, newMobile);
            }
        }
    });

    // Add User (transfer assignments)
    $('#mobileUpdateTable').on('click', '.btn-add-user', function () {
        const requestId   = $(this).data('request-id');
        const oldDoctorId = $(this).data('old-doctor-id');
        const newDoctorId = $(this).data('new-doctor-id');
        const newMobile   = $(this).data('new-mobile');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm transfer',
                text: 'Transfer all assignments from OLD doctor to the doctor with new mobile ' + newMobile + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, transfer'
            }).then((result) => {
                if (result.isConfirmed) {
                    applyAction('add_user', requestId, oldDoctorId, newDoctorId, newMobile);
                }
            });
        } else {
            if (confirm('Transfer assignments to new doctor?')) {
                applyAction('add_user', requestId, oldDoctorId, newDoctorId, newMobile);
            }
        }
    });

    function applyAction(action, requestId, oldDoctorId, newDoctorId, newMobile) {
        $.ajax({
            url: BASE_PATH + 'api/mobile_change_apply.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                request_id: requestId,
                old_doctor_id: oldDoctorId,
                new_doctor_id: newDoctorId,
                new_mobile: newMobile
            },
            success: function (res) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(
                        res.success ? 'Success' : 'Error',
                        res.message || '',
                        res.success ? 'success' : 'error'
                    );
                } else {
                    alert(res.message || (res.success ? 'Done' : 'Failed'));
                }
                if (res.success) {
                    loadRequests();
                }
            },
            error: function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Request failed.', 'error');
                } else {
                    alert('Request failed');
                }
            }
        });
    }
});
