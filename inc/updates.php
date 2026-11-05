<?php
/**
 * Update notices: WordPress checks this theme's public GitHub releases and shows "Update available" under
 * Appearance › Themes. Updating is a normal one-click WordPress theme update.
 * Library: Plugin Update Checker by Jānis Elsts (MIT), in inc/vendor/plugin-update-checker.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';

$jmw_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/jones-e-designs/just-my-website/',
	get_stylesheet_directory() . '/functions.php',
	'just-my-website'
);
