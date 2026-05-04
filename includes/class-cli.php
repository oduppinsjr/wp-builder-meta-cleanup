<?php
/**
 * WP-CLI commands.
 *
 * @package Builder_Meta_Cleanup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Builder_Meta_Cleanup_CLI {

	public static function register(): void {
		\WP_CLI::add_command(
			'builder-meta',
			array( __CLASS__, 'handle' )
		);
	}

	/**
	 * @param list<string> $args
	 * @param array<string, mixed> $assoc_args
	 */
	public static function handle( array $args, array $assoc_args ): void {
		$cmd = $args[0] ?? '';

		switch ( $cmd ) {
			case 'counts':
				self::cmd_counts();
				return;
			case 'option-counts':
				self::cmd_option_counts();
				return;
			case 'delete':
				self::cmd_delete( $assoc_args );
				return;
			case 'options-delete':
				self::cmd_options_delete( $assoc_args );
				return;
			case 'options-like-delete':
				self::cmd_options_like_delete( $assoc_args );
				return;
			default:
				self::usage();
		}
	}

	private static function usage(): void {
		\WP_CLI::log( 'wp builder-meta counts' );
		\WP_CLI::log( 'wp builder-meta delete --target=divi [--target=elementor] [--dry-run] [--yes]' );
		\WP_CLI::log( 'wp builder-meta option-counts' );
		\WP_CLI::log( 'wp builder-meta options-delete --option=et_divi [--yes] [--dry-run]' );
		\WP_CLI::log( 'wp builder-meta options-like-delete --target=fusion [--pattern=fs_options] [--yes] [--dry-run]' );
	}

	private static function cmd_counts(): void {
		\WP_CLI::log( '--- Targets (postmeta) ---' );
		foreach ( Builder_Meta_Cleanup_Service::get_targets() as $tid => $def ) {
			$ins = Builder_Meta_Cleanup_Service::is_target_installed( $tid ) ? 'installed' : 'absent';
			$act = Builder_Meta_Cleanup_Service::is_target_active( $tid ) ? 'active' : 'inactive';
			$cnt = Builder_Meta_Cleanup_Service::count_target_meta( $tid );
			\WP_CLI::log( sprintf( '%s [%s, %s]: %d rows', $def['label'], $ins, $act, $cnt ) );
			if ( ! empty( $def['meta'] ) ) {
				foreach ( $def['meta'] as $mid => $m ) {
					$like = Builder_Meta_Cleanup_Service::meta_like( $m['like_prefix'] );
					$c    = Builder_Meta_Cleanup_Service::count_meta_like( $like );
					\WP_CLI::log( sprintf( '  %s: %d', $m['label'], $c ) );
				}
			}
		}
		\WP_CLI::log( '' );
		\WP_CLI::log( 'Options: wp builder-meta option-counts' );
	}

	private static function cmd_option_counts(): void {
		foreach ( Builder_Meta_Cleanup_Service::get_targets() as $tid => $def ) {
			if ( empty( $def['options'] ) ) {
				continue;
			}
			$act = Builder_Meta_Cleanup_Service::is_target_active( $tid );
			foreach ( $def['options'] as $name => $label ) {
				$info = Builder_Meta_Cleanup_Service::option_row_info( $name );
				\WP_CLI::log(
					sprintf(
						'%s | %s | %s | %s',
						$name,
						$act ? 'ACTIVE' : 'inactive',
						$info['exists'] ? Builder_Meta_Cleanup_Service::format_bytes( $info['bytes'] ) : '-',
						$label
					)
				);
			}
		}
		\WP_CLI::log( '' );
		\WP_CLI::log( '--- Pattern options (wp_options LIKE) ---' );
		foreach ( Builder_Meta_Cleanup_Service::get_targets() as $tid => $def ) {
			if ( empty( $def['options_like'] ) ) {
				continue;
			}
			$act = Builder_Meta_Cleanup_Service::is_target_active( $tid );
			foreach ( $def['options_like'] as $pid => $label_row ) {
				$n = Builder_Meta_Cleanup_Service::count_target_options_like_block( $tid, (string) $pid );
				\WP_CLI::log(
					sprintf(
						'%s:%s | %s | %d | %s',
						$tid,
						$pid,
						$act ? 'ACTIVE' : 'inactive',
						$n,
						$label_row['label']
					)
				);
			}
		}
	}

	/**
	 * @param array<string, mixed> $assoc_args
	 */
	private static function cmd_delete( array $assoc_args ): void {
		$dry     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$targets = \WP_CLI\Utils\get_flag_value( $assoc_args, 'target', array() );
		if ( empty( $targets ) ) {
			\WP_CLI::error( 'Specify --target=divi (repeat for multiple). See: wp builder-meta counts' );
		}
		if ( ! is_array( $targets ) ) {
			$targets = array( $targets );
		}
		$targets = array_map( 'sanitize_key', $targets );
		$valid   = array_keys( Builder_Meta_Cleanup_Service::get_targets() );
		$targets = array_values( array_intersect( $targets, $valid ) );
		if ( empty( $targets ) ) {
			\WP_CLI::error( 'No valid target ids.' );
		}

		$registry = Builder_Meta_Cleanup_Service::get_targets();
		foreach ( $targets as $tid ) {
			if ( Builder_Meta_Cleanup_Service::is_target_active( $tid ) ) {
				\WP_CLI::warning( sprintf( 'Skipping %s (active). Deactivate first.', $tid ) );
				continue;
			}
			$count = Builder_Meta_Cleanup_Service::count_target_meta( $tid );
			\WP_CLI::log( sprintf( '%s: %d rows', $registry[ $tid ]['label'], $count ) );
			if ( $count < 1 || $dry ) {
				continue;
			}
			\WP_CLI::confirm( sprintf( 'Delete %d postmeta rows for %s?', $count, $tid ) );
			$removed = Builder_Meta_Cleanup_Service::delete_target_meta( $tid );
			\WP_CLI::success( sprintf( 'Removed %d rows.', $removed ) );
		}
		if ( ! $dry ) {
			Builder_Meta_Cleanup_Service::flush_caches();
		}
	}

	/**
	 * @param array<string, mixed> $assoc_args
	 */
	private static function cmd_options_delete( array $assoc_args ): void {
		$dry     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$options = \WP_CLI\Utils\get_flag_value( $assoc_args, 'option', array() );
		if ( empty( $options ) ) {
			\WP_CLI::error( 'Specify --option=et_divi (repeat). See: wp builder-meta option-counts' );
		}
		if ( ! is_array( $options ) ) {
			$options = array( $options );
		}

		$allowed = array();
		foreach ( Builder_Meta_Cleanup_Service::get_targets() as $tid => $def ) {
			if ( empty( $def['options'] ) || Builder_Meta_Cleanup_Service::is_target_active( $tid ) ) {
				continue;
			}
			$allowed = array_merge( $allowed, array_keys( $def['options'] ) );
		}
		$allowed = array_unique( $allowed );

		$options = array_values( array_intersect( $options, $allowed ) );
		if ( empty( $options ) ) {
			\WP_CLI::error( 'No valid options, or owning target is still active.' );
		}

		foreach ( $options as $name ) {
			$info = Builder_Meta_Cleanup_Service::option_row_info( $name );
			\WP_CLI::log( sprintf( '%s: %s', $name, $info['exists'] ? Builder_Meta_Cleanup_Service::format_bytes( $info['bytes'] ) : 'absent' ) );
			if ( ! $info['exists'] || $dry ) {
				continue;
			}
			\WP_CLI::confirm( sprintf( 'Delete option %s?', $name ) );
			if ( Builder_Meta_Cleanup_Service::delete_option_by_name( $name ) ) {
				\WP_CLI::success( sprintf( 'Deleted %s.', $name ) );
			} else {
				\WP_CLI::warning( sprintf( 'Could not delete %s.', $name ) );
			}
		}
		if ( ! $dry ) {
			Builder_Meta_Cleanup_Service::flush_caches();
		}
	}

	/**
	 * @param array<string, mixed> $assoc_args
	 */
	private static function cmd_options_like_delete( array $assoc_args ): void {
		$dry    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$tids   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'target', array() );
		$p_filter = \WP_CLI\Utils\get_flag_value( $assoc_args, 'pattern', array() );

		if ( empty( $tids ) ) {
			\WP_CLI::error( 'Specify --target=fusion (repeat). Optional: --pattern=fs_options. See: wp builder-meta option-counts' );
		}
		if ( ! is_array( $tids ) ) {
			$tids = array( $tids );
		}
		$tids = array_map( 'sanitize_key', $tids );

		if ( ! is_array( $p_filter ) ) {
			$p_filter = $p_filter ? array( $p_filter ) : array();
		}
		$p_filter = array_map( 'sanitize_key', $p_filter );

		$registry = Builder_Meta_Cleanup_Service::get_targets();
		$tids     = array_values( array_intersect( $tids, array_keys( $registry ) ) );
		if ( empty( $tids ) ) {
			\WP_CLI::error( 'No valid target ids.' );
		}

		foreach ( $tids as $tid ) {
			if ( Builder_Meta_Cleanup_Service::is_target_active( $tid ) ) {
				\WP_CLI::warning( sprintf( 'Skipping %s (active).', $tid ) );
				continue;
			}
			if ( empty( $registry[ $tid ]['options_like'] ) ) {
				\WP_CLI::warning( sprintf( '%s has no options_like patterns.', $tid ) );
				continue;
			}
			$pattern_ids = array_keys( $registry[ $tid ]['options_like'] );
			if ( ! empty( $p_filter ) ) {
				$pattern_ids = array_values( array_intersect( $pattern_ids, $p_filter ) );
			}
			if ( empty( $pattern_ids ) ) {
				\WP_CLI::warning( sprintf( 'No matching patterns for %s.', $tid ) );
				continue;
			}
			foreach ( $pattern_ids as $pid ) {
				$n = Builder_Meta_Cleanup_Service::count_target_options_like_block( $tid, $pid );
				\WP_CLI::log( sprintf( '%s:%s — %d rows', $tid, $pid, $n ) );
				if ( $n < 1 || $dry ) {
					continue;
				}
				\WP_CLI::confirm( sprintf( 'Delete %d wp_options rows for %s:%s?', $n, $tid, $pid ) );
				$removed = Builder_Meta_Cleanup_Service::delete_target_options_like_block( $tid, $pid );
				\WP_CLI::success( sprintf( 'Removed %d rows.', $removed ) );
			}
		}
		if ( ! $dry ) {
			Builder_Meta_Cleanup_Service::flush_caches();
		}
	}
}
