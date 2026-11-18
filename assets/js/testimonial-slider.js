/**
 * Super Widget - Testimonial Slider
 * Moves cards by setting two CSS variables (--d distance, --s side), so the
 * browser does all the animation. No libraries, no HTML injection.
 */
( function ( $ ) {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function revealRoot( root, isEditor ) {
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

	function mod( a, n ) {
		return ( ( a % n ) + n ) % n;
	}

	function cssInt( root, name, fallback ) {
		var v = parseInt( window.getComputedStyle( root ).getPropertyValue( name ), 10 );
		return isNaN( v ) ? fallback : v;
	}

	function Slider( root, isEditor ) {
		var cards = Array.prototype.slice.call( root.querySelectorAll( '.spw-t__card' ) );
		var dots = Array.prototype.slice.call( root.querySelectorAll( '.spw-t__dot' ) );
		var prevs = Array.prototype.slice.call( root.querySelectorAll( '.spw-t__arrow--prev' ) );
		var nexts = Array.prototype.slice.call( root.querySelectorAll( '.spw-t__arrow--next' ) );
		var stage = root.querySelector( '.spw-t__stage' );
		var n = cards.length;
		var loop = root.getAttribute( 'data-loop' ) === 'yes' && n > 1;
		var showProgress = root.getAttribute( 'data-progress' ) === 'yes';
		var index = 0;
		var timer = null;
		var dragged = false;
		var visible = 1;
		var peek = 1;
		var lastP = [];
		var lastIndex = 0;
		var first = true;

		/* Cards visible and side cards come from CSS variables, which Elementor
		 * sets per device. Reading them here keeps the breakpoints in one place. */
		function readLayout() {
			var v = Math.max( 1, Math.min( n, cssInt( root, '--spw-v', parseInt( root.getAttribute( 'data-visible' ), 10 ) || 1 ) ) );
			var k = Math.max( 0, Math.min( 3, cssInt( root, '--spw-peek', parseInt( root.getAttribute( 'data-side' ), 10 ) || 0 ) ) );
			// Side cards need spare cards: with 4 cards and 4 visible there is nothing
			// to peek, so no room is reserved for them (the row then fills the section).
			if ( n <= v ) {
				k = 0;
			} else if ( loop ) {
				k = Math.min( k, Math.floor( ( n - v ) / 2 ) );
			}
			var changed = v !== visible || k !== peek;
			visible = v;
			peek = k;
			root.style.setProperty( '--spw-peek-eff', k );
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

		function shortest( delta ) {
			return loop ? mod( delta + n / 2, n ) - n / 2 : delta;
		}

		function render() {
			var half = ( visible - 1 ) / 2;
			var step = shortest( index - lastIndex );

			cards.forEach( function ( card, i ) {
				var p = position( i );
				var active = Math.abs( p ) <= half + 0.001;
				var shown = Math.abs( p ) <= half + peek + 0.001;

				// A looping card that wraps round the back appears in place instead of flying across.
				var wrapped = ! first && loop && lastP[ i ] !== undefined && Math.abs( p - ( lastP[ i ] - step ) ) > 0.01;
				if ( wrapped ) {
					card.classList.add( 'is-jump' );
				}

				card.style.setProperty( '--d', p );
				card.style.setProperty( '--a', Math.min( 1, Math.abs( p ) ) );
				card.style.setProperty( '--s', active ? 0 : ( p < 0 ? -1 : 1 ) );
				card.classList.toggle( 'is-active', active );
				card.classList.toggle( 'is-shown', shown );

				if ( active ) {
					card.removeAttribute( 'aria-hidden' );
				} else {
					card.setAttribute( 'aria-hidden', 'true' );
				}

				if ( wrapped ) {
					void card.offsetWidth; // eslint-disable-line no-void
					card.classList.remove( 'is-jump' );
				}

				lastP[ i ] = p;
			} );

			lastIndex = index;
			first = false;

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

		function setPlaying( on ) {
			if ( ! showProgress ) {
				return;
			}
			root.classList.remove( 'is-playing' );
			if ( on ) {
				void root.offsetWidth; // eslint-disable-line no-void
				root.classList.add( 'is-playing' );
			}
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
			setPlaying( false );
		}

		function start() {
			stop();
			if ( root.getAttribute( 'data-autoplay' ) !== 'yes' || isEditor || reduceMotion || n < 2 ) {
				return;
			}
			var delay = Math.max( 1500, parseInt( root.getAttribute( 'data-speed' ), 10 ) || 4500 );
			root.style.setProperty( '--spw-ap', delay + 'ms' );
			setPlaying( true );
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
				setPlaying( true );
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

		// Click a side card to bring it to the middle.
		stage.addEventListener( 'click', function ( event ) {
			if ( dragged ) {
				dragged = false;
				return;
			}
			var card = event.target.closest ? event.target.closest( '.spw-t__card' ) : null;
			if ( card && ! card.classList.contains( 'is-active' ) && card.classList.contains( 'is-shown' ) ) {
				var p = position( cards.indexOf( card ) );
				go( p < 0 ? -1 : 1 );
				start();
			}
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

		/* ---- Swipe / drag: the cards follow the finger, then settle ---- */

		if ( root.getAttribute( 'data-swipe' ) === 'yes' && window.PointerEvent ) {
			var startX = null;
			var startY = 0;
			var pid = null;
			var moving = false;

			stage.addEventListener( 'pointerdown', function ( event ) {
				if ( event.pointerType === 'mouse' && event.button !== 0 ) {
					return;
				}
				startX = event.clientX;
				startY = event.clientY;
				pid = event.pointerId;
				moving = false;
				dragged = false;
			} );

			stage.addEventListener( 'pointermove', function ( event ) {
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
					stage.classList.add( 'is-dragging' );
					stop();
					try {
						stage.setPointerCapture( pid );
					} catch ( e ) {}
				}

				// Pull gets heavier at the ends when not looping.
				var atStart = ! loop && index <= 0 && dx > 0;
				var atEnd = ! loop && index >= maxIndex() && dx < 0;
				stage.style.setProperty( '--spw-drag', ( atStart || atEnd ? dx * 0.3 : dx ) + 'px' );
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
				dragged = true;

				stage.classList.remove( 'is-dragging' );
				stage.style.removeProperty( '--spw-drag' );

				var card = cards[ 0 ];
				var width = card ? card.getBoundingClientRect().width : 300;
				var limit = Math.max( 40, width * 0.14 );

				if ( ! cancelled && Math.abs( dx ) > limit ) {
					go( dx < 0 ? 1 : -1 );
				}
				start();
			};

			stage.addEventListener( 'pointerup', function ( event ) {
				finish( event, false );
			} );
			stage.addEventListener( 'pointercancel', function ( event ) {
				finish( event, true );
			} );

			// Stop the browser from dragging the photo instead of the slider.
			stage.addEventListener( 'dragstart', function ( event ) {
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

		/* ---- Device changes: re-place the cards instantly ---- */

		function relayout() {
			if ( ! readLayout() ) {
				return;
			}
			root.classList.add( 'spw-t--init' );
			goTo( index );
			window.requestAnimationFrame( function () {
				window.requestAnimationFrame( function () {
					root.classList.remove( 'spw-t--init' );
				} );
			} );
		}

		/* Size-dependent classes. They are set here (not with CSS container
		 * queries) so the widget never depends on size containment, which
		 * collapses the width inside shrink-to-fit parents. */
		var viewportEl = root.querySelector( '.spw-t__viewport' );

		function measure() {
			var cs = window.getComputedStyle( root );
			var inner = root.clientWidth - ( parseFloat( cs.paddingLeft ) || 0 ) - ( parseFloat( cs.paddingRight ) || 0 );
			root.classList.toggle( 'is-narrow', inner > 0 && inner < 640 );
			if ( viewportEl ) {
				root.style.setProperty( '--spw-vw', viewportEl.clientWidth + 'px' );
			}
		}

		var raf = 0;
		function onResize() {
			window.cancelAnimationFrame( raf );
			raf = window.requestAnimationFrame( function () {
				if ( ! root.isConnected ) {
					return;
				}
				measure();
				relayout();
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
				root.classList.remove( 'spw-t--init' );
			} );
		} );

		start();
	}

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-t' ) : null;
		if ( ! root || root.getAttribute( 'data-ready' ) === '1' ) {
			return;
		}
		root.setAttribute( 'data-ready', '1' );

		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );

		revealRoot( root, isEditor );

		if ( parseInt( root.getAttribute( 'data-count' ), 10 ) > 0 ) {
			Slider( root, isEditor );
		}
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-testimonial-slider.default', init );
	} );
} )( jQuery );
