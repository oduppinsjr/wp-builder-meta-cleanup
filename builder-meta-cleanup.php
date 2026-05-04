<?php
/**
 * Plugin Name:       Builder Meta Cleanup
 * Plugin URI:        https://github.com/oduppinsjr/wp-builder-meta-cleanup
 * Description:       Detect major page builders and remove orphaned postmeta / allowlisted options only when that stack is not active.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Update URI:        https://github.com/oduppinsjr/wp-builder-meta-cleanup
 * Author:            Duppins Technology
 * Author URI: 	      https://duppinstech.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       builder-meta-cleanup
 *
 * @package Builder_Meta_Cleanup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BUILDER_META_CLEANUP_VERSION', '2.1.0' );
define( 'BUILDER_META_CLEANUP_FILE', __FILE__ );
define( 'BUILDER_META_CLEANUP_DIR', plugin_dir_path( __FILE__ ) );

require_once BUILDER_META_CLEANUP_DIR . 'includes/class-service.php';
require_once BUILDER_META_CLEANUP_DIR . 'includes/class-updater.php';
require_once BUILDER_META_CLEANUP_DIR . 'includes/class-admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BUILDER_META_CLEANUP_DIR . 'includes/class-cli.php';
	add_action(
		'plugins_loaded',
		static function () {
			Builder_Meta_Cleanup_CLI::register();
		},
		30
	);
}

add_action(
	'plugins_loaded',
	static function () {
		Builder_Meta_Cleanup_Updater::init();
		Builder_Meta_Cleanup_Admin::init();
	},
	20
);
