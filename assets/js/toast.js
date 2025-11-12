// toast.js — Reusable Glassmorphism Toast System

export function showToast(message, type = "info") {
  const toast = document.createElement("div");
  toast.className = "glass-toast";
  toast.textContent = message;

  // Color accent per type
  const colors = {
    success: "rgba(34,197,94,0.6)", // green
    error: "rgba(239,68,68,0.6)",   // red
    warning: "rgba(234,179,8,0.6)", // yellow
    info: "rgba(59,130,246,0.6)"    // blue
  };

  toast.style.setProperty("--toast-color", colors[type] || colors.info);

  document.body.appendChild(toast);
  setTimeout(() => toast.classList.add("visible"), 50);
  setTimeout(() => toast.classList.remove("visible"), 4500);
  setTimeout(() => toast.remove(), 5200);
}

// Auto-check for PHP session messages
document.addEventListener("DOMContentLoaded", () => {
  const message = window.toastMessage || "";
  const type = window.toastType || "info";
  if (message) showToast(message, type);
});
// 🧊 Global Toast System
function showToast(message, type = 'info', duration = 3500) {
  let toast = document.getElementById('globalToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'globalToast';
    toast.className = 'toast hidden';
    document.body.appendChild(toast);
  }

  toast.textContent = message;
  toast.className = `toast show ${type}`;
  setTimeout(() => toast.className = 'toast hidden', duration);
}
