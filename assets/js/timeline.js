/**
 * Super Widget - Timeline
 * - Reveals each step when it scrolls into view.
 * - Vertical: the line fills while the visitor scrolls.
 * - Horizontal: steps light up as they come into view, arrows scroll the track.
 * No external requests, no eval, no HTML injection (only class / style variable changes).
 */
( function ( $ ) {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function clamp( v, a, b ) {
		return Math.max( a, Math.min( b, v ) );
	}

	function init( $scope ) {
		var root = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.spw-tl' ) : null;
		if ( ! root ) {
			return;
		}

		var track = root.querySelector( '.spw-tl__track' );
		var items = Array.prototype.slice.call( root.querySelectorAll( '.spw-tl__item' ) );
		var horizontal = root.getAttribute( 'data-mode' ) === 'horizontal';
		var once = root.getAttribute( 'data-once' ) !== 'no';
		var progress = root.getAttribute( 'data-progress' ) === 'yes';
		var isEditor = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );

		root.classList.add( 'spw-js' );

		/* ---- reveal ---- */
		if ( isEditor || ! ( 'IntersectionObserver' in window ) ) {
			items.forEach( function ( item ) {
				item.classList.add( 'is-visible' );
			} );
		} else {
			var io = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting ) {
							entry.target.classList.add( 'is-visible' );
							if ( once ) {
								io.unobserve( entry.target );
							}
						} else if ( ! once ) {
							entry.target.classList.remove( 'is-visible' );
						}
					} );
				},
				{ root: horizontal ? track : null, threshold: 0.15, rootMargin: horizontal ? '0px' : '0px 0px -8% 0px' }
			);
			items.forEach( function ( item ) {
				io.observe( item );
			} );
		}

		/* ---- progress ---- */
		var ticking = false;
		var focus = root.classList.contains( 'spw-tl--focus' );
		var parallax = root.classList.contains( 'spw-tl--parallax' ) && ! reduce;
		var fills = items.map( function () {
			return { cur: 0, to: 0 };
		} );
		var easing = false;

		// The line follows the scroll with a soft ease instead of jumping.
		function ease() {
			var moving = false;

			fills.forEach( function ( f, i ) {
				var d = f.to - f.cur;
				if ( Math.abs( d ) > 0.002 ) {
					f.cur += d * 0.14;
					moving = true;
				} else {
					f.cur = f.to;
				}
				items[ i ].style.setProperty( '--fill', f.cur.toFixed( 4 ) );
			} );

			if ( moving ) {
				window.requestAnimationFrame( ease );
			} else {
				easing = false;
			}
		}

		function setCurrent( index ) {
			items.forEach( function ( item, i ) {
				item.classList.toggle( 'is-current', i === index );
			} );
		}

		function updateVertical() {
			var vh = window.innerHeight;
			var line = vh * 0.6;
			var current = -1;
			var best = Infinity;

			items.forEach( function ( item, i ) {
				var r = item.getBoundingClientRect();
				var f = r.height > 0 ? clamp( ( line - r.top ) / r.height, 0, 1 ) : 0;

				fills[ i ].to = f;
				if ( reduce ) {
					fills[ i ].cur = f;
					item.style.setProperty( '--fill', f.toFixed( 3 ) );
				}
				item.classList.toggle( 'is-reached', line >= r.top + Math.min( 40, r.height ) );

				// The step whose area is nearest to the reading line is "current".
				var d = line < r.top ? r.top - line : ( line > r.bottom ? line - r.bottom : 0 );
				if ( d < best ) {
					best = d;
					current = i;
				}
			} );

			if ( focus || root.classList.contains( 'spw-tl--pulse' ) ) {
				setCurrent( current );
			}

			if ( ! reduce && ! easing ) {
				easing = true;
				window.requestAnimationFrame( ease );
			}

			if ( parallax ) {
				items.forEach( function ( item ) {
					var frame = item.querySelector( '.spw-tl__frame img' );
					if ( ! frame ) {
						return;
					}
					var r = item.getBoundingClientRect();
					var offset = ( r.top + r.height / 2 - vh / 2 ) / vh;
					frame.style.setProperty( '--py', clamp( offset * -22, -14, 14 ).toFixed( 1 ) + 'px' );
				} );
			}
		}

		function updateHorizontal() {
			var t = track.getBoundingClientRect();
			var center = t.left + t.width / 2;
			var current = -1;
			var best = Infinity;

			// A step counts as reached once its marker is inside the visible track.
			items.forEach( function ( item, i ) {
				var r = item.getBoundingClientRect();
				var mid = r.left + r.width / 2;
				item.classList.toggle( 'is-reached', mid >= t.left - 1 && mid <= t.right + 1 );

				if ( mid >= t.left - 1 && mid <= t.right + 1 && Math.abs( mid - center ) < best ) {
					best = Math.abs( mid - center );
					current = i;
				}
			} );

			if ( focus || root.classList.contains( 'spw-tl--pulse' ) ) {
				setCurrent( current );
			}
		}

		function update() {
			ticking = false;
			if ( progress || focus || parallax ) {
				if ( horizontal ) {
					updateHorizontal();
				} else {
					updateVertical();
				}
			}
			if ( horizontal ) {
				updateNav();
			}
		}

		function request() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}

		/* ---- horizontal arrows ---- */
		var nav = root.querySelector( '.spw-tl__nav' );
		var prev = root.querySelector( '.spw-tl__nav-btn--prev' );
		var next = root.querySelector( '.spw-tl__nav-btn--next' );

		function updateNav() {
			if ( ! nav || ! track ) {
				return;
			}
			var max = track.scrollWidth - track.clientWidth;
			nav.hidden = max <= 2;
			if ( prev ) {
				prev.disabled = track.scrollLeft <= 1;
			}
			if ( next ) {
				next.disabled = track.scrollLeft >= max - 1;
			}
		}

		function scrollByStep( dir ) {
			var first = items[ 0 ];
			var step = first ? first.getBoundingClientRect().width + parseFloat( window.getComputedStyle( track ).columnGap || 0 ) : track.clientWidth;
			track.scrollBy( { left: dir * step, behavior: reduce ? 'auto' : 'smooth' } );
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				scrollByStep( -1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				scrollByStep( 1 );
			} );
		}

		if ( horizontal ) {
			track.addEventListener( 'scroll', request, { passive: true } );
			track.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'ArrowRight' ) {
					scrollByStep( 1 );
					e.preventDefault();
				} else if ( e.key === 'ArrowLeft' ) {
					scrollByStep( -1 );
					e.preventDefault();
				}
			} );
		} else {
			window.addEventListener( 'scroll', request, { passive: true } );
		}
		window.addEventListener( 'resize', request );

		update();
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/spw-timeline.default', init );
	} );
} )( jQuery );
