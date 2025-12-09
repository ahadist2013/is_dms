<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../includes/header.php';

// Security: Enforce Admin role
if (($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: " . BASE_PATH . "index.php");
    exit();
}
// Note: $conn is assumed to be available from header.php
?>
<div class="card user-activity-card"> <div class="header-bar-custom"> <h2>📡 Live User Activity & Session Control</h2>
    </div>

    <div class="filters-and-info">
        <label for="status_filter" class="filter-label">Filter Status:</label>
        <select id="status_filter" data-filter="status" class="custom-select">
            <option value="all">All Users</option>
            <option value="logged_in">Logged In Only</option>
            <option value="logged_out">Logged Out Only</option>
        </select>
    </div>

    <div class="table-container-custom">
        <table class="data-table" id="user-activity-table"> <thead>
                <tr>
                    <th style="width: 50px;">SL</th>
                    <th style="width: 250px;">User Name</th>
                    <th style="width: 100px;">ID No</th>
                    <th style="width: 150px;">Zone</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 160px;">Last Activity</th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody id="activity-list-body">
                <tr><td colspan="7" style="text-align: center;">Loading data...</td></tr>
            </tbody>
        </table>
    </div>
    
    <div class="pager" id="pager"></div>
</div>

<style>
/* ==================================================== */
/* 🎨 UI/UX Styles for User Activity Page */
/* ==================================================== */

/* 1. General Card and Header Style */
.user-activity-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); /* Stronger, modern shadow */
    margin-bottom: 25px;
    padding: 0; /* Remove default padding as header/body will add it */
}

.header-bar-custom {
    background-color: #053a72; /* Primary Dark Blue */
    color: #fff;
    padding: 18px 25px;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    margin-bottom: 20px;
}

.header-bar-custom h2 {
    margin: 0;
    font-size: 1.5em;
    font-weight: 600;
}

/* 2. Filters and Select Styling */
.filters-and-info {
    padding: 0 25px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.filter-label {
    font-weight: 600;
    color: #333;
}

.custom-select {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
    min-width: 150px;
    appearance: none; /* Remove default arrow */
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center / 10px 10px;
}
.custom-select:focus {
    border-color: #007bff;
    outline: none;
}

/* 3. Table Styling */
.table-container-custom {
    padding: 0 25px 25px 25px;
    overflow-x: auto; /* Ensure responsiveness */
}

.data-table {
    width: 100%;
    border-collapse: separate; /* Use separate for rounded corners/spacing */
    border-spacing: 0;
    font-size: 14px;
    line-height: 1.5;
}

.data-table thead th {
    background-color: #e9f2ff; /* Light Blue Header */
    color: #053a72;
    padding: 12px 10px;
    text-align: left;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    border: none;
    position: sticky; /* Keep header visible */
    top: 0;
    z-index: 10;
}

.data-table tbody td {
    padding: 10px 10px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.data-table tbody tr:last-child td {
    border-bottom: none; /* Remove border for last row */
}

.data-table tbody tr:hover {
    background-color: #f7fbff; /* Very light hover effect */
}


/* 4. Status Badge Styling */
.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    min-width: 100px;
    justify-content: center;
}

/* Green Active Badge */
.status-badge.badge-primary {
    background-color: #e6f7eb;
    color: #28a745;
    border: 1px solid #28a74550;
}

/* Red Inactive Badge */
.status-badge.badge-secondary {
    background-color: #fce6e6;
    color: #dc3545;
    border: 1px solid #dc354550;
}

/* Status Indicator Dot (Pulsing) - Keep this for visual flair */
.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 5px;
    display: inline-block;
    vertical-align: middle;
}
.status-logged-in {
    background-color: #28a745; /* Green */
    box-shadow: 0 0 0 0 rgba(40, 167, 69, 1);
    animation: pulse-green 1.5s infinite;
}
.status-logged-out {
    background-color: #dc3545; /* Red */
    animation: none;
}
@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

/* 5. Action Button Style */
.btn-force-logout {
    background-color: #dc3545;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: background-color 0.2s, transform 0.1s;
    white-space: nowrap;
}
.btn-force-logout:hover {
    background-color: #c82333;
    transform: translateY(-1px);
}
</style>

<script>
// (function() { // Self-invoking function removed for simplicity, using $(document).ready
$(document).ready(function() {
    const apiUrl = '<?php echo BASE_PATH; ?>modules/admin/user_activity_data.php';
    const listBody = $('#activity-list-body');
    let state = { status: 'all' };
    
    function fetchAndRender() {
        listBody.html('<tr><td colspan="7" style="text-align: center;">Loading data...</td></tr>');
        
        const params = new URLSearchParams(state);
        
        fetch(apiUrl + '?' + params.toString())
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    renderTable(json.data || []);
                } else {
                    listBody.html('<tr><td colspan="7" style="text-align: center; color: red;">Error: ' + (json.msg || 'Failed to fetch data') + '</td></tr>');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                listBody.html('<tr><td colspan="7" style="text-align: center; color: red;">Network Error!</td></tr>');
            });
    }

    function renderTable(data) {
        let html = '';
        data.forEach((user, index) => {
            const is_logged_in = user.is_active === '1';
            const status_class = is_logged_in ? 'status-logged-in' : 'status-logged-out';
            
            // ✅ UI/UX Improvement: Use descriptive badge classes
            const full_status_text = is_logged_in ? 'Logged In' : 'Logged Out';
            const badge_class = is_logged_in ? 'badge-primary' : 'badge-secondary';

            const last_activity = is_logged_in ? (user.last_activity_time || 'N/A') : (user.logout_time || 'N/A');
            
            // Format Last Activity
            // Ensure proper date object creation for reliable toLocaleString()
            const last_activity_text = last_activity === 'N/A' ? 'N/A' : new Date(last_activity).toLocaleString();

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${user.name}</td>
                    <td>${user.login_id}</td>
                    <td>${user.zone_name}</td>
                    <td>
                        <span class="status-badge ${badge_class}">
                            <span class="status-indicator ${status_class}"></span>
                            ${full_status_text}
                        </span>
                    </td>
                    <td>${last_activity_text}</td>
                    <td>
                        ${is_logged_in ? 
                            `<button class="btn-force-logout" data-user-id="${user.user_id}" data-login-id="${user.login_id}">Force Logout</button>` : 
                            '<span style="color: #6c757d; font-size: 12px; font-style: italic;">Inactive</span>'
                        }
                    </td>
                </tr>
            `;
        });
        listBody.html(html || '<tr><td colspan="7" style="text-align: center;">No users found matching filter.</td></tr>');
    }

    // Filter change handler
    $('#status_filter').on('change', function() {
        state.status = $(this).val();
        fetchAndRender();
    });

    // Force Logout handler (Your existing AJAX logic)
    listBody.on('click', '.btn-force-logout', function() {
        const userId = $(this).data('user-id');
        const loginId = $(this).data('login-id');

        // Assuming Swal (SweetAlert2) is included globally
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to force logout user ${loginId}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, force logout!'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX call to force logout
                $.ajax({
                    url: apiUrl,
                    type: 'POST',
                    data: { action: 'force_logout', user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('✅ Logged Out!', response.msg, 'success');
                            fetchAndRender(); // Refresh the list
                        } else {
                            Swal.fire('⚠️ Failed!', response.msg, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('❌ Error!', 'Server request failed.', 'error');
                    }
                });
            }
        });
    });

    // Initial fetch
    fetchAndRender();
    
    // Auto-refresh the list every 15 seconds for "live" feel
    setInterval(fetchAndRender, 15000); 
});
// })(); // Self-invoking function end removed
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>