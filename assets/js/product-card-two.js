/**
 * Super Widget - Product Card 2
 * Entrance animation on scroll and ajax add to cart.
 * No external requests, no eval, no HTML injection (only class/attribute/text changes).
 */
( function ( $ ) {
	'use strict';

	function revealCards( grid, isEditor ) {
		var cards = Array.prototype.slice.call( grid.querySelectorAll( '.spw-pc2__card' ) );
		var once = grid.getAttribute( 'data-once' ) !== 'no';

		grid.classList.add( 'spw-js' );

		// In the editor (or without IntersectionObserver) show cards straight away.
		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			cards.forEach( function ( card, i ) {
				card.style.setProperty( '--spw-i', i );
				card.classList.add( 'is-visible' );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				var batch = 0;

				entries.forEach( function ( entry ) {
					var card = entry.target;

					if ( entry.isIntersecting ) {
						card.style.setProperty( '--spw-i', batch++ );
						card.classList.add( 'is-visible' );
						if ( once ) {
							observer.unobserve( card );
						}
					} else if ( ! once ) {
						card.classList.remove( 'is-visible' );
					}
				} );
			},
			{ threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
		);

		cards.forEach( function ( card ) {
			observer.observe( card );
		} );
	}

	/* WooCommerce add to cart over wc-ajax. Falls back to the normal link. */
	function addToCart( grid, btn ) {
		var ajaxUrl = grid.getAttribute( 'data-ajax' );
		if ( ! ajaxUrl || btn.hasAttribute( 'disabled' ) || btn.classList.contains( 'is-loading' ) ) {
			return;
		}

		var label = btn.querySelector( '.spw-pc2__btn-text' );
		var original = btn.getAttribute( 'data-label' ) || ( label ? label.textContent : '' );
		var body = new URLSearchParams();
		body.set( 'product_id', btn.getAttribute( 'data-product-id' ) );
		body.set( 'quantity', '1' );
		if ( btn.getAttribute( 'data-variation-id' ) && btn.getAttribute( 'data-variation-id' ) !== '0' ) {
			body.set( 'variation_id', btn.getAttribute( 'data-variation-id' ) );
		}

		btn.classList.add( 'is-loading' );

		window.fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) {
				return res.json();
			} )
			.then( function ( data ) {
				btn.classList.remove( 'is-loading' );

				// Variable products need options: send the visitor to the product page.
				if ( data && data.error && data.product_url ) {
					window.location.href = data.product_url;
					return;
				}

				if ( window.jQuery ) {
					window.jQuery( document.body ).trigger( 'added_to_cart', [ data.fragments, data.cart_hash, window.jQuery( btn ) ] );
				}

				var cart = grid.getAttribute( 'data-cart' );
				if ( cart ) {
					window.location.href = cart;
					return;
				}

				var added = grid.getAttribute( 'data-added' );
				if ( label && added ) {
					label.textContent = added;
					window.setTimeout( function () {
						label.textContent = original;
					}, 2000 );
				}
			} )
			.catch( function () {
				btn.classList.remove( 'is-loading' );
				window.location.href = btn.getAttribute( 'href' );
			} );
	}

	function init( $scope ) {
		var grid = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-pc2' ) : null;
		if ( ! grid ) {
			return;
		}

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );

		revealCards( grid, isEditor );

		grid.addEventListener( 'click', function ( event ) {
			var target = event.target;
			var btn = target && target.closest ? target.closest( '.spw-pc2__btn[data-product-id]' ) : null;

			if ( btn && grid.contains( btn ) ) {
				event.preventDefault();
				if ( ! isEditor ) {
					addToCart( grid, btn );
				}
			}
		} );
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-product-card-two.default', init );
	} );
} )( jQuery );
