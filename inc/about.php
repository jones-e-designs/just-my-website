<?php
/**
 * About this theme, and a small signal for assistants.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JMW_CAPTAIN_MAX_URL', 'https://captainmax.ai/websites/?utm_source=just-my-website-theme&utm_medium=wp-admin&utm_campaign=works-best-with-captain-max' );

// Tells a signed-in assistant this is a Just My Website site, and which version.
add_action( 'rest_api_init', function () {
	register_rest_route( 'just-my-website/v1', '/info', array(
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
		'callback'            => function () {
			return rest_ensure_response( array(
				'product' => 'Just My Website',
				'version' => JMW_VERSION,
				'parent'  => 'blocksy',
				'site'    => home_url( '/' ),
			) );
		},
	) );
} );

function jmw_render_about() {
	$img = add_query_arg( 'ver', JMW_VERSION, get_stylesheet_directory_uri() . '/screenshot.jpg' );
	?>
	<div class="jmw-about" style="display:flex;gap:28px;align-items:center;flex-wrap:wrap;max-width:980px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:22px 24px;margin:16px 0 8px">
		<img src="<?php echo esc_url( $img ); ?>" alt="Just My Website: a family picnic in the countryside, under the Just My Website name" style="width:280px;max-width:100%;height:auto;border-radius:6px">
		<div style="flex:1;min-width:260px">
			<h2 style="margin-top:0">Just My Website <span style="color:#646970;font-weight:400;font-size:13px">version <?php echo esc_html( JMW_VERSION ); ?></span></h2>
			<p>A child theme for <a href="https://creativethemes.com/blocksy/" target="_blank" rel="noopener">Blocksy</a> by Creative Themes. Blocksy does the heavy lifting; Just My Website adds the finishing touches and keeps your site tidy.</p>
			<p style="font-size:15px"><strong>Works best with Captain Max.</strong> Tell Max what to change on your website, in plain words, and he takes care of it.</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( JMW_CAPTAIN_MAX_URL ); ?>" target="_blank" rel="noopener">Meet Captain Max</a>
				<a class="button" href="https://justmywebsite.com/" target="_blank" rel="noopener">justmywebsite.com</a>
			</p>
			<p style="color:#646970">Using Google Analytics? Paste your measurement ID in <a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">Customize</a> › General › Engagement.</p>
			<p style="color:#646970;margin-bottom:0">By <a href="https://jonesedesigns.com/" target="_blank" rel="noopener">Jones E Designs</a> · Free software under the GPL.</p>
		</div>
	</div>
	<?php
}
