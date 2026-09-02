/**
 * Owner Update Notifications for MainWP
 * Admin scripts for the Manage Sites integration.
 *
 * Public globals expected on window:
 *   mcwNotify.ajaxUrl  — admin-ajax.php URL
 *   mcwNotify.i18n     — translated strings
 */
(function () {
	'use strict';

	if (!window.mcwNotify) { return; }
	var busy = new WeakSet();

	function insertNotice(message, type) {
		var host = document.querySelector('#wpbody-content');
		if (!host) { return; }
		var n = document.createElement('div');
		n.className = 'notice notice-' + type + ' is-dismissible mcw-notify-ajax-notice';
		var p = document.createElement('p');
		p.textContent = message;
		n.appendChild(p);
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'notice-dismiss';
		var sr = document.createElement('span');
		sr.className = 'screen-reader-text';
		sr.textContent = mcwNotify.i18n.dismiss;
		btn.appendChild(sr);
		btn.addEventListener('click', function () { n.remove(); });
		n.appendChild(btn);
		host.insertBefore(n, host.firstChild);
		// Auto-dismiss success notices; keep errors visible until user closes.
		if (type === 'success') {
			setTimeout(function () { if (n.parentNode) { n.remove(); } }, 6000);
		}
	}

	function restoreButton(btn, originalHtml) {
		btn.innerHTML = originalHtml;
		btn.classList.remove('mcw-notify-busy');
		btn.removeAttribute('aria-busy');
	}

	function replaceButton(btn, html) {
		var tmp = document.createElement('div');
		tmp.innerHTML = String(html).trim();
		var fresh = tmp.firstElementChild;
		if (fresh && btn.parentNode) {
			btn.parentNode.replaceChild(fresh, btn);
			return true;
		}
		return false;
	}

	// ── Single-row send (delegated click on any .mcw-notify-btn) ──────────
	document.addEventListener('click', function (evt) {
		var btn = evt.target.closest ? evt.target.closest('a.mcw-notify-btn') : null;
		if (!btn) { return; }
		evt.preventDefault();
		if (busy.has(btn)) { return; }

		var siteId = btn.getAttribute('data-site-id');
		var nonce  = btn.getAttribute('data-nonce');
		if (!siteId || !nonce) { return; }
		if (!window.confirm(mcwNotify.i18n.confirm)) { return; }

		busy.add(btn);
		var originalHtml = btn.innerHTML;
		btn.classList.add('mcw-notify-busy');
		btn.setAttribute('aria-busy', 'true');
		btn.textContent = mcwNotify.i18n.sending;

		var body = new URLSearchParams();
		body.append('action', 'mcw_ounm_send');
		body.append('site_id', siteId);
		body.append('nonce', nonce);

		fetch(mcwNotify.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json' },
			body: body
		}).then(function (r) {
			return r.json().then(function (j) { return { ok: r.ok, json: j }; })
			                .catch(function () { return { ok: r.ok, json: null }; });
		}).then(function (res) {
			busy.delete(btn);
			var payload = res.json || {};
			var data    = payload.data || {};
			var okFlag  = payload.success === true;
			if (data.button_html && replaceButton(btn, data.button_html)) {
				// button was swapped out; nothing to restore
			} else {
				restoreButton(btn, originalHtml);
			}
			insertNotice(data.message || (okFlag ? mcwNotify.i18n.confirm : mcwNotify.i18n.network), okFlag ? 'success' : 'error');
		}).catch(function () {
			busy.delete(btn);
			restoreButton(btn, originalHtml);
			insertNotice(mcwNotify.i18n.network, 'error');
		});
	});

	// ── Batch send (uses MainWP's row checkboxes) ─────────────────────────
	function sprintf(str, args) {
		var i = 0;
		return String(str)
			.replace(/%(\d+)\$d/g, function (_m, n) { return String(args[parseInt(n, 10) - 1]); })
			.replace(/%d/g, function () { return String(args[i++]); });
	}

	function findBodyTbody() {
		return document.getElementById('mainwp-manage-sites-body-table');
	}

	function getSelectedRows() {
		var tbody = findBodyTbody();
		if (!tbody) { return []; }
		var rows = [];
		tbody.querySelectorAll('tr.child-site input[type="checkbox"]').forEach(function (cb) {
			if (cb.checked) {
				var tr = cb.closest('tr.child-site');
				if (tr) { rows.push(tr); }
			}
		});
		return rows;
	}

	function updateBatchState(bar) {
		var btn    = bar.querySelector('.mcw-notify-batch-btn');
		var status = bar.querySelector('.mcw-notify-batch-status');
		var count  = getSelectedRows().length;
		btn.disabled = (count === 0) || bar.dataset.running === '1';
		status.textContent = sprintf(mcwNotify.i18n.batchCount, [count]);
	}

	function processBatch(bar, rows) {
		bar.dataset.running = '1';
		var btn = bar.querySelector('.mcw-notify-batch-btn');
		btn.disabled = true;

		var sent = 0, skipped = 0, failed = 0;
		var total = rows.length;

		function step(i) {
			if (i >= total) {
				bar.dataset.running = '0';
				btn.textContent = mcwNotify.i18n.batchBtn;
				// Clear the selection for every row we just processed. Semantic UI
				// mirrors state via a .checked class on the wrapper.
				rows.forEach(function (tr) {
					var cb = tr.querySelector('input[type="checkbox"]');
					if (!cb || !cb.checked) { return; }
					cb.checked = false;
					var wrap = cb.closest('.ui.checkbox');
					if (wrap) { wrap.classList.remove('checked'); }
					cb.dispatchEvent(new Event('change', { bubbles: true }));
				});
				updateBatchState(bar);
				insertNotice(
					sprintf(mcwNotify.i18n.batchSummary, [sent, skipped, failed]),
					failed > 0 ? 'error' : 'success'
				);
				return;
			}

			var tr   = rows[i];
			var link = tr.querySelector('a.mcw-notify-btn');
			btn.textContent = sprintf(mcwNotify.i18n.batchProgress, [i + 1, total]);

			// No notify button in this row means no owner email is set — skip.
			if (!link) {
				skipped++;
				setTimeout(function () { step(i + 1); }, 0);
				return;
			}

			var siteId = link.getAttribute('data-site-id');
			var nonce  = link.getAttribute('data-nonce');
			if (!siteId || !nonce) {
				skipped++;
				setTimeout(function () { step(i + 1); }, 0);
				return;
			}

			tr.classList.add('mcw-notify-row-processing');
			tr.classList.remove('mcw-notify-row-failed');
			var originalHtml = link.innerHTML;
			link.classList.add('mcw-notify-busy');
			link.setAttribute('aria-busy', 'true');
			link.textContent = mcwNotify.i18n.sending;

			var body = new URLSearchParams();
			body.append('action', 'mcw_ounm_send');
			body.append('site_id', siteId);
			body.append('nonce', nonce);

			fetch(mcwNotify.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Accept': 'application/json' },
				body: body
			}).then(function (r) {
				return r.json().then(function (j) { return { ok: r.ok, json: j }; })
				                .catch(function () { return { ok: r.ok, json: null }; });
			}).then(function (res) {
				tr.classList.remove('mcw-notify-row-processing');
				var payload = res.json || {};
				var data    = payload.data || {};
				var okFlag  = payload.success === true;
				var replaced = data.button_html ? replaceButton(link, data.button_html) : false;
				if (!replaced) { restoreButton(link, originalHtml); }
				if (okFlag) {
					sent++;
				} else {
					failed++;
					tr.classList.add('mcw-notify-row-failed');
				}
				// Small pacing gap between sends to be gentle on SMTP/Mailgun.
				setTimeout(function () { step(i + 1); }, 200);
			}).catch(function () {
				tr.classList.remove('mcw-notify-row-processing');
				tr.classList.add('mcw-notify-row-failed');
				restoreButton(link, originalHtml);
				failed++;
				setTimeout(function () { step(i + 1); }, 200);
			});
		}

		step(0);
	}

	function buildBar() {
		var bar = document.createElement('div');
		bar.className = 'mcw-notify-batch-bar';
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'button button-primary mcw-notify-batch-btn';
		btn.textContent = mcwNotify.i18n.batchBtn;
		btn.disabled = true;
		var status = document.createElement('span');
		status.className = 'mcw-notify-batch-status';
		status.textContent = sprintf(mcwNotify.i18n.batchCount, [0]);
		bar.appendChild(btn);
		bar.appendChild(status);

		btn.addEventListener('click', function () {
			if (bar.dataset.running === '1') { return; }
			var rows = getSelectedRows();
			if (rows.length === 0) {
				insertNotice(mcwNotify.i18n.batchNone, 'error');
				return;
			}
			if (!window.confirm(sprintf(mcwNotify.i18n.batchConfirm, [rows.length]))) { return; }
			processBatch(bar, rows);
		});

		return bar;
	}

	// Walk up from the sites <table> and pick an insertion point that sits
	// OUTSIDE any ancestor with overflow set (DataTables .dt-scroll-body,
	// Semantic UI wrappers, etc.), so the bar isn't clipped visually.
	function findInsertAnchor(table) {
		var anchor = table;
		var probe  = table.parentElement;
		while (probe && probe !== document.body) {
			var cs = getComputedStyle(probe);
			if (/(auto|scroll|hidden)/.test(cs.overflow + cs.overflowY + cs.overflowX)) {
				anchor = probe;
			}
			probe = probe.parentElement;
		}
		return anchor;
	}

	function adminBarOffset() {
		return (window.matchMedia && window.matchMedia('(max-width: 782px)').matches) ? 46 : 32;
	}

	// Total top offset = WP admin bar + MainWP's own sticky header (if visible).
	function computePinOffset() {
		var offset = adminBarOffset();
		var mw = document.getElementById('mainwp-top-header');
		if (mw) {
			var rect = mw.getBoundingClientRect();
			if (rect.bottom > offset) {
				offset = Math.round(rect.bottom);
			}
		}
		return offset;
	}

	function pinBar(bar, spacer) {
		if (bar.classList.contains('is-pinned')) { return; }
		var rect = spacer.getBoundingClientRect();
		spacer.style.height = bar.offsetHeight + 'px';
		bar.classList.add('is-pinned');
		bar.style.top   = computePinOffset() + 'px';
		bar.style.left  = rect.left + 'px';
		bar.style.width = rect.width + 'px';
	}

	function unpinBar(bar, spacer) {
		if (!bar.classList.contains('is-pinned')) { return; }
		bar.classList.remove('is-pinned');
		bar.style.top   = '';
		bar.style.left  = '';
		bar.style.width = '';
		spacer.style.height = '';
	}

	function mountBar(tbody) {
		var table = tbody.closest('table');
		if (!table) { return; }
		var anchor = findInsertAnchor(table);
		if (anchor.previousElementSibling && anchor.previousElementSibling.classList
			&& anchor.previousElementSibling.classList.contains('mcw-notify-batch-wrap')) {
			return;
		}

		// Wrapper holds a sentinel + spacer + bar. When the sentinel scrolls out
		// of view, the bar switches to position: fixed and the spacer keeps the
		// layout below from jumping.
		var wrap = document.createElement('div');
		wrap.className = 'mcw-notify-batch-wrap';

		var sentinel = document.createElement('div');
		sentinel.className = 'mcw-notify-batch-sentinel';
		sentinel.setAttribute('aria-hidden', 'true');
		sentinel.style.height = '1px';

		var spacer = document.createElement('div');
		spacer.className = 'mcw-notify-batch-spacer';
		spacer.setAttribute('aria-hidden', 'true');

		var bar = buildBar();

		wrap.appendChild(sentinel);
		wrap.appendChild(spacer);
		wrap.appendChild(bar);

		anchor.parentNode.insertBefore(wrap, anchor);

		// Recount when any row checkbox toggles.
		document.addEventListener('change', function (evt) {
			if (!evt.target || evt.target.type !== 'checkbox') { return; }
			if (!table.contains(evt.target)) { return; }
			updateBatchState(bar);
		});

		if ('IntersectionObserver' in window) {
			var offset = adminBarOffset();
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						unpinBar(bar, spacer);
					} else if (entry.boundingClientRect.top < offset) {
						pinBar(bar, spacer);
					}
				});
			}, { rootMargin: '-' + offset + 'px 0px 0px 0px', threshold: 0 });
			io.observe(sentinel);
		}

		window.addEventListener('resize', function () {
			if (bar.classList.contains('is-pinned')) {
				var rect = spacer.getBoundingClientRect();
				bar.style.top   = computePinOffset() + 'px';
				bar.style.left  = rect.left + 'px';
				bar.style.width = rect.width + 'px';
			}
		});

		window.addEventListener('scroll', function () {
			if (bar.classList.contains('is-pinned')) {
				bar.style.top = computePinOffset() + 'px';
			}
		}, { passive: true });
	}

	function whenTbodyReady(cb) {
		var tries = 0;
		(function poll() {
			var tbody = findBodyTbody();
			if (tbody && tbody.querySelector('tr.child-site')) { cb(tbody); return; }
			if (tries++ < 30) { setTimeout(poll, 300); }
		})();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { whenTbodyReady(mountBar); });
	} else {
		whenTbodyReady(mountBar);
	}
})();
