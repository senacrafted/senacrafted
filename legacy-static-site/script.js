// Renders the site from the data in works.js — you shouldn't need to edit this.

const $ = (sel) => document.querySelector(sel);
const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const money = (n) => "$" + (Number.isInteger(n) ? n : n.toFixed(2));
// price can be a number or a [low, high] range
const hasPrice = (w) => (Array.isArray(w.price) ? w.price.length === 2 : typeof w.price === "number" && w.price > 0);
const paras = (text) => String(text ?? "").split(/\n\s*\n/).filter(Boolean);
const priceText = (p) => (Array.isArray(p) ? `${money(p[0])}–${money(p[1])}` : money(p));

const mailto = (subject, body = "") =>
  `mailto:${SITE.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

// Older entries may use a single `image` instead of `photos`
const photosOf = (w) => (w.photos?.length ? w.photos : w.image ? [w.image] : []);

function placeholder(work) {
  const label = work.category === "leather" ? "Leather" : "Paper goods";
  return `<div class="placeholder"><div>${esc(work.title)}<small>${label} · photo coming</small></div></div>`;
}

function action(work) {
  if (work.status === "sold") return `<span class="muted" style="font-size:.85rem">Ask about similar</span>`;
  if (work.status === "showcase") return `<span class="muted" style="font-size:.85rem">Showcase piece</span>`;
  if (work.buyLink) {
    const label = /etsy\.com/.test(work.buyLink) ? "Buy on Etsy" : "Buy now";
    return `<a class="btn small" href="${esc(work.buyLink)}" target="_blank" rel="noopener">${label}</a>`;
  }
  const verb = work.status === "custom" ? "Order one" : "Ask about it";
  return `<a class="btn small ghost" href="${mailto(`About: ${work.title}`, `Hi! I'm interested in "${work.title}".\n\n`)}">${verb}</a>`;
}

function badge(work) {
  if (work.status === "sold") return `<span class="badge sold">Sold</span>`;
  if (work.status === "custom") return `<span class="badge">Made to order</span>`;
  return "";
}

function priceTag(w) {
  if (!hasPrice(w)) return "<span></span>";
  return `<span class="price">${priceText(w.price)}${w.status === "custom" ? "+" : ""}</span>`;
}

function renderGallery(filter = "all") {
  const items = WORKS.map((w, i) => ({ ...w, i }))
    .filter((w) => filter === "all" || w.category === filter)
    .sort((a, b) => !!b.buyLink - !!a.buyLink);   // pieces with a Buy link come first
  $("#gallery").innerHTML = items.map((w) => {
    const photos = photosOf(w);
    const media = photos.length
      ? `<img src="${esc(photos[0])}" alt="${esc(w.title)}" loading="lazy">` +
        (photos[1] ? `<img class="alt" src="${esc(photos[1])}" alt="" loading="lazy">` : "") +
        (photos.length > 1 ? `<span class="count">${photos.length} photos</span>` : "")
      : placeholder(w);
    return `
    <article class="card ${w.status === "sold" ? "is-sold" : ""}">
      <div class="media" data-i="${w.i}" role="button" tabindex="0" aria-label="See photos of ${esc(w.title)}">${media}${badge(w)}</div>
      <div class="info">
        <h3>${esc(w.title)}</h3>
        <p class="desc"><span>${esc(paras(w.description)[0])}</span></p>
        <div class="row">${priceTag(w)}${action(w)}</div>
      </div>
    </article>`;
  }).join("");
}

// ── Lightbox with photo carousel ──
let current = { work: null, photo: 0 };

function showPhoto(n) {
  const photos = photosOf(current.work);
  if (!photos.length) return;
  current.photo = (n + photos.length) % photos.length;
  $("#lightbox .lb-frame").innerHTML = `<img src="${esc(photos[current.photo])}" alt="${esc(current.work.title)}, photo ${current.photo + 1} of ${photos.length}">`;
  document.querySelectorAll("#lightbox .lb-thumbs button").forEach((b, i) => b.classList.toggle("on", i === current.photo));
}

function openLightbox(i) {
  const w = WORKS[i];
  const photos = photosOf(w);
  const box = $("#lightbox");
  current = { work: w, photo: 0 };
  box.querySelector(".lb-frame").innerHTML = photos.length ? "" : placeholder(w);
  box.querySelectorAll(".lb-nav").forEach((b) => (b.hidden = photos.length < 2));
  box.querySelector(".lb-thumbs").innerHTML = photos.length > 1
    ? photos.map((p, n) => `<button data-n="${n}" aria-label="Photo ${n + 1}"><img src="${esc(p)}" alt=""></button>`).join("")
    : "";
  box.querySelector(".lb-body").innerHTML = `
    <h3>${esc(w.title)}</h3>
    ${paras(w.description).map((p) => `<p class="muted">${esc(p)}</p>`).join("")}
    ${w.status === "sold" ? `<span class="price">Sold</span>` : hasPrice(w) ? priceTag(w) : `<p></p>`}
    ${action(w)}`;
  showPhoto(0);
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
    : `<li class="empty"><span class="muted">Festival dates to be determined.</span></li>`;
}

// ── Wavy frame around the custom-orders box ──
// Redrawn whenever the box changes size so the waves stay evenly spaced.
function drawWavy(el) {
  const { width: w, height: h } = el.getBoundingClientRect();
  const amp = 7, wave = 38, inset = amp + 7;
  const pts = [];
  const edge = (x0, y0, x1, y1, nx, ny) => {
    const len = Math.hypot(x1 - x0, y1 - y0);
    const k = Math.max(2, Math.round(len / wave)) ;
    for (let s = 0; s < len; s += 3) {
      const t = s / len, off = amp * Math.sin(Math.PI * k * t);
      pts.push([x0 + (x1 - x0) * t + nx * off, y0 + (y1 - y0) * t + ny * off]);
    }
  };
  const L = inset, T = inset, R = w - inset, B = h - inset;
  edge(L, T, R, T, 0, -1);
  edge(R, T, R, B, 1, 0);
  edge(R, B, L, B, 0, 1);
  edge(L, B, L, T, -1, 0);
  const d = "M" + pts.map(([x, y]) => `${x.toFixed(1)} ${y.toFixed(1)}`).join("L") + "Z";
  el.querySelector(".wave").innerHTML =
    `<path d="${d}" fill="var(--beige)" stroke="var(--red)" stroke-width="13" stroke-linejoin="round" filter="url(#painted)"/>`;
}

// Fill in site-wide text
document.querySelectorAll("[data-site]").forEach((el) => (el.textContent = SITE[el.dataset.site] ?? ""));
document.title = `${SITE.name} — Nature Paintings & Leather Crafts`;
$("#year").textContent = new Date().getFullYear();
$("#email-link").href = mailto("Hello!");
$("#email-link").textContent = "Email me";
$("#about-copy").innerHTML = [].concat(SITE.about).map((p) => `<p>${esc(p)}</p>`).join("");
if (SITE.instagram) $("#ig-link").href = `https://www.instagram.com/${SITE.instagram}/`;
else $("#ig-link").remove();
if (SITE.tiktok) $("#tiktok-link").href = `https://www.tiktok.com/@${SITE.tiktok}`;
else $("#tiktok-link").remove();
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
$("#gallery").addEventListener("keydown", (e) => {
  const m = e.target.closest(".media");
  if (m && (e.key === "Enter" || e.key === " ")) { e.preventDefault(); openLightbox(+m.dataset.i); }
});
$("#lightbox .close").addEventListener("click", () => $("#lightbox").close());
$("#lightbox").addEventListener("click", (e) => { if (e.target.id === "lightbox") e.target.close(); });
$("#lightbox .prev").addEventListener("click", () => showPhoto(current.photo - 1));
$("#lightbox .next").addEventListener("click", () => showPhoto(current.photo + 1));
$("#lightbox .lb-thumbs").addEventListener("click", (e) => {
  const b = e.target.closest("button");
  if (b) showPhoto(+b.dataset.n);
});
$("#lightbox").addEventListener("keydown", (e) => {
  if (e.key === "ArrowLeft") showPhoto(current.photo - 1);
  if (e.key === "ArrowRight") showPhoto(current.photo + 1);
});

// Let the hero fill exactly the first screen below the header
const setHead = () => document.documentElement.style.setProperty(
  "--head", `${($(".announce")?.offsetHeight || 0) + $(".nav").offsetHeight}px`);
setHead();
document.fonts.ready.then(setHead);   // the header can grow a little once the fonts load
const headWatch = new ResizeObserver(setHead);
headWatch.observe($(".nav"));
if ($(".announce")) headWatch.observe($(".announce"));

const wavy = $(".wavy");
wavy.insertAdjacentHTML("afterbegin", `<svg class="wave" aria-hidden="true"></svg>`);
new ResizeObserver(() => drawWavy(wavy)).observe(wavy);

renderGallery();
renderEvents();
