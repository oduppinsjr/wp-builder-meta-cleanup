<?php
/**
 * Preset targets: plugins often cited for leaving wp_options / postmeta after uninstall.
 * Extend or override via builder_meta_cleanup_targets filter.
 *
 * @package Builder_Meta_Cleanup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'magic_page'             => array(
		'label'        => __( 'Magic Page', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'magic-page/magic-page.php',
			'magic-page-builder/magic-page-builder.php',
		),
		'meta'         => array(
			'magic_page_u' => array(
				'label'       => __( 'meta_key LIKE _magic_page%', 'builder-meta-cleanup' ),
				'like_prefix' => '_magic_page',
			),
			'magic_u'      => array(
				'label'       => __( 'meta_key LIKE _magic%', 'builder-meta-cleanup' ),
				'like_prefix' => '_magic',
			),
			'magic_pub'    => array(
				'label'       => __( 'meta_key LIKE Magic Page%', 'builder-meta-cleanup' ),
				'like_prefix' => 'Magic Page',
			),
		),
		'options_like' => array(
			'magic_low' => array(
				'label'       => __( 'wp_options.option_name LIKE magic_page%', 'builder-meta-cleanup' ),
				'like_prefix' => 'magic_page',
			),
		),
	),
	'slider_revolution'      => array(
		'label'        => __( 'Slider Revolution', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'revslider/revslider.php' ),
		'meta'         => array(
			'rev' => array(
				'label'       => __( 'meta_key LIKE revslider%', 'builder-meta-cleanup' ),
				'like_prefix' => 'revslider',
			),
		),
		'options_like' => array(
			'rev_opt' => array(
				'label'       => __( 'wp_options.option_name LIKE revslider%', 'builder-meta-cleanup' ),
				'like_prefix' => 'revslider',
			),
			'rs_opt'  => array(
				'label'       => __( 'wp_options.option_name LIKE rs_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'rs_',
			),
		),
	),
	'layerslider'            => array(
		'label'        => __( 'LayerSlider', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'LayerSlider/layerslider.php',
			'layerslider/layerslider.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'ls' => array(
				'label'       => __( 'wp_options.option_name LIKE layerslider%', 'builder-meta-cleanup' ),
				'like_prefix' => 'layerslider',
			),
		),
	),
	'wpbakery'               => array(
		'label'        => __( 'WPBakery Page Builder (Visual Composer)', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'js_composer/js_composer.php' ),
		'meta'         => array(
			'wpb' => array(
				'label'       => __( 'meta_key LIKE _wpb_%', 'builder-meta-cleanup' ),
				'like_prefix' => '_wpb_',
			),
		),
		'options_like' => array(
			'wpb_js' => array(
				'label'       => __( 'wp_options.option_name LIKE wpb_js_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'wpb_js_',
			),
			'vc_short' => array(
				'label'       => __( 'wp_options.option_name LIKE vc_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'vc_',
			),
		),
	),
	'yoast_seo'              => array(
		'label'        => __( 'Yoast SEO', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'wordpress-seo/wp-seo.php',
			'wordpress-seo-premium/wp-seo-premium.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'wpseo' => array(
				'label'       => __( 'wp_options.option_name LIKE wpseo%', 'builder-meta-cleanup' ),
				'like_prefix' => 'wpseo',
			),
			'yoast' => array(
				'label'       => __( 'wp_options.option_name LIKE yoast%', 'builder-meta-cleanup' ),
				'like_prefix' => 'yoast',
			),
		),
	),
	'aioseo'                 => array(
		'label'        => __( 'All in One SEO', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'all-in-one-seo-pack/all_in_one_seo_pack.php',
			'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'aioseo' => array(
				'label'       => __( 'wp_options.option_name LIKE aioseo%', 'builder-meta-cleanup' ),
				'like_prefix' => 'aioseo',
			),
		),
	),
	'w3_total_cache'         => array(
		'label'        => __( 'W3 Total Cache', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'w3-total-cache/w3-total-cache.php' ),
		'meta'         => array(),
		'options_like' => array(
			'w3tc' => array(
				'label'       => __( 'wp_options.option_name LIKE w3tc_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'w3tc_',
			),
		),
	),
	'wordfence'              => array(
		'label'        => __( 'Wordfence Security', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'wordfence/wordfence.php' ),
		'meta'         => array(),
		'options_like' => array(
			'wf' => array(
				'label'       => __( 'wp_options.option_name LIKE wordfence%', 'builder-meta-cleanup' ),
				'like_prefix' => 'wordfence',
			),
		),
	),
	'updraftplus'            => array(
		'label'        => __( 'UpdraftPlus', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'updraftplus/updraftplus.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'updraft' => array(
				'label'       => __( 'wp_options.option_name LIKE updraft_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'updraft_',
			),
		),
	),
	'wp_rocket'              => array(
		'label'        => __( 'WP Rocket', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'wp-rocket/wp-rocket.php' ),
		'meta'         => array(),
		'options_like' => array(
			'rocket' => array(
				'label'       => __( 'wp_options.option_name LIKE wp_rocket_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'wp_rocket_',
			),
		),
	),
	'jetpack'                => array(
		'label'        => __( 'Jetpack', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'jetpack/jetpack.php' ),
		'meta'         => array(),
		'options_like' => array(
			'jp' => array(
				'label'       => __( 'wp_options.option_name LIKE jetpack_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'jetpack_',
			),
		),
	),
	'nextgen_gallery'        => array(
		'label'        => __( 'NextGEN Gallery', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'nextgen-gallery/nggallery.php' ),
		'meta'         => array(
			'ngg' => array(
				'label'       => __( 'meta_key LIKE _ngg%', 'builder-meta-cleanup' ),
				'like_prefix' => '_ngg',
			),
		),
		'options_like' => array(
			'ngg_opt' => array(
				'label'       => __( 'wp_options.option_name LIKE ngg_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'ngg_',
			),
		),
	),
	'gravity_forms'          => array(
		'label'        => __( 'Gravity Forms', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'gravityforms/gravityforms.php' ),
		'meta'         => array(
			'gf' => array(
				'label'       => __( 'meta_key LIKE _gform%', 'builder-meta-cleanup' ),
				'like_prefix' => '_gform',
			),
		),
		'options_like' => array(
			'gf_opt' => array(
				'label'       => __( 'wp_options.option_name LIKE rg_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'rg_',
			),
			'gf_gf'  => array(
				'label'       => __( 'wp_options.option_name LIKE gf_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'gf_',
			),
		),
	),
	'contact_form_7'         => array(
		'label'        => __( 'Contact Form 7', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'contact-form-7/wp-contact-form-7.php',
			'contact-form-7/contact-form-7.php',
			'contact-form-7/load.php',
		),
		'meta'         => array(
			'cf7' => array(
				'label'       => __( 'meta_key LIKE _wpcf7%', 'builder-meta-cleanup' ),
				'like_prefix' => '_wpcf7',
			),
		),
		'options_like' => array(
			'wpcf7' => array(
				'label'       => __( 'wpcf7 option blobs (option_name LIKE wpcf7%)', 'builder-meta-cleanup' ),
				'like_prefix' => 'wpcf7',
			),
		),
	),
	'wpml'                   => array(
		'label'        => __( 'WPML (SitePress)', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'sitepress-multilingual-cms/sitepress.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'icl' => array(
				'label'       => __( 'wp_options.option_name LIKE icl_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'icl_',
			),
		),
	),
	'polylang'               => array(
		'label'        => __( 'Polylang', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'polylang/polylang.php' ),
		'meta'         => array(),
		'options_like' => array(
			'pll' => array(
				'label'       => __( 'wp_options.option_name LIKE polylang%', 'builder-meta-cleanup' ),
				'like_prefix' => 'polylang',
			),
			'pllwidget' => array(
				'label'       => __( 'wp_options.option_name LIKE widget_polylang%', 'builder-meta-cleanup' ),
				'like_prefix' => 'widget_polylang',
			),
		),
	),
	'redirection'            => array(
		'label'        => __( 'Redirection', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'redirection/redirection.php' ),
		'meta'         => array(),
		'options_like' => array(
			'redir' => array(
				'label'       => __( 'wp_options.option_name LIKE redirection%', 'builder-meta-cleanup' ),
				'like_prefix' => 'redirection',
			),
		),
	),
	'really_simple_ssl'      => array(
		'label'        => __( 'Really Simple SSL', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'really-simple-ssl/rlrsssl-really-simple-ssl.php',
			'really-simple-ssl/really-simple-ssl.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'rsssl' => array(
				'label'       => __( 'wp_options.option_name LIKE rsssl_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'rsssl_',
			),
		),
	),
	'tablepress'             => array(
		'label'        => __( 'TablePress', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array( 'tablepress/tablepress.php' ),
		'meta'         => array(),
		'options_like' => array(
			'tp' => array(
				'label'       => __( 'wp_options.option_name LIKE tablepress_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'tablepress_',
			),
		),
	),
	'wpforms'                => array(
		'label'        => __( 'WPForms', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'wpforms-lite/wpforms.php',
			'wpforms/wpforms.php',
		),
		'meta'         => array(
			'wpf' => array(
				'label'       => __( 'meta_key LIKE _wpforms%', 'builder-meta-cleanup' ),
				'like_prefix' => '_wpforms',
			),
		),
		'options_like' => array(
			'wpforms_opt' => array(
				'label'       => __( 'wp_options.option_name LIKE wpforms_%', 'builder-meta-cleanup' ),
				'like_prefix' => 'wpforms_',
			),
		),
	),
	'smush'                  => array(
		'label'        => __( 'Smush', 'builder-meta-cleanup' ),
		'ui_tab'       => 'plugin',
		'plugin_paths' => array(
			'wp-smushit/wp-smush.php',
		),
		'meta'         => array(),
		'options_like' => array(
			'smush' => array(
				'label'       => __( 'wp_options.option_name LIKE wp-smush-%', 'builder-meta-cleanup' ),
				'like_prefix' => 'wp-smush-',
			),
			'smush_c' => array(
				'label'       => __( 'wp_options.option_name LIKE smush%', 'builder-meta-cleanup' ),
				'like_prefix' => 'smush',
			),
		),
	),
);
