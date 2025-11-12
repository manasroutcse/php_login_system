// 🌗 Global Theme Manager
document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.createElement("button");
  toggleBtn.id = "themeToggle";
  toggleBtn.className = "theme-toggle";
  document.body.appendChild(toggleBtn);

  const savedTheme = localStorage.getItem("theme");
  if (savedTheme === "light") {
    document.body.classList.add("light");
    toggleBtn.textContent = "🌙";
  } else {
    toggleBtn.textContent = "☀️";
  }

  toggleBtn.addEventListener("click", () => {
    document.body.classList.toggle("light");
    const isLight = document.body.classList.contains("light");
    localStorage.setItem("theme", isLight ? "light" : "dark");
    toggleBtn.textContent = isLight ? "🌙" : "☀️";
  });
});
// 🌗 Glassmorphism Theme Toggle
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.createElement("button");
  btn.className = "theme-toggle";
  document.body.appendChild(btn);

  const saved = localStorage.getItem("theme");
  if (saved === "light") {
    document.body.classList.add("light");
    btn.textContent = "🌙";
  } else {
    btn.textContent = "☀️";
  }

  btn.addEventListener("click", () => {
    document.body.classList.toggle("light");
    const isLight = document.body.classList.contains("light");
    localStorage.setItem("theme", isLight ? "light" : "dark");
    btn.textContent = isLight ? "🌙" : "☀️";
  });
});
