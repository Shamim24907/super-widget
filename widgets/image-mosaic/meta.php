<?php
/**
 * Image Mosaic module info (shown on the Super Widget > Widgets tab).
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Image Mosaic', 'super-widget' ),
	'description' => __( 'One image cut into a collage of shapes. Pick a ready layout or add, move and reshape your own tiles (rounded, circle, pill, arch, leaf, hexagon, diamond).', 'super-widget' ),
	'category'    => 'images',
	'icon'        => 'dashicons-layout',
	'version'     => '1.0.0',
);
