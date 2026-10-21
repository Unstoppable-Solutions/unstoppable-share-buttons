/**
 * Unstoppable Share Buttons - admin notice dismissal.
 */
( function () {
	'use strict';

	var config = window.ushbNotices || {};

	function dismiss( notice, announcementId ) {
		if ( ! config.ajaxUrl || ! window.fetch ) {
			return;
		}

		var body = new URLSearchParams();
		body.append( 'action', 'ushb_dismiss_notice' );
		body.append( 'nonce', config.nonce || '' );
		body.append( 'notice', notice );

		if ( announcementId ) {
			body.append( 'announcement_id', announcementId );
		}

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin'
		} ).catch( function () {} );
	}

	document.addEventListener( 'click', function ( event ) {
		var manual = event.target.closest( '.ushb-dismiss-notice' );

		if ( manual ) {
			event.preventDefault();

			var owner = manual.closest( '.ushb-notice' );
			dismiss( manual.getAttribute( 'data-notice' ), owner ? owner.getAttribute( 'data-announcement-id' ) : '' );

			if ( owner ) {
				owner.style.display = 'none';
			}

			return;
		}

		// WordPress's own dismiss button on our notices.
		if ( event.target.classList.contains( 'notice-dismiss' ) ) {
			var notice = event.target.closest( '.ushb-notice' );

			if ( notice ) {
				dismiss( notice.getAttribute( 'data-notice' ), notice.getAttribute( 'data-announcement-id' ) );
			}
		}
	} );
}() );
