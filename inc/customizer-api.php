<?php
/**
 * Customizer settings over the REST API, for people who can already use the Customizer (edit_theme_options).
 * Reads and changes theme settings (Blocksy's header, palette, fonts, page titles and so on), saves the old
 * values before every change so it can be undone, and rebuilds Blocksy's styles the way Publish does.
 *
 * GET  /wp-json/just-my-website/v1/customizer                 Names of the settings that are set.
 * GET  /wp-json/just-my-website/v1/customizer?keys=a,b        Values of those settings (null = not set).
 * POST /wp-json/just-my-website/v1/customizer                 {"settings": {"a": value}, "note": "why"}
 *                                                             A value of null removes the setting (back to default).
 * GET  /wp-json/just-my-website/v1/customizer/history         The saved changes, newest first.
 * POST /wp-json/just-my-website/v1/customizer/undo            {"id": "..."} or {} for the latest change.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const JMW_CUSTOMIZER_HISTORY = 'jmw_customizer_history';
const JMW_CUSTOMIZER_KEEP    = 30;

/** Settings that aren't Customizer design settings and stay out of reach. */
function jmw_customizer_blocked( $key ) {
	return in_array( $key, array( 'nav_menu_locations', 'sidebars_widgets', 'custom_css_post_id' ), true )
		|| ! preg_match( '/^[A-Za-z0-9_\-]{1,100}$/', $key );
}

/** Rebuild Blocksy's styles and clear the page cache, as the Customizer's Publish button does. */
function jmw_customizer_refresh() {
	// Blocksy keeps the settings in memory for the request; clear that first so the rebuild uses the new values.
	if ( function_exists( 'blocksy_manager' ) && isset( blocksy_manager()->db ) && method_exists( blocksy_manager()->db, 'wipe_cache' ) ) {
		blocksy_manager()->db->wipe_cache();
	}
	do_action( 'blocksy:dynamic-css:refresh-caches' );
	do_action( 'litespeed_purge_all' );
}

function jmw_customizer_apply( $values ) {
	foreach ( $values as $key => $value ) {
		if ( null === $value ) {
			remove_theme_mod( $key );
		} else {
			set_theme_mod( $key, $value );
		}
	}
	jmw_customizer_refresh();
}

function jmw_customizer_current( $keys ) {
	$mods = get_theme_mods();
	$out  = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = is_array( $mods ) && array_key_exists( $key, $mods ) ? $mods[ $key ] : null;
	}
	return $out;
}

function jmw_customizer_keys( $raw ) {
	$keys = array_values( array_filter( array_map( 'trim', is_array( $raw ) ? $raw : explode( ',', (string) $raw ) ) ) );
	foreach ( $keys as $key ) {
		if ( jmw_customizer_blocked( $key ) ) {
			return new WP_Error( 'jmw_blocked', sprintf( 'The setting "%s" can\'t be read or changed here.', $key ), array( 'status' => 400 ) );
		}
	}
	return $keys;
}

add_action( 'rest_api_init', function () {
	$can = function () {
		return current_user_can( 'edit_theme_options' );
	};

	register_rest_route( 'just-my-website/v1', '/customizer', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => $can,
			'callback'            => function ( WP_REST_Request $r ) {
				if ( ! $r->get_param( 'keys' ) ) {
					$mods = get_theme_mods();
					$keys = is_array( $mods ) ? array_keys( $mods ) : array();
					return array( 'settings' => array_values( array_filter( $keys, function ( $k ) {
						return ! jmw_customizer_blocked( (string) $k );
					} ) ) );
				}
				$keys = jmw_customizer_keys( $r->get_param( 'keys' ) );
				return is_wp_error( $keys ) ? $keys : array( 'settings' => jmw_customizer_current( $keys ) );
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => $can,
			'callback'            => function ( WP_REST_Request $r ) {
				$settings = $r->get_param( 'settings' );
				if ( ! is_array( $settings ) || ! $settings ) {
					return new WP_Error( 'jmw_empty', 'Send {"settings": {"name": value}}.', array( 'status' => 400 ) );
				}
				$keys = jmw_customizer_keys( array_keys( $settings ) );
				if ( is_wp_error( $keys ) ) {
					return $keys;
				}
				$entry = array(
					'id'     => gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 4, false ),
					'time'   => gmdate( 'c' ),
					'user'   => wp_get_current_user()->user_login,
					'note'   => sanitize_text_field( (string) $r->get_param( 'note' ) ),
					'before' => jmw_customizer_current( $keys ),
				);
				$history = (array) get_option( JMW_CUSTOMIZER_HISTORY, array() );
				array_unshift( $history, $entry );
				update_option( JMW_CUSTOMIZER_HISTORY, array_slice( $history, 0, JMW_CUSTOMIZER_KEEP ), false );
				jmw_customizer_apply( $settings );
				return array( 'saved' => true, 'undo_id' => $entry['id'], 'settings' => jmw_customizer_current( $keys ) );
			},
		),
	) );

	register_rest_route( 'just-my-website/v1', '/customizer/history', array(
		'methods'             => 'GET',
		'permission_callback' => $can,
		'callback'            => function () {
			return array_map( function ( $e ) {
				return array( 'id' => $e['id'], 'time' => $e['time'], 'user' => $e['user'], 'note' => $e['note'], 'settings' => array_keys( (array) $e['before'] ) );
			}, (array) get_option( JMW_CUSTOMIZER_HISTORY, array() ) );
		},
	) );

	register_rest_route( 'just-my-website/v1', '/customizer/undo', array(
		'methods'             => 'POST',
		'permission_callback' => $can,
		'callback'            => function ( WP_REST_Request $r ) {
			$history = (array) get_option( JMW_CUSTOMIZER_HISTORY, array() );
			$id      = (string) $r->get_param( 'id' );
			foreach ( $history as $i => $e ) {
				if ( '' === $id || $e['id'] === $id ) {
					jmw_customizer_apply( (array) $e['before'] );
					array_splice( $history, $i, 1 );
					update_option( JMW_CUSTOMIZER_HISTORY, $history, false );
					return array( 'undone' => $e['id'], 'settings' => jmw_customizer_current( array_keys( (array) $e['before'] ) ) );
				}
			}
			return new WP_Error( 'jmw_no_change', 'No saved change to undo.', array( 'status' => 404 ) );
		},
	) );
} );
