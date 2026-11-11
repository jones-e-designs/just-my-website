<?php
/**
 * Just My Website: full-width sections.
 *
 * Every top-level section of a page or post reaches both edges of the screen, so its background never stops
 * short. Its content stays at the normal page width (style.css). Two switches:
 *  - Edge to edge: content goes all the way across too. Per page: the "Edge to edge" box on the edit screen,
 *    or the post meta `jmw_edge` (true/false) over the REST API. Per section: the class `jmw-edge`.
 *  - `jmw-contained`: a coloured box that stays inside the page width (a card, a callout).
 *
 * License: GNU General Public License v2 or later.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	foreach ( array( 'page', 'post' ) as $type ) {
		register_post_meta( $type, 'jmw_edge', array(
			'type'          => 'boolean',
			'single'        => true,
			'default'       => false,
			'show_in_rest'  => true,
			'auth_callback' => function ( $allowed, $key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		) );
	}
} );

/** True when this page or post is set to edge to edge. */
function jmw_is_edge( $post = null ) {
	$post = get_post( $post );
	return $post && (bool) get_post_meta( $post->ID, 'jmw_edge', true );
}

add_filter( 'body_class', function ( $classes ) {
	if ( is_singular() && jmw_is_edge() ) {
		$classes[] = 'jmw-edge-page';
	}
	return $classes;
} );

// A small box on the edit screen, for people.
add_action( 'add_meta_boxes', function () {
	foreach ( array( 'page', 'post' ) as $type ) {
		add_meta_box( 'jmw-edge', 'Just My Website', 'jmw_render_edge_box', $type, 'side', 'default' );
	}
} );

function jmw_render_edge_box( $post ) {
	wp_nonce_field( 'jmw_edge_' . $post->ID, 'jmw_edge_nonce' );
	echo '<label><input type="checkbox" name="jmw_edge" value="1"' . checked( jmw_is_edge( $post ), true, false ) . '> Edge to edge</label>';
	echo '<p class="description">Section backgrounds always reach the screen edges. Tick this to let the content go all the way across too, for photo or gallery pages.</p>';
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['jmw_edge_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['jmw_edge_nonce'] ), 'jmw_edge_' . $post_id ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! empty( $_POST['jmw_edge'] ) ) {
		update_post_meta( $post_id, 'jmw_edge', true );
	} else {
		delete_post_meta( $post_id, 'jmw_edge' );
	}
} );
