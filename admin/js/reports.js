// /admin/js/reports.js
document.addEventListener('DOMContentLoaded', () => {
  const endpoint = '../php/fetch_reports.php';
  const summaryEl = document.getElementById('summaryCards');
  const apptsTableBody = document.querySelector('#apptsTable tbody');
  const servicesTableBody = document.querySelector('#servicesTable tbody');
  const ageGroupsEl = document.getElementById('ageGroups');
  const genderCountsEl = document.getElementById('genderCounts');
  const zoneCountsEl = document.getElementById('zoneCounts');
  const apptsChartCtx = document.getElementById('apptsChart').getContext('2d');

  let chartInstance = null;
  let lastData = null;

  async function loadReports() {
    try {
      const res = await fetch(endpoint);
      const j = await res.json();
      if (j.error) { console.error('fetch_reports error', j); return; }
      lastData = j;
      renderSummary(j.summary);
      renderAppts(j.appointments_by_date);
      renderDemographics(j.age_buckets, j.gender_counts, j.zone_counts);
      renderServices(j.services);
    } catch (e) {
      console.error(e);
    }
  }

  function renderSummary(s) {
    summaryEl.innerHTML = '';
    const cards = [
      { title: 'Total Appointments', value: s.total },
      { title: 'Completed', value: s.completed },
      { title: 'Pending', value: s.pending },
      { title: 'Today', value: s.today },
      { title: 'This Month', value: s.this_month },
      { title: 'No-shows / Missed', value: s.missed },
    ];
    cards.forEach(c => {
      const el = document.createElement('div');
      el.className = 'card';
      el.innerHTML = `<h3>${c.title}</h3><p>${c.value}</p>`;
      summaryEl.appendChild(el);
    });
  }

  function renderAppts(rows) {
    apptsTableBody.innerHTML = '';
    const labels = [];
    const totals = [];
    const completed = [];
    const missed = [];
    const cancelled = [];

    rows.forEach(r => {
      labels.push(r.date);
      totals.push(r.total || 0);
      completed.push(r.completed || 0);
      missed.push(r.missed || 0);
      cancelled.push(r.cancelled || 0);

      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.date}</td><td>${r.total}</td><td>${r.completed}</td><td>${r.missed}</td><td>${r.cancelled}</td>`;
      apptsTableBody.appendChild(tr);
    });

    // draw chart (line)
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(apptsChartCtx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          { label: 'Total', data: totals, borderWidth: 2, fill: false, tension: 0.2 },
          { label: 'Completed', data: completed, borderWidth: 2, fill: false, tension: 0.2 },
          { label: 'Missed', data: missed, borderWidth: 2, fill: false, tension: 0.2 },
          { label: 'Cancelled', data: cancelled, borderWidth: 2, fill: false, tension: 0.2 },
        ]
      },
      options: {
        plugins: { legend: { position: 'top' } },
        scales: { x: { display: true }, y: { beginAtZero: true } }
      }
    });
  }

  function renderDemographics(ageBuckets, genderCounts, zoneCounts) {
    ageGroupsEl.innerHTML = '';
    for (const k in ageBuckets) {
      const li = document.createElement('li');
      li.textContent = `${k}: ${ageBuckets[k]}`;
      ageGroupsEl.appendChild(li);
    }

    genderCountsEl.innerHTML = '';
    (genderCounts || []).forEach(g => {
      const li = document.createElement('li');
      li.textContent = `${g.gender}: ${g.cnt}`;
      genderCountsEl.appendChild(li);
    });

    zoneCountsEl.innerHTML = '';
    for (const [zone, cnt] of Object.entries(zoneCounts || {})) {
      const li = document.createElement('li');
      li.textContent = `${zone}: ${cnt}`;
      zoneCountsEl.appendChild(li);
    }
  }

  function renderServices(list) {
    servicesTableBody.innerHTML = '';
    (list || []).forEach(s => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${escapeHtml(s.service)}</td><td>${s.cnt}</td>`;
      servicesTableBody.appendChild(tr);
    });
  }

  // CSV export helper (array of objects -> CSV)
  function toCSV(rows, keys) {
    const lines = [];
    lines.push(keys.join(','));
    rows.forEach(r => {
      const row = keys.map(k => {
        let v = r[k] ?? '';
        v = String(v).replace(/"/g, '""');
        return `"${v}"`;
      });
      lines.push(row.join(','));
    });
    return lines.join('\r\n');
  }

  // export handlers
  document.getElementById('exportApptCsv').addEventListener('click', () => {
    if (!lastData) return;
    const csv = toCSV(lastData.appointments_by_date.map(r => ({
      date: r.date, total: r.total, completed: r.completed, missed: r.missed, cancelled: r.cancelled
    })), ['date','total','completed','missed','cancelled']);
    downloadCSV(csv, 'appointments_summary.csv');
  });

  document.getElementById('exportDemCsv').addEventListener('click', () => {
    if (!lastData) return;
    const ageRows = Object.entries(lastData.age_buckets).map(([k,v])=>({group:k,count:v}));
    const genderRows = (lastData.gender_counts || []).map(g=>({gender:g.gender,cnt:g.cnt}));
    const zoneRows = Object.entries(lastData.zone_counts).map(([k,v])=>({zone:k,count:v}));
    let csv = toCSV(ageRows, ['group','count']) + '\r\n\r\n' + toCSV(genderRows, ['gender','cnt']) + '\r\n\r\n' + toCSV(zoneRows, ['zone','count']);
    downloadCSV(csv, 'demographics.csv');
  });

  document.getElementById('exportSvcCsv').addEventListener('click', () => {
    if (!lastData) return;
    const csv = toCSV(lastData.services.map(s=>({service:s.service,cnt:s.cnt})), ['service','cnt']);
    downloadCSV(csv, 'services.csv');
  });

  document.getElementById('exportAllCsv').addEventListener('click', () => {
    if (!lastData) return;
    // create a multi-section CSV
    const s1 = toCSV([{k:'total',v:lastData.summary.total},{k:'completed',v:lastData.summary.completed}], ['k','v']);
    const s2 = toCSV(lastData.appointments_by_date.map(r=>({date:r.date,total:r.total,completed:r.completed,missed:r.missed,cancelled:r.cancelled})), ['date','total','completed','missed','cancelled']);
    downloadCSV(s1 + '\r\n\r\n' + s2, 'reports_export.csv');
  });

  document.getElementById('printBtn').addEventListener('click', () => window.print());

  function downloadCSV(content, filename){
    const blob = new Blob([content], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename; document.body.appendChild(a);
    a.click(); a.remove();
    URL.revokeObjectURL(url);
  }

  // small helper to sanitize
  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  // initial load
  loadReports();
});
