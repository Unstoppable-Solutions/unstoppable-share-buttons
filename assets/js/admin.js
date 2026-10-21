/**
 * Unstoppable Share Buttons - admin behaviour.
 */
( function () {
	'use strict';

	var config = window.ushbAdmin || {};

	/* ------------------------------------------------------------------ *
	 * Destructive actions ask first
	 * ------------------------------------------------------------------ */

	function guardForms() {
		document.querySelectorAll( '[data-confirm]' ).forEach( function ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				var kind = form.getAttribute( 'data-confirm' );
				var message = 'stats' === kind ? config.confirmResetStats : config.confirmResetSettings;

				if ( ! window.confirm( message ) ) {
					event.preventDefault();
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Network picker: add, remove, reorder
	 * ------------------------------------------------------------------ */

	function chipMarkup( chip, chosen, fieldName ) {
		var slug = chip.getAttribute( 'data-network' );
		var icon = chip.querySelector( '.ushb-chip__icon' );
		var label = chip.getAttribute( 'data-label' );
		var note = chip.querySelector( '.ushb-chip__note' );

		var next = document.createElement( 'li' );
		next.className = 'ushb-chip ushb-chip--' + slug + ( chosen ? ' is-chosen' : '' );
		next.setAttribute( 'data-network', slug );
		next.setAttribute( 'data-label', label );
		next.setAttribute( 'data-group', chip.getAttribute( 'data-group' ) );

		if ( chosen ) {
			next.setAttribute( 'draggable', 'true' );
			var grip = document.createElement( 'span' );
			grip.className = 'ushb-chip__grip';
			grip.setAttribute( 'aria-hidden', 'true' );
			next.appendChild( grip );
		}

		var iconWrap = document.createElement( 'span' );
		iconWrap.className = 'ushb-chip__icon';
		iconWrap.innerHTML = icon ? icon.innerHTML : '';
		next.appendChild( iconWrap );

		var labelWrap = document.createElement( 'span' );
		labelWrap.className = 'ushb-chip__label';
		labelWrap.textContent = label;
		next.appendChild( labelWrap );

		if ( note ) {
			next.appendChild( note.cloneNode( true ) );
		}

		var toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.className = 'ushb-chip__toggle';
		toggle.innerHTML = chosen ? '&times;' : '+';
		next.appendChild( toggle );

		if ( chosen ) {
			var input = document.createElement( 'input' );
			input.type = 'hidden';
			input.name = fieldName;
			input.value = slug;
			next.appendChild( input );
		}

		return next;
	}

	function refreshPicker( picker ) {
		var list = picker.querySelector( '[data-sortable]' );
		var count = picker.querySelector( '.ushb-count' );
		var empty = picker.querySelector( '.ushb-picker__empty' );
		var total = list ? list.children.length : 0;

		if ( count ) {
			count.textContent = total;
		}

		if ( empty ) {
			empty.hidden = total > 0;
		}
	}

	function wirePicker( picker ) {
		var fieldName = picker.getAttribute( 'data-field' );
		var chosenList = picker.querySelector( '[data-sortable]' );
		var available = picker.querySelector( '.ushb-picker__available' );

		picker.addEventListener( 'click', function ( event ) {
			var toggle = event.target.closest( '.ushb-chip__toggle' );

			if ( ! toggle ) {
				return;
			}

			event.preventDefault();

			var chip = toggle.closest( '.ushb-chip' );
			var isChosen = chip.classList.contains( 'is-chosen' );

			if ( isChosen ) {
				// Return it to its group in the available list.
				var group = chip.getAttribute( 'data-group' );
				var target = available.querySelector( '.ushb-grid[data-group="' + group + '"]' );

				if ( ! target ) {
					target = available.querySelector( '.ushb-grid' );
				}

				if ( target ) {
					target.appendChild( chipMarkup( chip, false, fieldName ) );
				}

				chip.remove();
			} else {
				chosenList.appendChild( chipMarkup( chip, true, fieldName ) );
				chip.remove();
			}

			refreshPicker( picker );
		} );

		if ( ! chosenList ) {
			return;
		}

		var dragged = null;

		chosenList.addEventListener( 'dragstart', function ( event ) {
			var chip = event.target.closest( '.ushb-chip' );

			if ( ! chip ) {
				return;
			}

			dragged = chip;
			chip.classList.add( 'is-dragging' );
			event.dataTransfer.effectAllowed = 'move';
			// Firefox needs data set for a drag to begin at all.
			event.dataTransfer.setData( 'text/plain', chip.getAttribute( 'data-network' ) );
		} );

		chosenList.addEventListener( 'dragend', function () {
			if ( dragged ) {
				dragged.classList.remove( 'is-dragging' );
			}

			dragged = null;
		} );

		chosenList.addEventListener( 'dragover', function ( event ) {
			event.preventDefault();

			if ( ! dragged ) {
				return;
			}

			var over = event.target.closest( '.ushb-chip' );

			if ( ! over || over === dragged ) {
				return;
			}

			var rect = over.getBoundingClientRect();
			var after = ( event.clientY - rect.top ) > ( rect.height / 2 );

			chosenList.insertBefore( dragged, after ? over.nextSibling : over );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Live preview
	 * ------------------------------------------------------------------ */

	var CLASS_KEYS = {
		shape: 'ushb--shape-',
		style: 'ushb--style-',
		labels: 'ushb--labels-',
		hover_effect: 'ushb--hover-',
		inline_align: 'ushb--align-',
		palette: 'ushb--palette-',
		color_scheme: 'ushb--scheme-'
	};

	function updatePreview() {
		var bar = document.querySelector( '.ushb-preview__stage .ushb' );

		if ( ! bar ) {
			return;
		}

		Object.keys( CLASS_KEYS ).forEach( function ( key ) {
			var field = document.querySelector( '[data-key="' + key + '"]:checked' ) ||
				document.querySelector( 'select[data-key="' + key + '"]' );

			if ( ! field ) {
				return;
			}

			var prefix = CLASS_KEYS[ key ];

			bar.className = bar.className
				.split( ' ' )
				.filter( function ( name ) {
					return name.indexOf( prefix ) !== 0;
				} )
				.join( ' ' );

			bar.classList.add( prefix + field.value );
		} );

		[ [ 'size', '--ushb-size' ], [ 'gap', '--ushb-gap' ], [ 'radius', '--ushb-radius' ] ].forEach( function ( pair ) {
			var field = document.querySelector( '[data-key="' + pair[ 0 ] + '"]' );

			if ( field ) {
				bar.style.setProperty( pair[ 1 ], parseInt( field.value, 10 ) + 'px' );
			}
		} );

		var color = document.querySelector( '[data-key="custom_color"]' );
		var textColor = document.querySelector( '[data-key="custom_text_color"]' );
		var palette = document.querySelector( '[data-key="palette"]:checked' );

		if ( color && palette && 'custom' === palette.value ) {
			bar.style.setProperty( '--ushb-brand', color.value );

			if ( textColor ) {
				bar.style.setProperty( '--ushb-on-brand', textColor.value );
			}
		} else {
			bar.style.removeProperty( '--ushb-brand' );
			bar.style.removeProperty( '--ushb-on-brand' );
		}

		if ( color ) {
			var swatch = color.parentNode.querySelector( 'code' );

			if ( swatch ) {
				swatch.textContent = color.value;
			}
		}
	}

	function init() {
		guardForms();

		document.querySelectorAll( '[data-picker]' ).forEach( wirePicker );

		document.addEventListener( 'input', updatePreview );
		document.addEventListener( 'change', updatePreview );

		updatePreview();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
