/**
 * Super Widget - Image Shapes
 * Only job: play the entrance animation when the widget scrolls into view.
 * No external requests, no eval, no HTML injection.
 */
( function ( $ ) {
	'use strict';

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-is' ) : null;
		if ( ! root ) {
			return;
		}

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );
		root.classList.add( 'spw-js' );

		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			root.classList.add( 'is-in' );
			return;
		}

		var io = new IntersectionObserver(
			function ( entries ) {
				if ( entries[ 0 ].isIntersecting ) {
					root.classList.add( 'is-in' );
					io.disconnect();
				}
			},
			{ threshold: 0.12 }
		);
		io.observe( root );
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-image-shapes.default', init );
	} );
} )( jQuery );
