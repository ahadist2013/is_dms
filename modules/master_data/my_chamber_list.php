<?php
// ফাইল: modules/master_data/my_chamber_list.php
// (Code is identical to chamber_list.php except for the title and the flag)

include '../../includes/header.php'; // Includes session_start, db_connection, and BASE_PATH

// Fetch initial data: Divisions for filtering
$divisions_result = $conn->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC");
$divisions = [];
if ($divisions_result) {
    while ($row = $divisions_result->fetch_assoc()) {
        $divisions[] = $row;
    }
    $divisions_result->free();
}

// Configuration for this page
$is_my_chamber_list = true; // Flag: THIS IS THE ONLY DIFFERENCE
$page_title = "🧑‍💻 My Chamber List (Created By You)";
?>

<div class="content-area">
    <h2><?php echo $page_title; ?></h2>
    
    <div class="filter-controls card" style="margin-bottom: 20px; padding: 15px;">
        <form id="chamber_filter_form" class="form-inline">
            <input type="hidden" id="is_my_chamber_list" value="<?php echo (int)$is_my_chamber_list; ?>">
            <input type="hidden" id="current_page" value="1">
            <input type="hidden" id="limit_per_page" value="100">

            <div class="form-group-inline">
                <label for="division_filter">Division:</label>
                <select id="division_filter" name="division_id">
                    <option value="">-- All Divisions --</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?php echo $division['division_id']; ?>">
                            <?php echo htmlspecialchars($division['division_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-inline">
                <label for="district_filter">District:</label>
                <select id="district_filter" name="district_id" disabled>
                    <option value="">-- Select District --</option>
                </select>
            </div>

            <div class="form-group-inline">
                <label for="upazila_filter">Upazila:</label>
                <select id="upazila_filter" name="upazila_id" disabled>
                    <option value="">-- Select Upazila --</option>
                </select>
            </div>
            
            <div class="form-group-inline search-right">
                <label for="search_chamber">Search Chamber:</label>
                <input type="text" id="search_chamber" placeholder="Type Chamber Name..." style="width: 200px;">
            </div>
        </form>
    </div>

    <div class="card" style="padding: 10px;">
        <div id="chamber_table_container">
            <p style="text-align: center; color: #555;">Loading chamber data...</p>
        </div>
        
        <div id="pagination_links" class="pagination" style="text-align: center; margin-top: 15px;">
            </div>
    </div>
</div>

<?php 
include '../../includes/footer.php'; 
?>