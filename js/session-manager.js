// 🕒 Session Timeout Manager
const timeoutDuration = 15 * 60 * 1000; // 15 minutes
const warningTime = 14 * 60 * 1000;     // warn at 14 min
let timeoutWarning, timeoutLogout, countdownInterval;
let remainingSeconds = 60;

function createPopup() {
  if (document.getElementById('session-warning')) return;
  const popup = document.createElement('div');
  popup.id = 'session-warning';
  popup.style.cssText = `
    position: fixed; bottom: 40px; right: 40px; padding: 25px; width: 280px;
    background: rgba(25,25,35,0.7); backdrop-filter: blur(10px);
    border: 1px solid rgba(0,123,255,0.4); border-radius: 16px;
    box-shadow: 0 0 15px rgba(0,123,255,0.5); color: #fff;
    text-align: center; font-family: Poppins, sans-serif;
    opacity: 0; transition: opacity 0.5s ease; z-index: 9999;
  `;
  popup.innerHTML = `
    <p style="font-weight:600;">⚠️ Session Expiring Soon</p>
    <p id="countdown" style="font-size:14px; color:#ffcc00;">Logging out in 60 seconds...</p>
    <button id="stayLoggedIn" style="
      background:linear-gradient(90deg,#007bff,#00bfff); border:none; color:white;
      padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer;
      transition:0.3s; margin-top:8px;">Stay Logged In</button>
  `;
  document.body.appendChild(popup);
  setTimeout(() => popup.style.opacity = '1', 50);
  document.getElementById('stayLoggedIn').addEventListener('click', extendSession);
  startCountdown();
}

function startCountdown() {
  remainingSeconds = 60;
  const countdown = document.getElementById('countdown');
  countdownInterval = setInterval(() => {
    remainingSeconds--;
    countdown.textContent = `Logging out in ${remainingSeconds} seconds...`;
    if (remainingSeconds <= 0) {
      clearInterval(countdownInterval);
      logoutUser();
    }
  }, 1000);
}

function extendSession() {
  fetch('refresh_session.php')
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') { resetTimers(); fadeOutPopup(); }
      else { window.location.href = 'login.php?timeout=1'; }
    });
}

function fadeOutPopup() {
  const popup = document.getElementById('session-warning');
  if (popup) {
    popup.style.opacity = '0';
    setTimeout(() => popup.remove(), 500);
  }
  clearInterval(countdownInterval);
}

function logoutUser() { window.location.href = 'logout.php'; }

function resetTimers() {
  clearTimeout(timeoutWarning);
  clearTimeout(timeoutLogout);
  clearInterval(countdownInterval);
  fadeOutPopup();
  timeoutWarning = setTimeout(createPopup, warningTime);
  timeoutLogout = setTimeout(logoutUser, timeoutDuration);
}

['click','mousemove','keypress','scroll','touchstart'].forEach(evt => {
  document.addEventListener(evt, resetTimers, false);
});

resetTimers();
