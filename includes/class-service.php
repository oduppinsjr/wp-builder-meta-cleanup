<?php
/**
 * Registry, environment detection, and DB operations.
 *
 * @package Builder_Meta_Cleanup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Builder_Meta_Cleanup_Service {

	public const BATCH = 3000;
	public const NONCE = 'builder_meta_cleanup_action';

	/**
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $targets_cache = null;

	/**
	 * Target definitions: meta SQL patterns, optional exact wp_options keys, and optional wp_options LIKE patterns.
	 * Cleanup is only offered when the stack is not active (see is_target_active).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_targets(): array {
		if ( null !== self::$targets_cache ) {
			return self::$targets_cache;
		}

		$targets = array(
			'elementor'   => array(
				'label'  => __( 'Elementor', 'builder-meta-cleanup' ),
				'ui_tab' => 'page_builder',
				'meta'   => array(
					'elementor' => array(
						'label'       => __( 'meta_key LIKE _elementor_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_elementor_',
					),
				),
				'options' => array(
					'elementor_version'     => __( 'Elementor — elementor_version', 'builder-meta-cleanup' ),
					'elementor_active_kit'  => __( 'Elementor — elementor_active_kit', 'builder-meta-cleanup' ),
				),
			),
			'divi'        => array(
				'label'  => __( 'Divi / Extra (Elegant Themes)', 'builder-meta-cleanup' ),
				'ui_tab' => 'page_builder',
				'meta'   => array(
					'divi_private' => array(
						'label'       => __( 'meta_key LIKE _et_pb_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_et_pb_',
					),
					'divi_public'  => array(
						'label'       => __( 'meta_key LIKE et_pb_%', 'builder-meta-cleanup' ),
						'like_prefix' => 'et_pb_',
					),
				),
				'options' => array(
					'et_divi'            => __( 'Divi — Theme Options (et_divi)', 'builder-meta-cleanup' ),
					'theme_mods_divi'    => __( 'Divi — Customizer (theme_mods_divi)', 'builder-meta-cleanup' ),
					'theme_mods_Divi'    => __( 'Divi — Customizer (theme_mods_Divi)', 'builder-meta-cleanup' ),
					'et_extra'           => __( 'Extra — Theme Options (et_extra)', 'builder-meta-cleanup' ),
					'theme_mods_extra'   => __( 'Extra — Customizer (theme_mods_extra)', 'builder-meta-cleanup' ),
					'theme_mods_Extra'   => __( 'Extra — Customizer (theme_mods_Extra)', 'builder-meta-cleanup' ),
				),
			),
			'beaver'      => array(
				'label'  => __( 'Beaver Builder', 'builder-meta-cleanup' ),
				'ui_tab' => 'page_builder',
				'meta'   => array(
					'beaver' => array(
						'label'       => __( 'meta_key LIKE _fl_builder_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_fl_builder_',
					),
				),
			),
			'bricks'      => array(
				'label'  => __( 'Bricks', 'builder-meta-cleanup' ),
				'ui_tab' => 'page_builder',
				'meta'   => array(
					'bricks' => array(
						'label'       => __( 'meta_key LIKE _bricks_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_bricks_',
					),
				),
			),
			'seedprod'    => array(
				'label'  => __( 'SeedProd', 'builder-meta-cleanup' ),
				'ui_tab' => 'page_builder',
				'meta'   => array(
					'seedprod_u' => array(
						'label'       => __( 'meta_key LIKE _seedprod_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_seedprod_',
					),
					'seedprod'   => array(
						'label'       => __( 'meta_key LIKE seedprod_%', 'builder-meta-cleanup' ),
						'like_prefix' => 'seedprod_',
					),
				),
			),
			'hello'       => array(
				'label'  => __( 'Hello Elementor', 'builder-meta-cleanup' ),
				'ui_tab' => 'theme',
				'meta'   => array(
					'hello' => array(
						'label'       => __( 'meta_key LIKE _hello_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_hello_',
					),
				),
			),
			'betheme'     => array(
				'label'  => __( 'BeTheme / Muffin', 'builder-meta-cleanup' ),
				'ui_tab' => 'theme',
				'meta'   => array(
					'mfn' => array(
						'label'       => __( 'meta_key LIKE mfn-%', 'builder-meta-cleanup' ),
						'like_prefix' => 'mfn-',
					),
				),
				'options' => array(
					'betheme'            => __( 'BeTheme — Theme Options (betheme)', 'builder-meta-cleanup' ),
					'theme_mods_betheme' => __( 'BeTheme — Customizer (theme_mods_betheme)', 'builder-meta-cleanup' ),
				),
			),
			'astra'       => array(
				'label'  => __( 'Astra', 'builder-meta-cleanup' ),
				'ui_tab' => 'theme',
				'meta'   => array(
					'astra'         => array(
						'label'       => __( 'meta_key LIKE ast-%', 'builder-meta-cleanup' ),
						'like_prefix' => 'ast-',
					),
					'astra_private' => array(
						'label'       => __( 'meta_key LIKE _astra_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_astra_',
					),
				),
				'options' => array(
					'astra-settings'   => __( 'Astra — Theme options (astra-settings)', 'builder-meta-cleanup' ),
					'theme_mods_astra' => __( 'Astra — Customizer (theme_mods_astra)', 'builder-meta-cleanup' ),
				),
			),
			'fusion'                   => array(
				'label'    => __( 'Fusion / Avada Builder', 'builder-meta-cleanup' ),
				'ui_tab'   => 'theme',
				'meta'     => array(
					'fusion_root' => array(
						'label'       => __( 'meta_key LIKE _fusion%', 'builder-meta-cleanup' ),
						'like_prefix' => '_fusion',
					),
				),
				'options_like' => array(
					'fs_options' => array(
						'label'       => __( 'wp_options.option_name LIKE FS_% (Fusion option fragments)', 'builder-meta-cleanup' ),
						'like_prefix' => 'FS_',
					),
				),
			),
			'premium_addons_elementor' => array(
				'label'        => __( 'Premium Addons for Elementor', 'builder-meta-cleanup' ),
				'ui_tab'       => 'plugin',
				'plugin_paths' => array(
					'premium-addons-for-elementor/premium-addons-for-elementor.php',
					'premium-addons-pro/premium-addons-pro-for-elementor.php',
				),
				'meta'         => array(),
				'options_like' => array(
					'pa_options' => array(
						'label'       => __( 'wp_options.option_name LIKE PA_% (dynamic assets / caches)', 'builder-meta-cleanup' ),
						'like_prefix' => 'PA_',
					),
				),
			),
			'essential_addons_elementor' => array(
				'label'        => __( 'Essential Addons for Elementor', 'builder-meta-cleanup' ),
				'ui_tab'       => 'plugin',
				'plugin_paths' => array(
					'essential-addons-for-elementor/essential-addons-for-elementor.php',
					'essential-addons-elementor-pro/essential-addons-elementor-pro.php',
					'essential-addons-for-elementor-pro/essential-addons-for-elementor-pro.php',
				),
				'meta'         => array(),
				'options_like' => array(
					'eael_options' => array(
						'label'       => __( 'wp_options.option_name LIKE eael_%', 'builder-meta-cleanup' ),
						'like_prefix' => 'eael_',
					),
				),
			),
			'ultimate_addons_elementor' => array(
				'label'        => __( 'Ultimate Addons for Elementor', 'builder-meta-cleanup' ),
				'ui_tab'       => 'plugin',
				'plugin_paths' => array(
					'ultimate-elementor/ultimate-elementor.php',
					'ultimate-elementor-pro/ultimate-elementor-pro.php',
				),
				'meta'         => array(),
				'options_like' => array(
					'uael_options' => array(
						'label'       => __( 'wp_options.option_name LIKE uael_%', 'builder-meta-cleanup' ),
						'like_prefix' => 'uael_',
					),
				),
			),
		);

		$cruft_file = BUILDER_META_CLEANUP_DIR . 'includes/data-plugin-cruft-targets.php';
		if ( file_exists( $cruft_file ) ) {
			$cruft_included = include $cruft_file;
			if ( is_array( $cruft_included ) ) {
				$targets = array_merge( $targets, $cruft_included );
			}
		}

		foreach ( $targets as $tid => &$target_def ) {
			if ( ! empty( $target_def['plugin_paths'] ) && is_array( $target_def['plugin_paths'] ) ) {
				$target_def['plugin_paths'] = apply_filters( 'builder_meta_cleanup_plugin_paths', $target_def['plugin_paths'], (string) $tid );
			}
		}
		unset( $target_def );

		/**
		 * Filter full target registry (add targets, meta patterns, or option rows).
		 *
		 * @param array<string, array<string, mixed>> $targets
		 */
		self::$targets_cache = apply_filters( 'builder_meta_cleanup_targets', $targets );

		return self::$targets_cache;
	}

	public static function load_plugin_functions(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * @param list<string> $relative_paths Paths relative to WP_PLUGIN_DIR.
	 */
	private static function is_any_plugin_active( array $relative_paths ): bool {
		self::load_plugin_functions();
		foreach ( $relative_paths as $rel ) {
			$rel = (string) $rel;
			if ( '' === $rel ) {
				continue;
			}
			if ( file_exists( WP_PLUGIN_DIR . '/' . $rel ) && is_plugin_active( $rel ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param list<string> $relative_paths Paths relative to WP_PLUGIN_DIR.
	 */
	private static function is_any_plugin_present( array $relative_paths ): bool {
		foreach ( $relative_paths as $rel ) {
			$rel = (string) $rel;
			if ( '' === $rel ) {
				continue;
			}
			if ( file_exists( WP_PLUGIN_DIR . '/' . $rel ) ) {
				return true;
			}
		}
		return false;
	}

	public static function is_target_active( string $target_id ): bool {
		self::load_plugin_functions();

		$registry = self::get_targets();
		if ( ! empty( $registry[ $target_id ]['plugin_paths'] ) && is_array( $registry[ $target_id ]['plugin_paths'] ) ) {
			return self::is_any_plugin_active( $registry[ $target_id ]['plugin_paths'] );
		}

		switch ( $target_id ) {
			case 'elementor':
				return class_exists( '\Elementor\Plugin', false );

			case 'divi':
				$t = strtolower( (string) get_template() );
				$s = strtolower( (string) get_stylesheet() );
				if ( in_array( $t, array( 'divi', 'extra' ), true ) || in_array( $s, array( 'divi', 'extra' ), true ) ) {
					return true;
				}
				return is_plugin_active( 'divi-builder/divi-builder.php' );

			case 'beaver':
				return class_exists( 'FLBuilder', false );

			case 'bricks':
				return defined( 'BRICKS_VERSION' );

			case 'seedprod':
				if ( defined( 'SEEDPROD_BUILD' ) || defined( 'SEEDPROD_VERSION' ) ) {
					return true;
				}
				$paths = array(
					'coming-soon/coming-soon.php',
					'coming-soon/seedprod-coming-soon.php',
					'seedprod-coming-soon-pro/seedprod-coming-soon-pro.php',
				);
				foreach ( $paths as $rel ) {
					if ( file_exists( WP_PLUGIN_DIR . '/' . $rel ) && is_plugin_active( $rel ) ) {
						return true;
					}
				}
				return false;

			case 'hello':
				$t = strtolower( (string) get_template() );
				$s = strtolower( (string) get_stylesheet() );
				return ( 'hello-elementor' === $t || 'hello-elementor' === $s );

			case 'betheme':
				$t = strtolower( (string) get_template() );
				$s = strtolower( (string) get_stylesheet() );
				return ( 'betheme' === $t || 'betheme' === $s );

			case 'astra':
				$t = strtolower( (string) get_template() );
				$s = strtolower( (string) get_stylesheet() );
				return ( 'astra' === $t || 'astra' === $s );

			case 'fusion':
				$t = strtolower( (string) get_template() );
				$s = strtolower( (string) get_stylesheet() );
				if ( 'avada' === $t || 'avada' === $s ) {
					return true;
				}
				return self::is_any_plugin_active(
					array(
						'fusion-core/fusion-core.php',
						'fusion-builder/fusion-builder.php',
					)
				);

			default:
				return (bool) apply_filters( 'builder_meta_cleanup_is_target_active', false, $target_id );
		}
	}

	public static function is_target_installed( string $target_id ): bool {
		self::load_plugin_functions();

		$registry = self::get_targets();
		if ( ! empty( $registry[ $target_id ]['plugin_paths'] ) && is_array( $registry[ $target_id ]['plugin_paths'] ) ) {
			return self::is_any_plugin_present( $registry[ $target_id ]['plugin_paths'] );
		}

		switch ( $target_id ) {
			case 'elementor':
				return file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' )
					|| file_exists( WP_PLUGIN_DIR . '/elementor-pro/elementor-pro.php' );

			case 'divi':
				if ( wp_get_theme( 'Divi' )->exists() || wp_get_theme( 'Extra' )->exists() ) {
					return true;
				}
				return file_exists( WP_PLUGIN_DIR . '/divi-builder/divi-builder.php' );

			case 'beaver':
				return file_exists( WP_PLUGIN_DIR . '/beaver-builder-lite-version/fl-builder.php' )
					|| file_exists( WP_PLUGIN_DIR . '/bb-plugin/fl-builder.php' );

			case 'bricks':
				return file_exists( WP_PLUGIN_DIR . '/bricks/bricks.php' );

			case 'seedprod':
				$paths = array(
					'coming-soon/coming-soon.php',
					'coming-soon/seedprod-coming-soon.php',
					'seedprod-coming-soon-pro/seedprod-coming-soon-pro.php',
				);
				foreach ( $paths as $rel ) {
					if ( file_exists( WP_PLUGIN_DIR . '/' . $rel ) ) {
						return true;
					}
				}
				return false;

			case 'hello':
				return wp_get_theme( 'hello-elementor' )->exists();

			case 'betheme':
				return wp_get_theme( 'betheme' )->exists();

			case 'astra':
				return wp_get_theme( 'astra' )->exists();

			case 'fusion':
				if ( wp_get_theme( 'Avada' )->exists() ) {
					return true;
				}
				return self::is_any_plugin_present(
					array(
						'fusion-core/fusion-core.php',
						'fusion-builder/fusion-builder.php',
					)
				);

			default:
				return (bool) apply_filters( 'builder_meta_cleanup_is_target_installed', false, $target_id );
		}
	}

	public static function meta_like( string $prefix ): string {
		global $wpdb;
		return $wpdb->esc_like( $prefix ) . '%';
	}

	public static function count_meta_like( string $like ): int {
		global $wpdb;
		if ( '' === $like ) {
			return 0;
		}
		// No WordPress API exists to COUNT(*) postmeta rows by meta_key LIKE; required for admin counts.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
				$like
			)
		);
		// phpcs:enable
	}

	public static function delete_meta_like( string $like ): int {
		global $wpdb;
		if ( '' === $like ) {
			return 0;
		}
		$total = 0;
		$batch = absint( self::BATCH );
		// Batched DELETE by meta_key pattern; delete_post_meta() has no bulk-LIKE equivalent.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		do {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s LIMIT %d",
					$like,
					$batch
				)
			);
			if ( false === $deleted ) {
				break;
			}
			$total += (int) $deleted;
		} while ( $deleted > 0 );
		// phpcs:enable

		return $total;
	}

	public static function count_options_like( string $like ): int {
		global $wpdb;
		if ( '' === $like ) {
			return 0;
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
		// phpcs:enable
	}

	public static function delete_options_like( string $like ): int {
		global $wpdb;
		if ( '' === $like ) {
			return 0;
		}
		$total = 0;
		$batch = absint( self::BATCH );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		do {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d",
					$like,
					$batch
				)
			);
			if ( false === $deleted ) {
				break;
			}
			$total += (int) $deleted;
		} while ( $deleted > 0 );
		// phpcs:enable

		return $total;
	}

	/**
	 * @return string|null SQL LIKE pattern including trailing %, or null if unknown ids.
	 */
	public static function options_like_pattern_for( string $target_id, string $pattern_id ): ?string {
		$targets = self::get_targets();
		if ( ! isset( $targets[ $target_id ]['options_like'][ $pattern_id ]['like_prefix'] ) ) {
			return null;
		}
		$pfx = (string) $targets[ $target_id ]['options_like'][ $pattern_id ]['like_prefix'];
		return self::meta_like( $pfx );
	}

	public static function count_target_options_like_block( string $target_id, string $pattern_id ): int {
		$like = self::options_like_pattern_for( $target_id, $pattern_id );
		if ( null === $like ) {
			return 0;
		}
		return self::count_options_like( $like );
	}

	public static function delete_target_options_like_block( string $target_id, string $pattern_id ): int {
		$like = self::options_like_pattern_for( $target_id, $pattern_id );
		if ( null === $like ) {
			return 0;
		}
		return self::delete_options_like( $like );
	}

	public static function count_target_options_like_total( string $target_id ): int {
		$targets = self::get_targets();
		if ( empty( $targets[ $target_id ]['options_like'] ) ) {
			return 0;
		}
		$sum = 0;
		foreach ( array_keys( $targets[ $target_id ]['options_like'] ) as $pid ) {
			$sum += self::count_target_options_like_block( $target_id, (string) $pid );
		}
		return $sum;
	}

	/**
	 * @return array{exists: bool, bytes: int}
	 */
	public static function option_row_info( string $option_name ): array {
		global $wpdb;
		// LENGTH() avoids loading large serialized options into PHP; get_option() would not be appropriate here.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$len = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT LENGTH(option_value) FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$option_name
			)
		);
		// phpcs:enable
		if ( null === $len ) {
			return array( 'exists' => false, 'bytes' => 0 );
		}
		return array( 'exists' => true, 'bytes' => (int) $len );
	}

	public static function delete_option_by_name( string $option_name ): bool {
		return delete_option( $option_name );
	}

	public static function flush_caches(): void {
		if ( function_exists( 'wp_cache_flush_runtime' ) ) {
			wp_cache_flush_runtime();
		} elseif ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
	}

	public static function format_bytes( int $bytes ): string {
		if ( $bytes < 1024 ) {
			return sprintf( '%d B', $bytes );
		}
		if ( $bytes < 1048576 ) {
			return sprintf( '%.1f KB', $bytes / 1024 );
		}
		return sprintf( '%.1f MB', $bytes / 1048576 );
	}

	public static function count_target_meta( string $target_id ): int {
		$targets = self::get_targets();
		if ( ! isset( $targets[ $target_id ]['meta'] ) ) {
			return 0;
		}
		$sum = 0;
		foreach ( $targets[ $target_id ]['meta'] as $block ) {
			$sum += self::count_meta_like( self::meta_like( $block['like_prefix'] ) );
		}
		return $sum;
	}

	public static function delete_target_meta( string $target_id ): int {
		$targets = self::get_targets();
		if ( ! isset( $targets[ $target_id ]['meta'] ) ) {
			return 0;
		}
		$total = 0;
		foreach ( $targets[ $target_id ]['meta'] as $block ) {
			$total += self::delete_meta_like( self::meta_like( $block['like_prefix'] ) );
		}
		return $total;
	}
}
