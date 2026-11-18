<?php
/**
 * Timeline module info (shown on the Super Widget > Widgets tab).
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Timeline', 'super-widget' ),
	'description' => __( 'Vertical or horizontal timeline with year, title and text, with or without pictures. Alternating sides, markers, a progress line and scroll animation.', 'super-widget' ),
	'category'    => 'timelines',
	'icon'        => 'dashicons-clock',
	'version'     => '1.0.0',
);
