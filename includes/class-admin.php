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

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builder-meta-cleanup' ) );
		}

		$messages = array();
		$targets   = Builder_Meta_Cleanup_Service::get_targets();

		if ( isset( $_POST['bmc_action'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), Builder_Meta_Cleanup_Service::NONCE ) ) {
			$action = sanitize_key( wp_unslash( $_POST['bmc_action'] ) );

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

			<h2><?php esc_html_e( 'Detected stacks', 'builder-meta-cleanup' ); ?></h2>
			<p class="description"><?php esc_html_e( '“Installed” means the theme folder or main plugin file is present. “Active” means that stack is currently powering the editor or theme. Cleanup is only available when Active = No.', 'builder-meta-cleanup' ); ?></p>

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
				<?php foreach ( $targets as $tid => $def ) : ?>
					<?php
					$installed = Builder_Meta_Cleanup_Service::is_target_installed( $tid );
					$active    = Builder_Meta_Cleanup_Service::is_target_active( $tid );
					$mcount    = Builder_Meta_Cleanup_Service::count_target_meta( $tid );
					$can_meta  = ! $active && $mcount > 0;
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $def['label'] ); ?></strong>
							<div class="bmc-meta-detail">
								<?php
								foreach ( $def['meta'] as $m ) {
									echo esc_html( $m['label'] );
									echo ' — <strong>' . esc_html( (string) Builder_Meta_Cleanup_Service::count_meta_like( Builder_Meta_Cleanup_Service::meta_like( $m['like_prefix'] ) ) ) . '</strong><br />';
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
								<label><input type="checkbox" name="meta_targets[]" form="bmc-form-meta" value="<?php echo esc_attr( $tid ); ?>" />
								<?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></label>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form id="bmc-form-meta" method="post" style="margin-top:12px">
				<?php wp_nonce_field( Builder_Meta_Cleanup_Service::NONCE ); ?>
				<input type="hidden" name="bmc_action" value="clean_meta" />
				<?php
				submit_button(
					__( 'Delete selected postmeta', 'builder-meta-cleanup' ),
					'primary',
					'submit_meta',
					true,
					array(
						'onclick' => "return confirm('" . esc_js( __( 'This cannot be undone. Continue?', 'builder-meta-cleanup' ) ) . "');",
					)
				);
				?>
			</form>

			<hr style="margin:2.5em 0" />

			<h2><?php esc_html_e( 'Theme / plugin options (inactive stacks only)', 'builder-meta-cleanup' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Exact wp_options rows. Checkboxes appear only when the owning stack is not active and the option exists.', 'builder-meta-cleanup' ); ?></p>

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
					<?php if ( empty( $def['options'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php
					$t_active = Builder_Meta_Cleanup_Service::is_target_active( $tid );
					?>
					<?php foreach ( $def['options'] as $opt_name => $opt_label ) : ?>
						<?php
						$info = Builder_Meta_Cleanup_Service::option_row_info( $opt_name );
						$can  = ! $t_active && $info['exists'];
						?>
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
									<label><input type="checkbox" name="option_names[]" form="bmc-form-options" value="<?php echo esc_attr( $opt_name ); ?>" /> <?php esc_html_e( 'Delete', 'builder-meta-cleanup' ); ?></label>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form id="bmc-form-options" method="post" style="margin-top:12px">
				<?php wp_nonce_field( Builder_Meta_Cleanup_Service::NONCE ); ?>
				<input type="hidden" name="bmc_action" value="clean_options" />
				<?php
				submit_button(
					__( 'Delete selected options', 'builder-meta-cleanup' ),
					'primary',
					'submit_options',
					true,
					array(
						'onclick' => "return confirm('" . esc_js( __( 'This cannot be undone. Continue?', 'builder-meta-cleanup' ) ) . "');",
					)
				);
				?>
			</form>

			<hr style="margin:2.5em 0" />

			<h2><?php esc_html_e( 'Notes', 'builder-meta-cleanup' ); ?></h2>
			<ul class="description" style="max-width:900px">
				<li><?php esc_html_e( 'This does not remove shortcodes or HTML inside post_content.', 'builder-meta-cleanup' ); ?></li>
				<li><?php esc_html_e( 'Astra also registers some post meta keys without the ast- prefix (for example site-sidebar-layout). Those are not deleted by this tool.', 'builder-meta-cleanup' ); ?></li>
				<li><?php esc_html_e( 'Extend targets via the builder_meta_cleanup_targets filter.', 'builder-meta-cleanup' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'WP-CLI', 'builder-meta-cleanup' ); ?></h2>
			<pre style="background:#f6f7f7;padding:12px;max-width:920px;overflow:auto">wp builder-meta counts
wp builder-meta delete --target=divi --target=elementor --yes
wp builder-meta option-counts
wp builder-meta options-delete --option=et_divi --yes</pre>
		</div>
		<?php
	}
}
