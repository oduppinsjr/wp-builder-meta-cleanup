<?php
/**
 * GitHub (and optional WordPress.org) plugin update integration.
 *
 * @package Builder_Meta_Cleanup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Builder_Meta_Cleanup_Updater {

	public const OPTION_SOURCE = 'builder_meta_cleanup_update_source';

	private const TRANSIENT_GITHUB = 'builder_meta_cleanup_gh_release';

	private const TRANSIENT_WPORG = 'builder_meta_cleanup_wporg_plugin';

	private const CACHE_TTL = 43200; // 12 hours.

	public static function init(): void {
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'inject_update' ), 15, 1 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'maybe_rename_extracted_folder' ), 10, 4 );
	}

	public static function clear_cache(): void {
		delete_site_transient( self::TRANSIENT_GITHUB );
		delete_site_transient( self::TRANSIENT_WPORG );
	}

	/**
	 * @return 'github'|'wordpress'|'both'
	 */
	public static function get_update_source(): string {
		$v = get_option( self::OPTION_SOURCE, 'github' );
		return in_array( $v, array( 'github', 'wordpress', 'both' ), true ) ? $v : 'github';
	}

	/**
	 * owner/repo
	 */
	public static function get_github_repo(): string {
		$repo = apply_filters( 'builder_meta_cleanup_github_repo', 'oduppinsjr/wp-builder-meta-cleanup' );
		$repo = trim( (string) $repo, '/' );
		return ( '' !== $repo ) ? $repo : 'oduppinsjr/wp-builder-meta-cleanup';
	}

	public static function get_wporg_slug(): string {
		$slug = apply_filters( 'builder_meta_cleanup_wporg_slug', 'builder-meta-cleanup' );
		$slug = sanitize_title( (string) $slug );
		return '' !== $slug ? $slug : 'builder-meta-cleanup';
	}

	/**
	 * @return array{version: string, package: string, url: string, tested?: string}|null
	 */
	public static function get_github_payload( bool $force_refresh = false ): ?array {
		if ( ! $force_refresh ) {
			$cached = get_site_transient( self::TRANSIENT_GITHUB );
			if ( is_array( $cached ) && isset( $cached['error'] ) ) {
				return null;
			}
			if ( is_array( $cached ) && isset( $cached['version'], $cached['package'] ) ) {
				return $cached;
			}
		}

		$parts = explode( '/', self::get_github_repo(), 2 );
		if ( count( $parts ) !== 2 || '' === $parts[0] || '' === $parts[1] ) {
			set_site_transient( self::TRANSIENT_GITHUB, array( 'error' => 'bad_repo' ), HOUR_IN_SECONDS );
			return null;
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			rawurlencode( $parts[0] ),
			rawurlencode( $parts[1] )
		);

		$request_args = array(
			'timeout' => 15,
			'headers' => array(
				'Accept'               => 'application/vnd.github+json',
				'X-GitHub-Api-Version' => '2022-11-28',
				'User-Agent'           => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . (string) wp_parse_url( home_url(), PHP_URL_HOST ),
			),
		);

		/**
		 * @param array<string, mixed> $request_args wp_remote_get() arguments.
		 * @param string               $url          GitHub API URL.
		 */
		$request_args = apply_filters( 'builder_meta_cleanup_github_http_args', $request_args, $url );

		$response = wp_remote_get( $url, $request_args );

		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
			set_site_transient( self::TRANSIENT_GITHUB, array( 'error' => 'http' ), HOUR_IN_SECONDS );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_site_transient( self::TRANSIENT_GITHUB, array( 'error' => 'parse' ), HOUR_IN_SECONDS );
			return null;
		}

		$tag     = (string) $body['tag_name'];
		$version = self::normalize_version( $tag );
		$package = '';
		if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
			foreach ( $body['assets'] as $asset ) {
				if ( empty( $asset['browser_download_url'] ) || empty( $asset['name'] ) ) {
					continue;
				}
				$name = (string) $asset['name'];
				if ( ! preg_match( '/\.zip$/i', $name ) ) {
					continue;
				}
				if ( false !== stripos( $name, 'builder-meta-cleanup' ) ) {
					$package = (string) $asset['browser_download_url'];
					break;
				}
			}
			if ( '' === $package ) {
				foreach ( $body['assets'] as $asset ) {
					if ( ! empty( $asset['browser_download_url'] ) && ! empty( $asset['name'] ) && preg_match( '/\.zip$/i', (string) $asset['name'] ) ) {
						$package = (string) $asset['browser_download_url'];
						break;
					}
				}
			}
		}

		if ( '' === $package ) {
			$package = sprintf(
				'https://github.com/%s/%s/archive/refs/tags/%s.zip',
				$parts[0],
				$parts[1],
				rawurlencode( $tag )
			);
		}

		$html_url = isset( $body['html_url'] ) ? (string) $body['html_url'] : 'https://github.com/' . self::get_github_repo();

		$payload = array(
			'version' => $version,
			'package' => $package,
			'url'     => $html_url,
			'tested'  => '',
		);

		set_site_transient( self::TRANSIENT_GITHUB, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * @return array{version: string, package: string, url: string, tested?: string}|null
	 */
	public static function get_wporg_payload( bool $force_refresh = false ): ?array {
		if ( ! $force_refresh ) {
			$cached = get_site_transient( self::TRANSIENT_WPORG );
			if ( is_array( $cached ) && isset( $cached['error'] ) ) {
				return null;
			}
			if ( is_array( $cached ) && isset( $cached['version'], $cached['package'] ) ) {
				return $cached;
			}
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => self::get_wporg_slug(),
				'fields' => array(
					'download_link' => true,
					'tested'        => true,
				),
			)
		);

		if ( is_wp_error( $api ) || empty( $api->version ) || empty( $api->download_link ) ) {
			set_site_transient( self::TRANSIENT_WPORG, array( 'error' => 'not_found' ), self::CACHE_TTL );
			return null;
		}

		$payload = array(
			'version' => (string) $api->version,
			'package' => (string) $api->download_link,
			'url'     => isset( $api->homepage ) ? (string) $api->homepage : 'https://wordpress.org/plugins/' . self::get_wporg_slug() . '/',
			'tested'  => isset( $api->tested ) ? (string) $api->tested : '',
		);

		set_site_transient( self::TRANSIENT_WPORG, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * @param mixed $transient
	 * @return mixed
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) || ! is_array( $transient->checked ) ) {
			return $transient;
		}

		$file  = plugin_basename( BUILDER_META_CLEANUP_FILE );
		$local = $transient->checked[ $file ] ?? '';
		if ( '' === $local ) {
			return $transient;
		}

		$mode = self::get_update_source();
		$best = null;

		if ( 'github' === $mode || 'both' === $mode ) {
			$g = self::get_github_payload( false );
			if ( $g && version_compare( $local, $g['version'], '<' ) ) {
				$best = $g;
			}
		}

		if ( 'wordpress' === $mode || 'both' === $mode ) {
			$w = self::get_wporg_payload( false );
			if ( $w && version_compare( $local, $w['version'], '<' ) ) {
				if ( null === $best || version_compare( $w['version'], $best['version'], '>' ) ) {
					$best = $w;
				}
			}
		}

		if ( null === $best ) {
			return $transient;
		}

		$transient->response[ $file ] = (object) array(
			'id'            => self::get_github_repo(),
			'slug'          => 'builder-meta-cleanup',
			'plugin'        => $file,
			'new_version'   => $best['version'],
			'url'           => $best['url'],
			'package'       => $best['package'],
			'requires_php'  => '7.4',
			'requires'      => '6.0',
			'tested'        => $best['tested'] ?? '',
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
		);

		return $transient;
	}

	/**
	 * GitHub zipballs use the repository folder name; normalize to the installed plugin directory.
	 *
	 * @param string      $source
	 * @param string      $remote_source
	 * @param mixed       $upgrader
	 * @param array|null  $hook_extra
	 * @return string
	 */
	public static function maybe_rename_extracted_folder( $source, $remote_source, $upgrader, $hook_extra = null ) {
		global $wp_filesystem;

		if ( ! is_string( $source ) || '' === $source || ! is_object( $upgrader ) ) {
			return $source;
		}

		if ( ! $upgrader instanceof Plugin_Upgrader ) {
			return $source;
		}

		$our_plugin = plugin_basename( BUILDER_META_CLEANUP_FILE );
		$plugin     = is_array( $hook_extra ) && isset( $hook_extra['plugin'] ) ? (string) $hook_extra['plugin'] : '';
		if ( $plugin !== $our_plugin ) {
			return $source;
		}

		$wanted = trailingslashit( dirname( $source ) ) . 'builder-meta-cleanup';
		if ( trailingslashit( $source ) === trailingslashit( $wanted ) ) {
			return $source;
		}

		$base = basename( $source );
		if ( 'builder-meta-cleanup' === $base ) {
			return $source;
		}

		if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
			return $source;
		}

		if ( $wp_filesystem->exists( $wanted ) ) {
			$wp_filesystem->delete( $wanted, true );
		}

		if ( @rename( $source, $wanted ) ) {
			return $wanted;
		}

		if ( $wp_filesystem->move( $source, $wanted, true ) ) {
			return $wanted;
		}

		return $source;
	}

	private static function normalize_version( string $tag ): string {
		return ltrim( $tag, "vV \t\n\r\0\x0B" );
	}
}
