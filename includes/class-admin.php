<?php
/**
 * Admin UI under Tools.
 *
 * @package Builder_Meta_Cleanup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Builder_Meta_Cleanup_Admin {

	private const SLUG = 'builder-meta-cleanup';

	private const TAB_THEME   = 'theme';
	private const TAB_BUILDER = 'page_builder';
	private const TAB_PLUGIN  = 'plugin';
	private const TAB_ABOUT   = 'about';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu(): void {
		add_management_page(
			__( 'Builder Meta Cleanup', 'builder-meta-cleanup' ),
			__( 'Builder Meta Cleanup', 'builder-meta-cleanup' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Option names allowed to delete (only from targets that are not active).
	 *
	 * @return array<string, string> option_name => label
	 */
	private static function allowed_options_for_inactive_targets(): array {
		$out = array();
		foreach ( Builder_Meta_Cleanup_Service::get_targets() as $tid => $def ) {
			if ( empty( $def['options'] ) || Builder_Meta_Cleanup_Service::is_target_active( $tid ) ) {
				continue;
			}
			foreach ( $def['options'] as $name => $label ) {
				$out[ $name ] = $label;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, array<string, mixed>> $def Target definition.
	 */
	private static function target_ui_tab( array $def ): string {
		return isset( $def['ui_tab'] ) ? (string) $def['ui_tab'] : 'page_builder';
	}

	/**
	 * @param array<string, array<string, mixed>> $targets
	 * @return array<string, array<string, mixed>>
	 */
	private static function targets_for_ui_tab( array $targets, string $tab ): array {
		$out = array();
		foreach ( $targets as $tid => $def ) {
			if ( self::target_ui_tab( $def ) === $tab ) {
				$out[ $tid ] = $def;
			}
		}
		return $out;
	}

	private static function cleanup_tab_url( string $tab ): string {
		return add_query_arg(
			array(
				'page'    => self::SLUG,
				'bmc_tab' => $tab,
			),
			admin_url( 'tools.php' )
		);
	}

	/**
	 * @param array<string, array<string, mixed>> $targets
	 */
	private static function options_like_choice_allowed( array $targets, string $target_id, string $pattern_id ): bool {
		if ( Builder_Meta_Cleanup_Service::is_target_active( $target_id ) ) {
			return false;
		}
		if ( empty( $targets[ $target_id ]['options_like'][ $pattern_id ] ) ) {
			return false;
		}
		return Builder_Meta_Cleanup_Service::count_target_options_like_block( $target_id, $pattern_id ) > 0;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builder-meta-cleanup' ) );
		}

		$messages = array();
		$targets   = Builder_Meta_Cleanup_Service::get_targets();

		if ( isset( $_POST['bmc_action'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), Builder_Meta_Cleanup_Service::NONCE ) ) {
			$action = sanitize_key( wp_unslash( $_POST['bmc_action'] ) );

			if ( 'save_update_settings' === $action ) {
				$src = sanitize_key( wp_unslash( $_POST['update_source'] ?? 'github' ) );
				if ( ! in_array( $src, array( 'github', 'wordpress', 'both' ), true ) ) {
					$src = 'github';
				}
				update_option( Builder_Meta_Cleanup_Updater::OPTION_SOURCE, $src );
				Builder_Meta_Cleanup_Updater::clear_cache();
				$messages[] = __( 'Update check settings saved. The remote version cache was cleared so the next check fetches fresh data.', 'builder-meta-cleanup' );
			}

			if ( 'clean_meta' === $action && ! empty( $_POST['meta_targets'] ) && is_array( $_POST['meta_targets'] ) ) {
				$posted = array_map( 'sanitize_key', wp_unslash( $_POST['meta_targets'] ) );
				$posted = array_values( array_intersect( $posted, array_keys( $targets ) ) );

				foreach ( $posted as $tid ) {
					if ( Builder_Meta_Cleanup_Service::is_target_active( $tid ) ) {
						$messages[] = sprintf(
							/* translators: %s: builder id */
							__( 'Skipped %s (still active).', 'builder-meta-cleanup' ),
							'<code>' . esc_html( $tid ) . '</code>'
						);
						continue;
					}
					if ( Builder_Meta_Cleanup_Service::count_target_meta( $tid ) < 1 ) {
						continue;
					}
					$removed = Builder_Meta_Cleanup_Service::delete_target_meta( $tid );
					$messages[] = sprintf(
						/* translators: 1: label, 2: row count */
						__( 'Removed %2$d postmeta rows for %1$s.', 'builder-meta-cleanup' ),
						esc_html( $targets[ $tid ]['label'] ),
						$removed
					);
				}
				Builder_Meta_Cleanup_Service::flush_caches();
			}

			if ( 'clean_options' === $action && ! empty( $_POST['option_names'] ) && is_array( $_POST['option_names'] ) ) {
				$allowed = self::allowed_options_for_inactive_targets();
				$posted  = array_map( 'strval', wp_unslash( $_POST['option_names'] ) );
				$posted  = array_values( array_intersect( $posted, array_keys( $allowed ) ) );

				foreach ( $posted as $name ) {
					$owner = '';
					foreach ( $targets as $tid => $def ) {
						if ( ! empty( $def['options'] ) && isset( $def['options'][ $name ] ) ) {
							$owner = (string) $tid;
							break;
						}
					}
					if ( $owner && Builder_Meta_Cleanup_Service::is_target_active( $owner ) ) {
						continue;
					}
					if ( Builder_Meta_Cleanup_Service::delete_option_by_name( $name ) ) {
						$messages[] = sprintf(
							__( 'Deleted option %s.', 'builder-meta-cleanup' ),
							'<code>' . esc_html( $name ) . '</code>'
						);
					} else {
						$messages[] = sprintf(
							__( 'Option %s was not present.', 'builder-meta-cleanup' ),
							'<code>' . esc_html( $name ) . '</code>'
						);
					}
				}
				Builder_Meta_Cleanup_Service::flush_caches();
			}

			if ( 'clean_options_like' === $action && ! empty( $_POST['options_like_keys'] ) && is_array( $_POST['options_like_keys'] ) ) {
				$posted = array_map( 'strval', wp_unslash( $_POST['options_like_keys'] ) );
				foreach ( $posted as $compound ) {
					if ( ! preg_match( '/^([a-z0-9_]+):([a-z0-9_]+)$/', $compound, $m ) ) {
						continue;
					}
					$tid = $m[1];
					$pid = $m[2];
					if ( ! self::options_like_choice_allowed( $targets, $tid, $pid ) ) {
						continue;
					}
					$removed   = Builder_Meta_Cleanup_Service::delete_target_options_like_block( $tid, $pid );
					$pat_label = isset( $targets[ $tid ]['options_like'][ $pid ]['label'] )
						? (string) $targets[ $tid ]['options_like'][ $pid ]['label']
						: $compound;
					$messages[] = sprintf(
						/* translators: 1: pattern description, 2: row count */
						__( 'Removed %2$d wp_options rows matching: %1$s.', 'builder-meta-cleanup' ),
						$pat_label,
						$removed
					);
				}
				Builder_Meta_Cleanup_Service::flush_caches();
			}
		}

		?>
		<div class="wrap bmc-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<style>
				.bmc-badge { display:inline-block; padding:2px 8px; border-radius:3px; font-size:12px; margin-right:6px; }
				.bmc-yes { background:#d5f5dd; color:#1e4620; }
				.bmc-no { background:#f0f0f1; color:#50575e; }
				.bmc-warn { background:#fcf9e8; color:#614200; }
				.bmc-meta-detail { font-size:12px; color:#646970; margin:4px 0 0; }
			</style>

			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Back up your database first.', 'builder-meta-cleanup' ); ?></strong>
				<?php esc_html_e( 'Postmeta deletion is blocked while the matching builder/theme is active. Options can only be removed for inactive stacks.', 'builder-meta-cleanup' ); ?></p>
			</div>

			<?php if ( $messages ) : ?>
				<div class="notice notice-success is-dismissible"><ul>
					<?php foreach ( $messages as $m ) : ?>
						<li><?php echo wp_kses_post( $m ); ?></li>
					<?php endforeach; ?>
				</ul></div>
			<?php endif; ?>

			<?php
			$bmc_tab = isset( $_GET['bmc_tab'] ) ? sanitize_key( wp_unslash( $_GET['bmc_tab'] ) ) : self::TAB_THEME;
			if ( ! in_array( $bmc_tab, array( self::TAB_THEME, self::TAB_BUILDER, self::TAB_PLUGIN, self::TAB_ABOUT ), true ) ) {
				$bmc_tab = self::TAB_THEME;
			}
			$tab_slice     = self::targets_for_ui_tab( $targets, $bmc_tab );
			$form_slug     = str_replace( '_', '-', $bmc_tab );
			$tab_form_base = admin_url( 'tools.php' );
			$tab_action    = add_query_arg(
				array(
					'page'    => self::SLUG,
					'bmc_tab' => $bmc_tab,
				),
				$tab_form_base
			);
			?>
			<h2 class="nav-tab-wrapper bmc-nav-tabs" style="margin:1em 0 0;padding-top:0;border-bottom:1px solid #c3c4c7;">
				<a href="<?php echo esc_url( self::cleanup_tab_url( self::TAB_THEME ) ); ?>" class="nav-tab <?php echo self::TAB_THEME === $bmc_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Themes & frameworks', 'builder-meta-cleanup' ); ?></a>
				<a href="<?php echo esc_url( self::cleanup_tab_url( self::TAB_BUILDER ) ); ?>" class="nav-tab <?php echo self::TAB_BUILDER === $bmc_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Page builders', 'builder-meta-cleanup' ); ?></a>
				<a href="<?php echo esc_url( self::cleanup_tab_url( self::TAB_PLUGIN ) ); ?>" class="nav-tab <?php echo self::TAB_PLUGIN === $bmc_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Plugins', 'builder-meta-cleanup' ); ?></a>
				<a href="<?php echo esc_url( self::cleanup_tab_url( self::TAB_ABOUT ) ); ?>" class="nav-tab <?php echo self::TAB_ABOUT === $bmc_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'About & tools', 'builder-meta-cleanup' ); ?></a>
			</h2>

			<?php if ( self::TAB_ABOUT === $bmc_tab ) : ?>
			<h2 style="margin-top:1.25em"><?php esc_html_e( 'Plugin updates', 'builder-meta-cleanup' ); ?></h2>
			<p class="description" style="max-width:900px;margin-top:8px">
				<?php esc_html_e( 'This plugin is not distributed only through WordPress.org. The plugin header sets Update URI to GitHub so core does not treat another plugin with the same folder name as this one. Choose where to look when WordPress checks for updates (Plugins screen, Dashboard → Updates, or WP-Cron).', 'builder-meta-cleanup' ); ?>
			</p>
			<?php
			$upd_src = Builder_Meta_Cleanup_Updater::get_update_source();
			$gh_repo = Builder_Meta_Cleanup_Updater::get_github_repo();
			$org_slug = Builder_Meta_Cleanup_Updater::get_wporg_slug();
			$gh_info  = Builder_Meta_Cleanup_Updater::get_github_payload( false );
			$wp_info  = Builder_Meta_Cleanup_Updater::get_wporg_payload( false );
			?>
			<form method="post" action="<?php echo esc_url( self::cleanup_tab_url( self::TAB_ABOUT ) ); ?>" style="max-width:720px;margin-bottom:1.5em">
				<?php wp_nonce_field( Builder_Meta_Cleanup_Service::NONCE ); ?>
				<input type="hidden" name="bmc_action" value="save_update_settings" />
				<fieldset>
					<p><strong><?php esc_html_e( 'Update source', 'builder-meta-cleanup' ); ?></strong></p>
					<label style="display:block;margin:6px 0">
						<input type="radio" name="update_source" value="github" <?php checked( $upd_src, 'github' ); ?> />
						<?php esc_html_e( 'GitHub releases only (recommended for this repo)', 'builder-meta-cleanup' ); ?>
					</label>
					<label style="display:block;margin:6px 0">
						<input type="radio" name="update_source" value="wordpress" <?php checked( $upd_src, 'wordpress' ); ?> />
						<?php esc_html_e( 'WordPress.org only (slug below; no update if the plugin is not listed there)', 'builder-meta-cleanup' ); ?>
					</label>
					<label style="display:block;margin:6px 0">
						<input type="radio" name="update_source" value="both" <?php checked( $upd_src, 'both' ); ?> />
						<?php esc_html_e( 'Both: compare versions and offer the newer release', 'builder-meta-cleanup' ); ?>
					</label>
				</fieldset>
				<?php submit_button( __( 'Save update settings', 'builder-meta-cleanup' ), 'secondary', 'submit_updates', false ); ?>
			</form>
			<p class="description" style="max-width:900px">
				<?php
				printf(
					/* translators: 1: owner/repo, 2: plugin slug */
					esc_html__( 'GitHub API: %1$s · WordPress.org slug (filterable): %2$s', 'builder-meta-cleanup' ),
					'<code>' . esc_html( $gh_repo ) . '</code>',
					'<code>' . esc_html( $org_slug ) . '</code>'
				);
				?>
			</p>
			<table class="widefat striped" style="max-width:720px;margin-top:12px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'builder-meta-cleanup' ); ?></th>
						<th><?php esc_html_e( 'Latest seen', 'builder-meta-cleanup' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'GitHub', 'builder-meta-cleanup' ); ?></td>
						<td><?php echo $gh_info ? '<code>' . esc_html( $gh_info['version'] ) . '</code>' : esc_html__( '— (not fetched yet or API error)', 'builder-meta-cleanup' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WordPress.org', 'builder-meta-cleanup' ); ?></td>
						<td><?php echo $wp_info ? '<code>' . esc_html( $wp_info['version'] ) . '</code>' : esc_html__( '— (not listed or not fetched)', 'builder-meta-cleanup' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Installed', 'builder-meta-cleanup' ); ?></td>
						<td><code><?php echo esc_html( BUILDER_META_CLEANUP_VERSION ); ?></code></td>
					</tr>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'For reliable one-click updates from GitHub, attach a release .zip whose internal folder is named builder-meta-cleanup, or rely on the built-in rename step after extraction.', 'builder-meta-cleanup' ); ?></p>

			<hr style="margin:2.5em 0" />

			<h2><?php esc_html_e( 'Notes', 'builder-meta-cleanup' ); ?></h2>
			<ul class="description" style="max-width:900px">
				<li><?php esc_html_e( 'This does not remove shortcodes or HTML inside post_content.', 'builder-meta-cleanup' ); ?></li>
				<li><?php esc_html_e( 'Astra also registers some post meta keys without the ast- prefix (for example site-sidebar-layout). Those are not deleted by this tool.', 'builder-meta-cleanup' ); ?></li>
				<li><?php esc_html_e( 'Extend targets via the builder_meta_cleanup_targets filter.', 'builder-meta-cleanup' ); ?></li>
				<li><?php esc_html_e( 'This tool does not drop custom database tables some plugins create (for example Yoast indexables). It only removes matching postmeta and wp_options rows.', 'builder-meta-cleanup' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'WP-CLI', 'builder-meta-cleanup' ); ?></h2>
			<pre style="background:#f6f7f7;padding:12px;max-width:920px;overflow:auto">wp builder-meta counts
wp builder-meta delete --target=divi --target=elementor --yes
wp builder-meta option-counts
wp builder-meta options-delete --option=et_divi --yes
wp builder-meta options-like-delete --target=premium_addons_elementor --pattern=pa_options --yes</pre>

			<?php else : ?>

				<?php if ( self::TAB_PLUGIN === $bmc_tab ) : ?>
					<p class="description" style="margin-top:12px;max-width:960px">
						<?php esc_html_e( 'These entries cover widely used plugins that often leave wp_options or postmeta behind after uninstall (reports are common for caching, SEO, backup, and slider plugins). Patterns use conservative SQL LIKE prefixes—only run cleanup after you have removed the plugin and taken a backup. Adjust Magic Page paths with the builder_meta_cleanup_plugin_paths filter.', 'builder-meta-cleanup' ); ?>
					</p>
				<?php endif; ?>

				<h2 style="margin-top:1em"><?php esc_html_e( 'Detected stacks', 'builder-meta-cleanup' ); ?></h2>
				<p class="description"><?php esc_html_e( '“Installed” means the theme folder or main plugin file is present. “Active” means that stack is currently in use. Cleanup is only available when Active = No.', 'builder-meta-cleanup' ); ?></p>

				<table class="widefat striped" style="max-width:1100px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Stack', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Installed', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Active', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Postmeta rows', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Clean postmeta', 'builder-meta-cleanup' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $tab_slice as $tid => $def ) : ?>
						<?php
						$installed = Builder_Meta_Cleanup_Service::is_target_installed( $tid );
						$active    = Builder_Meta_Cleanup_Service::is_target_active( $tid );
						$mcount    = Builder_Meta_Cleanup_Service::count_target_meta( $tid );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $def['label'] ); ?></strong>
								<div class="bmc-meta-detail">
									<?php
									if ( ! empty( $def['meta'] ) ) {
										foreach ( $def['meta'] as $m ) {
											echo esc_html( $m['label'] );
											echo ' — <strong>' . esc_html( (string) Builder_Meta_Cleanup_Service::count_meta_like( Builder_Meta_Cleanup_Service::meta_like( $m['like_prefix'] ) ) ) . '</strong><br />';
										}
									} else {
										echo esc_html__( '—', 'builder-meta-cleanup' );
									}
									?>
								</div>
							</td>
							<td>
								<span class="bmc-badge <?php echo $installed ? 'bmc-yes' : 'bmc-no'; ?>">
									<?php echo $installed ? esc_html__( 'Installed', 'builder-meta-cleanup' ) : esc_html__( 'Not installed', 'builder-meta-cleanup' ); ?>
								</span>
							</td>
							<td>
								<span class="bmc-badge <?php echo $active ? 'bmc-warn' : 'bmc-yes'; ?>">
									<?php echo $active ? esc_html__( 'Active', 'builder-meta-cleanup' ) : esc_html__( 'Inactive', 'builder-meta-cleanup' ); ?>
								</span>
							</td>
							<td><strong><?php echo esc_html( (string) $mcount ); ?></strong></td>
							<td>
								<?php if ( $active ) : ?>
									<em><?php esc_html_e( 'Deactivate stack to enable.', 'builder-meta-cleanup' ); ?></em>
								<?php elseif ( $mcount < 1 ) : ?>
									—
								<?php else : ?>
									<label><input type="checkbox" name="meta_targets[]" form="<?php echo esc_attr( 'bmc-form-meta-' . $form_slug ); ?>" value="<?php echo esc_attr( $tid ); ?>" />
									<?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></label>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<form id="<?php echo esc_attr( 'bmc-form-meta-' . $form_slug ); ?>" method="post" action="<?php echo esc_url( $tab_action ); ?>" style="margin-top:12px">
					<?php wp_nonce_field( Builder_Meta_Cleanup_Service::NONCE ); ?>
					<input type="hidden" name="bmc_action" value="clean_meta" />
					<?php
					submit_button(
						__( 'Delete selected postmeta', 'builder-meta-cleanup' ),
						'primary',
						'submit_meta_' . $form_slug,
						true,
						array(
							'onclick' => "return confirm('" . esc_js( __( 'This cannot be undone. Continue?', 'builder-meta-cleanup' ) ) . "');",
						)
					);
					?>
				</form>

				<hr style="margin:2.5em 0" />

				<h2><?php esc_html_e( 'Exact wp_options (inactive only)', 'builder-meta-cleanup' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Named option rows for targets on this tab. Checkboxes appear only when the owning stack is inactive and the option exists.', 'builder-meta-cleanup' ); ?></p>

				<table class="widefat striped" style="max-width:920px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'option_name', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Description', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Present', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Size', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $targets as $tid => $def ) : ?>
						<?php if ( self::target_ui_tab( $def ) !== $bmc_tab ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php if ( empty( $def['options'] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php
						$t_active = Builder_Meta_Cleanup_Service::is_target_active( $tid );
						?>
						<?php foreach ( $def['options'] as $opt_name => $opt_label ) : ?>
							<?php $info = Builder_Meta_Cleanup_Service::option_row_info( $opt_name ); ?>
							<tr>
								<td><code><?php echo esc_html( $opt_name ); ?></code></td>
								<td><?php echo esc_html( $opt_label ); ?></td>
								<td><?php echo $info['exists'] ? esc_html__( 'Yes', 'builder-meta-cleanup' ) : esc_html__( 'No', 'builder-meta-cleanup' ); ?></td>
								<td><?php echo $info['exists'] ? esc_html( Builder_Meta_Cleanup_Service::format_bytes( $info['bytes'] ) ) : '—'; ?></td>
								<td>
									<?php if ( $t_active ) : ?>
										<em><?php esc_html_e( 'Stack active', 'builder-meta-cleanup' ); ?></em>
									<?php elseif ( ! $info['exists'] ) : ?>
										—
									<?php else : ?>
										<label><input type="checkbox" name="option_names[]" form="<?php echo esc_attr( 'bmc-form-options-' . $form_slug ); ?>" value="<?php echo esc_attr( $opt_name ); ?>" /> <?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></label>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
					</tbody>
				</table>

				<form id="<?php echo esc_attr( 'bmc-form-options-' . $form_slug ); ?>" method="post" action="<?php echo esc_url( $tab_action ); ?>" style="margin-top:12px">
					<?php wp_nonce_field( Builder_Meta_Cleanup_Service::NONCE ); ?>
					<input type="hidden" name="bmc_action" value="clean_options" />
					<?php
					submit_button(
						__( 'Delete selected options', 'builder-meta-cleanup' ),
						'primary',
						'submit_options_' . $form_slug,
						true,
						array(
							'onclick' => "return confirm('" . esc_js( __( 'This cannot be undone. Continue?', 'builder-meta-cleanup' ) ) . "');",
						)
					);
					?>
				</form>

				<hr style="margin:2.5em 0" />

				<h2><?php esc_html_e( 'Pattern-based wp_options (inactive only)', 'builder-meta-cleanup' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Deletes rows where option_name matches the pattern (SQL LIKE). Use after the plugin or theme is removed.', 'builder-meta-cleanup' ); ?></p>

				<table class="widefat striped" style="max-width:1100px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Stack', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Pattern', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Rows', 'builder-meta-cleanup' ); ?></th>
							<th><?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $targets as $tid => $def ) : ?>
						<?php if ( self::target_ui_tab( $def ) !== $bmc_tab ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php if ( empty( $def['options_like'] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php foreach ( $def['options_like'] as $pattern_id => $pat ) : ?>
							<?php
							$t_active = Builder_Meta_Cleanup_Service::is_target_active( $tid );
							$pc       = Builder_Meta_Cleanup_Service::count_target_options_like_block( $tid, (string) $pattern_id );
							$compound = $tid . ':' . $pattern_id;
							?>
							<tr>
								<td><strong><?php echo esc_html( $def['label'] ); ?></strong></td>
								<td><?php echo esc_html( $pat['label'] ); ?></td>
								<td><strong><?php echo esc_html( (string) $pc ); ?></strong></td>
								<td>
									<?php if ( $t_active ) : ?>
										<em><?php esc_html_e( 'Stack active', 'builder-meta-cleanup' ); ?></em>
									<?php elseif ( $pc < 1 ) : ?>
										—
									<?php else : ?>
										<label><input type="checkbox" name="options_like_keys[]" form="<?php echo esc_attr( 'bmc-form-options-like-' . $form_slug ); ?>" value="<?php echo esc_attr( $compound ); ?>" /> <?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></label>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
					</tbody>
				</table>

				<form id="<?php echo esc_attr( 'bmc-form-options-like-' . $form_slug ); ?>" method="post" action="<?php echo esc_url( $tab_action ); ?>" style="margin-top:12px">
					<?php wp_nonce_field( Builder_Meta_Cleanup_Service::NONCE ); ?>
					<input type="hidden" name="bmc_action" value="clean_options_like" />
					<?php
					submit_button(
						__( 'Delete selected pattern options', 'builder-meta-cleanup' ),
						'primary',
						'submit_options_like_' . $form_slug,
						true,
						array(
							'onclick' => "return confirm('" . esc_js( __( 'This cannot be undone. Continue?', 'builder-meta-cleanup' ) ) . "');",
						)
					);
					?>
				</form>

			<?php endif; ?>

		</div>
		<?php
	}
}
