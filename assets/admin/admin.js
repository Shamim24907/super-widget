/**
 * Super Widget - admin dashboard helpers (no dependencies).
 */
( function () {
	'use strict';

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var root = document.querySelector( '.spw-admin' );
		if ( ! root ) {
			return;
		}

		/* Widget filters: search + category. Hidden cards stay in the form, so saving never loses a setting. */
		var search = root.querySelector( '#spw-widget-search' );
		var groups = root.querySelectorAll( '#spw-widgets .spw-group' );
		var chips = root.querySelectorAll( '[data-spw-cat-filter]' );
		var empty = root.querySelector( '.spw-empty' );
		var state = { cat: 'all', term: '' };

		function apply() {
			var shown = 0;
			Array.prototype.forEach.call( groups, function ( group ) {
				var inCat = state.cat === 'all' || group.getAttribute( 'data-spw-group' ) === state.cat;
				var any = 0;
				Array.prototype.forEach.call( group.querySelectorAll( '.spw-wcard' ), function ( card ) {
					var hay = card.getAttribute( 'data-search' ) || '';
					var match = inCat && ( state.term === '' || hay.indexOf( state.term ) !== -1 );
					card.classList.toggle( 'is-hidden', ! match );
					if ( match ) {
						any++;
					}
				} );
				group.classList.toggle( 'is-hidden', any === 0 );
				shown += any;
			} );
			if ( empty ) {
				empty.hidden = shown !== 0;
			}
			Array.prototype.forEach.call( chips, function ( chip ) {
				var on = chip.getAttribute( 'data-spw-cat-filter' ) === state.cat;
				chip.classList.toggle( 'is-active', on );
				chip.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );
		}

		function setCat( cat ) {
			state.cat = cat;
			apply();
			if ( window.history && window.history.replaceState ) {
				try {
					var url = new URL( window.location.href );
					if ( cat === 'all' ) {
						url.searchParams.delete( 'cat' );
					} else {
						url.searchParams.set( 'cat', cat );
					}
					window.history.replaceState( null, '', url.toString() );
				} catch ( e ) {}
			}
		}

		if ( groups.length ) {
			var start = root.getAttribute( 'data-spw-cat' ) || 'all';
			var known = Array.prototype.some.call( chips, function ( c ) {
				return c.getAttribute( 'data-spw-cat-filter' ) === start;
			} );
			state.cat = known ? start : 'all';
			apply();
		}

		Array.prototype.forEach.call( chips, function ( chip ) {
			chip.addEventListener( 'click', function () {
				setCat( chip.getAttribute( 'data-spw-cat-filter' ) );
			} );
		} );

		if ( search ) {
			search.addEventListener( 'input', function () {
				state.term = search.value.toLowerCase().trim();
				apply();
			} );
		}

		/* Enable / disable all (only the cards you can see) */
		Array.prototype.forEach.call( root.querySelectorAll( '[data-spw-toggle-all]' ), function ( btn ) {
			btn.addEventListener( 'click', function () {
				var on = btn.getAttribute( 'data-spw-toggle-all' ) === 'on';
				Array.prototype.forEach.call( root.querySelectorAll( '#spw-widgets .spw-wcard:not(.is-hidden) input[type="checkbox"]' ), function ( box ) {
					box.checked = on;
				} );
			} );
		} );

		/* Enable / disable one category */
		Array.prototype.forEach.call( root.querySelectorAll( '[data-spw-group-toggle]' ), function ( btn ) {
			btn.addEventListener( 'click', function () {
				var on = btn.getAttribute( 'data-spw-group-toggle' ) === 'on';
				var group = btn.closest( '.spw-group' );
				if ( ! group ) {
					return;
				}
				Array.prototype.forEach.call( group.querySelectorAll( '.spw-wcard:not(.is-hidden) input[type="checkbox"]' ), function ( box ) {
					box.checked = on;
				} );
			} );
		} );

		/* Color picker <-> text field */
		var picker = root.querySelector( '.spw-color-picker' );
		var text = root.querySelector( '.spw-color-text' );
		if ( picker && text ) {
			picker.addEventListener( 'input', function () {
				text.value = picker.value;
			} );
			text.addEventListener( 'input', function () {
				if ( /^#[0-9a-fA-F]{6}$/.test( text.value ) ) {
					picker.value = text.value;
				}
			} );
		}

		var clear = root.querySelector( '[data-spw-clear-color]' );
		if ( clear && text ) {
			clear.addEventListener( 'click', function () {
				text.value = '';
			} );
		}

		/* Confirm dialogs */
		Array.prototype.forEach.call( root.querySelectorAll( 'form[data-spw-confirm]' ), function ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				if ( ! window.confirm( form.getAttribute( 'data-spw-confirm' ) ) ) {
					event.preventDefault();
				}
			} );
		} );

		/* Copy to clipboard */
		Array.prototype.forEach.call( root.querySelectorAll( '[data-spw-copy]' ), function ( btn ) {
			btn.addEventListener( 'click', function () {
				var target = root.querySelector( btn.getAttribute( 'data-spw-copy' ) );
				if ( ! target ) {
					return;
				}
				target.select();
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( target.value );
				} else {
					document.execCommand( 'copy' );
				}
			} );
		} );
	} );
} )();
