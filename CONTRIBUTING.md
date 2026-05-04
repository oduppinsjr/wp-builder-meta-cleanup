# Contributing

Thank you for helping improve **Builder Meta Cleanup**. This document describes how we accept contributions for this WordPress plugin.

## Ground rules

- **License:** By contributing, you agree your contributions are licensed under the [GNU General Public License v2 or later](LICENSE.md) (same as the project).
- **Scope:** Prefer focused changes (one logical fix or feature per pull request). Avoid unrelated refactors or drive-by formatting outside touched lines unless necessary.
- **Safety:** This plugin performs destructive database operations in admin/WP-CLI. Changes that widen deletion patterns or relax “active stack” checks need extra review and clear rationale.

## How to contribute

1. **Issues first (usually):** Open a [GitHub issue](https://github.com/oduppinsjr/wp-builder-meta-cleanup/issues) for bugs or feature ideas so maintainers can confirm direction—especially for new builder/add-on targets or new SQL patterns.
2. **Fork and branch:** Branch from `main` using a descriptive name (examples: `fix/cli-options-like`, `feature/add-target-xyz`).
3. **Implement:** Match existing PHP style (spacing, naming, docblocks where the file already uses them). Extend the registry via `builder_meta_cleanup_targets` when adding stacks rather than hard-coding one-off logic when filters suffice.
4. **Verify locally:**
   - `php -l` on every PHP file you change.
   - In a WordPress dev environment: activate the plugin, exercise **Tools → Builder Meta Cleanup** for affected flows, and WP-CLI commands if you touched CLI code.
5. **Pull request:** Describe **what** changed and **why**. Link related issues. Mention any deployment notes (readme bump, new WP-CLI flags).

## Coding expectations

- **Registry:** New stacks should set `ui_tab` to `theme`, `page_builder`, or `plugin`. Plugins detected only by main file can list paths in `plugin_paths`. Bulk presets for plugins-with-cruft live in `includes/data-plugin-cruft-targets.php` (merged automatically).
- **WordPress:** Target PHP version and WordPress version headers in `builder-meta-cleanup.php` must remain satisfied.
- **Database:** Prefer batched deletes as implemented in `Builder_Meta_Cleanup_Service`; avoid loading large option blobs into PHP when counting bytes (follow existing `LENGTH(option_value)` patterns).
- **Internationalization:** User-visible strings in PHP should use translation functions with the `builder-meta-cleanup` text domain, consistent with existing files.
- **Security:** Sanitize and validate input; use nonces for admin actions; preserve capability checks (`manage_options`).

## Release alignment

- WordPress.org–oriented changelog snippets belong in **readme.txt** as well as **CHANGELOG.md** when cutting a release.
- Version constants and headers live in `builder-meta-cleanup.php`; keep them in sync with tags and release notes.

## Questions

See [SUPPORT.md](SUPPORT.md) for where to ask questions versus report bugs.
