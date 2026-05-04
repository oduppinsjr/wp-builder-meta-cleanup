# Changelog

All notable changes to **Builder Meta Cleanup** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] — 2026

### Added

- Fusion / Avada Builder: postmeta `meta_key LIKE '_fusion%'` and pattern-based `wp_options` rows matching `FS_%`.
- Elementor companion targets (independent of core Elementor active state): Premium Addons (`PA_%`), Essential Addons (`eael_%`), Ultimate Addons (`uael_%`) with install/active detection.
- Tools screen sections: **Page builders** vs **Companion plugins**, plus **Pattern-based wp_options** cleanup table.
- WP-CLI: `options-like-delete` with optional `--pattern` filter.

### Changed

- Target registry supports optional `category` (`builder` | `addon`) and `options_like` patterns (see `builder_meta_cleanup_targets` filter).

## [2.0.1]

### Changed

- Maintenance and documentation updates for plugin directory checks.

## [2.0.0]

### Added

- Initial public release: multi-builder detection, safe postmeta and allowlisted `wp_options` cleanup, WP-CLI commands (`counts`, `delete`, `option-counts`, `options-delete`).
- Core stacks: Elementor, Divi / Extra, Beaver Builder, Bricks, SeedProd, Hello Elementor, BeTheme / Muffin, Astra.

[Unreleased]: https://github.com/oduppinsjr/wp-builder-meta-cleanup/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/oduppinsjr/wp-builder-meta-cleanup/releases/tag/v2.1.0
[2.0.1]: https://github.com/oduppinsjr/wp-builder-meta-cleanup/releases/tag/v2.0.1
[2.0.0]: https://github.com/oduppinsjr/wp-builder-meta-cleanup/releases/tag/v2.0.0
