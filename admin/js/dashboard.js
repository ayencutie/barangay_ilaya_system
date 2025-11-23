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

  // attach action handlers for Approve/Reject
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
    // UPDATED: Gumamit ng class na 'btn-reminder' para hindi maghalo sa approve button
    div.innerHTML = `<p><strong>${a.patient_name}</strong> — ${a.service} — ${a.date} ${a.time_slot}</p>
                     <button class="btn-reminder" style="margin-left:auto; cursor:pointer;" data-id="${a.appointment_id}">Send Reminder</button>`;
    cont.appendChild(div);
  });

  // UPDATED: Logic para sa Send Reminder
  cont.querySelectorAll('.btn-reminder').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      
      // UI Feedback: Disable button agad
      const originalText = btn.textContent;
      btn.textContent = 'Sending...';
      btn.disabled = true;

      try {
        const res = await fetch('../php/admin/send_reminder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ appointment_id: id })
        });

        const result = await res.json();

        if (result.status === 'success') {
            btn.textContent = 'Sent ✔';
            alert('Reminder sent successfully to patient inbox!');
        } else {
            // Kung fail, ibalik para pwede itry ulit
            btn.textContent = originalText;
            btn.disabled = false;
            alert('Failed: ' + result.message);
        }

      } catch (err) {
          console.error(err);
          btn.textContent = 'Error';
          alert('Network error occurred. Check console.');
          btn.disabled = false;
      }
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