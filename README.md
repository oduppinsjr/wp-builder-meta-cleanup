# Builder Meta Cleanup

Repository: [github.com/oduppinsjr/wp-builder-meta-cleanup](https://github.com/oduppinsjr/wp-builder-meta-cleanup)

WordPress plugin that lists common page-builder / theme stacks, shows whether each is **installed** and **active**, and lets you delete matching **postmeta** (and a small **allowlist** of `wp_options`) only when that stack is **not active**.

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

- Postmeta cleanup is **blocked** while the detector considers that stack **active** (e.g. Elementor loaded, Divi/Extra parent theme, Beaver’s `FLBuilder`, Bricks constant, SeedProd plugin active, Hello Elementor or Astra as the active theme, BeTheme as active theme).
- Options in the UI are only offered for removal when the owning stack is **inactive**.

## Install

Copy the `builder-meta-cleanup` folder into `wp-content/plugins/`, then activate **Builder Meta Cleanup** in wp-admin.

Admin screen: **Tools → Builder Meta Cleanup**.

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

GPL-2.0-or-later.

## Publishing (maintainers)

From this directory, with GitHub credentials configured ([PAT](https://github.com/settings/tokens) or SSH):

```bash
git remote add origin https://github.com/oduppinsjr/wp-builder-meta-cleanup.git   # if not already added
git push -u origin main
git push origin v2.0.0
```

Create the GitHub **Release** from tag `v2.0.0` (UI: Releases → Draft → choose tag), or with GitHub CLI:

```bash
gh release create v2.0.0 --title "v2.0.0" --notes-file - <<'EOF'
Initial public release: multi-builder detection, safe postmeta/options cleanup, WP-CLI.
EOF
```
