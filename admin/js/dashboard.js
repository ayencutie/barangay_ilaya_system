// admin/js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
  loadDashboard();
  document.getElementById('adminSearch')?.addEventListener('input', filterPending);
});

let pendingData = []; // keep a copy for filtering

async function loadDashboard(){
  try {
    const res = await fetch('../php/admin/fetch_dashboard.php', { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Network response not ok');
    const data = await res.json();

    // Populate stat cards
    document.getElementById('totalPatients').textContent = data.totalPatients ?? '0';
    document.getElementById('todayAppointments').textContent = data.todayAppointments ?? '0';
    document.getElementById('completedAppointments').textContent = data.completedAppointments ?? '0';

    // Pending table
    pendingData = data.pendingAppointments || [];
    renderPendingTable(pendingData);

    // Upcoming list
    renderUpcoming(data.upcomingAppointments || []);
  } catch (err) {
    console.error('Failed to load dashboard:', err);
    const tbody = document.querySelector('#pendingTable tbody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="6">⚠️ Error loading data.</td></tr>`;
  }
}

function renderPendingTable(list){
  const tbody = document.querySelector('#pendingTable tbody');
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6">No pending appointments.</td></tr>';
    return;
  }
  list.forEach(a => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${a.appointment_id}</td>
      <td>${a.patient_name}</td>
      <td>${a.service}</td>
      <td>${a.time_slot}</td>
      <td>${a.date}</td>
      <td>
        <button class="btn-approve" data-id="${a.appointment_id}">Approve</button>
        <button class="btn-reject" data-id="${a.appointment_id}">Reject</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  // attach action handlers
  tbody.querySelectorAll('.btn-approve').forEach(btn => {
    btn.addEventListener('click', () => updateAppointmentStatus(btn.dataset.id, 'Approved'));
  });
  tbody.querySelectorAll('.btn-reject').forEach(btn => {
    btn.addEventListener('click', () => updateAppointmentStatus(btn.dataset.id, 'Rejected'));
  });
}

function renderUpcoming(list){
  const cont = document.getElementById('upcomingList');
  cont.innerHTML = '';
  if (!list.length){
    cont.innerHTML = '<div class="upcoming-item">No upcoming appointments.</div>';
    return;
  }
  list.forEach(a => {
    const div = document.createElement('div');
    div.className = 'upcoming-item';
    div.innerHTML = `<p><strong>${a.patient_name}</strong> — ${a.service} — ${a.date} ${a.time_slot}</p>
                     <button class="btn-approve" data-id="${a.appointment_id}">Send Reminder</button>`;
    cont.appendChild(div);
  });

  // send reminder action (dummy)
  cont.querySelectorAll('.btn-approve').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.textContent = 'Reminder Sent';
      btn.disabled = true;
    });
  });
}

function filterPending(e){
  const q = e.target.value.trim().toLowerCase();
  const filtered = pendingData.filter(a => 
    a.patient_name.toLowerCase().includes(q) ||
    a.service.toLowerCase().includes(q) ||
    a.date.toLowerCase().includes(q) ||
    a.time_slot.toLowerCase().includes(q)
  );
  renderPendingTable(filtered);
}

async function updateAppointmentStatus(id, status){
  if (!confirm(`Set appointment #${id} to ${status}?`)) return;
  try {
    const res = await fetch('../php/admin/update_appointment.php', {
      method: 'POST',
      headers:{ 'Content-Type':'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ id, status })
    });
    const data = await res.json();
    if (data.status === 'success') {
      alert('Updated successfully.');
      loadDashboard();
    } else {
      alert('Update failed: ' + (data.message || 'unknown'));
    }
  } catch (err) {
    console.error(err);
    alert('Server error.');
  }
}
