(function(){
  const qs = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  const listBody = qs('#list-body');
  const pagerDiv = qs('#pager');
  const searchInput = qs('[data-filter="q"]'); 
  const perPageSelect = qs('[data-filter="per_page"]'); 
  
  // Initial state uses the ID passed from PHP
  let state = {
    page: 1,
    per_page: parseInt(perPageSelect.value) || 50,
    sort_by: defaultSortBy,
    sort_order: 'ASC',
    q: '',
    territory_id: initialTerritoryId // Crucial parameter
  };
  
  // --- 1. Filter Collector ---
  function getFilters() {
      return {
          page: state.page,
          per_page: state.per_page,
          sort_by: state.sort_by,
          sort_order: state.sort_order,
          q: state.q,
          territory_id: state.territory_id // Always include this ID
      };
  }

  // --- 2. Data Fetching ---
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
          updateSortIndicators();
          
        } else {
          listBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: red;">${json.msg || 'Error fetching data.'}</td></tr>`;
          renderPager(1, 20, 0); 
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        listBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: red;">API সংযোগ ব্যর্থ হয়েছে।</td></tr>`;
      });
  }

  // --- 3. Table Rendering ---
  function renderTable(data, currentPage, perPage) {
    listBody.innerHTML = '';
    if (data.length === 0) {
      listBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No doctors assigned to this territory.</td></tr>';
      return;
    }

    data.forEach((item, index) => {
      const sl = (currentPage - 1) * perPage + index + 1;
      const row = document.createElement('tr');
      
      const typeClass = item.doctor_type === 'in_house' ? 'text-success' : 'text-primary';

      row.innerHTML = `
        <td>${sl}</td>
        <td>${item.name || 'N/A'}</td>
        <td>${item.designation_name || 'N/A'}</td>
        <td>${item.institute_name || 'N/A'}</td>
        <td>${item.mobile_number || 'N/A'}</td>
        <td><span class="${typeClass}">${item.doctor_type.toUpperCase()}</span></td>
        <td>
          <button class="btn btn-primary btn-sm" data-action="edit" data-id="${item.doctor_id}">Edit</button>
          <button class="btn btn-danger btn-sm" data-action="remove" data-id="${item.assignment_id}">Remove</button>
        </td>
      `;
      listBody.appendChild(row);
    });
  }
  
  // --- 4. Pager Rendering ---
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
  
  // --- 5. Sort Indicator Update ---
  function updateSortIndicators() {
    qsa('#territory-doctor-table th[data-sort]').forEach(th => {
        const iconSpan = th.querySelector('.sort-indicator'); 
        
        th.classList.remove('active-sort');
        th.removeAttribute('data-order');
        
        if (iconSpan) {
             iconSpan.classList.remove('fa-sort-up', 'fa-sort-down');
             iconSpan.classList.add('fa-sort');
        }

        if (th.dataset.sort === state.sort_by) {
            th.classList.add('active-sort');
            th.dataset.order = state.sort_order;
            
            if (iconSpan) {
                iconSpan.classList.remove('fa-sort'); 
                let iconClass = state.sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
                iconSpan.classList.add(iconClass);
            }
        }
    });
  }

  // --- 6. Event Listeners ---
  document.addEventListener('click', e => {
    // Pager click
    const btn = e.target.closest('#pager button');
    if (btn && !btn.classList.contains('active') && !btn.disabled) {
      state.page = parseInt(btn.dataset.page);
      fetchAndRender();
    }
    
    // Sort click
    const sortHeader = e.target.closest('#territory-doctor-table th[data-sort]');
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
    
    // Action Buttons (Edit/Remove - placeholder)
    // Add logic here later for actual actions (like the remove logic in listing.js)
    const actionBtn = e.target.closest('button[data-action]');
    if (actionBtn) {
        const action = actionBtn.dataset.action;
        const id = actionBtn.dataset.id;
        Swal.fire('Action Clicked', `${action} on ID: ${id} - Logic to be implemented.`, 'info');
    }
  });

  // --- Search Input Listener (Debounce) ---
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
  
  // --- Per Page Select Listener ---
  if (perPageSelect) {
      perPageSelect.addEventListener('change', () => {
          state.per_page = parseInt(perPageSelect.value);
          state.page = 1; 
          fetchAndRender();
      });
  }
  
  // Initial load
  fetchAndRender();

})();