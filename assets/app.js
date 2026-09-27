"use strict";
const menu = document.querySelector("#menu-toggle");
const sidebar = document.querySelector("#sidebar");
menu?.addEventListener("click", () => {
  const open = sidebar.classList.toggle("is-open");
  menu.setAttribute("aria-expanded", String(open));
  menu.setAttribute(
    "aria-label",
    open ? "Chiudi navigazione" : "Apri navigazione",
  );
});
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    sidebar?.classList.remove("is-open");
    menu?.setAttribute("aria-expanded", "false");
  }
});
document.addEventListener("click", (event) => {
  if (
    sidebar?.classList.contains("is-open") &&
    !sidebar.contains(event.target) &&
    !menu?.contains(event.target)
  ) {
    sidebar.classList.remove("is-open");
    menu?.setAttribute("aria-expanded", "false");
  }
});
document.querySelectorAll("form[data-confirm]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});
