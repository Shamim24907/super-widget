/**
 * Super Widget - Premium Cards
 * - Fades the cards in when they scroll into view.
 * - Slider: arrows, dots, keyboard, optional autoplay (CSS scroll-snap does the scrolling).
 * No external requests, no eval, no HTML injection.
 */
( function ( $ ) {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-pc' ) : null;
		if ( ! root ) {
			return;
		}

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );
		root.classList.add( 'spw-js' );

		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			root.classList.add( 'is-in' );
		} else {
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

		if ( ! root.hasAttribute( 'data-slider' ) ) {
			return;
		}

		var vp = root.querySelector( '.spw-pc__viewport' );
		var prev = root.querySelector( '.spw-pc__btnav--prev' );
		var next = root.querySelector( '.spw-pc__btnav--next' );
		var dotsBox = root.querySelector( '.spw-pc__dots' );
		var dots = [];

		function max() {
			return Math.max( 0, vp.scrollWidth - vp.clientWidth );
		}

		function pages() {
			var m = max();
			return m < 4 ? 1 : Math.ceil( m / step() - 0.02 ) + 1;
		}

		var snapTimer = 0;
		function goTo( left ) {
			left = Math.max( 0, Math.min( max(), left ) );
			if ( reduce ) {
				vp.scrollLeft = left;
				return;
			}
			// Snap points can fight a smooth scroll in some browsers: pause them while moving.
			vp.style.scrollSnapType = 'none';
			window.clearTimeout( snapTimer );
			snapTimer = window.setTimeout( function () {
				vp.style.scrollSnapType = '';
			}, 700 );
			vp.scrollTo( { left: left, behavior: 'smooth' } );
		}

		function step() {
			var slide = vp.querySelector( '.spw-pc__slide' );
			if ( ! slide ) {
				return vp.clientWidth;
			}
			var gap = parseFloat( window.getComputedStyle( vp.firstElementChild ).columnGap ) || 0;
			var w = slide.getBoundingClientRect().width + gap;
			var n = Math.max( 1, Math.floor( ( vp.clientWidth + gap ) / w + 0.01 ) );
			return w * n;
		}

		function buildDots() {
			if ( ! dotsBox ) {
				return;
			}
			var n = pages();
			if ( dots.length === n ) {
				return;
			}
			while ( dotsBox.firstChild ) {
				dotsBox.removeChild( dotsBox.firstChild );
			}
			dots = [];
			if ( n < 2 ) {
				return;
			}
			for ( var i = 0; i < n; i++ ) {
				( function ( idx ) {
					var b = document.createElement( 'button' );
					b.type = 'button';
					b.className = 'spw-pc__dot';
					b.setAttribute( 'aria-label', ( idx + 1 ) + ' / ' + n );
					b.addEventListener( 'click', function () {
						goTo( Math.min( max(), idx * step() ) );
					} );
					dotsBox.appendChild( b );
					dots.push( b );
				} )( i );
			}
		}

		function update() {
			var m = max();
			var x = Math.abs( vp.scrollLeft );
			var can = m >= 4;
			if ( can ) {
				vp.setAttribute( 'tabindex', '0' );
			} else {
				vp.removeAttribute( 'tabindex' );
			}
			if ( prev ) {
				prev.hidden = ! can;
				prev.disabled = x <= 2;
			}
			if ( next ) {
				next.hidden = ! can;
				next.disabled = x >= m - 2;
			}
			buildDots();
			if ( dots.length ) {
				var on = Math.min( dots.length - 1, Math.round( x / step() ) );
				if ( x >= m - 2 ) {
					on = dots.length - 1;
				}
				dots.forEach( function ( d, i ) {
					d.classList.toggle( 'is-on', i === on );
				} );
			}
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				goTo( vp.scrollLeft - step() );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				goTo( vp.scrollLeft + step() );
			} );
		}

		vp.addEventListener( 'scroll', function () {
			window.requestAnimationFrame( update );
		}, { passive: true } );

		vp.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowRight' ) {
				goTo( vp.scrollLeft + step() );
			} else if ( e.key === 'ArrowLeft' ) {
				goTo( vp.scrollLeft - step() );
			}
		} );

		var timer = 0;
		window.addEventListener( 'resize', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( update, 120 );
		} );
		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( update ).observe( vp );
		}
		update();

		/* ---- autoplay ---- */
		var delay = parseInt( root.getAttribute( 'data-autoplay' ), 10 );
		if ( delay && ! reduce ) {
			var paused = false;
			var tick = window.setInterval( function () {
				if ( ! root.isConnected ) {
					window.clearInterval( tick );
					return;
				}
				if ( paused || document.hidden || max() < 4 ) {
					return;
				}
				var x = Math.abs( vp.scrollLeft );
				goTo( x >= max() - 2 ? 0 : x + step() );
			}, Math.max( 1500, delay ) );
			[ 'mouseenter', 'focusin', 'touchstart' ].forEach( function ( ev ) {
				root.addEventListener( ev, function () { paused = true; }, { passive: true } );
			} );
			[ 'mouseleave', 'focusout', 'touchend' ].forEach( function ( ev ) {
				root.addEventListener( ev, function () { paused = false; }, { passive: true } );
			} );
		}
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-premium-cards.default', init );
	} );
} )( jQuery );
