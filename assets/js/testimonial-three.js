/**
 * Super Widget - Testimonial 3
 * Reveals the cards and the summary bar on scroll. Nothing else: the layout
 * is plain CSS. No external requests, no HTML injection.
 */
( function ( $ ) {
	'use strict';

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-t3' ) : null;
		if ( ! root || root.getAttribute( 'data-ready' ) === '1' ) {
			return;
		}
		root.setAttribute( 'data-ready', '1' );

		var once = root.getAttribute( 'data-once' ) !== 'no';
		var items = Array.prototype.slice.call( root.querySelectorAll( '.spw-t3__card, .spw-t3__bar' ) );
		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );

		root.classList.add( 'spw-js' );

		// In the editor (or without IntersectionObserver) show everything straight away.
		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			items.forEach( function ( el, i ) {
				el.style.setProperty( '--spw-i', i );
				el.classList.add( 'is-visible' );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				var batch = 0;

				entries.forEach( function ( entry ) {
					var el = entry.target;

					if ( entry.isIntersecting ) {
						el.style.setProperty( '--spw-i', batch++ );
						el.classList.add( 'is-visible' );
						if ( once ) {
							observer.unobserve( el );
						}
					} else if ( ! once ) {
						el.classList.remove( 'is-visible' );
					}
				} );
			},
			{ threshold: 0.12, rootMargin: '0px 0px -30px 0px' }
		);

		items.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-testimonial-three.default', init );
	} );
} )( jQuery );
