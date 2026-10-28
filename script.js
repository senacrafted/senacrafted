// Renders the site from the data in works.js — you shouldn't need to edit this.

const $ = (sel) => document.querySelector(sel);
const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const money = (n) => "$" + (Number.isInteger(n) ? n : n.toFixed(2));

const mailto = (subject, body = "") =>
  `mailto:${SITE.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

function media(work) {
  if (work.image) return `<img src="${esc(work.image)}" alt="${esc(work.title)}" loading="lazy">`;
  const label = work.category === "leather" ? "Leather" : "Painting";
  return `<div class="placeholder ${work.category}"><div>${esc(work.title)}<small>${label} · photo coming</small></div></div>`;
}

function action(work) {
  if (work.status === "sold") return `<span class="muted" style="font-size:.85rem">Ask about similar</span>`;
  if (work.buyLink) return `<a class="btn small" href="${esc(work.buyLink)}" target="_blank" rel="noopener">Buy now</a>`;
  const verb = work.status === "custom" ? "Order one" : "Ask about it";
  return `<a class="btn small ghost" href="${mailto(`About: ${work.title}`, `Hi! I'm interested in "${work.title}".\n\n`)}">${verb}</a>`;
}

function badge(work) {
  if (work.status === "sold") return `<span class="badge sold">Sold</span>`;
  if (work.status === "custom") return `<span class="badge">Made to order</span>`;
  return "";
}

function renderGallery(filter = "all") {
  const items = WORKS.map((w, i) => ({ ...w, i })).filter((w) => filter === "all" || w.category === filter);
  $("#gallery").innerHTML = items.map((w) => `
    <article class="card ${w.status === "sold" ? "is-sold" : ""}">
      <div class="media" data-i="${w.i}">${media(w)}${badge(w)}</div>
      <div class="info">
        <h3>${esc(w.title)}</h3>
        <p class="desc">${esc(w.description)}</p>
        <div class="row">
          <span class="price">${w.price ? money(w.price) : ""}${w.status === "custom" ? "+" : ""}</span>
          ${action(w)}
        </div>
      </div>
    </article>`).join("");
}

function openLightbox(i) {
  const w = WORKS[i];
  const box = $("#lightbox");
  box.querySelector(".lb-media").innerHTML = media(w);
  box.querySelector(".lb-body").innerHTML = `
    <h3>${esc(w.title)}</h3>
    <p class="muted">${esc(w.description)}</p>
    <p><strong>${w.status === "sold" ? "Sold" : w.price ? money(w.price) : ""}</strong></p>
    ${action(w)}`;
  box.showModal();
}

function renderEvents() {
  $("#event-list").innerHTML = EVENTS.length
    ? EVENTS.map((e) => `
      <li>
        <span class="date">${esc(e.date)}</span>
        <div><strong>${esc(e.name)}</strong><div class="place">${esc(e.place)}</div></div>
        ${e.link ? `<a class="btn small ghost" href="${esc(e.link)}" target="_blank" rel="noopener">Details</a>` : ""}
      </li>`).join("")
    : `<li class="empty"><span class="muted">Festival dates to be determined — follow along on Instagram or email me to hear first.</span></li>`;
}

// Fill in site-wide text
document.querySelectorAll("[data-site]").forEach((el) => (el.textContent = SITE[el.dataset.site] ?? ""));
document.title = `${SITE.name} — Nature Paintings & Leather Crafts`;
$("#year").textContent = new Date().getFullYear();
$("#email-link").href = mailto("Hello!");
$("#email-link").textContent = "Email me";
if (SITE.instagram) {
  $("#ig-link").href = `https://instagram.com/${SITE.instagram}`;
  $("#ig-link").textContent = `@${SITE.instagram}`;
} else {
  $("#ig-link").remove();
}
$("#custom-btn").href = mailto(
  "Custom order request",
  "Hi! I'd love a custom piece.\n\nWhat I'm looking for:\nSize / colors:\nWhen I need it by:\n"
);

// Interactions
document.querySelectorAll(".filter").forEach((btn) =>
  btn.addEventListener("click", () => {
    document.querySelectorAll(".filter").forEach((b) => b.classList.toggle("active", b === btn));
    renderGallery(btn.dataset.filter);
  })
);
$("#gallery").addEventListener("click", (e) => {
  const m = e.target.closest(".media");
  if (m) openLightbox(+m.dataset.i);
});
$("#lightbox .close").addEventListener("click", () => $("#lightbox").close());
$("#lightbox").addEventListener("click", (e) => { if (e.target.id === "lightbox") e.target.close(); });

renderGallery();
renderEvents();
