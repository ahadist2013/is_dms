(function(){
  const qs = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  const listBody = qs('#list-body');
  const pagerDiv = qs('#pager');
  const searchInput = qs('[data-filter="q"]'); 

  // Initial state for filtering and sorting
  let state = {
    page: 1,
    per_page: 50,
    sort_by: defaultSortBy, // Fetched from the global constant
    sort_order: 'DESC',
    q: '' // Search query state
  };
  
  function getFilters() {
      return {
          page: state.page,
          per_page: state.per_page,
          sort_by: state.sort_by,
          sort_order: state.sort_order,
          q: state.q
      };
  }

  // --- 1. Data Fetching ---
function fetchAndRender() {
    listBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Loading data...</td></tr>';
    
    const params = new URLSearchParams(getFilters());

    fetch(apiUrl + '?' + params.toString())
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          state.total = json.total;
          state.sort_by = json.sort_by;
          state.sort_order = json.sort_order;
          
          renderTable(json.data || [], json.page, json.per_page);
          renderPager(json.page, json.per_page, json.total);
          updateSortIndicators(); // Call the fixed function here
          
        } else {
          listBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: red;">${json.msg || 'Error fetching data.'}</td></tr>`;
          renderPager(1, 50, 0); 
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        listBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: red;">API সংযোগ ব্যর্থ হয়েছে। (Check Console)</td></tr>`;
      });
  }

  // assets/js/user_entries.js
// ... (অন্যান্য ফাংশন)

// --- 3. Table Rendering ---
function renderTable(data, currentPage, perPage) {
  listBody.innerHTML = '';
  if (data.length === 0) {
    listBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No entries found.</td></tr>';
    return;
  }

  data.forEach((item, index) => {
    const sl = (currentPage - 1) * perPage + index + 1;
    const row = document.createElement('tr');
    // ✅ নিশ্চিত করুন: item.territory_id ডাটাবেস থেকে আসছে
    row.innerHTML = `
      <td>${sl}</td>
      <td>${item.name || 'N/A'}</td>
      <td>${item.login_id || 'N/A'}</td>
      <td>${item.zone_name || 'N/A'}</td>
      <td>${item.territory_name || 'N/A'}</td>
      <td style="font-weight: bold; text-align: center;">${item.total_entry}</td>
      <td>
        <button class="btn btn-primary btn-view-entry" 
                data-user-id="${item.user_id}" 
                data-user-name="${item.name}" 
                data-territory-id="${item.territory_id}"> 
          View
        </button>
      </td>
    `;
    listBody.appendChild(row);
  });
}

// ... (বাকি ফাংশন)
  
  // --- 3. Pager Rendering ---
  function renderPager(page, perPage, total) {
    const totalPages = Math.ceil(total / perPage);
    pagerDiv.innerHTML = '';
    
    if (total <= 0) return;

    const start = Math.max(1, page - 2);
    const end = Math.min(totalPages, page + 2);

    const btn = (p, text, isDisabled = false) => 
      `<button data-page="${p}" ${isDisabled ? 'disabled' : ''}>${text}</button>`;

    let html = '';
    html += btn(1, '« First', page === 1);
    html += btn(Math.max(1, page - 1), '‹ Prev', page === 1);

    for (let p = start; p <= end; p++) {
      html += `<button data-page="${p}" class="${p === page ? 'active' : ''}">${p}</button>`;
    }

    if (end < totalPages) html += '...';

    html += btn(Math.min(totalPages, page + 1), 'Next ›', page === totalPages);
    html += btn(totalPages, 'Last »', page === totalPages);

    pagerDiv.innerHTML = html + `<span style="margin-left: 15px;">Page ${page} of ${totalPages} (${total} total)</span>`;
  }
  
  // --- 4. Sort Indicator Update (Final Safe Fix) ---
  function updateSortIndicators() {
    qsa('#user-entry-table th[data-sort]').forEach(th => {
        const iconSpan = th.querySelector('.sort-indicator'); // Target the span with class 'sort-indicator'
        
        th.classList.remove('active-sort');
        th.removeAttribute('data-order');
        
        // Reset all icons to default (fas fa-sort)
        if (iconSpan) {
             iconSpan.classList.remove('fa-sort-up', 'fa-sort-down');
             iconSpan.classList.add('fa-sort');
        }

        if (th.dataset.sort === state.sort_by) {
            th.classList.add('active-sort');
            th.dataset.order = state.sort_order;
            
            if (iconSpan) {
                // Set the correct sorting icon for the active column
                iconSpan.classList.remove('fa-sort'); 
                let iconClass = state.sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
                iconSpan.classList.add(iconClass);
            }
        }
    });
  }

  // assets/js/user_entries.js - Event Listeners এর ভেতরের অংশ
// ...

  // --- 5. Event Listeners ---
  document.addEventListener('click', e => {
    // Pager click
    const btn = e.target.closest('#pager button');
    if (btn && !btn.classList.contains('active') && !btn.disabled) {
      state.page = parseInt(btn.dataset.page);
      fetchAndRender();
    }
    
    // Sort click
    const sortHeader = e.target.closest('#user-entry-table th[data-sort]');
    if (sortHeader) {
      const newSortBy = sortHeader.dataset.sort;
      let newSortOrder = 'ASC';
      
      if (newSortBy === state.sort_by) {
        newSortOrder = state.sort_order === 'ASC' ? 'DESC' : 'ASC';
      }
      
      state.sort_by = newSortBy;
      state.sort_order = newSortOrder;
      state.page = 1; // Reset to page 1 on sort change
      fetchAndRender();
    }
    
   const viewBtn = e.target.closest('.btn-view-entry');
    if (viewBtn) {
      // 🚨 ফিক্স ১: ইভেন্টের ডিফল্ট অ্যাকশন এবং ইভেন্ট বাব্লিং বন্ধ করা। 
      // এটি অন্য সকল ডুপ্লিকেট হ্যান্ডলারকে কার্যকরভাবে থামিয়ে দেবে।
      e.preventDefault(); 
      e.stopPropagation();

      const territoryId = viewBtn.dataset.territoryId;
      const userName = viewBtn.dataset.userName;
      
      // Territory ID চেক করে সরাসরি নেভিগেট করা
      if (territoryId && territoryId !== 'undefined') {
          // নেভিগেশন লজিক
          window.location.href = baseUrl + 'pages/territory_doctors.php?territory_id=' + territoryId + '&name=' + encodeURIComponent(userName);
      } else {
          // এটি কেবল ডাটা মিসিং এর ক্ষেত্রেই দেখাবে, যা এখন Territory ID 187 আসার কারণে ঘটবে না।
          Swal.fire('Error', `Territory ID missing or invalid for ${userName}. Cannot navigate.`, 'error');
      }
      return; // নিশ্চিত করতে যে এই ব্লকের পর আর কিছু চলবে না।
    }
  });

// ... (বাকি কোড)
  // --- Search Input Listener (Debounce for performance) ---
  let searchTimeout;
  if (searchInput) {
      searchInput.addEventListener('input', () => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
              const newQ = searchInput.value.trim();
              if (newQ !== state.q) {
                  state.q = newQ;
                  state.page = 1; 
                  fetchAndRender();
              }
          }, 400); 
      });
  }
  
  // Initial load
  fetchAndRender();

})();