(function(){
  const apiOpt  = window.API_FILTER_OPTIONS;
  const apiData = window.API_FILTER_DATA;

  // DOM refs
  const $bdd  = document.getElementById('f_bdd_zone');
  const $reg  = document.getElementById('f_region');
  const $unit = document.getElementById('f_unit');
  const $zone = document.getElementById('f_zone');
  const $terr = document.getElementById('f_territory');
  const $dtype= document.getElementById('f_doctor_type');
  const $q    = document.getElementById('f_q');
  const $body = document.getElementById('doc-body');
  const $pager= document.getElementById('pager');
  const $reset= document.getElementById('btn_reset');

  let page = 1, per_page = 100;

  // Helpers
  const opt = (v,t)=> `<option value="${v}">${t}</option>`;
  const setDisabled = (el,yn)=> { el.disabled = !!yn; };

  // Loaders
  async function loadBddZones(){
    setDisabled($reg, true); setDisabled($unit, true); setDisabled($zone, true); setDisabled($terr, true);
    $bdd.innerHTML = opt('', 'All Zones');
    const r = await fetch(apiOpt, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'bdd_zones'})});
    const j = await r.json();
    if(j.success){
      j.data.forEach(row => $bdd.insertAdjacentHTML('beforeend', opt(row.bdd_zone_id, row.bdd_zone_name)));
    }
  }
  async function loadRegions(){
    $reg.innerHTML = opt('', 'All Regions');
    setDisabled($reg, true);
    const bdd_zone_id = $bdd.value || '';
    if(!bdd_zone_id){ return; }
    const r = await fetch(apiOpt, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'regions', bdd_zone_id})});
    const j = await r.json();
    if(j.success){
      j.data.forEach(row => $reg.insertAdjacentHTML('beforeend', opt(row.region_id, row.region_name)));
      setDisabled($reg, false);
    }
  }
  async function loadUnits(){
    $unit.innerHTML = opt('', 'All Units');
    setDisabled($unit, true);
    const region_id = $reg.value || '';
    if(!region_id){ return; }
    const r = await fetch(apiOpt, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'units', region_id})});
    const j = await r.json();
    if(j.success){
      j.data.forEach(row => $unit.insertAdjacentHTML('beforeend', opt(row.unit_id, row.unit_name)));
      setDisabled($unit, false);
    }
  }
  async function loadZones(){
    $zone.innerHTML = opt('', 'All Zones');
    setDisabled($zone, true);
    const region_id = $reg.value || '';
    if(!region_id){ return; }
    const r = await fetch(apiOpt, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'zones', region_id})});
    const j = await r.json();
    if(j.success){
      j.data.forEach(row => $zone.insertAdjacentHTML('beforeend', opt(row.zone_id, row.zone_name)));
      setDisabled($zone, false);
    }
  }
  async function loadTerritories(){
    $terr.innerHTML = opt('', 'All Territories');
    setDisabled($terr, true);
    const zone_id = $zone.value || '';
    if(!zone_id){ return; }
    const r = await fetch(apiOpt, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'territories', zone_id})});
    const j = await r.json();
    if(j.success){
      j.data.forEach(row => $terr.insertAdjacentHTML('beforeend', opt(row.territory_id, row.territory_name)));
      setDisabled($terr, false);
    }
  }

  // List loader
  async function loadList(goPage){
    if(goPage) page = goPage;
    const params = new URLSearchParams({
      page, per_page,
      bdd_zone_id: $bdd.value || '',
      region_id   : $reg.value || '',
      unit_id     : $unit.value || '',
      zone_id     : $zone.value || '',
      territory_id: $terr.value || '',
      doctor_type : $dtype.value || '',
      q           : $q.value || ''
    });
    $body.innerHTML = `<tr><td colspan="5">Loading...</td></tr>`;
    const r = await fetch(`${apiData}?${params.toString()}`);
    const j = await r.json().catch(()=>({success:false}));
    if(!j || !j.success){
      $body.innerHTML = `<tr><td colspan="5" style="color:#b91c1c;">Failed to load.</td></tr>`;
      $pager.innerHTML = '';
      return;
    }

    // Render rows
    if(!j.data.length){
      $body.innerHTML = `<tr><td colspan="5">No data found</td></tr>`;
      $pager.innerHTML = '';
      return;
    }
    let html = '';
    const startSL = (j.page-1)*j.per_page;
    j.data.forEach((r, idx) => {
      html += `
        <tr>
          <td style="text-align:center;">${startSL + idx + 1}</td>
          <td style="text-align:center;">${r.doctor_id}</td>
          <td title="${(r.full_name || '').replace(/"/g,'&quot;')}"><b>${r.full_name || ''}</b></td>
          <td>${r.mobile_number || ''}</td>
          <td title="${(r.chamber_name || '').replace(/"/g,'&quot;')}">${r.chamber_name || ''}</td>
        </tr>
      `;
    });
    $body.innerHTML = html;

    // Pager
    renderPager(j.page, j.per_page, j.total);
  }

  function renderPager(page, per, total){
    const pages = Math.max(1, Math.ceil(total / per));
    const prev  = Math.max(1, page-1);
    const next  = Math.min(pages, page+1);

    let html = `
      <button class="pbtn" data-p="1" ${page===1?'disabled':''}>First</button>
      <button class="pbtn" data-p="${prev}" ${page===1?'disabled':''}>Prev</button>
      <span style="font-size:10px;">Page ${page} of ${pages} • ${total} rows</span>
      <button class="pbtn" data-p="${next}" ${page===pages?'disabled':''}>Next</button>
      <button class="pbtn" data-p="${pages}" ${page===pages?'disabled':''}>Last</button>
    `;
    $pager.innerHTML = html;
    $pager.querySelectorAll('button[data-p]').forEach(b=>{
      b.addEventListener('click', ()=> loadList(parseInt(b.getAttribute('data-p'),10)));
    });
  }

  // Events: cascading
  $bdd.addEventListener('change', async ()=>{ 
    // reset subsequent
    $reg.innerHTML = `<option value="">All Regions</option>`;
    $unit.innerHTML = `<option value="">All Units</option>`;
    $zone.innerHTML = `<option value="">All Zones</option>`;
    $terr.innerHTML = `<option value="">All Territories</option>`;
    setDisabled($reg,true); setDisabled($unit,true); setDisabled($zone,true); setDisabled($terr,true);
    await loadRegions();
    await loadList(1);
  });
  $reg.addEventListener('change', async ()=>{
    $unit.innerHTML = `<option value="">All Units</option>`;
    $zone.innerHTML = `<option value="">All Zones</option>`;
    $terr.innerHTML = `<option value="">All Territories</option>`;
    setDisabled($unit,true); setDisabled($zone,true); setDisabled($terr,true);
    await loadUnits();
    await loadZones();
    await loadList(1);
  });
  $unit.addEventListener('change', ()=> loadList(1));
  $zone.addEventListener('change', async ()=>{
    $terr.innerHTML = `<option value="">All Territories</option>`;
    setDisabled($terr,true);
    await loadTerritories();
    await loadList(1);
  });
  $terr.addEventListener('change', ()=> loadList(1));
  $dtype.addEventListener('change', ()=> loadList(1));
  $q.addEventListener('input', ()=>{
    clearTimeout($q._t);
    $q._t = setTimeout(()=> loadList(1), 350);
  });
  $reset.addEventListener('click', ()=>{
    $bdd.value = ''; $reg.value=''; $unit.value=''; $zone.value=''; $terr.value=''; $dtype.value=''; $q.value='';
    setDisabled($reg,true); setDisabled($unit,true); setDisabled($zone,true); setDisabled($terr,true);
    loadBddZones().then(()=> loadList(1));
  });

  // Init
  loadBddZones().then(()=> loadList(1));
})();
