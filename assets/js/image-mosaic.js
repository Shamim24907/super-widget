/**
 * Super Widget - Image Mosaic
 * Starts the entrance animation when the mosaic scrolls into view.
 * No external requests, no eval, no HTML injection (only class changes).
 */
( function ( $ ) {
	'use strict';

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-im' ) : null;
		if ( ! root ) {
			return;
		}

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );
		var once = root.getAttribute( 'data-once' ) !== 'no';

		root.classList.add( 'spw-js' );

		// In the editor (or without IntersectionObserver) show it straight away.
		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			root.classList.add( 'is-visible' );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						root.classList.add( 'is-visible' );
						if ( once ) {
							observer.disconnect();
						}
					} else if ( ! once ) {
						root.classList.remove( 'is-visible' );
					}
				} );
			},
			{ threshold: 0.2 }
		);

		observer.observe( root );
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-image-mosaic.default', init );
	} );
} )( jQuery );
