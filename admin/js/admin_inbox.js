// admin/js/admin_inbox.js
// Admin-side inbox messenger (calls ../php/* endpoints)

// Get admin id from a hidden input on the admin inbox page
const ADMIN_ID = document.getElementById('adminId') ? document.getElementById('adminId').value : null;

let currentPatientId = null;
let currentConversationId = null;
let pollHandle = null;

// DOM refs
const userList = document.getElementById('userList');            // left list of patients
const chatMessages = document.getElementById('chatMessages');    // chat message container
const chatHeader = document.getElementById('chatHeader');        // header h3 for patient name
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const typingIndicator = document.getElementById('typingIndicator');
const userSearch = document.getElementById('userSearch');

// small html-escape helper
function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// Load patient list (server must provide /php/fetch_patients.php)
function loadPatients(q = '') {
  fetch('../php/fetch_patients.php') // create this endpoint if missing: returns [{patient_id, first_name, last_name, unread_count?}, ...]
    .then(r => r.json())
    .then(list => {
      userList.innerHTML = '';
      list.filter(u => ((u.first_name + ' ' + u.last_name).toLowerCase().includes(q.toLowerCase()) || (u.patient_id||'').toLowerCase().includes(q.toLowerCase())))
        .forEach(u => {
          const div = document.createElement('div');
          div.className = 'user';
          div.dataset.pid = u.patient_id;
          div.innerHTML = `<div class="name">${escapeHtml(u.first_name)} ${escapeHtml(u.last_name)}</div>
                           <div class="meta">${escapeHtml(u.patient_id)}${u.unread_count ? ` • ${u.unread_count}` : ''}</div>`;
          div.addEventListener('click', () => openConversation(u.patient_id, `${u.first_name} ${u.last_name}`));
          userList.appendChild(div);
        });
      if(list.length === 0) userList.innerHTML = '<div class="empty">No patients found</div>';
    }).catch(err => {
      console.error('loadPatients error', err);
      userList.innerHTML = '<div class="error">Failed to load patients</div>';
    });
}

// Open or create conversation with a patient
function openConversation(patientId, patientName){
  currentPatientId = patientId;
  chatHeader.innerHTML = `<h3>${escapeHtml(patientName)}</h3>`;
  // request conversation creation / lookup
  fetch(`../php/get_or_create_conversation.php?patient_id=${encodeURIComponent(patientId)}&admin_id=${encodeURIComponent(ADMIN_ID)}`)
    .then(r => r.json())
    .then(data => {
      if(!data || !data.conversation){ alert('Unable to open conversation'); return; }
      currentConversationId = data.conversation.id;
      startPolling();
      loadMessages(); // immediate load
    }).catch(err => {
      console.error('openConversation error', err);
      alert('Failed to open conversation.');
    });
}

// Polling for messages & typing
function startPolling(){
  if(pollHandle) clearInterval(pollHandle);
  pollHandle = setInterval(() => {
    loadMessages();
    checkTyping();
  }, 1200);
}

// Stop polling (when leaving conversation)
function stopPolling(){
  if(pollHandle) clearInterval(pollHandle);
  pollHandle = null;
  currentConversationId = null;
}

// Load messages for currentConversationId
function loadMessages(){
  if(!currentConversationId) return;
  fetch(`../php/fetch_messages.php?conversation_id=${encodeURIComponent(currentConversationId)}`)
    .then(r => r.json())
    .then(rows => {
      chatMessages.innerHTML = '';
      if(!Array.isArray(rows) || rows.length === 0){
        chatMessages.innerHTML = '<div class="empty">No messages yet</div>';
        return;
      }
      rows.forEach(m => {
        const cls = (m.sender_role === 'patient') ? 'message user' : 'message admin';
        const el = document.createElement('div');
        el.className = cls;
        el.innerHTML = `<div class="msg">${escapeHtml(m.message || m.content || '')}</div>
                        <time>${escapeHtml(m.created_at)}</time>`;
        chatMessages.appendChild(el);
      });
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }).catch(err => {
      console.error('loadMessages', err);
    });
}

// Send message (admin -> patient)
function sendMessage(){
  if(!currentConversationId) return alert('Open a conversation first');
  const text = msgInput.value.trim();
  if(!text) return;
  const body = new URLSearchParams();
  body.append('conversation_id', currentConversationId);
  body.append('message', text);
  body.append('sender', 'admin');

  fetch('../php/send_message.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString()
  }).then(r => r.json())
    .then(j => {
      if(j && j.success){
        msgInput.value = '';
        loadMessages();
        sendTyping(false);
      } else {
        console.error('send failed', j);
        alert('Send failed');
      }
    }).catch(err => {
      console.error('sendMessage error', err);
      alert('Send error');
    });
}

// Typing indicator handling (admin)
let adminTypingState = false;
let adminTypingTimeout = null;
function sendTyping(state){
  if(!currentConversationId) return;
  if(state){
    if(!adminTypingState){
      adminTypingState = true;
      fetch('../php/set_typing.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `conversation_id=${encodeURIComponent(currentConversationId)}&typing=1&sender=admin`
      }).catch(()=>{});
    }
    clearTimeout(adminTypingTimeout);
    adminTypingTimeout = setTimeout(()=> sendTyping(false), 2000);
  } else {
    if(adminTypingState){
      adminTypingState = false;
      fetch('../php/set_typing.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `conversation_id=${encodeURIComponent(currentConversationId)}&typing=0&sender=admin`
      }).catch(()=>{});
    }
  }
}

function checkTyping(){
  if(!currentConversationId) return;
  fetch(`../php/get_typing.php?conversation_id=${encodeURIComponent(currentConversationId)}`)
    .then(r => r.json())
    .then(j => {
      typingIndicator.style.display = (j && j.patient_typing) ? 'block' : 'none';
    }).catch(()=>{ typingIndicator.style.display='none'; });
}

// wire up events
sendBtn && sendBtn.addEventListener('click', sendMessage);
msgInput && msgInput.addEventListener('keydown', e => {
  if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }
  else sendTyping(true);
});
msgInput && msgInput.addEventListener('input', () => sendTyping(true));
userSearch && userSearch.addEventListener('input', e => loadPatients(e.target.value));

// initial
loadPatients();
