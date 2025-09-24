<?php
/**
 * AMC Magazin – Divi Child
 * Theme functions.
 */

// Version constant from child theme stylesheet header
if ( ! defined( 'AMC_CHILD_VERSION' ) ) {
	$theme = wp_get_theme();
	define( 'AMC_CHILD_VERSION', $theme->get( 'Version' ) );
}

/**
 * Enqueue parent + child styles.
 * Note: Divi enqueues its own CSS; we register a distinct handle to avoid duplicates.
 */
add_action( 'wp_enqueue_scripts', function() {
	$parent = wp_get_theme( 'Divi' );
	$parent_ver = $parent ? $parent->get( 'Version' ) : null;

	wp_enqueue_style( 'divi-parent-style', get_template_directory_uri() . '/style.css', [], $parent_ver );
	wp_enqueue_style( 'amc-child-style', get_stylesheet_uri(), [ 'divi-parent-style' ], AMC_CHILD_VERSION );
}, 20 );

/**
 * Custom image sizes for consistent 3:2 hero/teaser crops.
 */
add_action( 'after_setup_theme', function() {
	add_image_size( 'amc-card', 1536, 1024, true ); // 3:2 hard crop
} );

/**
 * Include shortcodes & other theme code.
 */
require_once get_stylesheet_directory() . '/inc/article-feed.php';
