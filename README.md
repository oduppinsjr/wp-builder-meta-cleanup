# Builder Meta Cleanup

Detect major page builders and companion plugins, show install/active state, and remove orphaned postmeta, allowlisted options, or prefix-matched `wp_options` rows only when each stack is inactive. Includes WP-CLI.

## Compatibility

- **Tested up to:** WordPress 6.7  
- **Stable tag:** 2.2.0  
- **License:** GPLv2 or later

Full WordPress.org–style readme (headers, changelog, FAQ): see **readme.txt** in this repository.

Repository: [github.com/oduppinsjr/wp-builder-meta-cleanup](https://github.com/oduppinsjr/wp-builder-meta-cleanup)

The admin screen uses **tabs**: Themes & frameworks, Page builders, Plugins (preset targets for common plugins that leave cruft), and About & tools (updates / WP-CLI).

## Repository documentation

| Document | Purpose |
| -------- | ------- |
| [CHANGELOG.md](CHANGELOG.md) | Version history (Keep a Changelog). |
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to propose changes and pull requests. |
| [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) | Community expectations. |
| [LICENSE.md](LICENSE.md) | License statement (GPL-2.0-or-later); see also `LICENSE` at repo root. |
| [SECURITY.md](SECURITY.md) | How to report vulnerabilities privately. |
| [SUPPORT.md](SUPPORT.md) | Where to get help and file issues. |
| [PROJECT_MEMORY.md](PROJECT_MEMORY.md) | Maintainer/agent notes: codebase map, version bump, Git release flow. |

## Supported stacks (postmeta patterns)

| Stack | Meta patterns |
|-------|----------------|
| Elementor | `meta_key LIKE '_elementor_%'` |
| Divi / Extra | `_et_pb_%`, `et_pb_%` |
| Beaver Builder | `_fl_builder_%` |
| Bricks | `_bricks_%` |
| SeedProd | `_seedprod_%`, `seedprod_%` |
| Hello Elementor | `_hello_%` |
| BeTheme / Muffin | `mfn-%` |
| Astra | `ast-%`, `_astra_%` |
| Fusion / Avada | `_fusion%` plus `wp_options` names `FS_%` |
| Premium Addons for Elementor | `wp_options` names `PA_%` |
| Essential Addons for Elementor | `wp_options` names `eael_%` |
| Ultimate Addons for Elementor | `wp_options` names `uael_%` |

Companion plugins are gated on **their** plugin being active; Elementor may stay installed while you remove leftover Premium Addons rows.

Fusion / Avada also supports deleting `wp_options` rows whose names match **`FS_%`** (ThemeFusion option fragments).

Astra also registers some keys **without** the `ast-` prefix (for example `site-sidebar-layout`). Those are **not** removed by this plugin.

## Safety

- Postmeta cleanup is **blocked** while the matching stack is **active**.
- Exact options and prefix (`LIKE`) option cleanup are only offered when the owning stack or plugin is **inactive**.

## Install

Copy the `builder-meta-cleanup` folder into `wp-content/plugins/`, then activate **Builder Meta Cleanup** in wp-admin.

Admin screen: **Tools → Builder Meta Cleanup**.

## Updates (GitHub vs WordPress.org)

The plugin may declare an `Update URI` and include optional GitHub-based update logic. For WordPress.org distribution, follow directory guidelines for update handling.

## WP-CLI

```bash
wp builder-meta counts
wp builder-meta delete --target=astra --yes
wp builder-meta option-counts
wp builder-meta options-delete --option=astra-settings --yes
wp builder-meta options-like-delete --target=premium_addons_elementor --pattern=pa_options --yes
```

## Extend

PHP filter: `builder_meta_cleanup_targets` — add targets, meta `like_prefix` entries, or `options` keys.

## License

GPL-2.0-or-later. See [LICENSE.md](LICENSE.md) and the [GNU GPL v2](https://www.gnu.org/licenses/gpl-2.0.html).

## Publishing (maintainers)

From this directory, with GitHub credentials configured ([PAT](https://github.com/settings/tokens) or SSH):

```bash
git remote add origin https://github.com/oduppinsjr/wp-builder-meta-cleanup.git
git push -u origin main
git push origin v2.1.0
```

Create the GitHub **Release** from tag `v2.1.0`, or with GitHub CLI:

```bash
gh release create v2.1.0 --title "v2.1.0" --notes-file - <<'EOF'
Fusion / Avada, Elementor companion addons, pattern-based wp_options cleanup, WP-CLI options-like-delete.
EOF
```
