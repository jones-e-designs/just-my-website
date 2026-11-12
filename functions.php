<?php
/**
 * Just My Website by Jones E Designs: a child theme for Blocksy (by Creative Themes).
 *
 * 1. Loads Blocksy's and this theme's styles.
 * 2. Preview pages (web address starting "preview-") are kept out of search engines, the sitemap and site search.
 * 3. Modern Events Calendar uses the Blocksy palette and fonts (inc/mec-bridge.php).
 * 4. Appearance › Just My Website: about this theme and the plugin checklist (inc/plugins.php, inc/about.php).
 * 5. Update notices from the theme's GitHub releases (inc/updates.php).
 * 6. The login screens use the site's logo and Blocksy colours (inc/login.php).
 * 7. Customizer settings can be read and changed over the REST API, with undo (inc/customizer-api.php).
 * 8. Section backgrounds reach the screen edges; per-page and per-section edge-to-edge switch (inc/sections.php).
 *
 * License: GNU General Public License v2 or later (see LICENSE).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JMW_VERSION', '1.4.4' );

require_once __DIR__ . '/inc/plugins.php';    // Plugin checklist and the plugin list for assistants
require_once __DIR__ . '/inc/about.php';      // Appearance › Just My Website, and the /info signal
require_once __DIR__ . '/inc/mec-bridge.php'; // Modern Events Calendar follows the Blocksy Customizer
require_once __DIR__ . '/inc/login.php';      // Login screens use the site's logo and colours
require_once __DIR__ . '/inc/customizer-api.php'; // Customizer settings over the REST API, with undo
require_once __DIR__ . '/inc/updates.php';    // Update notices from GitHub releases
require_once __DIR__ . '/inc/sections.php';   // Full-width sections and the edge-to-edge switch

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme( get_template() )->get( 'Version' ) );
	wp_enqueue_style( 'just-my-website', get_stylesheet_uri(), array( 'parent-style' ), JMW_VERSION );
}, 20 );

/* ---- Preview pages ------------------------------------------------------------------------------- */

/** A preview page: published, but its web address starts with "preview-". */
function jmw_is_preview_post( $post = null ) {
	$post = get_post( $post );
	return $post && 0 === strpos( (string) $post->post_name, 'preview-' );
}

// Search engines are told not to list a preview page.
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_singular() && jmw_is_preview_post() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
} );

// Preview pages are left out of WordPress's sitemap.
add_filter( 'wp_sitemaps_posts_query_args', function ( $args ) {
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE 'preview-%' AND post_status = 'publish'" );
	if ( $ids ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), array_map( 'intval', $ids ) );
	}
	return $args;
} );

// And out of the site's own search results.
add_filter( 'posts_where', function ( $where, $query ) {
	global $wpdb;
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$where .= " AND {$wpdb->posts}.post_name NOT LIKE 'preview-%'";
	}
	return $where;
}, 10, 2 );

// And out of WordPress's automatic page lists (the fallback header menu when no menu is set, page widgets).
add_filter( 'wp_list_pages_excludes', function ( $exclude ) {
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name LIKE 'preview-%'" );
	return array_merge( (array) $exclude, array_map( 'intval', $ids ) );
} );
