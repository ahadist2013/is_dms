(function(){
  const qs = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  let state = {
    scope: window.LIST_SCOPE || 'my',
    page: 1,
    per_page: window.PER_PAGE || 300,
    q: '',
    doctor_type: ''
  };

  const apiUrl = window.API_URL;

  function fetchAndRender(){
    const params = new URLSearchParams(state);
    fetch(apiUrl + '?' + params.toString())
      .then(r => r.json())
      .then(json => {
        renderTable(json.data || []);
        renderPager(json.page, json.per_page, json.total);
      });
  }

  function renderTable(rows){
    const tb = qs('#list-body');
    tb.innerHTML = '';
    if (!rows.length) {
      tb.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#999;">No doctors found</td></tr>`;
      return;
    }

    rows.forEach((r, i) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
            <td>${(state.page - 1) * state.per_page + i + 1}</td>
            <td>${r.doctor_id}</td>
            <td title="${r.full_name || ''}"><b>${r.full_name || ''}</b></td>
            <td>${r.mobile_number || ''}</td>
            <td>${r.designation_name || ''}</td>
            <td>${r.chamber_name || ''}</td>
            <td>
                <a href="#">Edit</a>
                <button class="btn btn-danger" data-action="remove" data-id="${r.assignment_id}">Remove</button>
                ${window.IS_ADMIN ? `<button class="btn btn-outline-danger" data-action="delete" data-id="${r.doctor_id}">Delete</button>` : ''}
            </td>
        `;
      tb.appendChild(tr);
    });
  }

  function renderPager(page, perPage, total){
    const el = qs('#pager');
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    const prev = Math.max(1, page - 1);
    const next = Math.min(totalPages, page + 1);
    el.innerHTML = `
      <button data-page="1">« First</button>
      <button data-page="${prev}">‹ Prev</button>
      <span>Page ${page} of ${totalPages} (${total} total)</span>
      <button data-page="${next}">Next ›</button>
      <button data-page="${totalPages}">Last »</button>
    `;
  }

  document.addEventListener('click', e => {
    const btn = e.target.closest('#pager button');
    if (btn) {
      state.page = parseInt(btn.dataset.page);
      fetchAndRender();
    }

    const remove = e.target.closest('button[data-action="remove"]');
    if (remove) {
      const id = remove.dataset.id;
      Swal.fire({
        title: 'Are you sure?',
        text: "Remove this doctor assignment?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove it!'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('/is_dms/api/doctor_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'remove_assignment', assignment_id: id })
          })
          .then(r => r.json())
          .then(j => {
            if (j.ok) {
              Swal.fire('✅ Removed!', j.msg, 'success');
              fetchAndRender();
            } else {
              Swal.fire('⚠️ Access Denied', j.msg, 'error');
            }
          });
        }
      });
    }
  });

  let t;
  qsa('[data-filter]').forEach(el => {
    el.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => {
        state.page = 1;
        state[el.dataset.filter] = el.value.trim();
        fetchAndRender();
      }, 400);
    });
  });

  fetchAndRender();
})();
