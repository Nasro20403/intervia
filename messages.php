<?php
session_start();
require_once 'db.php';

// ===== CONFIG =====
$WS_SECRET = 'fdhnjksldgnjg784uig98hygruhhjrerngh443hghrignreknjhberbg4y3bh3erpo32uwgwjgnhgo43ghhrwefjne2@kmgjeiu4ugj5$$$tjhnrink';
$WS_TOKEN_TTL = 3600; // مدة صلاحية التوكن بالثواني

// ===== AUTH =====
$me_id = $_SESSION['user_id'];
$me_username = $_GET['test_user'] ?? $_SESSION['username'];

// توليد HMAC token
$expires = time() + $WS_TOKEN_TTL;
$payload = $me_username . ':' . $expires;
$signature = hash_hmac('sha256', $payload, $WS_SECRET, true);
$token = base64_encode($payload . ':' . base64_encode($signature));

// ===== FETCH USERS =====
$stmt = $conn->prepare("SELECT fullname, username, profilePic FROM users WHERE username != ? ORDER BY fullname ASC");
$stmt->bind_param("s", $me_username);
$stmt->execute();
$res = $stmt->get_result();
$users = [];
while ($r = $res->fetch_assoc()) $users[] = $r;
?><!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>INTERVIA | Messages | <?= htmlspecialchars($me_username, ENT_QUOTES) ?></title>
<style>
:root{--blue:#0a84ff;--bg:#0f1720}
body{margin:0;font-family:Arial;background:var(--bg);color:#fff}
.app{display:flex;height:100vh}
.sidebar{width:320px;background:#111;padding:12px;overflow:auto}
.user-card{display:flex;gap:10px;align-items:center;padding:8px;border-radius:8px;cursor:pointer}
.user-card img{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #222}
.chat{flex:1;display:flex;flex-direction:column;background:#f6f7fb;color:#111}
.top{padding:12px;border-bottom:1px solid #e6eaf0;display:flex;align-items:center;gap:12px}
.messages{flex:1;overflow:auto;padding:16px;background:#fff}
.msg{max-width:70%;padding:10px 14px;border-radius:14px;margin-bottom:10px;word-wrap:break-word}
.msg.me{background:var(--blue);color:#fff;margin-left:auto}
.msg.other{background:#fff;border:1px solid #eef2f8;margin-right:auto}
.compose{display:flex;padding:12px;border-top:1px solid #eaeff5;background:#fff}
.compose input{flex:1;padding:10px;border-radius:10px;border:1px solid #dbe7ff}
.compose button{background:var(--blue);color:#fff;border:none;padding:8px 14px;border-radius:8px;cursor:pointer}
.badge{margin-left:auto;width:16px;height:16px;border-radius:50%;transition:background 0.3s}
.small{font-size:13px;color:#6b7280}
</style>
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <h3>USERS</h3>
    <h2>Hello <?= htmlspecialchars($me_username, ENT_QUOTES) ?></h2>
    <?php foreach($users as $u): ?>
      <div class="user-card" data-username="<?= htmlspecialchars($u['username'],ENT_QUOTES) ?>" data-fullname="<?= htmlspecialchars($u['fullname'],ENT_QUOTES) ?>" data-profile="<?= htmlspecialchars($u['profilePic'],ENT_QUOTES) ?>">
        <img src="uploads/<?= htmlspecialchars($u['profilePic'],ENT_QUOTES) ?>" alt="">
        <div>
          <div><?= htmlspecialchars($u['fullname'],ENT_QUOTES) ?></div>
          <div class="small">@<?= htmlspecialchars($u['username'],ENT_QUOTES) ?></div>
        </div>
        <div class="badge" data-badge-for="<?= htmlspecialchars($u['username'],ENT_QUOTES) ?>"></div>
      </div>
    <?php endforeach; ?>
  </aside>

  <section class="chat">
    <div class="top" id="topbar">
      <div style="display:flex;gap:12px;align-items:center">
        <img id="cProfile" src="" style="width:44px;height:44px;border-radius:50%;display:none">
        <div><div id="cName">اختر مستخدماً</div><div id="cSub" class="small">سيظهر هنا اسم المستخدم</div></div>
      </div>
    </div>

    <div class="messages" id="messages" aria-live="polite"></div>

    <div class="compose" id="compose" style="display:none">
      <input id="msgInput" placeholder="اكتب رسالة..." />
      <button id="sendBtn">Send</button>
    </div>
  </section>
</div>

<script>
(() => {
  const ME = <?= json_encode($me_username) ?>;
  const WS_TOKEN = <?= json_encode($token) ?>;
  const WS_URL = "ws://localhost:8080";
  let ws = null;
  let connected = false;
  let current = { otherUsername: '', lastId: 0 };
  const messagesEl = document.getElementById('messages');
  const composeEl = document.getElementById('compose');
  const msgInput = document.getElementById('msgInput');
  const sendBtn = document.getElementById('sendBtn');

  document.getElementById('sidebar').addEventListener('click', e => {
    const card = e.target.closest('.user-card');
    if (!card) return;
    openChat(card.dataset.username, card.dataset.fullname, card.dataset.profile);
  });

  function openChat(username, fullname, profile) {
    current.otherUsername = username;
    document.getElementById('cName').textContent = fullname;
    document.getElementById('cSub').textContent = '@' + username;
    const cProfile = document.getElementById('cProfile');
    cProfile.src = profile ? ('uploads/' + profile) : '';
    cProfile.style.display = profile ? 'inline-block' : 'none';
    composeEl.style.display = 'flex';
    messagesEl.innerHTML = '';
    current.lastId = 0;

    fetch(`fetch_messages.php?username=${encodeURIComponent(username)}&after_id=0`)
      .then(r => r.json())
      .then(res => {
        if(res.ok) res.messages.forEach(m => appendMessage(m));
        current.lastId = res.messages.length ? res.messages[res.messages.length-1].id : 0;
        scrollBottom();
      }).catch(console.error);
  }

  function appendMessage(m) {
    if(m.id && [...messagesEl.children].some(c => c.dataset.msgId == m.id)) return;
    const div = document.createElement('div');
    div.className = 'msg ' + (m.sender_username == ME ? 'me' : 'other');
    div.dataset.msgId = m.id || '';
    div.innerHTML = `<div>${m.message}</div><div class="small">${m.created_at||''}</div>`;
    messagesEl.appendChild(div);
    if(messagesEl.children.length > 800) messagesEl.removeChild(messagesEl.firstChild);
  }

  function scrollBottom() { messagesEl.scrollTop = messagesEl.scrollHeight; }

  function initWS() {
    ws = new WebSocket(WS_URL);

    ws.addEventListener('open', () => {
      connected = true;
      ws.send(JSON.stringify({ type:'init', token: WS_TOKEN }));
    });

    ws.addEventListener('message', ev => {
      try {
        const d = JSON.parse(ev.data);
        if(d.type === 'message') {
          if(current.otherUsername && (d.sender_username == current.otherUsername || d.receiver_username == current.otherUsername)) appendMessage(d);
          else {
            const b = document.querySelector(`.badge[data-badge-for="${d.sender_username}"]`);
            if(b) b.style.background = '#ff3b30';
          }
        } else if(d.type === 'sent_ack' && d.receiver_username == current.otherUsername) {
          fetch(`fetch_messages.php?username=${encodeURIComponent(current.otherUsername)}&after_id=${current.lastId}`)
            .then(r => r.json())
            .then(resp => resp.ok && resp.messages.forEach(m => appendMessage(m)))
            .catch(console.error);
        }
      } catch(e){ console.error(e); }
    });

    ws.addEventListener('close', () => {
      connected = false;
      setTimeout(initWS, 2000);
    });
    ws.addEventListener('error', e => { console.error(e); ws.close(); });
  }
  initWS();

  function sendMessage() {
    const txt = msgInput.value.trim();
    if(!txt || !current.otherUsername) return;
    const temp_id = 't_' + Date.now();
    appendMessage({ id: temp_id, sender_username: ME, receiver_username: current.otherUsername, message: txt });
    scrollBottom();
    msgInput.value = '';

    if(connected && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ type:'message', receiver_username: current.otherUsername, message: txt, temp_id }));
      return;
    }

    fetch('send_message.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`receiver_username=${encodeURIComponent(current.otherUsername)}&message=${encodeURIComponent(txt)}`
    }).then(r=>r.json()).then(res=>{
      if(res.ok) fetch(`fetch_messages.php?username=${encodeURIComponent(current.otherUsername)}&after_id=${current.lastId}`)
        .then(r=>r.json()).then(resp=> resp.ok && resp.messages.forEach(m => appendMessage(m)))
        .catch(console.error);
    }).catch(console.error);
  }

  sendBtn.addEventListener('click', sendMessage);
  msgInput.addEventListener('keydown', e => e.key === 'Enter' && sendMessage());

})();
</script>
</body>
</html>
