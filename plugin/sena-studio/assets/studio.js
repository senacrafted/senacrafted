/* Sena Studio – admin screen behaviour. Vanilla JS, no build step. */
(function () {
	'use strict';

	var i18n = (window.SenaStudio && window.SenaStudio.i18n) || {};

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	/* ---- Dismiss notices ---- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-dismiss]');
		if (btn) { var n = btn.closest('.sena-notice'); if (n) { n.remove(); } }
	});

	/* ---- Confirm links & buttons ---- */
	document.addEventListener('click', function (e) {
		var el = e.target.closest('[data-confirm]');
		if (!el) { return; }
		if (!window.confirm(el.getAttribute('data-confirm') || i18n.confirm || 'Are you sure?')) {
			e.preventDefault();
			e.stopImmediatePropagation();
		}
	}, true);

	/* ---- Toggle inline forms ---- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-toggle]');
		if (!btn) { return; }
		var target = $(btn.getAttribute('data-toggle'));
		if (!target) { return; }
		var card = btn.closest('.sena-card');
		if (card) {
			$$('.sena-inline-form', card).forEach(function (f) { if (f !== target) { f.classList.add('is-hidden'); } });
		}
		target.classList.toggle('is-hidden');
		if (!target.classList.contains('is-hidden')) {
			var first = target.querySelector('input:not([type=hidden]), select, textarea');
			if (first) { first.focus(); }
		}
	});

	/* ---- Dialogs ---- */
	function openDialog(dlg) {
		if (!dlg) { return; }
		if (typeof dlg.showModal === 'function') { dlg.showModal(); } else { dlg.setAttribute('open', ''); }
		var first = dlg.querySelector('input:not([type=hidden]):not([type=file]), textarea, select');
		if (first && window.innerWidth >= 768) { first.focus(); }
	}
	function closeDialog(dlg) {
		if (!dlg) { return; }
		if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
	}
	document.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-open]');
		if (opener) { e.preventDefault(); openDialog($(opener.getAttribute('data-open'))); return; }
		var closer = e.target.closest('[data-close]');
		if (closer) { e.preventDefault(); closeDialog(closer.closest('dialog')); return; }
		// Click on backdrop closes.
		if (e.target.tagName === 'DIALOG' && e.target.classList.contains('sena-dialog')) {
			var r = e.target.getBoundingClientRect();
			if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) { closeDialog(e.target); }
		}
	});
	$$('dialog[data-auto-open]').forEach(openDialog);

	/* ---- Auto-grow textareas ---- */
	function autogrow(ta) { ta.style.height = 'auto'; ta.style.height = (ta.scrollHeight + 2) + 'px'; }
	$$('textarea[data-autogrow]').forEach(function (ta) {
		autogrow(ta);
		ta.addEventListener('input', function () { autogrow(ta); });
	});

	/* ---- Copy caption / copy arbitrary text ---- */
	function copyText(text, btn) {
		var done = function () {
			if (!btn) { return; }
			var original = btn.innerHTML;
			btn.innerHTML = '<span class="dashicons dashicons-yes" aria-hidden="true"></span> ' + (i18n.copied || 'Copied');
			btn.disabled = true;
			setTimeout(function () { btn.innerHTML = original; btn.disabled = false; }, 1600);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
		} else {
			fallbackCopy(text); done();
		}
	}
	function fallbackCopy(text) {
		var ta = document.createElement('textarea');
		ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.top = '-1000px';
		document.body.appendChild(ta); ta.select();
		try { document.execCommand('copy'); } catch (err) { /* ignore */ }
		document.body.removeChild(ta);
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-copy-caption]');
		if (btn) {
			var form = btn.closest('form');
			var caption = form ? (form.querySelector('[name=caption]') || {}).value || '' : '';
			var tags = form ? (form.querySelector('[name=hashtags]') || {}).value || '' : '';
			copyText(tags ? caption + '\n\n' + tags : caption, btn);
			return;
		}
		var c = e.target.closest('[data-copy]');
		if (c) { copyText(c.getAttribute('data-copy') || '', c); }
	});

	/* ---- wp.media pickers ---- */
	function mediaPicker(buttonSel, hiddenSel, previewSel, multiple) {
		var btn = $(buttonSel);
		if (!btn || !window.wp || !wp.media) { return; }
		var frame;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({
				title: i18n.choosePhotos || 'Choose photos',
				button: { text: i18n.usePhotos || 'Use these photos' },
				library: { type: 'image' },
				multiple: multiple ? 'add' : false
			});
			frame.on('select', function () {
				var items = frame.state().get('selection').toJSON();
				var hidden = $(hiddenSel);
				var preview = $(previewSel);
				if (!multiple) { items = items.slice(0, 1); }
				var ids = items.map(function (a) { return a.id; });
				if (hidden) { hidden.value = ids.join(','); }
				if (preview) {
					preview.innerHTML = '';
					items.forEach(function (a) {
						var src = (a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url) || a.url;
						var img = document.createElement('img');
						img.src = src; img.alt = '';
						preview.appendChild(img);
					});
				}
			});
			frame.open();
		});
	}
	mediaPicker('#sena-pick-photos', '#sena-image-ids', '#sena-photo-preview', true);
	mediaPicker('#sena-pick-post-image', '#sena-post-image-id', '#sena-post-image-preview', false);

	/* ---- Native file input preview (camera capture) ---- */
	$$('.sena-file input[type=file]').forEach(function (input) {
		input.addEventListener('change', function () {
			var preview = $('#sena-photo-preview');
			if (!preview || !input.files) { return; }
			$$('img[data-local]', preview).forEach(function (i) { i.remove(); });
			Array.prototype.forEach.call(input.files, function (file) {
				if (!file.type || file.type.indexOf('image/') !== 0) { return; }
				var img = document.createElement('img');
				img.alt = ''; img.setAttribute('data-local', '1');
				img.src = URL.createObjectURL(file);
				img.onload = function () { URL.revokeObjectURL(img.src); };
				preview.appendChild(img);
			});
		});
	});

	/* ---- Keep focused order open and scrolled into view ---- */
	var focus = $('.sena-order.is-focus, .sena-request.is-active, #sena-event-form');
	if (focus && window.location.hash === '') {
		setTimeout(function () { focus.scrollIntoView({ block: 'start', behavior: 'smooth' }); }, 50);
	}

	/* ---- Prevent double submits ---- */
	$$('.sena-studio form').forEach(function (form) {
		form.addEventListener('submit', function () {
			setTimeout(function () {
				$$('button[type=submit]', form).forEach(function (b) { b.disabled = true; });
			}, 0);
		});
	});
})();
