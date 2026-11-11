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
 *  - required: installed and active on every site. 'also' lists other plugins that count (folder or name,
 *    e.g. Blocksy Companion (Premium) for Blocksy Companion). The owner can Skip one; it then counts as done.
 *  - shelf: added or switched on only when the site needs it. Never shows the dashboard notice.
 * Source: 'wporg' can be installed by name from WordPress.org. 'bundled' comes with the base copy and
 * 'premium' is a download from its maker ('url'); neither is in the WordPress.org directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function jmw_plugin_list() {
	$list = array(
		array( 'slug' => 'blocksy-companion', 'name' => 'Blocksy Companion', 'group' => 'required', 'source' => 'wporg', 'also' => array( 'blocksy-companion-pro', 'Blocksy Companion (Premium)', 'Blocksy Companion Pro' ), 'why' => 'Extra Blocksy features used by this theme. Blocksy Companion (Premium) counts too.' ),
		array( 'slug' => 'classic-widgets', 'name' => 'Classic Widgets', 'group' => 'required', 'source' => 'wporg', 'why' => 'The classic widget screen, used for the footer.' ),
		array( 'slug' => 'code-snippets', 'name' => 'Code Snippets', 'group' => 'required', 'source' => 'wporg', 'why' => 'Holds all custom CSS and code, one named snippet per job.' ),
		array( 'slug' => 'honeypot', 'name' => 'WP Armour', 'group' => 'required', 'source' => 'wporg', 'why' => 'Stops spam on forms and the login page.' ),
		array( 'slug' => 'autodescription', 'name' => 'The SEO Framework', 'group' => 'required', 'source' => 'wporg', 'why' => 'Page titles, descriptions and search settings.' ),
		array( 'slug' => 'redirection', 'name' => 'Redirection', 'group' => 'required', 'source' => 'wporg', 'why' => 'Keeps old addresses working when a page moves.' ),
		array( 'slug' => 'litespeed-cache', 'name' => 'LiteSpeed Cache', 'group' => 'required', 'source' => 'wporg', 'why' => 'Speed. Clear it after changes.' ),
		array( 'slug' => 'fluentform', 'name' => 'Fluent Forms', 'group' => 'required', 'source' => 'wporg', 'why' => 'Contact and other forms.' ),
		array( 'slug' => 'fluent-smtp', 'name' => 'FluentSMTP', 'group' => 'required', 'source' => 'wporg', 'why' => 'Makes sure site and form emails arrive.' ),
		array( 'slug' => 'koko-analytics', 'name' => 'Koko Analytics', 'group' => 'required', 'source' => 'wporg', 'why' => 'Visitor stats without cookies or a consent banner.' ),
		array( 'slug' => 'fluent-crm', 'name' => 'FluentCRM', 'group' => 'required', 'source' => 'wporg', 'why' => 'One record per person: form entries, sign-ups and history.' ),
		array( 'slug' => 'classic-editor', 'name' => 'Classic Editor', 'group' => 'shelf', 'source' => 'wporg', 'why' => 'The older editing screen, for sites that write pages in HTML.' ),
		array( 'slug' => 'modern-events-calendar-lite', 'name' => 'Modern Events Calendar Lite', 'group' => 'shelf', 'source' => 'bundled', 'why' => 'Events and repeating events, when the site needs a calendar.' ),
		array( 'slug' => 'blocksy-companion-pro', 'name' => 'Blocksy Companion (Premium)', 'group' => 'shelf', 'source' => 'premium', 'also' => array( 'Blocksy Companion (Premium)', 'Blocksy Companion Pro' ), 'url' => 'https://creativethemes.com/blocksy/pricing/', 'why' => 'Blocksy\'s paid extras, for sites with a licence.' ),
		array( 'slug' => 'fluenthub', 'name' => 'FluentHub', 'group' => 'shelf', 'source' => 'premium', 'also' => array( 'FluentHub', 'fluent-hub' ), 'url' => 'https://wpmanageninja.com/fluenthub/', 'why' => 'One dashboard for the Fluent plugins, and a way for AI assistants to work with them.' ),
		array( 'slug' => 'userswp', 'name' => 'UsersWP', 'group' => 'shelf', 'source' => 'wporg', 'why' => 'Member logins, sign-up and profiles, when the site needs them.' ),
		array( 'slug' => 'pwa-for-wp', 'name' => 'PWA for WP', 'group' => 'shelf', 'source' => 'wporg', 'why' => 'Lets visitors add the site to their phone like an app.' ),
	);
	return apply_filters( 'jmw_plugin_list', $list );
}

/** Installed plugins: file => header data. */
function jmw_installed_plugins() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	static $all = null;
	if ( null === $all ) {
		$all = get_plugins();
	}
	return $all;
}

/** Finds the installed plugin file for a folder name or a plugin name (e.g. "fluentform/fluentform.php"), or ''. */
function jmw_plugin_file( $slug_or_name ) {
	$want = strtolower( (string) $slug_or_name );
	foreach ( jmw_installed_plugins() as $file => $data ) {
		if ( strtolower( dirname( $file ) ) === $want || strtolower( (string) $data['Name'] ) === $want ) {
			return $file;
		}
	}
	return '';
}

/** Plugins the owner chose to skip (slugs). */
function jmw_skipped_plugins() {
	return array_values( array_filter( (array) get_option( 'jmw_plugin_skipped', array() ), 'is_string' ) );
}

/**
 * The list with status: active, inactive or missing. 'matched' is the installed plugin that counts for it
 * (itself, or one of its 'also' matches). 'ok' means nothing to do: required ones active (or skipped);
 * shelf ones never need anything.
 */
function jmw_plugin_status() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$skipped = jmw_skipped_plugins();
	$out     = array();
	foreach ( jmw_plugin_list() as $p ) {
		$file = jmw_plugin_file( $p['slug'] );
		if ( ! $file ) {
			$file = jmw_plugin_file( $p['name'] );
		}
		$status = ! $file ? 'missing' : ( is_plugin_active( $file ) ? 'active' : 'inactive' );
		$by     = $file;
		if ( 'active' !== $status && ! empty( $p['also'] ) ) {
			foreach ( (array) $p['also'] as $alt ) {
				$f = jmw_plugin_file( $alt );
				if ( $f && is_plugin_active( $f ) ) {
					$status = 'active';
					$by     = $f;
					break;
				}
			}
		}
		$p['file']    = $file;
		$p['matched'] = $by;
		$p['status']  = $status;
		$p['skipped'] = in_array( $p['slug'], $skipped, true );
		$p['ok']      = 'required' === $p['group'] ? ( 'active' === $status || $p['skipped'] ) : true;
		unset( $p['also'] );
		$out[] = $p;
	}
	return $out;
}

function jmw_plugin_problems() {
	return array_values( array_filter( jmw_plugin_status(), function ( $p ) {
		return ! $p['ok'];
	} ) );
}

// Skip and undo, from the checklist.
add_action( 'admin_post_jmw_skip_plugin', function () {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_die( 'Not allowed.' );
	}
	$slug = isset( $_GET['plugin'] ) ? sanitize_key( wp_unslash( $_GET['plugin'] ) ) : '';
	check_admin_referer( 'jmw_skip_' . $slug );
	$known = wp_list_pluck( jmw_plugin_list(), 'slug' );
	if ( in_array( $slug, $known, true ) ) {
		$skipped = jmw_skipped_plugins();
		if ( ! empty( $_GET['undo'] ) ) {
			$skipped = array_values( array_diff( $skipped, array( $slug ) ) );
		} elseif ( ! in_array( $slug, $skipped, true ) ) {
			$skipped[] = $slug;
		}
		update_option( 'jmw_plugin_skipped', $skipped, false );
	}
	wp_safe_redirect( admin_url( 'themes.php?page=jmw-setup' ) );
	exit;
} );

function jmw_skip_url( $slug, $undo = false ) {
	$args = array( 'action' => 'jmw_skip_plugin', 'plugin' => $slug );
	if ( $undo ) {
		$args['undo'] = 1;
	}
	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'jmw_skip_' . $slug );
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
	$out = '';
	if ( $p['skipped'] ) {
		return '<a class="button" href="' . esc_url( jmw_skip_url( $p['slug'], true ) ) . '">Undo skip</a>';
	}
	if ( 'missing' === $p['status'] ) {
		if ( 'premium' === $p['source'] ) {
			$out = '<em>Not in the WordPress.org directory.</em> ' . ( ! empty( $p['url'] ) ? '<a href="' . esc_url( $p['url'] ) . '" target="_blank" rel="noopener">Get it from the maker</a>' : '' );
		} elseif ( 'bundled' === $p['source'] ) {
			$out = '<em>Not in the WordPress.org directory. Install it from the maker\'s site, or ask Jones E Designs.</em>';
		} else {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $p['slug'] ) ), 'install-plugin_' . $p['slug'] );
			$out = '<a class="button' . ( 'required' === $p['group'] ? ' button-primary' : '' ) . '" href="' . esc_url( $url ) . '">Install</a>';
		}
	} elseif ( 'inactive' === $p['status'] && 'required' === $p['group'] ) {
		$url = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $p['file'] ) ), 'activate-plugin_' . $p['file'] );
		$out = '<a class="button button-primary" href="' . esc_url( $url ) . '">Activate</a>';
	}
	if ( 'required' === $p['group'] && 'active' !== $p['status'] ) {
		$out .= ' <a class="button-link" style="margin-left:8px" href="' . esc_url( jmw_skip_url( $p['slug'] ) ) . '">Skip</a>';
	}
	return $out;
}

function jmw_status_label( $p ) {
	if ( $p['skipped'] && 'active' !== $p['status'] ) {
		return '<span style="color:#646970">Skipped</span>';
	}
	if ( 'required' === $p['group'] ) {
		if ( 'active' === $p['status'] ) {
			$alt = $p['matched'] && $p['matched'] !== $p['file'] ? jmw_installed_plugins()[ $p['matched'] ]['Name'] ?? '' : '';
			return '<span style="color:#008a20">&#10004; Active' . ( $alt ? ' (' . esc_html( $alt ) . ')' : '' ) . '</span>';
		}
		return 'inactive' === $p['status'] ? '<span style="color:#b32d2e">Switched off</span>' : '<span style="color:#b32d2e">Not installed</span>';
	}
	return 'active' === $p['status'] ? '<span style="color:#008a20">&#10004; In use</span>'
		: ( 'inactive' === $p['status'] ? '<span style="color:#008a20">&#10004; On the shelf</span>' : '<span style="color:#646970">Not installed (add it when needed)</span>' );
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
	echo '<p>Every Just My Website site uses the plugins below. <strong>Required</strong> plugins stay switched on; if you use something else instead, press Skip. <strong>Shelf</strong> plugins are only added or switched on when the site needs them, so they never need attention.</p>';
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
