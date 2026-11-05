<?php
/**
 * Just My Website: plugin checklist.
 *
 * One list, used two ways:
 *  - People: Appearance › Just My Website (ticks, Install and Activate buttons) and a dashboard notice
 *    while a required plugin is missing or switched off.
 *  - Assistants (signed in): GET /wp-json/just-my-website/v1/plugins returns the same list with each plugin's
 *    status, to install or activate through WordPress's own /wp/v2/plugins endpoint.
 *
 * Groups:
 *  - required: installed and active on every site.
 *  - shelf: installed but switched off until the site needs it.
 * Source: 'wporg' can be installed by name from WordPress.org. 'bundled' must already be on the site
 * (it comes with the base copy) because it isn't in the WordPress.org directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function jmw_plugin_list() {
	$list = array(
		array( 'slug' => 'blocksy-companion', 'name' => 'Blocksy Companion', 'group' => 'required', 'source' => 'wporg', 'why' => 'Extra Blocksy features used by this theme.' ),
		array( 'slug' => 'classic-editor', 'name' => 'Classic Editor', 'group' => 'required', 'source' => 'wporg', 'why' => 'Pages are written in HTML, without the block editor.' ),
		array( 'slug' => 'classic-widgets', 'name' => 'Classic Widgets', 'group' => 'required', 'source' => 'wporg', 'why' => 'Classic widget screen to match.' ),
		array( 'slug' => 'code-snippets', 'name' => 'Code Snippets', 'group' => 'required', 'source' => 'wporg', 'why' => 'Holds all custom CSS and code, one named snippet per job.' ),
		array( 'slug' => 'honeypot', 'name' => 'WP Armour', 'group' => 'required', 'source' => 'wporg', 'why' => 'Stops spam on forms and the login page.' ),
		array( 'slug' => 'autodescription', 'name' => 'The SEO Framework', 'group' => 'required', 'source' => 'wporg', 'why' => 'Page titles, descriptions and search settings.' ),
		array( 'slug' => 'redirection', 'name' => 'Redirection', 'group' => 'required', 'source' => 'wporg', 'why' => 'Keeps old addresses working when a page moves.' ),
		array( 'slug' => 'litespeed-cache', 'name' => 'LiteSpeed Cache', 'group' => 'required', 'source' => 'wporg', 'why' => 'Speed. Clear it after changes.' ),
		array( 'slug' => 'google-site-kit', 'name' => 'Site Kit by Google', 'group' => 'required', 'source' => 'wporg', 'why' => 'Google Analytics and Search Console.' ),
		array( 'slug' => 'fluentform', 'name' => 'Fluent Forms', 'group' => 'required', 'source' => 'wporg', 'why' => 'Contact and other forms.' ),
		array( 'slug' => 'fluent-smtp', 'name' => 'FluentSMTP', 'group' => 'required', 'source' => 'wporg', 'why' => 'Makes sure site and form emails arrive.' ),
		array( 'slug' => 'modern-events-calendar-lite', 'name' => 'Modern Events Calendar Lite', 'group' => 'shelf', 'source' => 'bundled', 'why' => 'Events and repeating events, when the site needs a calendar.' ),
		array( 'slug' => 'userswp', 'name' => 'UsersWP', 'group' => 'shelf', 'source' => 'wporg', 'why' => 'Member logins and profiles, when the site needs them.' ),
	);
	return apply_filters( 'jmw_plugin_list', $list );
}

/** Finds the installed plugin file (e.g. "fluentform/fluentform.php") for a folder name, or ''. */
function jmw_plugin_file( $slug ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	static $all = null;
	if ( null === $all ) {
		$all = get_plugins();
	}
	foreach ( array_keys( $all ) as $file ) {
		if ( dirname( $file ) === $slug ) {
			return $file;
		}
	}
	return '';
}

/** The list with status: active, inactive or missing. 'ok' means as expected for its group. */
function jmw_plugin_status() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$out = array();
	foreach ( jmw_plugin_list() as $p ) {
		$file   = jmw_plugin_file( $p['slug'] );
		$status = ! $file ? 'missing' : ( is_plugin_active( $file ) ? 'active' : 'inactive' );
		$p['file']   = $file;
		$p['status'] = $status;
		$p['ok']     = 'required' === $p['group'] ? 'active' === $status : 'missing' !== $status;
		$out[]       = $p;
	}
	return $out;
}

function jmw_plugin_problems() {
	return array_values( array_filter( jmw_plugin_status(), function ( $p ) {
		return ! $p['ok'];
	} ) );
}

/* ---- For assistants --------------------------------------------------------------------------------------- */
add_action( 'rest_api_init', function () {
	register_rest_route( 'just-my-website/v1', '/plugins', array(
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
		'callback'            => function () {
			$all = jmw_plugin_status();
			return array(
				'version'  => JMW_VERSION,
				'all_ok'   => ! array_filter( $all, function ( $p ) { return ! $p['ok']; } ),
				'plugins'  => array_map( function ( $p ) {
					unset( $p['why'] );
					return $p;
				}, $all ),
			);
		},
	) );
} );

/* ---- For people ------------------------------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_theme_page( 'Just My Website', 'Just My Website', 'install_plugins', 'jmw-setup', 'jmw_render_setup_page' );
} );

function jmw_plugin_button( $p ) {
	if ( 'missing' === $p['status'] ) {
		if ( 'bundled' === $p['source'] ) {
			return '<em>Not in the WordPress.org directory. Install it from the maker\'s site, or ask Jones E Designs.</em>';
		}
		$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $p['slug'] ) ), 'install-plugin_' . $p['slug'] );
		return '<a class="button button-primary" href="' . esc_url( $url ) . '">Install</a>';
	}
	if ( 'inactive' === $p['status'] && 'required' === $p['group'] ) {
		$url = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $p['file'] ) ), 'activate-plugin_' . $p['file'] );
		return '<a class="button button-primary" href="' . esc_url( $url ) . '">Activate</a>';
	}
	return '';
}

function jmw_status_label( $p ) {
	if ( 'required' === $p['group'] ) {
		return 'active' === $p['status'] ? '<span style="color:#008a20">&#10004; Active</span>'
			: ( 'inactive' === $p['status'] ? '<span style="color:#b32d2e">Switched off</span>' : '<span style="color:#b32d2e">Not installed</span>' );
	}
	return 'active' === $p['status'] ? '<span style="color:#008a20">&#10004; In use</span>'
		: ( 'inactive' === $p['status'] ? '<span style="color:#008a20">&#10004; On the shelf</span>' : '<span style="color:#b32d2e">Not installed</span>' );
}

function jmw_render_setup_page() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}
	$all = jmw_plugin_status();
	echo '<div class="wrap"><h1>Just My Website</h1>';
	if ( function_exists( 'jmw_render_about' ) ) {
		jmw_render_about();
	}
	echo '<h2 style="margin-top:28px">Plugin checklist</h2>';
	echo '<p>Every Just My Website site uses the plugins below. <strong>Required</strong> plugins stay switched on. <strong>Shelf</strong> plugins stay installed but switched off until the site needs them.</p>';
	foreach ( array( 'required' => 'Required', 'shelf' => 'On the shelf' ) as $group => $title ) {
		echo '<h2>' . esc_html( $title ) . '</h2><table class="widefat striped" style="max-width:900px"><tbody>';
		foreach ( $all as $p ) {
			if ( $p['group'] !== $group ) {
				continue;
			}
			echo '<tr><td style="width:32%"><strong>' . esc_html( $p['name'] ) . '</strong><br><span class="description">' . esc_html( $p['why'] ) . '</span></td>';
			echo '<td style="width:20%">' . jmw_status_label( $p ) . '</td><td>' . jmw_plugin_button( $p ) . '</td></tr>'; // phpcs:ignore -- built from escaped parts.
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

// After installing from the checklist, offer a way back to it.
add_filter( 'install_plugin_complete_actions', function ( $actions ) {
	$actions['jmw_setup'] = '<a href="' . esc_url( admin_url( 'themes.php?page=jmw-setup' ) ) . '">Back to the Just My Website checklist</a>';
	return $actions;
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'appearance_page_jmw-setup' === $screen->id ) {
		return;
	}
	$n = count( jmw_plugin_problems() );
	if ( ! $n ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p><strong>Just My Website:</strong> %s <a href="%s">Open the setup checklist</a></p></div>',
		esc_html( sprintf( _n( '%d plugin needs attention.', '%d plugins need attention.', $n ), $n ) ),
		esc_url( admin_url( 'themes.php?page=jmw-setup' ) )
	);
} );
