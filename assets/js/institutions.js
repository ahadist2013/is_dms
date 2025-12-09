(function(){
  const qs = s=>document.querySelector(s);
  const qsa = s=>Array.from(document.querySelectorAll(s));

  let state = {
    page:1,
    per_page:100,
    q:'',
    division_id:'',
    district_id:'',
    type:''
  };

  const apiUrl = window.API_URL;
  const geoUrl = window.API_GEO;
  const typeUrl = window.API_TYPES;

  // 🔹 Load Division & Districts
  function loadDivisions(){
    fetch(geoUrl+'?action=divisions')
      .then(r=>r.json())
      .then(j=>{
        const el=qs('#f_division');
        el.innerHTML='<option value="">Division</option>';
        (j.data||[]).forEach(x=>{
          const o=document.createElement('option');
          o.value=x.id;o.textContent=x.name;el.appendChild(o);
        });
      });
  }

  function loadDistricts(divId){
    const dSel=qs('#f_district');
    dSel.innerHTML='<option>Loading...</option>';
    if(!divId){dSel.innerHTML='<option value="">District</option>';return;}
    fetch(geoUrl+'?action=districts&division_id='+divId)
      .then(r=>r.json())
      .then(j=>{
        dSel.innerHTML='<option value="">District</option>';
        (j.data||[]).forEach(x=>{
          const o=document.createElement('option');
          o.value=x.id;o.textContent=x.name;dSel.appendChild(o);
        });
      });
  }

  // 🔹 Load Types dynamically
  function loadTypes(){
    fetch(typeUrl)
      .then(r=>r.json())
      .then(j=>{
        const el=qs('#f_type');
        el.innerHTML='<option value="">Type</option>';
        (j.data||[]).forEach(x=>{
          const o=document.createElement('option');
          o.value=x.id;
          o.textContent=x.name;
          el.appendChild(o);
        });
      });
  }

  // 🔹 Events
  qs('#f_division').addEventListener('change',e=>{
    state.division_id=e.target.value;
    state.district_id='';
    loadDistricts(state.division_id);
    fetchAndRender(true);
  });

  qs('#f_district').addEventListener('change',e=>{
    state.district_id=e.target.value;
    fetchAndRender(true);
  });

  qs('#f_type').addEventListener('change',e=>{
    state.type=e.target.value;
    fetchAndRender(true);
  });

  let t;
  qsa('[data-filter]').forEach(el=>{
    el.addEventListener('input',()=>{
      clearTimeout(t);
      t=setTimeout(()=>{
        state.page=1;
        state[el.dataset.filter]=el.value.trim();
        fetchAndRender(true);
      },300);
    });
  });

  // 🔹 Fetch Data
  function fetchAndRender(){
    const params=new URLSearchParams(state);
    fetch(apiUrl+'?'+params.toString())
      .then(r=>r.json())
      .then(j=>{
        renderTable(j.data||[]);
        renderPager(j.page,j.per_page,j.total);
      });
  }

  function renderTable(rows){
    const tb=qs('#list-body');
    tb.innerHTML='';
    if(!rows.length){tb.innerHTML='<tr><td colspan="6" style="text-align:center;color:#999;">No institutes found</td></tr>';return;}
    rows.forEach((r,i)=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`
        <td style="text-align:center;">${(state.page-1)*state.per_page+i+1}</td>
        <td style="text-align:center;">${r.institute_id}</td>
        <td title="${r.institute_name||''}"><b>${r.institute_name||''}</b></td>
        <td>${r.institute_type||''}</td>
        <td>${r.division_name||''}</td>
        <td>${r.district_name||''}</td>
      `;
      tb.appendChild(tr);
    });
  }

  function renderPager(page,perPage,total){
    const el=qs('#pager');
    const totalPages=Math.max(1,Math.ceil(total/perPage));
    const prev=Math.max(1,page-1),next=Math.min(totalPages,page+1);
    el.innerHTML=`
      <button class="btn" data-page="1">« First</button>
      <button class="btn" data-page="${prev}">‹ Prev</button>
      <span>Page ${page} of ${totalPages} (${total} total)</span>
      <button class="btn" data-page="${next}">Next ›</button>
      <button class="btn" data-page="${totalPages}">Last »</button>`;
    el.onclick=e=>{
      const b=e.target.closest('button[data-page]');
      if(!b)return;state.page=parseInt(b.dataset.page);fetchAndRender();
    };
  }

  // Initialize
  loadDivisions();
  loadTypes();
  fetchAndRender();
})();
