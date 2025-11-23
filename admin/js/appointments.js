// /admin/js/appointments.js
document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#appointmentsTable tbody');
  const tabs = document.querySelectorAll('.tab-btn');
  const searchInput = document.getElementById('apptSearch');
  const exportBtn = document.getElementById('exportCsv');
  const errorBanner = document.getElementById('errorBanner');

  let currentStatus = 'All';
  let appointments = [];
  let openDropdown = null;

  function escapeHtml(s){
    return String(s || '').replace(/[&<>"']/g,(m)=>({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m]));
  }

  function showError(msg, clickable=false, onClick=null){
    if(!msg){
      errorBanner.style.display='none';
      errorBanner.textContent='';
      errorBanner.onclick=null;
      return;
    }
    errorBanner.style.display='block';
    errorBanner.textContent = msg;
    if(clickable && typeof onClick==='function'){
      errorBanner.style.cursor='pointer';
      errorBanner.onclick = onClick;
    } else {
      errorBanner.style.cursor='default';
      errorBanner.onclick=null;
    }
  }

  async function loadAppointments(){
    showError(null);
    try{
      const res = await fetch('../php/admin/fetch_appointments.php',{ cache:'no-store' });
      if(!res.ok){
        const t = await res.text();
        throw new Error(`Server ${res.status}: ${t}`);
      }
      const data = await res.json();
      if(data.status!=='success' || !Array.isArray(data.appointments)){
        throw new Error(data.message || 'Invalid response');
      }
      appointments = data.appointments;
      renderTable();
    } catch(err){
      console.error(err);
      showError('Error loading appointments. Click to retry.', true, ()=>loadAppointments());
      tableBody.innerHTML = `<tr><td colspan="7">Failed to load.</td></tr>`;
    }
  }

  function renderTable(){
    const q = (searchInput.value || '').trim().toLowerCase();
    tableBody.innerHTML = '';

    const filtered = appointments.filter(a => {
      if(currentStatus !== 'All'){
        if(currentStatus === 'Upcoming'){
          const today = new Date().toISOString().split('T')[0];
          if(!a.date || a.date < today) return false;
          if(['Completed','Missed','Cancelled'].includes(a.status)) return false;
        } else {
          if(a.status !== currentStatus) return false;
        }
      }
      if(!q) return true;
      return (
        a.appointment_id.toString().includes(q) ||
        (a.patient_name || '').toLowerCase().includes(q) ||
        (a.service || '').toLowerCase().includes(q) ||
        (a.date || '').toLowerCase().includes(q)
      );
    });

    if(filtered.length === 0){
      tableBody.innerHTML = `<tr><td colspan="7">No appointments found.</td></tr>`;
      return;
    }

    filtered.forEach(a => {
      const tr = document.createElement('tr');

      const noActions = ['Completed', 'Missed', 'Cancelled'].includes(a.status);

      tr.innerHTML = `
        <td>${escapeHtml(a.appointment_id)}</td>
        <td>${escapeHtml(a.patient_name)}<br><small>${escapeHtml(a.patient_id)}</small></td>
        <td>${escapeHtml(a.service)}</td>
        <td>${escapeHtml(a.date)}</td>
        <td>${escapeHtml(a.time_slot)}</td>
        <td><span class="status-chip status-${escapeHtml(a.status)}">${escapeHtml(a.status)}</span></td>

        <td class="action-cell">
          ${
            noActions
              ? ""
              : `<button class="dots-btn" data-id="${a.appointment_id}">⋯</button>`
          }
        </td>
      `;

      tableBody.appendChild(tr);

      if (!noActions) {
        tr.querySelector('.dots-btn')
          .addEventListener('click', e => openMenu(e, a));
      }
    });
  }

  const dropdown = document.createElement('div');
  dropdown.className = 'dropdown fixed-dropdown';
  dropdown.style.position = 'fixed';
  dropdown.style.display = 'none';
  dropdown.style.zIndex = '9999';
  dropdown.style.background = '#fff';
  dropdown.style.borderRadius = '10px';
  dropdown.style.boxShadow = '0 6px 18px rgba(0,0,0,0.15)';
  dropdown.style.padding = '6px 0';
  dropdown.style.width = '190px';
  document.body.appendChild(dropdown);

  function openMenu(e, appt){
    e.stopPropagation();

    if(openDropdown === appt.appointment_id){
      dropdown.style.display = 'none';
      openDropdown = null;
      return;
    }

    openDropdown = appt.appointment_id;

    dropdown.innerHTML = `
      ${appt.status === 'Pending' ? `<button class="dropdown-item approve">Approve</button>` : ''}
      ${appt.status === 'Pending' ? `<button class="dropdown-item reject">Reject</button>` : ''}
      <button class="dropdown-item completed">Mark Completed</button>
      <button class="dropdown-item missed">Mark Missed</button>
      <button class="dropdown-item edit">Edit</button>
    `;

    const rect = e.target.getBoundingClientRect();
    dropdown.style.top = rect.bottom + 6 + 'px';
    dropdown.style.left = (rect.left - 140) + 'px';
    dropdown.style.display = 'block';

    if(dropdown.querySelector('.approve'))
      dropdown.querySelector('.approve').onclick = () =>
        changeStatus(appt.appointment_id, 'Approved');

    if(dropdown.querySelector('.reject'))
      dropdown.querySelector('.reject').onclick = () => {
        const note = prompt('Optional reject note:');
        changeStatus(appt.appointment_id, 'Rejected', note || null);
      };

    dropdown.querySelector('.completed').onclick =
      () => changeStatus(appt.appointment_id, 'Completed');

    dropdown.querySelector('.missed').onclick =
      () => changeStatus(appt.appointment_id, 'Missed');

    dropdown.querySelector('.edit').onclick =
      () => openEditModal(appt);
  }

  document.addEventListener('click', () => {
    dropdown.style.display='none';
    openDropdown=null;
  });

  async function changeStatus(id, status, note=null){
    showError(null);
    try{
      const res = await fetch('../php/admin/update_appointment.php',{
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({ id, status, note })
      });
      const data = await res.json();
      if(data.status !== 'success') throw new Error(data.message);
      loadAppointments();
    } catch(err){
      console.error(err);
      showError('Update failed. Click to retry.', true,
        ()=> changeStatus(id,status,note)
      );
    }
  }

  const modal = document.getElementById('editModal');
  const editForm = document.getElementById('editForm');
  const closeModalBtn = document.getElementById('closeModal');

  function openEditModal(a){
    document.getElementById('editAppointmentId').value = a.appointment_id;
    document.getElementById('editService').value = a.service;
    document.getElementById('editDate').value = a.date;
    document.getElementById('editTime').value = a.time_slot;
    modal.style.display = 'flex';
  }

  closeModalBtn.onclick = () => modal.style.display = 'none';

  editForm.onsubmit = async e => {
    e.preventDefault();
    const payload = {
      id: document.getElementById('editAppointmentId').value,
      service: document.getElementById('editService').value,
      date: document.getElementById('editDate').value,
      time_slot: document.getElementById('editTime').value
    };
    try{
      const res = await fetch('../php/admin/edit_appointment.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if(data.status === 'success'){
        modal.style.display='none';
        loadAppointments();
      }
    } catch(err){
      console.error(err);
      showError('Saving failed.');
    }
  };

  searchInput.addEventListener('input', renderTable);

  tabs.forEach(t => t.addEventListener('click', () => {
    tabs.forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    currentStatus = t.dataset.status;
    renderTable();
  }));

  exportBtn.addEventListener('click', () => {
    const params = new URLSearchParams();
    if(currentStatus !== 'All') params.set('status', currentStatus);
    if(searchInput.value) params.set('q', searchInput.value.trim());
    window.location = '../admin/export_appointments.php?' + params.toString();
  });

  loadAppointments();
  setInterval(loadAppointments, 60000);
});
