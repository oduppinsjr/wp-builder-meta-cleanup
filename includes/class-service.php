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
	 * Target definitions: meta SQL patterns and optional exact wp_options keys.
	 * Cleanup is only offered when the stack is not active (see is_target_active).
	 *
	 * @return array<string, array{label: string, meta: array<string, array{label: string, like_prefix: string}>, options?: array<string, string>}>
	 */
	public static function get_targets(): array {
		$targets = array(
			'elementor'   => array(
				'label' => __( 'Elementor', 'builder-meta-cleanup' ),
				'meta'  => array(
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
				'label' => __( 'Divi / Extra (Elegant Themes)', 'builder-meta-cleanup' ),
				'meta'  => array(
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
				'label' => __( 'Beaver Builder', 'builder-meta-cleanup' ),
				'meta'  => array(
					'beaver' => array(
						'label'       => __( 'meta_key LIKE _fl_builder_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_fl_builder_',
					),
				),
			),
			'bricks'      => array(
				'label' => __( 'Bricks', 'builder-meta-cleanup' ),
				'meta'  => array(
					'bricks' => array(
						'label'       => __( 'meta_key LIKE _bricks_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_bricks_',
					),
				),
			),
			'seedprod'    => array(
				'label' => __( 'SeedProd', 'builder-meta-cleanup' ),
				'meta'  => array(
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
				'label' => __( 'Hello Elementor', 'builder-meta-cleanup' ),
				'meta'  => array(
					'hello' => array(
						'label'       => __( 'meta_key LIKE _hello_%', 'builder-meta-cleanup' ),
						'like_prefix' => '_hello_',
					),
				),
			),
			'betheme'     => array(
				'label' => __( 'BeTheme / Muffin', 'builder-meta-cleanup' ),
				'meta'  => array(
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
				'label' => __( 'Astra', 'builder-meta-cleanup' ),
				'meta'  => array(
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
		);

		/**
		 * Filter full target registry (add targets, meta patterns, or option rows).
		 *
		 * @param array<string, array<string, mixed>> $targets
		 */
		return apply_filters( 'builder_meta_cleanup_targets', $targets );
	}

	public static function load_plugin_functions(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	public static function is_target_active( string $target_id ): bool {
		self::load_plugin_functions();

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

			default:
				return (bool) apply_filters( 'builder_meta_cleanup_is_target_active', false, $target_id );
		}
	}

	public static function is_target_installed( string $target_id ): bool {
		self::load_plugin_functions();

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
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			$like
		);
		return (int) $wpdb->get_var( $sql );
	}

	public static function delete_meta_like( string $like ): int {
		global $wpdb;
		if ( '' === $like ) {
			return 0;
		}
		$total = 0;
		$batch = absint( self::BATCH );
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

		return $total;
	}

	/**
	 * @return array{exists: bool, bytes: int}
	 */
	public static function option_row_info( string $option_name ): array {
		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT LENGTH(option_value) FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
			$option_name
		);
		$len = $wpdb->get_var( $sql );
		if ( null === $len ) {
			return array( 'exists' => false, 'bytes' => 0 );
		}
		return array( 'exists' => true, 'bytes' => (int) $len );
	}

	public static function delete_option_by_name( string $option_name ): bool {
		global $wpdb;
		$exists = null !== $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$option_name
			)
		);
		if ( ! $exists ) {
			return false;
		}
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
