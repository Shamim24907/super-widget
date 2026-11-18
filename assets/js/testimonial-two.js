/**
 * Super Widget - Testimonial 2
 * One testimonial at a time. Slides are moved by CSS variables (--d), the
 * browser animates them. No libraries, no HTML injection.
 */
( function ( $ ) {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function mod( a, n ) {
		return ( ( a % n ) + n ) % n;
	}

	function reveal( root, isEditor ) {
		root.classList.add( 'spw-js' );

		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			root.classList.add( 'is-visible' );
			return;
		}

		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						root.classList.add( 'is-visible' );
						io.disconnect();
					}
				} );
			},
			{ threshold: 0.12 }
		);
		io.observe( root );
	}

	function Slider( root, isEditor ) {
		var slides = Array.prototype.slice.call( root.querySelectorAll( '.spw-tc__slide' ) );
		var dots = Array.prototype.slice.call( root.querySelectorAll( '.spw-tc__dot' ) );
		var prevs = Array.prototype.slice.call( root.querySelectorAll( '.spw-tc__arrow--prev' ) );
		var nexts = Array.prototype.slice.call( root.querySelectorAll( '.spw-tc__arrow--next' ) );
		var track = root.querySelector( '.spw-tc__track' );
		var n = slides.length;
		var loop = root.getAttribute( 'data-loop' ) === 'yes' && n > 1;
		var visible = 1;
		var index = 0;
		var lastIndex = 0;
		var lastD = [];
		var first = true;
		var timer = null;

		/* Columns come from a CSS variable that Elementor sets per device. */
		function readLayout() {
			var v = parseInt( window.getComputedStyle( root ).getPropertyValue( '--spw-v' ), 10 );
			if ( isNaN( v ) ) {
				v = parseInt( root.getAttribute( 'data-visible' ), 10 ) || 1;
			}
			v = Math.max( 1, Math.min( n, v ) );
			var changed = v !== visible;
			visible = v;
			root.classList.toggle( 'spw-tc--multi', v > 1 );
			return changed;
		}

		function maxIndex() {
			return loop ? n - 1 : Math.max( 0, n - visible );
		}

		function position( i ) {
			var k = i - index - ( visible - 1 ) / 2;
			var p = loop ? mod( k + n / 2, n ) - n / 2 : k;
			return Math.round( p * 100 ) / 100;
		}

		function render() {
			var half = ( visible - 1 ) / 2;
			var step = loop ? mod( index - lastIndex + n / 2, n ) - n / 2 : index - lastIndex;

			slides.forEach( function ( slide, i ) {
				var d = position( i );
				var active = Math.abs( d ) <= half + 0.001;

				// Only a slide that wraps round the back is placed without animation.
				var wrapped = ! first && loop && lastD[ i ] !== undefined && Math.abs( d - ( lastD[ i ] - step ) ) > 0.01;
				if ( wrapped ) {
					slide.classList.add( 'is-jump' );
				}

				slide.style.setProperty( '--d', d );
				slide.classList.toggle( 'is-active', active );

				if ( active ) {
					slide.removeAttribute( 'aria-hidden' );
				} else {
					slide.setAttribute( 'aria-hidden', 'true' );
				}

				if ( wrapped ) {
					void slide.offsetWidth; // eslint-disable-line no-void
					slide.classList.remove( 'is-jump' );
				}

				lastD[ i ] = d;
			} );

			lastIndex = index;
			first = false;
			root.style.setProperty( '--i', index );

			var last = maxIndex();
			dots.forEach( function ( dot ) {
				var di = parseInt( dot.getAttribute( 'data-index' ), 10 );
				var on = di === index;
				dot.hidden = ! loop && di > last;
				dot.classList.toggle( 'is-active', on );
				if ( on ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );

			if ( ! loop ) {
				prevs.forEach( function ( b ) {
					b.disabled = index <= 0;
				} );
				nexts.forEach( function ( b ) {
					b.disabled = index >= last;
				} );
			}
		}

		function goTo( i ) {
			index = loop ? mod( i, n ) : Math.max( 0, Math.min( maxIndex(), i ) );
			render();
		}

		function go( step ) {
			goTo( index + step );
		}

		/* ---- Autoplay ---- */

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function start() {
			stop();
			if ( root.getAttribute( 'data-autoplay' ) !== 'yes' || isEditor || reduceMotion || n < 2 ) {
				return;
			}
			var delay = Math.max( 1500, parseInt( root.getAttribute( 'data-speed' ), 10 ) || 5000 );
			timer = window.setInterval( function () {
				if ( ! root.isConnected ) {
					stop();
					return;
				}
				if ( ! loop && index >= maxIndex() ) {
					goTo( 0 );
				} else {
					go( 1 );
				}
			}, delay );
		}

		/* ---- Controls ---- */

		prevs.forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				go( -1 );
				start();
			} );
		} );

		nexts.forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				go( 1 );
				start();
			} );
		} );

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				goTo( parseInt( dot.getAttribute( 'data-index' ), 10 ) );
				start();
			} );
		} );

		root.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'ArrowLeft' ) {
				go( -1 );
				start();
			} else if ( event.key === 'ArrowRight' ) {
				go( 1 );
				start();
			}
		} );

		/* ---- Swipe / drag: the slide follows the finger, then settles ---- */

		if ( root.getAttribute( 'data-swipe' ) === 'yes' && window.PointerEvent ) {
			var startX = null;
			var startY = 0;
			var pid = null;
			var moving = false;

			track.addEventListener( 'pointerdown', function ( event ) {
				if ( event.pointerType === 'mouse' && event.button !== 0 ) {
					return;
				}
				startX = event.clientX;
				startY = event.clientY;
				pid = event.pointerId;
				moving = false;
			} );

			track.addEventListener( 'pointermove', function ( event ) {
				if ( startX === null || event.pointerId !== pid ) {
					return;
				}
				var dx = event.clientX - startX;
				var dy = event.clientY - startY;

				if ( ! moving ) {
					if ( Math.abs( dx ) < 6 || Math.abs( dx ) < Math.abs( dy ) ) {
						return;
					}
					moving = true;
					track.classList.add( 'is-dragging' );
					stop();
					try {
						track.setPointerCapture( pid );
					} catch ( e ) {}
				}

				var atStart = ! loop && index <= 0 && dx > 0;
				var atEnd = ! loop && index >= maxIndex() && dx < 0;
				track.style.setProperty( '--spw-drag', ( atStart || atEnd ? dx * 0.3 : dx ) + 'px' );
			} );

			var finish = function ( event, cancelled ) {
				if ( startX === null || event.pointerId !== pid ) {
					return;
				}
				var dx = event.clientX - startX;
				startX = null;

				if ( ! moving ) {
					return;
				}
				moving = false;

				track.classList.remove( 'is-dragging' );
				track.style.removeProperty( '--spw-drag' );

				var width = track.getBoundingClientRect().width || 300;
				if ( ! cancelled && Math.abs( dx ) > Math.max( 40, width * 0.12 ) ) {
					go( dx < 0 ? 1 : -1 );
				}
				start();
			};

			track.addEventListener( 'pointerup', function ( event ) {
				finish( event, false );
			} );
			track.addEventListener( 'pointercancel', function ( event ) {
				finish( event, true );
			} );
			track.addEventListener( 'dragstart', function ( event ) {
				event.preventDefault();
			} );
		}

		/* ---- Pause on hover / focus ---- */

		if ( root.getAttribute( 'data-pause' ) === 'yes' ) {
			root.addEventListener( 'mouseenter', stop );
			root.addEventListener( 'mouseleave', start );
		}
		root.addEventListener( 'focusin', stop );
		root.addEventListener( 'focusout', start );

		/* ---- Device changes: re-place the slides instantly ---- */

		/* Size-dependent classes. They are set here (not with CSS container
		 * queries) so the widget never depends on size containment, which
		 * collapses the width inside shrink-to-fit parents. */
		function measure() {
			var cs = window.getComputedStyle( root );
			var inner = root.clientWidth - ( parseFloat( cs.paddingLeft ) || 0 ) - ( parseFloat( cs.paddingRight ) || 0 );
			root.classList.toggle( 'is-narrow', inner > 0 && inner < 640 );

			// Read the slide width after the class above has changed the side space.
			var slideW = slides[ 0 ] ? slides[ 0 ].offsetWidth : 0;
			root.classList.toggle( 'is-slide-narrow', slideW > 0 && slideW < 520 );
		}

		var raf = 0;
		function onResize() {
			window.cancelAnimationFrame( raf );
			raf = window.requestAnimationFrame( function () {
				if ( ! root.isConnected ) {
					return;
				}
				measure();
				if ( ! readLayout() ) {
					return;
				}
				root.classList.add( 'spw-tc--init' );
				goTo( index );
				window.requestAnimationFrame( function () {
					window.requestAnimationFrame( function () {
						root.classList.remove( 'spw-tc--init' );
					} );
				} );
			} );
		}

		window.addEventListener( 'resize', onResize );
		if ( window.ResizeObserver ) {
			new window.ResizeObserver( onResize ).observe( root );
		}

		measure();
		readLayout();
		render();

		// Transitions stay off for the first paint, then switch on.
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				root.classList.remove( 'spw-tc--init' );
			} );
		} );

		start();
	}

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-tc' ) : null;
		if ( ! root || root.getAttribute( 'data-ready' ) === '1' ) {
			return;
		}
		root.setAttribute( 'data-ready', '1' );

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );

		reveal( root, isEditor );

		if ( parseInt( root.getAttribute( 'data-count' ), 10 ) > 0 ) {
			Slider( root, isEditor );
		}
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-testimonial-two.default', init );
	} );
} )( jQuery );
