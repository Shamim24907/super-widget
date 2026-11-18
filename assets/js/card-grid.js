/**
 * Super Widget - Card Grid
 * Entrance animation on scroll, variant switching, ajax add to cart, wishlist.
 * No external requests, no eval, no HTML injection (only class/attribute changes).
 */
( function ( $ ) {
	'use strict';

	function revealCards( grid, isEditor ) {
		var cards = Array.prototype.slice.call( grid.querySelectorAll( '.spw-card' ) );
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

	var WISH_KEY = 'spw_wishlist';

	function readWishlist() {
		try {
			var raw = window.localStorage.getItem( WISH_KEY );
			var list = raw ? JSON.parse( raw ) : [];
			return Array.isArray( list ) ? list : [];
		} catch ( e ) {
			return [];
		}
	}

	function writeWishlist( list ) {
		try {
			window.localStorage.setItem( WISH_KEY, JSON.stringify( list ) );
		} catch ( e ) {}
	}

	function restoreWishlist( grid ) {
		var list = readWishlist();
		if ( ! list.length ) {
			return;
		}
		Array.prototype.forEach.call( grid.querySelectorAll( '.spw-card__wish[data-product-id]' ), function ( btn ) {
			if ( list.indexOf( btn.getAttribute( 'data-product-id' ) ) !== -1 ) {
				btn.classList.add( 'is-active' );
				btn.setAttribute( 'aria-pressed', 'true' );
			}
		} );
	}

	/* Variant chip: update price, old price, badge, image and button. */
	function selectChip( chip ) {
		var card = chip.closest( '.spw-card' );
		var group = chip.parentNode;

		Array.prototype.forEach.call( group.querySelectorAll( '.spw-chip' ), function ( el ) {
			el.classList.remove( 'is-active' );
			el.setAttribute( 'aria-pressed', 'false' );
		} );
		chip.classList.add( 'is-active' );
		chip.setAttribute( 'aria-pressed', 'true' );

		// Plain custom chips carry no variation data: nothing else to update.
		if ( ! chip.hasAttribute( 'data-variation-id' ) || ! card ) {
			return;
		}

		var price = card.querySelector( '.spw-card__price' );
		if ( price && chip.getAttribute( 'data-price' ) ) {
			price.textContent = chip.getAttribute( 'data-price' );
		}

		var old = card.querySelector( '.spw-card__old-price' );
		if ( old ) {
			var oldText = chip.getAttribute( 'data-old' );
			old.textContent = oldText ? oldText : '\u00a0';
			old.classList.toggle( 'is-empty', ! oldText );
		}

		var badge = card.querySelector( '.spw-card__badge[data-sale]' );
		if ( badge ) {
			badge.hidden = chip.getAttribute( 'data-sale' ) !== '1';
		}

		var imgUrl = chip.getAttribute( 'data-img' );
		var img = card.querySelector( '.spw-card__media img' );
		if ( img && imgUrl ) {
			img.removeAttribute( 'srcset' );
			img.removeAttribute( 'sizes' );
			img.src = imgUrl;
		}

		var btn = card.querySelector( '.spw-card__btn[data-product-id]' );
		if ( btn ) {
			var inStock = chip.getAttribute( 'data-stock' ) === '1';
			var label = btn.querySelector( '.spw-card__btn-text' );
			btn.setAttribute( 'data-variation-id', chip.getAttribute( 'data-variation-id' ) );
			if ( inStock ) {
				btn.removeAttribute( 'disabled' );
				btn.removeAttribute( 'aria-disabled' );
				if ( label ) {
					label.textContent = btn.getAttribute( 'data-label' ) || label.textContent;
				}
			} else {
				btn.setAttribute( 'disabled', 'disabled' );
				btn.setAttribute( 'aria-disabled', 'true' );
				if ( label ) {
					label.textContent = btn.getAttribute( 'data-oos' ) || 'Out of stock';
				}
			}
		}
	}

	/* WooCommerce add to cart over wc-ajax. Falls back to the normal link. */
	function addToCart( grid, btn, isEditor ) {
		var ajaxUrl = grid.getAttribute( 'data-ajax' );
		if ( isEditor ) {
			return;
		}
		if ( ! ajaxUrl || btn.hasAttribute( 'disabled' ) || btn.classList.contains( 'is-loading' ) ) {
			return;
		}

		var label = btn.querySelector( '.spw-card__btn-text' );
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

	function bindInteractions( grid, isEditor ) {
		restoreWishlist( grid );

		grid.addEventListener( 'click', function ( event ) {
			var target = event.target;
			if ( ! target || ! target.closest ) {
				return;
			}

			var chip = target.closest( '.spw-chip' );
			if ( chip && grid.contains( chip ) && ! chip.classList.contains( 'spw-chip--ghost' ) ) {
				selectChip( chip );
				return;
			}

			var btn = target.closest( '.spw-card__btn[data-product-id]' );
			if ( btn && grid.contains( btn ) ) {
				event.preventDefault();
				addToCart( grid, btn, isEditor );
				return;
			}

			var wish = target.closest( '.spw-card__wish' );
			if ( wish && grid.contains( wish ) ) {
				var active = wish.classList.toggle( 'is-active' );
				wish.setAttribute( 'aria-pressed', active ? 'true' : 'false' );

				var pid = wish.getAttribute( 'data-product-id' );
				if ( pid && ! isEditor ) {
					var list = readWishlist().filter( function ( id ) {
						return id !== pid;
					} );
					if ( active ) {
						list.push( pid );
					}
					writeWishlist( list );
				}

				// Lets other scripts hook in (e.g. a wishlist plugin).
				grid.dispatchEvent(
					new CustomEvent( 'spw:wishlist-toggle', {
						bubbles: true,
						detail: { active: active, card: wish.closest( '.spw-card' ), productId: pid }
					} )
				);
			}
		} );
	}

	function init( $scope ) {
		var grid = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-card-grid' ) : null;
		if ( ! grid ) {
			return;
		}

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );

		revealCards( grid, isEditor );
		bindInteractions( grid, isEditor );
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-card-grid.default', init );
	} );
} )( jQuery );
