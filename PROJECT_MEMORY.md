# Maintainer memory — Builder Meta Cleanup

Keep this file updated when architecture or release workflow changes. Cursor/agents should read it at the start of maintainer tasks.

## Purpose

WordPress plugin: detect page builders/themes/plugins, show install/active state, delete matching **postmeta** and selected **wp_options** only when the owning stack is **inactive**. WP-CLI parity with admin.

## Repo map (where things live)

| Area | Path |
|------|------|
| Bootstrap, version constant | `builder-meta-cleanup.php` |
| Target registry, DB helpers, `is_target_*`, `get_targets()` cache | `includes/class-service.php` |
| Tools UI (tabs, forms), nonce actions | `includes/class-admin.php` |
| GitHub / WP.org update injection | `includes/class-updater.php` |
| WP-CLI | `includes/class-cli.php` |
| Preset “cruft” plugin targets (merged into registry) | `includes/data-plugin-cruft-targets.php` |

## Target model

- **`meta`**: `like_prefix` → SQL `LIKE` on `postmeta.meta_key` (batched deletes).
- **`options`**: exact `option_name` keys (admin allowlist).
- **`options_like`**: prefix patterns on `options.option_name`.
- **`ui_tab`**: `theme` \| `page_builder` \| `plugin` — drives admin tabs (`bmc_tab` query arg).
- **`plugin_paths`**: list of `WP_PLUGIN_DIR`-relative main plugin files; if present, active/installed use `is_any_plugin_active` / `is_any_plugin_present` (no hard-coded switch).

**Filters**

- `builder_meta_cleanup_targets` — full registry merge.
- `builder_meta_cleanup_plugin_paths` — adjust paths per target (e.g. Magic Page custom folder).
- `builder_meta_cleanup_is_target_active` / `builder_meta_cleanup_is_target_installed` — edge cases.
- `builder_meta_cleanup_github_repo` — default `oduppinsjr/wp-builder-meta-cleanup`.

## Version bump checklist (every release)

1. `builder-meta-cleanup.php`: header `Version:` and `BUILDER_META_CLEANUP_VERSION`.
2. `readme.txt`: `Stable tag:` + changelog section + `Upgrade Notice` if user-visible.
3. `CHANGELOG.md`: Keep a Changelog entry + compare links at bottom.
4. `README.md`: stable tag line if it lists a version.

Do not forget — sites compare GitHub **release tag** to these strings.

## Versioning policy (SemVer)

| Change type | Version bump | Examples |
|-------------|----------------|----------|
| Tiny fixes, copy, micro UI, typos | **Patch** `x.y.Z+1` | 2.2.0 → 2.2.1 |
| New targets, new CLI flags, tab changes, features | **Minor** `x.Y+1.0` | 2.2.x → 2.3.0 |
| Breaking behavior, renames, incompatible registry shape | **Major** `X+1.0.0` | 3.0.0 |

Align changelog wording with the bump you ship.

## Git & GitHub release workflow (required)

1. **Commit every coherent change** to `main` (or PR then merge to `main`).
2. **Push to GitHub** after commits so the remote stays the source of truth.
3. **Tag releases** for sites using GitHub updates: `git tag -a vX.Y.Z -m "vX.Y.Z: …"` then `git push origin vX.Y.Z`.
4. **Create a GitHub Release** for that tag (attach a zip whose root folder is `builder-meta-cleanup` if you distribute by zip; releases drive `releases/latest` for the updater).

Auto-updates in WordPress use `Builder_Meta_Cleanup_Updater`: it reads **GitHub Releases API** (`releases/latest`) when update source is GitHub/both. **No GitHub Release → no update notification** for those installs.

Quick release commands (after push):

```bash
git tag -a v2.2.0 -m "v2.2.0: …"
git push origin main
git push origin v2.2.0
gh release create v2.2.0 --title "Builder Meta Cleanup v2.2.0" --generate-notes
```

## Admin URL

`tools.php?page=builder-meta-cleanup&bmc_tab=theme|page_builder|plugin|about`

## Safety rules (do not regress)

- Never offer bulk delete while `is_target_active()` is true for that target.
- Options/pattern deletes require inactive owner; validate posted IDs against registry.
- Direct DB deletes for meta/options LIKE — keep batching in `BATCH` constant.

## WP-CLI quick ref

```bash
wp builder-meta counts
wp builder-meta delete --target=elementor --yes
wp builder-meta option-counts
wp builder-meta options-like-delete --target=slider_revolution --pattern=rev_opt --yes
```

---

*Last aligned with development practices for this repository; adjust dates/versions when process changes.*
