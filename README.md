# Builder Meta Cleanup

Detect major page builders, show install/active state, and remove orphaned postmeta or allowlisted options only when each stack is inactive. Includes WP-CLI.

## Compatibility

- **Tested up to:** WordPress 6.7  
- **Stable tag:** 2.0.1  
- **License:** GPLv2 or later

Full WordPress.org–style readme (headers, changelog, FAQ): see **readme.txt** in this repository.

Repository: [github.com/oduppinsjr/wp-builder-meta-cleanup](https://github.com/oduppinsjr/wp-builder-meta-cleanup)

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

Astra also registers some keys **without** the `ast-` prefix (for example `site-sidebar-layout`). Those are **not** removed by this plugin.

## Safety

- Postmeta cleanup is **blocked** while the matching stack is **active**.
- Options in the UI are only offered when the owning stack is **inactive**.

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
```

## Extend

PHP filter: `builder_meta_cleanup_targets` — add targets, meta `like_prefix` entries, or `options` keys.

## License

GPLv2 or later. See [License URI](https://www.gnu.org/licenses/gpl-2.0.html).

## Publishing (maintainers)

From this directory, with GitHub credentials configured ([PAT](https://github.com/settings/tokens) or SSH):

```bash
git remote add origin https://github.com/oduppinsjr/wp-builder-meta-cleanup.git
git push -u origin main
git push origin v2.0.0
```

Create the GitHub **Release** from tag `v2.0.0`, or with GitHub CLI:

```bash
gh release create v2.0.0 --title "v2.0.0" --notes-file - <<'EOF'
Initial public release: multi-builder detection, safe postmeta/options cleanup, WP-CLI.
EOF
```
