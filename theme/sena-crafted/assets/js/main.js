/* Sena Crafted — front-end behaviour (vanilla JS, no jQuery).
   Ported from the static site's script.js: category filters, the photo lightbox,
   the hand-painted wavy frame and the header-height variable used by the hero. */
(function () {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const i18n = Object.assign(
    { photoOf: "%1$s, photo %2$s of %3$s", photo: "Photo %s" },
    (window.senaConfig && window.senaConfig.i18n) || {}
  );
  const fmt = (tpl, ...args) => {
    let n = 0;
    return tpl.replace(/%(\d+\$)?s/g, (m, pos) => esc(args[pos ? parseInt(pos, 10) - 1 : n++]));
  };

  /* ── Header height → --head (hero fills the first screen below it) ── */
  const nav = $(".nav");
  const announce = $(".announce");
  const setHead = () => {
    if (!nav) return;
    const h = (announce ? announce.offsetHeight : 0) + nav.offsetHeight;
    document.documentElement.style.setProperty("--head", `${h}px`);
  };
  if (nav) {
    setHead();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(setHead);
    if ("ResizeObserver" in window) {
      const headWatch = new ResizeObserver(setHead);
      headWatch.observe(nav);
      if (announce) headWatch.observe(announce);
    } else {
      window.addEventListener("resize", setHead);
    }
  }

  /* ── Category filters (cards carry data-cats="slug slug…") ── */
  const filters = $$(".filter[data-filter]");
  const cards = () => $$("#gallery .card, .gallery.products .card");
  function applyFilter(filter) {
    cards().forEach((card) => {
      const cats = (card.dataset.cats || "").split(/\s+/).filter(Boolean);
      card.hidden = filter !== "all" && !cats.includes(filter);
    });
  }
  filters.forEach((btn) =>
    btn.addEventListener("click", () => {
      filters.forEach((b) => {
        const on = b === btn;
        b.classList.toggle("active", on);
        b.setAttribute("aria-pressed", on ? "true" : "false");
      });
      applyFilter(btn.dataset.filter);
    })
  );

  /* ── Lightbox with photo carousel (data comes from data-lightbox JSON) ── */
  const box = $("#lightbox");
  let current = { work: null, photo: 0 };

  function placeholder(work) {
    return `<div class="placeholder"><div>${esc(work.title)}</div></div>`;
  }

  function showPhoto(n) {
    const photos = current.work ? current.work.photos || [] : [];
    if (!photos.length) return;
    current.photo = (n + photos.length) % photos.length;
    $(".lb-frame", box).innerHTML =
      `<img src="${esc(photos[current.photo].src)}" alt="${fmt(i18n.photoOf, current.work.title, current.photo + 1, photos.length)}">`;
    $$(".lb-thumbs button", box).forEach((b, i) => b.classList.toggle("on", i === current.photo));
  }

  function openLightbox(work) {
    if (!box || typeof box.showModal !== "function") {
      if (work && work.url) window.location.href = work.url;
      return;
    }
    const photos = work.photos || [];
    current = { work, photo: 0 };
    $(".lb-frame", box).innerHTML = photos.length ? "" : placeholder(work);
    $$(".lb-nav", box).forEach((b) => (b.hidden = photos.length < 2));
    $(".lb-thumbs", box).innerHTML = photos.length > 1
      ? photos.map((p, n) => `<button type="button" data-n="${n}" aria-label="${fmt(i18n.photo, n + 1)}"><img src="${esc(p.thumb || p.src)}" alt="" loading="lazy"></button>`).join("")
      : "";
    // price and action markup are generated and escaped server-side (PHP)
    $(".lb-body", box).innerHTML = `
      <h3>${work.url ? `<a href="${esc(work.url)}">${esc(work.title)}</a>` : esc(work.title)}</h3>
      ${(work.paras || []).map((p) => `<p class="muted">${esc(p)}</p>`).join("")}
      ${work.price || "<p></p>"}
      ${work.action || ""}`;
    showPhoto(0);
    box.showModal();
  }

  function dataOf(media) {
    try {
      return JSON.parse(media.dataset.lightbox || "null");
    } catch (e) {
      return null;
    }
  }

  document.addEventListener("click", (e) => {
    // let real links / buttons inside a card behave normally
    if (e.target.closest("a, button")) return;
    const m = e.target.closest(".media[data-lightbox]");
    if (!m) return;
    const work = dataOf(m);
    if (work) openLightbox(work);
  });
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Enter" && e.key !== " ") return;
    const m = e.target.closest && e.target.closest(".media[data-lightbox]");
    if (!m) return;
    e.preventDefault();
    const work = dataOf(m);
    if (work) openLightbox(work);
  });

  if (box) {
    $(".close", box).addEventListener("click", () => box.close());
    box.addEventListener("click", (e) => { if (e.target === box) box.close(); });
    $(".prev", box).addEventListener("click", () => showPhoto(current.photo - 1));
    $(".next", box).addEventListener("click", () => showPhoto(current.photo + 1));
    $(".lb-thumbs", box).addEventListener("click", (e) => {
      const b = e.target.closest("button");
      if (b) showPhoto(+b.dataset.n);
    });
    box.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") showPhoto(current.photo - 1);
      if (e.key === "ArrowRight") showPhoto(current.photo + 1);
    });
    // Close the viewer when an "Add to cart" inside it succeeds, so the WooCommerce notice is visible.
    box.addEventListener("click", (e) => {
      const add = e.target.closest(".ajax_add_to_cart");
      if (add) setTimeout(() => { if (box.open) box.close(); }, 900);
    });
  }

  /* ── Wavy frame around the custom-orders box ── */
  function drawWavy(el) {
    const wave = $(".wave", el);
    if (!wave) return;
    const { width: w, height: h } = el.getBoundingClientRect();
    if (!w || !h) return;
    const amp = 7, len0 = 38, inset = amp + 7;
    const pts = [];
    const edge = (x0, y0, x1, y1, nx, ny) => {
      const len = Math.hypot(x1 - x0, y1 - y0);
      const k = Math.max(2, Math.round(len / len0));
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
    wave.innerHTML =
      `<path d="${d}" fill="var(--beige)" stroke="var(--red)" stroke-width="13" stroke-linejoin="round" filter="url(#painted)"/>`;
  }
  $$(".wavy").forEach((wavy) => {
    if (!$(".wave", wavy)) wavy.insertAdjacentHTML("afterbegin", '<svg class="wave" aria-hidden="true" focusable="false"></svg>');
    drawWavy(wavy);
    if ("ResizeObserver" in window) new ResizeObserver(() => drawWavy(wavy)).observe(wavy);
    else window.addEventListener("resize", () => drawWavy(wavy));
  });
})();
