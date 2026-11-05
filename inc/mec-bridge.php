<?php
/**
 * Modern Events Calendar → Blocksy styling (Just My Website).
 *
 * MEC paints its accent colour (#40d9f1) straight into its stylesheet in about a hundred places, and uses its
 * own font and colour settings. This makes every MEC calendar, list and event page follow the site's Blocksy
 * Customizer instead: palette colour 1 for the accent, the site's fonts, heading and text colours. Change the
 * palette in the Customizer and the calendars follow; nothing to set in MEC.
 *
 * How: when MEC is active, read MEC's own frontend stylesheet, keep only the declarations that use its accent
 * colour, swap that colour for a CSS variable, and print those rules right after MEC's stylesheet. Cached per
 * MEC version, so it's rebuilt automatically when MEC updates.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The MEC colour variables, pointed at Blocksy's palette and type. Beats MEC's own :root block. */
function jmw_mec_root_vars() {
	$accent = 'var(--theme-palette-color-1, #2872fa)';
	$mix    = function ( $pct ) use ( $accent ) {
		return "color-mix(in srgb, {$accent} {$pct}%, transparent)";
	};
	return 'html:root, :root *::before, :root *::after {'
		. "--mec-color-skin: {$accent};"
		. '--mec-color-skin-rgba-1: ' . $mix( 25 ) . ';'
		. '--mec-color-skin-rgba-2: ' . $mix( 50 ) . ';'
		. '--mec-color-skin-rgba-3: ' . $mix( 75 ) . ';'
		. '--mec-color-skin-rgba-4: ' . $mix( 11 ) . ';'
		. "--mec-fes-main-color: {$accent};"
		. '--mec-heading-font-family: var(--theme-font-family, inherit);'
		. '--mec-paragraph-font-family: var(--theme-font-family, inherit);'
		. '--mec-title-color: var(--theme-headings-color, var(--theme-palette-color-4, inherit));'
		. '--mec-title-color-hover: var(--theme-link-hover-color, var(--theme-palette-color-2, inherit));'
		. '--mec-content-color: var(--theme-text-color, var(--theme-palette-color-3, inherit));'
		. '--mec-primary-border-radius: var(--theme-button-border-radius, 3px);'
		. '}'
		. '.mec-wrap, .mec-wrap :is(h1,h2,h3,h4,h5,h6,p,a,span,div,li,button,input,select) { font-family: inherit; }';
}

/** MEC's accent colour, in every spelling its stylesheet uses, and what replaces it. */
function jmw_mec_colour_map() {
	return array(
		'/#40d9f1\b/i'                                  => 'var(--mec-color-skin)',
		'/rgba\(\s*64\s*,\s*217\s*,\s*241\s*,\s*\.?25\s*\)/i' => 'var(--mec-color-skin-rgba-1)',
		'/rgba\(\s*64\s*,\s*217\s*,\s*241\s*,\s*\.5\s*\)/i'   => 'var(--mec-color-skin-rgba-2)',
		'/rgba\(\s*64\s*,\s*217\s*,\s*241\s*,\s*\.75\s*\)/i'  => 'var(--mec-color-skin-rgba-3)',
		'/rgba\(\s*64\s*,\s*217\s*,\s*241\s*,\s*\.11\s*\)/i'  => 'var(--mec-color-skin-rgba-4)',
		'/rgba\(\s*64\s*,\s*217\s*,\s*241\s*,\s*(\.?\d+)\s*\)/i' => 'color-mix(in srgb, var(--mec-color-skin) calc($1 * 100%), transparent)',
	);
}

/** Keep only declarations that use MEC's accent, with it swapped; keeps @media/@supports; drops everything else. */
function jmw_mec_filter_css( $css ) {
	$out = '';
	$len = strlen( $css );
	$i   = 0;
	$map = jmw_mec_colour_map();
	$hit = '/#40d9f1\b|rgba\(\s*64\s*,\s*217\s*,\s*241/i';
	while ( $i < $len ) {
		$open = strpos( $css, '{', $i );
		if ( false === $open ) {
			break;
		}
		$prelude = trim( substr( $css, $i, $open - $i ) );
		// Find the matching closing brace.
		$depth = 1;
		$j     = $open + 1;
		while ( $j < $len && $depth > 0 ) {
			if ( '{' === $css[ $j ] ) {
				$depth++;
			} elseif ( '}' === $css[ $j ] ) {
				$depth--;
			}
			$j++;
		}
		$body = substr( $css, $open + 1, $j - $open - 2 );
		$i    = $j;
		if ( '' === $prelude ) {
			continue;
		}
		if ( '@' === $prelude[0] ) {
			if ( preg_match( '/^@(media|supports|container|layer)\b/i', $prelude ) ) {
				$inner = jmw_mec_filter_css( $body );
				if ( '' !== $inner ) {
					$out .= $prelude . '{' . $inner . '}';
				}
			}
			continue; // @keyframes, @font-face, @import…: not needed here.
		}
		if ( ! preg_match( $hit, $body ) ) {
			continue;
		}
		$keep = array();
		foreach ( explode( ';', $body ) as $decl ) {
			if ( preg_match( $hit, $decl ) ) {
				$keep[] = trim( preg_replace( array_keys( $map ), array_values( $map ), $decl ) );
			}
		}
		if ( $keep ) {
			$out .= $prelude . '{' . implode( ';', $keep ) . '}';
		}
	}
	return $out;
}

/** The bridge CSS for the installed MEC version (cached). */
function jmw_mec_bridge_css() {
	if ( ! defined( 'MEC_ABSPATH' ) ) {
		return '';
	}
	$file = MEC_ABSPATH . 'assets/css/frontend.min.css';
	if ( ! is_readable( $file ) ) {
		$file = MEC_ABSPATH . 'assets/css/frontend.css';
	}
	if ( ! is_readable( $file ) ) {
		return jmw_mec_root_vars();
	}
	$key    = 'jmw_mec_bridge_' . md5( JMW_VERSION . '|' . ( defined( 'MEC_VERSION' ) ? MEC_VERSION : '' ) . '|' . filemtime( $file ) );
	$cached = get_transient( $key );
	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}
	$css = (string) file_get_contents( $file );
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$out = jmw_mec_root_vars() . jmw_mec_filter_css( $css );
	set_transient( $key, $out, MONTH_IN_SECONDS );
	return $out;
}

// Print right after MEC's own stylesheet, wherever and whenever MEC prints it.
add_action( 'wp_enqueue_scripts', function () {
	if ( defined( 'MEC_ABSPATH' ) && wp_style_is( 'mec-frontend-style', 'registered' ) ) {
		wp_add_inline_style( 'mec-frontend-style', jmw_mec_bridge_css() );
	}
}, 100 );
