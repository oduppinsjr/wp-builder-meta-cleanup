=== Builder Meta Cleanup ===
Contributors: oduppinsjr
Donate link: https://duppinstech.com
Tags: elementor, divi, database, postmeta, cleanup
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 2.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Detect major page builders, show install/active state, and remove orphaned postmeta or allowlisted options only when each stack is inactive. Includes WP-CLI.

== Description ==

Builder Meta Cleanup helps after you migrate away from a page builder or theme framework. It lists common stacks (Elementor, Divi, Beaver Builder, Bricks, SeedProd, Hello Elementor, BeTheme, Astra), shows whether each is installed and active, and lets you delete matching **postmeta** (and a small allowlist of **wp_options**) only when that stack is **not** active—so you do not wipe data for a builder you still use.

* **Tools screen** — row counts, badges, and checkboxes for safe cleanup.
* **WP-CLI** — `wp builder-meta counts`, `delete`, `option-counts`, `options-delete`.

Extend behavior with the `builder_meta_cleanup_targets` filter.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from Releases.
2. Activate **Builder Meta Cleanup** through the Plugins menu.
3. Go to **Tools → Builder Meta Cleanup**.

== Frequently Asked Questions ==

= Will this delete shortcodes in post content? =

No. It only removes matching rows from `postmeta` (and selected `options` you choose). Post content is unchanged.

= Why is cleanup disabled for an “active” stack? =

So you cannot delete meta that the live theme or plugin still needs.

== Screenshots ==

1. Tools screen with install/active badges and cleanup controls.

== Changelog ==

= 2.0.1 =
* Maintenance and documentation updates for plugin directory checks.

== Upgrade Notice ==

= 2.0.1 =
Maintenance release.
