<?php
/**
 * Styles WordPress's own login screens (log in, lost password, new password, messages) with the site's
 * logo and Blocksy colours. WordPress keeps doing all the work; this only changes how it looks.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One Blocksy palette colour (1–8), falling back to Blocksy's default palette. */
function jmw_palette( $n ) {
	$defaults = array( 1 => '#2872fa', 2 => '#1559ed', 3 => '#3A4F66', 4 => '#192a3d', 5 => '#e1e8ed', 6 => '#f2f5f7', 7 => '#FAFBFC', 8 => '#ffffff' );
	$palette  = get_theme_mod( 'colorPalette', array() );
	$value    = is_array( $palette ) && isset( $palette[ 'color' . $n ]['color'] ) ? $palette[ 'color' . $n ]['color'] : '';
	return ( is_string( $value ) && preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) ? $value : $defaults[ $n ];
}

/** The site's body font from the Blocksy Customizer, or '' for the system font. */
function jmw_body_font() {
	$t      = get_theme_mod( 'rootTypography', array() );
	$family = is_array( $t ) && ! empty( $t['family'] ) ? (string) $t['family'] : '';
	if ( '' === $family || 'System Default' === $family || 0 === strpos( $family, 'ct_' ) ) {
		return '';
	}
	return preg_replace( '/[^A-Za-z0-9 \-]/', '', $family );
}

add_action( 'login_enqueue_scripts', function () {
	$logo_id  = (int) get_theme_mod( 'custom_logo' );
	$logo     = $logo_id ? wp_get_attachment_image_src( $logo_id, 'medium' ) : false;
	$font     = jmw_body_font();
	$stack    = ( $font ? "'" . $font . "', " : '' ) . "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
	$c1       = jmw_palette( 1 );
	$c2       = jmw_palette( 2 );
	$text     = jmw_palette( 3 );
	$heading  = jmw_palette( 4 );
	$border   = jmw_palette( 5 );
	$bg       = jmw_palette( 6 );
	$white    = jmw_palette( 8 );
	$logo_css = $logo
		? sprintf( 'background-image:url(%s);background-size:contain;background-position:center;width:%dpx;height:%dpx;', esc_url( $logo[0] ), 240, max( 48, min( 120, (int) round( 240 * $logo[2] / max( 1, $logo[1] ) ) ) ) )
		: 'background:none;width:auto;height:auto;text-indent:0;font-size:26px;font-weight:700;line-height:1.3;color:' . $heading . ';';
	$css = "
body.login{background:{$bg};font-family:{$stack};color:{$text}}
body.login #login{width:360px;padding:8vh 0 24px}
.login h1 a{{$logo_css}margin:0 auto 24px;overflow:visible}
.login form{background:{$white};border:1px solid {$border};border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.06);padding:26px 26px 30px;margin-top:0}
.login label{color:{$heading};font-size:14px;font-weight:600}
.login input[type=text],.login input[type=password],.login input[type=email]{border:1px solid {$border};border-radius:6px;padding:8px 12px;font-size:16px;box-shadow:none;background:{$white}}
.login input[type=text]:focus,.login input[type=password]:focus,.login input[type=email]:focus{border-color:{$c1};box-shadow:0 0 0 1px {$c1}}
.login .button-primary{background:{$c1};border-color:{$c1};border-radius:6px;padding:4px 18px;font-size:15px;font-weight:600;text-shadow:none;box-shadow:none}
.login .button-primary:hover,.login .button-primary:focus{background:{$c2};border-color:{$c2}}
.login .button.wp-hide-pw .dashicons,.login .button.wp-hide-pw:focus{color:{$c1};box-shadow:none;border-color:transparent}
.login input[type=checkbox]:checked::before{color:{$c1}}
.login #nav,.login #backtoblog{text-align:center;padding:0}
.login #nav a,.login #backtoblog a,.login .privacy-policy-page-link a{color:{$text}}
.login #nav a:hover,.login #backtoblog a:hover{color:{$c1}}
.login .message,.login .notice,.login .success,.login #login_error{border-radius:8px;border-left-width:4px;box-shadow:none;border-left-color:{$c1}}
.login #login_error{border-left-color:#d63638}
.login .language-switcher{display:none}
";
	wp_register_style( 'jmw-login', false, array(), JMW_VERSION );
	wp_enqueue_style( 'jmw-login' );
	wp_add_inline_style( 'jmw-login', $css );
} );

// The logo links to this site, not WordPress.org, and is named after it.
add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', function () {
	return get_bloginfo( 'name' );
} );
