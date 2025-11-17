// js/inbox.js
// Patient-side inbox messenger (uses ./php/* endpoints)

const PATIENT_ID = (typeof window.PATIENT_ID !== 'undefined') ? window.PATIENT_ID : null;

let currentConversation = null;
let pollHandle = null;
let typingTimeout = null;
let typingState = false;

// DOM refs
const adminListEl = document.getElementById('adminList');
const chatHeaderEl = document.getElementById('chatHeader');
const chatMessagesEl = document.getElementById('chatMessages');
const typingIndicatorEl = document.getElementById('typingIndicator');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const archiveBtn = document.getElementById('archiveBtn');
const remindersListEl = document.getElementById('remindersList');
const archiveListEl = document.getElementById('archiveList');

function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// Load admins
function loadAdmins(){
  fetch('./php/fetch_admins.php')
    .then(r => r.json())
    .then(list => {
      adminListEl.innerHTML = '';
      if(!Array.isArray(list) || list.length === 0){
        adminListEl.innerHTML = '<div class="empty">No admins</div>';
        return;
      }
      list.forEach(adm => {
        const el = document.createElement('div');
        el.className = 'user';
        el.dataset.adminId = adm.admin_id;
        el.textContent = `${adm.name}${adm.unread_count ? ' • ' + adm.unread_count : ''}`;
        el.addEventListener('click', () => openConversationWith(adm.admin_id, adm.name));
        adminListEl.appendChild(el);
      });
    }).catch(err => {
      console.error('loadAdmins', err);
      adminListEl.innerHTML = '<div class="error">Error loading admins</div>';
    });
}

// Open or create conversation (patient -> admin)
function openConversationWith(admin_id, admin_name){
  fetch(`./php/get_or_create_conversation.php?admin_id=${encodeURIComponent(admin_id)}`)
    .then(r => r.json())
    .then(data => {
      if(!data || !data.conversation){ alert('Cannot open conversation'); return; }
      currentConversation = data.conversation;
      chatHeaderEl.querySelector('h3').textContent = admin_name;
      startPolling();
      loadMessages();
    }).catch(err => {
      console.error('openConversationWith', err);
      alert('Failed to open conversation');
    });
}

// polling
function startPolling(){
  if(pollHandle) clearInterval(pollHandle);
  pollHandle = setInterval(() => {
    loadMessages();
    checkTyping();
  }, 1000);
}
function stopPolling(){ if(pollHandle) clearInterval(pollHandle); pollHandle = null; }

// load messages
function loadMessages(){
  if(!currentConversation || !currentConversation.id) return;
  fetch(`./php/fetch_messages.php?conversation_id=${encodeURIComponent(currentConversation.id)}`)
    .then(r => r.json())
    .then(rows => {
      chatMessagesEl.innerHTML = '';
      if(!Array.isArray(rows) || rows.length === 0){
        chatMessagesEl.innerHTML = '<div class="empty">No messages yet</div>';
        return;
      }
      rows.forEach(m => {
        const cls = (m.sender_role === 'patient') ? 'bubble you' : 'bubble admin';
        const el = document.createElement('div');
        el.className = cls;
        el.innerHTML = `<div class="msg">${escapeHtml(m.message || m.content || '')}</div>
                        <div class="meta">${escapeHtml(m.created_at)}</div>`;
        chatMessagesEl.appendChild(el);
      });
      chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
    }).catch(err => {
      console.error('loadMessages', err);
    });
}

// send message (patient)
function sendMessage(){
  if(!currentConversation || !currentConversation.id) return alert('Select an admin first');
  const text = msgInput.value.trim(); if(!text) return;
  const body = new URLSearchParams();
  body.append('conversation_id', currentConversation.id);
  body.append('message', text);
  body.append('sender', 'patient');

  fetch('./php/send_message.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString()
  }).then(r => r.json()).then(j => {
    if(j && j.success){
      msgInput.value = '';
      loadMessages();
      sendTyping(false);
    } else {
      console.error('send failed', j);
      alert('Send failed');
    }
  }).catch(err => { console.error('sendMessage', err); alert('Error sending'); });
}

// typing
function sendTyping(state){
  if(!currentConversation || !currentConversation.id) return;
  if(state){
    if(!typingState){
      typingState = true;
      fetch('./php/set_typing.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `conversation_id=${encodeURIComponent(currentConversation.id)}&typing=1&sender=patient`
      }).catch(()=>{});
    }
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(()=> sendTyping(false), 2000);
  } else {
    if(typingState){
      typingState = false;
      fetch('./php/set_typing.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `conversation_id=${encodeURIComponent(currentConversation.id)}&typing=0&sender=patient`
      }).catch(()=>{});
    }
  }
}

function checkTyping(){
  if(!currentConversation || !currentConversation.id) return;
  fetch(`./php/get_typing.php?conversation_id=${encodeURIComponent(currentConversation.id)}`)
    .then(r => r.json())
    .then(j => {
      if(j && j.admin_typing) typingIndicatorEl.style.display = 'block';
      else typingIndicatorEl.style.display = 'none';
    }).catch(()=> typingIndicatorEl.style.display = 'none');
}

// archive conversation
archiveBtn && archiveBtn.addEventListener('click', () => {
  if(!currentConversation || !currentConversation.id) return alert('Open a conversation first');
  if(!confirm('Archive this conversation?')) return;
  const body = new URLSearchParams();
  body.append('conversation_id', currentConversation.id);
  body.append('archive', '1');
  fetch('./php/archive_conversation.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() })
    .then(r => r.json()).then(j => {
      if(j && j.success){
        stopPolling();
        currentConversation = null;
        chatMessagesEl.innerHTML = '';
        chatHeaderEl.querySelector('h3').textContent = 'Select an Admin';
        loadAdmins();
      } else alert('Archive failed');
    }).catch(err => console.error('archive', err));
});

// events
sendBtn && sendBtn.addEventListener('click', sendMessage);
msgInput && msgInput.addEventListener('keydown', e => {
  if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }
  else sendTyping(true);
});
msgInput && msgInput.addEventListener('input', () => sendTyping(true));

// load reminders & archived functions (optional)
function loadReminders(){
  fetch('./php/get_reminders.php').then(r => r.json()).then(data => {
    remindersListEl.innerHTML = '';
    if(!data || !data.length) remindersListEl.innerHTML = '<div class="empty">No reminders</div>';
    else data.forEach(rem => {
      const d = document.createElement('div');
      d.className = 'reminder';
      d.innerHTML = `<strong>${escapeHtml(rem.service)}</strong><div>${escapeHtml(rem.date)} ${escapeHtml(rem.time || '')}</div>`;
      remindersListEl.appendChild(d);
    });
  }).catch(()=> remindersListEl.innerHTML = '<div class="error">Error</div>');
}
function loadArchived(){ fetch('./php/fetch_archived.php').then(r=>r.json()).then(data=>{/* render */}).catch(()=>{}); }

// init
loadAdmins();
loadReminders();
