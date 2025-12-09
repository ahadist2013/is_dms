document.addEventListener("DOMContentLoaded", function() {
    // ---------- Minimal toast (optional) ----------
    function toast(msg, type){
      const el = document.getElementById('tiny_toast');
      if(!el) return;
      el.textContent = msg;
      el.style.background = (type==='error') ? '#c62828' : '#2e7d32';
      el.style.display = 'block';
      setTimeout(()=>{ el.style.display='none'; }, 2000);
    }
    
    // ---------- Safe doctorId ----------
    (function(){
      const hid = document.getElementById('doctor_id');
      const fromHidden = hid ? hid.value : '';
      const fromUrl = new URLSearchParams(location.search).get('doctor_id') || '';
      window.DOCTOR_ID = window.DOCTOR_ID || fromHidden || fromUrl;
    })();
    
    $(document).ready(function(){
    
      // Select2: institute & chamber
      $('#institute_id').select2({
        placeholder:'Search Institute...',
        ajax:{
          url:'../../modules/master_data/ajax_handlers.php',
          dataType:'json', delay:300,
          data:params=>({ action:'search_institutes', query: params.term||'' }),
          processResults:data=>({ results:(data.data||[]).map(x=>({id:x.id,text:x.name})) })
        }, minimumInputLength:1, width:'100%'
      });
    
      $('#chamber_id').select2({
        placeholder:'Search Chamber/Clinic...',
        ajax:{
          url:'../../modules/master_data/ajax_handlers.php',
          dataType:'json', delay:300,
          data:params=>({ action:'search_chambers', query: params.term||'' }),
          processResults:data=>({ results:(data.data||[]).map(x=>({id:x.id,text:x.name})) })
        }, minimumInputLength:1, width:'100%'
      });
    
      // ----------- Zone → Territory (edit page, race-condition free) -----------
      window.__assignData = {};  // store assignment info globally
    
      $('#zone_id').on('change', function(){
        const zid = $(this).val();
        const $sel = $('#territory_id')
            .prop('disabled', true)
            .empty()
            .append('<option value="">Loading...</option>');
    
        if(!zid){
            $sel.html('<option value="">Select Zone First</option>');
            return;
        }
    
        $.getJSON('../../modules/master_data/ajax_handlers.php',
            { action:'get_territories_by_zone', zone_id: zid, edit:1 },
            function(r){
    
                $sel.empty().append('<option value="">Select Territory</option>');
    
                if(r && r.success && r.data){
    
                    r.data.forEach(item=>{
                      $sel.append(
                        '<option value="'+item.territory_id+'" data-user="'+(item.user_name||'')+'" data-login="'+(item.login_id||'')+'">'+
                        item.territory_name+
                        '</option>'
                      );
                    });
    
                    // ⭐ Prefill AFTER AJAX loads
                    if (window.__assignData.territory_id){
                        $sel.val(String(window.__assignData.territory_id)).trigger('change');
                    }
    
                    $sel.prop('disabled', false);
    
                } else {
                    $sel.append('<option value="">No Territories</option>');
                }
            }
        );
      });
    
      $('#territory_id').on('change', function(){
        const o = $(this).find('option:selected');
        $('#officer_info').text('Officer: ' + (o.data('user')||'N/A') + ' | ID: ' + (o.data('login')||'N/A'));
      });
    
      // ----------- Doctor type toggle (same as create.php) -----------
      function toggleDoctorType(){
        const v = $('input[name="doctor_type"]:checked').val();
        const unitBox = $('#unit_container'), visitBox = $('#visit_type_container');
        const unit = $('#unit_id'), visit = $('#visit_type');
        unitBox.hide(); unit.prop('required', false);
        visitBox.hide(); visit.prop('required', false);
        if(v==='in_house'){ unitBox.show(); unit.prop('required', true); }
        if(v==='outside'){ visitBox.show(); visit.prop('required', true); }
      }
      $('input[name="doctor_type"]').on('change', toggleDoctorType);

            // ============================================================
      // ✅ Qualifications (Degrees) — dynamic add/remove (edit page)
      // ============================================================

      // পরের নতুন row এর index সবসময় DOM থেকে বের করব
      function getNextQualIndex(){
        // সব qualification-item গণনা করি (main + অতিরিক্ত)
        return $('.qualification-item').length; // শুরুতে শুধু qual_0 থাকে → length = 1 → next idx = 1
      }

      // Add More Degree (➕ button)
      $('#add_qualification_btn')
        .off('click.dedit') // একই handler পুনরায় bind হওয়ার ঝামেলা বন্ধ
        .on('click.dedit', function(){
          const degreeOptions = $('#degree_id_0').html();
          if(!degreeOptions){
            console.warn('No degree options found in #degree_id_0');
            return;
          }

          const idx = getNextQualIndex();   // নতুন row এর index

          const html = `
            <div class="qualification-item" id="qual_${idx}">
              <button type="button" class="remove-qual" data-index="${idx}">X</button>

              <div class="form-group">
                <label for="degree_id_${idx}">Degree</label>
                <select id="degree_id_${idx}" name="degree_ids[]" data-index="${idx}">
                  ${degreeOptions}
                </select>
              </div>

              <div class="form-group">
                <label for="q_specialization_${idx}">Specialization/Discipline</label>
                <select id="q_specialization_${idx}" name="q_specializations[]">
                  <option value="">Select Degree First</option>
                </select>
              </div>
            </div>
          `;

          $('#additional_qualifications').append(html);
        });

      // Remove a qualification row
      $(document)
        .off('click.dedit', '.remove-qual')
        .on('click.dedit', '.remove-qual', function(){
          const idx = $(this).data('index');
          $('#qual_'+idx).remove();
        });

      // Degree change → load specialization (only for edit page)
      $(document)
        .off('change.dedit', 'select[id^="degree_id_"]')
        .on('change.dedit', 'select[id^="degree_id_"]', function(){
          const degreeId = $(this).val();
          const idx = $(this).data('index');
          const $spec = $('#q_specialization_'+idx);

          $spec.html('<option value="">Loading Disciplines...</option>');

          if(!degreeId){
            $spec.html('<option value="">Select Degree First</option>');
            return;
          }

          $.ajax({
            url: '../../modules/master_data/ajax_handlers.php',
            type: 'GET',
            data: { action:'get_specializations_by_degree', degree_id: degreeId },
            dataType: 'json',
            success: function(response){
              $spec.empty().append('<option value="">Select Specialization (Optional)</option>');
              if(response && response.success && response.data && response.data.length){
                response.data.forEach(function(item){
                  $spec.append('<option value="'+item.value+'">'+item.text+'</option>');
                });
              }else{
                $spec.append('<option value="">No Specializations found</option>');
              }
            },
            error: function(xhr,status,error){
              console.error('Spec load error', status, error);
              $spec.empty().append('<option value="">Error Loading Data</option>');
            }
          });
        });


      // ------------------ Load From API (PREFILL SECTION) ---------------------
      const did = window.DOCTOR_ID;
      if(!did){ console.error('doctor_id missing'); return; }
    
      $.getJSON('../../api/doctor_edit_fetch.php', { doctor_id: did }, function(res){
        if(!res || !res.success){
            Swal.fire('Error', (res&&res.msg)||'Load failed','error');
            return;
        }
    
        const d = res.doctor || {};
        const a = res.assignment || {};
    
        // Save assignment globally for AJAX prefill
        window.__assignData = a;
    
        // ----- Basic doctor info -----
        $('#mobile_number').val(d.mobile_number||'');
        $('#bmdc_number').val((d.bmdc_number||'').replace(/^A-/, ''));
        $('#title_id').val(d.title_id||'');
        $('#name').val(d.name||'');
        $('#email').val(d.email||'');
        $('#date_of_birth').val(d.date_of_birth||'');
        $('#designation').val(d.designation_id||'');
        $('input[name="designation_status"][value="'+(d.designation_status||'Current')+'"]').prop('checked',true);
        $('#specialization_id').val(d.specialization_id||'');
        $('input[name="is_ex_institute"][value="'+(d.is_ex_institute||0)+'"]').prop('checked',true);
    
        // institute/chamber select2
        if(d.institute_id && d.institute_name){
          $('#institute_id').append(new Option(d.institute_name, d.institute_id, true, true)).trigger('change');
        }
        if(d.chamber_id && d.chamber_name){
          $('#chamber_id').append(new Option(d.chamber_name, d.chamber_id, true, true)).trigger('change');
        }
    
        // ----- Degrees Prefill -----
        const list = res.degrees || [];
        if(list.length){
          // first degree → existing row
          $('#degree_id_0').val(list[0].degree_id||'');
          if(list[0].detail_id){
            $.getJSON('../../modules/master_data/ajax_handlers.php',
              { action:'get_specializations_by_degree', degree_id: list[0].degree_id },
              function(r){
                const $s = $('#q_specialization_0').empty().append('<option value="">Select Specialization (Optional)</option>');
                (r.data||[]).forEach(it => $s.append('<option value="'+it.value+'">'+it.text+'</option>'));
                if(list[0].detail_id) $s.val(list[0].detail_id);
              });
          }
            // remaining degrees
              for (let i = 1; i < list.length; i++) {
    
                // নতুন row তৈরি করো (আমাদের handler একটাই, namespaced)
                $('#add_qualification_btn').trigger('click.dedit');
    
                // এখন মোট যতগুলো qualification-item আছে, তার শেষটাই নতুন row
                const idx = $('.qualification-item').length - 1;
    
                // degree সেট করো
                $('#degree_id_' + idx).val(list[i].degree_id || '');
    
                // specialization সেট করো (থাকলে)
                if (list[i].detail_id) {
                  $.getJSON('../../modules/master_data/ajax_handlers.php',
                    { action: 'get_specializations_by_degree', degree_id: list[i].degree_id },
                    function (r) {
                      const $s = $('#q_specialization_' + idx)
                        .empty()
                        .append('<option value="">Select Specialization (Optional)</option>');
    
                      (r.data || []).forEach(it =>
                        $s.append('<option value="' + it.value + '">' + it.text + '</option>')
                      );
    
                      if (list[i].detail_id)
                        $s.val(list[i].detail_id);
                    }
                  );
                }
              }

          // editQualCount now auto–updated via click handler
        }
    
        // ---- Assignment Prefill ----
        $('input[name="doctor_type"][value="'+(a.doctor_type||'outside')+'"]').prop('checked',true);
        toggleDoctorType();
    
        $('#doctor_grade').val(a.grade_id||'');
    
        // Zone prefill triggers territory AJAX
        $('#zone_id').val(a.zone_id||'').trigger('change');
    
        $('#unit_id').val(a.unit_id||'');
        $('#room_number').val(a.room_number||'');
        $('#visit_type').val(a.visit_type||'');
    
      });
    });
});
