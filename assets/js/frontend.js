/**
 * Unstoppable Share Buttons - front end behaviour.
 *
 * Vanilla JavaScript, no dependencies. WordPress already ships jQuery and this
 * plugin has no reason to add to a page's weight.
 */
( function () {
	'use strict';

	var data = window.ushbData || {};
	var i18n = data.i18n || {};

	/* ------------------------------------------------------------------ *
	 * Small helpers
	 * ------------------------------------------------------------------ */

	function toast( message ) {
		var el = document.createElement( 'div' );
		el.className = 'ushb-toast';
		el.setAttribute( 'role', 'status' );
		el.textContent = message;
		document.body.appendChild( el );

		requestAnimationFrame( function () {
			el.classList.add( 'is-visible' );
		} );

		setTimeout( function () {
			el.classList.remove( 'is-visible' );
			setTimeout( function () {
				if ( el.parentNode ) {
					el.parentNode.removeChild( el );
				}
			}, 250 );
		}, 2600 );
	}

	function openPopup( url ) {
		var width = 640;
		var height = 560;
		var left = window.screenX + ( window.outerWidth - width ) / 2;
		var top = window.screenY + ( window.outerHeight - height ) / 2.4;
		var features = 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top +
			',toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1';

		var win = window.open( url, 'ushb-share', features );

		// Popup blocked, so fall back to a normal tab rather than doing nothing.
		if ( ! win || win.closed || typeof win.closed === 'undefined' ) {
			window.open( url, '_blank', 'noopener' );
			return;
		}

		win.focus();
	}

	/* ------------------------------------------------------------------ *
	 * Measurement. Each destination is opt-in and fires only if the site
	 * already loaded that vendor's script. Nothing is loaded here.
	 * ------------------------------------------------------------------ */

	function recordShare( network, placement, postId ) {
		if ( data.ga4 && typeof window.gtag === 'function' ) {
			window.gtag( 'event', 'share', {
				method: network,
				content_type: 'post',
				item_id: String( postId || '' )
			} );
		}

		if ( data.pixel && typeof window.fbq === 'function' ) {
			window.fbq( 'trackCustom', 'Share', { network: network, placement: placement } );
		}

		if ( data.dataLayer ) {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( {
				event: 'unstoppable_share',
				shareNetwork: network,
				sharePlacement: placement,
				sharePostId: postId
			} );
		}

		if ( ! data.track || ! data.ajaxUrl ) {
			return;
		}

		var body = new URLSearchParams();
		body.append( 'action', 'ushb_track_share' );
		body.append( 'nonce', data.nonce || '' );
		body.append( 'network', network );
		body.append( 'placement', placement || 'inline' );
		body.append( 'post_id', postId || 0 );

		// keepalive lets the request survive the page unloading behind a share.
		if ( window.fetch ) {
			window.fetch( data.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				keepalive: true
			} ).catch( function () {} );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Device actions
	 * ------------------------------------------------------------------ */

	function copyLink( url ) {
		function fallback() {
			var field = document.createElement( 'textarea' );
			field.value = url;
			field.setAttribute( 'readonly', '' );
			field.style.position = 'fixed';
			field.style.opacity = '0';
			document.body.appendChild( field );
			field.select();

			var ok = false;

			try {
				ok = document.execCommand( 'copy' );
			} catch ( e ) {
				ok = false;
			}

			document.body.removeChild( field );
			toast( ok ? i18n.copied : i18n.copyFailed );
		}

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( url ).then( function () {
				toast( i18n.copied );
			} ).catch( fallback );
			return;
		}

		fallback();
	}

	function nativeShare( url, title ) {
		if ( ! navigator.share ) {
			copyLink( url );
			return;
		}

		navigator.share( { title: title, url: url } ).catch( function () {} );
	}

	function shareToMastodon( url, title ) {
		var stored = '';

		try {
			stored = window.localStorage.getItem( 'ushb-mastodon-host' ) || '';
		} catch ( e ) {
			stored = '';
		}

		var host = window.prompt( i18n.mastodonPrompt, stored || 'mastodon.social' );

		if ( ! host ) {
			return;
		}

		host = host.trim().replace( /^https?:\/\//, '' ).replace( /\/+$/, '' );

		// Only a bare hostname is accepted, so this cannot be pointed anywhere odd.
		if ( ! /^[a-z0-9.-]+\.[a-z]{2,}$/i.test( host ) ) {
			return;
		}

		try {
			window.localStorage.setItem( 'ushb-mastodon-host', host );
		} catch ( e ) {}

		openPopup( 'https://' + host + '/share?text=' + encodeURIComponent( title + ' ' + url ) );
	}

	/* ------------------------------------------------------------------ *
	 * QR codes
	 * ------------------------------------------------------------------ */

	/**
	 * Minimal QR encoder: byte mode, error correction level L, versions 1-10.
	 * Block tables come from ISO/IEC 18004.
	 */
	function buildQr(text) {
		var TABLES = {
			1:  { ec: 7,  groups: [[1, 19]] },
			2:  { ec: 10, groups: [[1, 34]] },
			3:  { ec: 15, groups: [[1, 55]] },
			4:  { ec: 20, groups: [[1, 80]] },
			5:  { ec: 26, groups: [[1, 108]] },
			6:  { ec: 18, groups: [[2, 68]] },
			7:  { ec: 20, groups: [[2, 78]] },
			8:  { ec: 24, groups: [[2, 97]] },
			9:  { ec: 30, groups: [[2, 116]] },
			10: { ec: 18, groups: [[2, 68], [2, 69]] },
			11: { ec: 20, groups: [[4, 81]] },
			12: { ec: 24, groups: [[2, 92], [2, 93]] },
			13: { ec: 26, groups: [[4, 107]] },
			14: { ec: 30, groups: [[3, 115], [1, 116]] },
			15: { ec: 22, groups: [[5, 87], [1, 88]] }
		};
		var ALIGN = {
			1: [], 2: [6, 18], 3: [6, 22], 4: [6, 26], 5: [6, 30],
			6: [6, 34], 7: [6, 22, 38], 8: [6, 24, 42], 9: [6, 26, 46], 10: [6, 28, 50],
			11: [6, 30, 54], 12: [6, 32, 58], 13: [6, 34, 62],
			14: [6, 26, 46, 66], 15: [6, 26, 48, 70]
		};
		var REMAINDER = {
			1: 0, 2: 7, 3: 7, 4: 7, 5: 7, 6: 7,
			7: 0, 8: 0, 9: 0, 10: 0, 11: 0, 12: 0, 13: 0,
			14: 3, 15: 3
		};

		// --- Galois field ---
		var EXP = new Array(512), LOG = new Array(256);
		for (var i = 0, x = 1; i < 255; i++) {
			EXP[i] = x; LOG[x] = i;
			x <<= 1;
			if (x & 0x100) x ^= 0x11d;
		}
		for (i = 255; i < 512; i++) EXP[i] = EXP[i - 255];

		function mul(a, b) { return (a === 0 || b === 0) ? 0 : EXP[LOG[a] + LOG[b]]; }

		function genPoly(degree) {
			var poly = [1];
			for (var d = 0; d < degree; d++) {
				var next = new Array(poly.length + 1).fill(0);
				for (var j = 0; j < poly.length; j++) {
					next[j] ^= poly[j];
					next[j + 1] ^= mul(poly[j], EXP[d]);
				}
				poly = next;
			}
			return poly;
		}

		function ecc(data, count) {
			var poly = genPoly(count);
			var rem = data.slice().concat(new Array(count).fill(0));
			for (var i = 0; i < data.length; i++) {
				var factor = rem[i];
				if (factor === 0) continue;
				for (var j = 1; j < poly.length; j++) rem[i + j] ^= mul(poly[j], factor);
			}
			return rem.slice(data.length);
		}

		// --- UTF-8 bytes ---
		var bytes = [];
		var encoded = unescape(encodeURIComponent(text));
		for (i = 0; i < encoded.length; i++) bytes.push(encoded.charCodeAt(i) & 0xff);

		// --- pick a version ---
		var version = 0;
		for (var v = 1; v <= 15; v++) {
			var cap = 0;
			TABLES[v].groups.forEach(function (g) { cap += g[0] * g[1]; });
			var cc = v < 10 ? 8 : 16;
			if (4 + cc + bytes.length * 8 <= cap * 8) { version = v; break; }
		}
		if (!version) return null;

		var table = TABLES[version];
		var totalData = 0;
		table.groups.forEach(function (g) { totalData += g[0] * g[1]; });
		var ccBits = version < 10 ? 8 : 16;

		// --- bit stream ---
		var bits = [];
		function push(value, length) {
			for (var b = length - 1; b >= 0; b--) bits.push((value >> b) & 1);
		}
		push(4, 4);
		push(bytes.length, ccBits);
		for (i = 0; i < bytes.length; i++) push(bytes[i], 8);

		var capacityBits = totalData * 8;
		for (i = 0; i < 4 && bits.length < capacityBits; i++) bits.push(0);
		while (bits.length % 8 !== 0) bits.push(0);

		var codewords = [];
		for (i = 0; i < bits.length; i += 8) {
			var byte = 0;
			for (var k = 0; k < 8; k++) byte = (byte << 1) | bits[i + k];
			codewords.push(byte);
		}
		var padding = [0xec, 0x11], p = 0;
		while (codewords.length < totalData) codewords.push(padding[p++ % 2]);

		// --- split into blocks, add error correction ---
		var dataBlocks = [], ecBlocks = [], offset = 0;
		table.groups.forEach(function (g) {
			for (var n = 0; n < g[0]; n++) {
				var block = codewords.slice(offset, offset + g[1]);
				offset += g[1];
				dataBlocks.push(block);
				ecBlocks.push(ecc(block, table.ec));
			}
		});

		// --- interleave ---
		var final = [], maxData = 0;
		dataBlocks.forEach(function (b) { maxData = Math.max(maxData, b.length); });
		for (i = 0; i < maxData; i++) {
			for (var b = 0; b < dataBlocks.length; b++) {
				if (i < dataBlocks[b].length) final.push(dataBlocks[b][i]);
			}
		}
		for (i = 0; i < table.ec; i++) {
			for (b = 0; b < ecBlocks.length; b++) final.push(ecBlocks[b][i]);
		}

		var finalBits = [];
		final.forEach(function (byte) {
			for (var b = 7; b >= 0; b--) finalBits.push((byte >> b) & 1);
		});
		for (i = 0; i < REMAINDER[version]; i++) finalBits.push(0);

		// --- matrix ---
		var size = version * 4 + 17;
		var m = [], reserved = [];
		for (i = 0; i < size; i++) {
			m.push(new Array(size).fill(0));
			reserved.push(new Array(size).fill(false));
		}
		function set(r, c, value) { m[r][c] = value ? 1 : 0; reserved[r][c] = true; }

		function finder(row, col) {
			for (var r = -1; r <= 7; r++) {
				for (var c = -1; c <= 7; c++) {
					var rr = row + r, cc2 = col + c;
					if (rr < 0 || rr >= size || cc2 < 0 || cc2 >= size) continue;
					var dark = (r >= 0 && r <= 6 && (c === 0 || c === 6)) ||
						(c >= 0 && c <= 6 && (r === 0 || r === 6)) ||
						(r >= 2 && r <= 4 && c >= 2 && c <= 4);
					set(rr, cc2, dark);
				}
			}
		}
		finder(0, 0); finder(0, size - 7); finder(size - 7, 0);

		for (i = 8; i < size - 8; i++) {
			set(6, i, i % 2 === 0);
			set(i, 6, i % 2 === 0);
		}

		var positions = ALIGN[version];
		for (i = 0; i < positions.length; i++) {
			for (var j = 0; j < positions.length; j++) {
				var ar = positions[i], ac = positions[j];
				if ((ar <= 8 && ac <= 8) || (ar <= 8 && ac >= size - 9) || (ar >= size - 9 && ac <= 8)) continue;
				for (var dr = -2; dr <= 2; dr++) {
					for (var dc = -2; dc <= 2; dc++) {
						set(ar + dr, ac + dc, Math.max(Math.abs(dr), Math.abs(dc)) !== 1);
					}
				}
			}
		}

		set(size - 8, 8, true);

		// Reserve the format areas so data placement skips them.
		for (i = 0; i < 9; i++) {
			if (!reserved[8][i]) { reserved[8][i] = true; m[8][i] = 0; }
			if (!reserved[i][8]) { reserved[i][8] = true; m[i][8] = 0; }
		}
		for (i = 0; i < 8; i++) {
			if (!reserved[8][size - 1 - i]) { reserved[8][size - 1 - i] = true; m[8][size - 1 - i] = 0; }
			if (!reserved[size - 1 - i][8]) { reserved[size - 1 - i][8] = true; m[size - 1 - i][8] = 0; }
		}
		if (version >= 7) {
			for (i = 0; i < 6; i++) {
				for (j = 0; j < 3; j++) {
					reserved[size - 11 + j][i] = true; m[size - 11 + j][i] = 0;
					reserved[i][size - 11 + j] = true; m[i][size - 11 + j] = 0;
				}
			}
		}

		// --- place data in the zigzag ---
		var idx = 0, upward = true;
		for (var col = size - 1; col > 0; col -= 2) {
			if (col === 6) col--;
			for (var step = 0; step < size; step++) {
				var row = upward ? size - 1 - step : step;
				for (var side = 0; side < 2; side++) {
					var cc3 = col - side;
					if (reserved[row][cc3]) continue;
					m[row][cc3] = idx < finalBits.length ? finalBits[idx] : 0;
					idx++;
				}
			}
			upward = !upward;
		}

		// --- masking ---
		function maskFn(id, r, c) {
			switch (id) {
				case 0: return (r + c) % 2 === 0;
				case 1: return r % 2 === 0;
				case 2: return c % 3 === 0;
				case 3: return (r + c) % 3 === 0;
				case 4: return (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0;
				case 5: return ((r * c) % 2) + ((r * c) % 3) === 0;
				case 6: return (((r * c) % 2) + ((r * c) % 3)) % 2 === 0;
				case 7: return (((r + c) % 2) + ((r * c) % 3)) % 2 === 0;
			}
		}

		function penalty(grid) {
			var score = 0, r, c, run, i2;
			for (r = 0; r < size; r++) {
				run = 1;
				for (c = 1; c < size; c++) {
					if (grid[r][c] === grid[r][c - 1]) { run++; } else { if (run >= 5) score += run - 2; run = 1; }
				}
				if (run >= 5) score += run - 2;
			}
			for (c = 0; c < size; c++) {
				run = 1;
				for (r = 1; r < size; r++) {
					if (grid[r][c] === grid[r - 1][c]) { run++; } else { if (run >= 5) score += run - 2; run = 1; }
				}
				if (run >= 5) score += run - 2;
			}
			for (r = 0; r < size - 1; r++) {
				for (c = 0; c < size - 1; c++) {
					var s = grid[r][c] + grid[r][c + 1] + grid[r + 1][c] + grid[r + 1][c + 1];
					if (s === 0 || s === 4) score += 3;
				}
			}
			var pat1 = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
			var pat2 = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
			function match(line, pat) {
				for (var k2 = 0; k2 < 11; k2++) if (line[k2] !== pat[k2]) return false;
				return true;
			}
			for (r = 0; r < size; r++) {
				for (c = 0; c + 11 <= size; c++) {
					var h = grid[r].slice(c, c + 11);
					if (match(h, pat1) || match(h, pat2)) score += 40;
				}
			}
			for (c = 0; c < size; c++) {
				for (r = 0; r + 11 <= size; r++) {
					var vline = [];
					for (i2 = 0; i2 < 11; i2++) vline.push(grid[r + i2][c]);
					if (match(vline, pat1) || match(vline, pat2)) score += 40;
				}
			}
			var dark = 0;
			for (r = 0; r < size; r++) for (c = 0; c < size; c++) dark += grid[r][c];
			var pct = (dark * 100) / (size * size);
			score += Math.floor(Math.abs(pct - 50) / 5) * 10;
			return score;
		}

		function formatBits(mask) {
			var data = (0x01 << 3) | mask; // 0b01 is level L.
			var rem = data << 10;
			for (var b = 14; b >= 10; b--) {
				if ((rem >> b) & 1) rem ^= 0x537 << (b - 10);
			}
			return ((data << 10) | rem) ^ 0x5412;
		}

		function versionBits(ver) {
			var rem = ver << 12;
			for (var b = 17; b >= 12; b--) {
				if ((rem >> b) & 1) rem ^= 0x1f25 << (b - 12);
			}
			return (ver << 12) | rem;
		}

		function build(mask) {
			var grid = [];
			for (var r = 0; r < size; r++) grid.push(m[r].slice());
			for (r = 0; r < size; r++) {
				for (var c = 0; c < size; c++) {
					if (!reserved[r][c] && maskFn(mask, r, c)) grid[r][c] ^= 1;
				}
			}
			var fmt = formatBits(mask);
			for (var i3 = 0; i3 < 15; i3++) {
				var bit = (fmt >> i3) & 1;
				if (i3 < 6) grid[i3][8] = bit;
				else if (i3 === 6) grid[7][8] = bit;
				else if (i3 === 7) grid[8][8] = bit;
				else if (i3 === 8) grid[8][7] = bit;
				else grid[8][14 - i3] = bit;

				if (i3 < 8) grid[8][size - 1 - i3] = bit;
				else grid[size - 15 + i3][8] = bit;
			}
			grid[size - 8][8] = 1;
			if (version >= 7) {
				var vb = versionBits(version);
				for (i3 = 0; i3 < 18; i3++) {
					var vbit = (vb >> i3) & 1;
					var rr2 = Math.floor(i3 / 3), cc4 = i3 % 3;
					grid[size - 11 + cc4][rr2] = vbit;
					grid[rr2][size - 11 + cc4] = vbit;
				}
			}
			return grid;
		}

		var best = null, bestScore = Infinity;
		for (var mask = 0; mask < 8; mask++) {
			var candidate = build(mask);
			var score = penalty(candidate);
			if (score < bestScore) { bestScore = score; best = candidate; }
		}
		return { size: size, modules: best, version: version };
	}

	function qrCanvas( text ) {
		var result = buildQr( text );

		if ( ! result ) {
			return null;
		}

		var quiet = 4;
		var scale = 6;
		var total = ( result.size + quiet * 2 ) * scale;
		var canvas = document.createElement( 'canvas' );

		canvas.width = total;
		canvas.height = total;

		var ctx = canvas.getContext( '2d' );
		ctx.fillStyle = '#ffffff';
		ctx.fillRect( 0, 0, total, total );
		ctx.fillStyle = '#000000';

		for ( var r = 0; r < result.size; r++ ) {
			for ( var c = 0; c < result.size; c++ ) {
				if ( result.modules[ r ][ c ] ) {
					ctx.fillRect( ( c + quiet ) * scale, ( r + quiet ) * scale, scale, scale );
				}
			}
		}

		return canvas;
	}

	function showQr( url ) {
		var modal = document.createElement( 'div' );
		modal.className = 'ushb-modal';
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		modal.setAttribute( 'aria-label', i18n.qrTitle || 'QR code' );

		var backdrop = document.createElement( 'div' );
		backdrop.className = 'ushb-modal__backdrop';

		var panel = document.createElement( 'div' );
		panel.className = 'ushb-modal__panel';

		var head = document.createElement( 'div' );
		head.className = 'ushb-modal__head';

		var title = document.createElement( 'h2' );
		title.textContent = i18n.qrTitle || '';

		var close = document.createElement( 'button' );
		close.type = 'button';
		close.className = 'ushb-modal__close';
		close.setAttribute( 'aria-label', i18n.close || 'Close' );
		close.innerHTML = '&times;';

		head.appendChild( title );
		head.appendChild( close );

		var body = document.createElement( 'div' );
		body.className = 'ushb-qr';

		var canvas = qrCanvas( url );

		if ( canvas ) {
			body.appendChild( canvas );
		} else {
			var warn = document.createElement( 'p' );
			warn.textContent = i18n.qrUnavailable || '';
			body.appendChild( warn );
		}

		var link = document.createElement( 'p' );
		link.className = 'ushb-qr__url';
		link.textContent = url;
		body.appendChild( link );

		panel.appendChild( head );
		panel.appendChild( body );
		modal.appendChild( backdrop );
		modal.appendChild( panel );
		document.body.appendChild( modal );

		function dismiss() {
			if ( modal.parentNode ) {
				modal.parentNode.removeChild( modal );
			}

			document.removeEventListener( 'keydown', onKey );
		}

		function onKey( event ) {
			if ( 'Escape' === event.key ) {
				dismiss();
			}
		}

		backdrop.addEventListener( 'click', dismiss );
		close.addEventListener( 'click', dismiss );
		document.addEventListener( 'keydown', onKey );
		close.focus();
	}

	/* ------------------------------------------------------------------ *
	 * Dialogs
	 * ------------------------------------------------------------------ */

	var lastTrigger = null;

	function openDialog( dialog ) {
		dialog.hidden = false;
		lastTrigger = document.activeElement;

		var focusable = dialog.querySelector( '.ushb-modal__close' );

		if ( focusable ) {
			focusable.focus();
		}

		document.addEventListener( 'keydown', dialogKeys );
	}

	function closeDialog( dialog ) {
		dialog.hidden = true;
		document.removeEventListener( 'keydown', dialogKeys );

		if ( lastTrigger && lastTrigger.focus ) {
			lastTrigger.focus();
		}
	}

	function dialogKeys( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}

		var open = document.querySelector( '.ushb-modal:not([hidden])' );

		if ( open ) {
			closeDialog( open );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Wiring
	 * ------------------------------------------------------------------ */

	function onClick( event ) {
		var trigger = event.target.closest( '.ushb-popup__trigger' );

		if ( trigger ) {
			var dialog = document.getElementById( trigger.getAttribute( 'data-dialog' ) );

			if ( dialog ) {
				event.preventDefault();
				openDialog( dialog );
			}

			return;
		}

		if ( event.target.closest( '[data-close]' ) ) {
			var owner = event.target.closest( '.ushb-modal' );

			if ( owner ) {
				event.preventDefault();
				closeDialog( owner );
			}

			return;
		}

		var button = event.target.closest( '[data-network]' );

		if ( ! button ) {
			return;
		}

		var network = button.getAttribute( 'data-network' );
		var placement = button.getAttribute( 'data-place' ) || 'inline';
		var postId = button.getAttribute( 'data-post' ) || 0;
		var action = button.getAttribute( 'data-action' );
		var url = button.getAttribute( 'data-url' ) || window.location.href;
		var title = button.getAttribute( 'data-title' ) || document.title;

		if ( action ) {
			event.preventDefault();

			switch ( action ) {
				case 'copy':
					copyLink( url );
					break;
				case 'print':
					window.print();
					break;
				case 'native':
					nativeShare( url, title );
					break;
				case 'qr':
					showQr( url );
					break;
				case 'mastodon':
					shareToMastodon( url, title );
					break;
				case 'bookmark':
					toast( i18n.bookmarkHint );
					break;
				default:
					return;
			}

			recordShare( network, placement, postId );
			return;
		}

		if ( '1' === button.getAttribute( 'data-popup' ) ) {
			event.preventDefault();
			openPopup( button.getAttribute( 'href' ) );
		}

		recordShare( network, placement, postId );
	}

	/**
	 * Shows the floating and sticky bars only at the widths they suit.
	 */
	function applyBreakpoints() {
		var width = window.innerWidth;

		var floating = document.querySelector( '.ushb-floating-wrap' );

		if ( floating ) {
			floating.classList.toggle( 'is-visible', width >= ( data.floatMin || 1024 ) );
		}

		var sticky = document.querySelector( '.ushb-sticky-wrap' );

		if ( sticky ) {
			sticky.classList.toggle( 'is-visible', width <= ( data.stickyMax || 782 ) );
		}
	}

	function init() {
		// The device share sheet button stays hidden unless the browser has one.
		if ( navigator.share ) {
			document.documentElement.classList.add( 'ushb-has-native' );
		}

		document.addEventListener( 'click', onClick );

		applyBreakpoints();

		var timer = null;
		window.addEventListener( 'resize', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( applyBreakpoints, 150 );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
