# TranslatePress (Dukkan fork)

A Dukkan-maintained fork of [TranslatePress – Multilingual](https://translatepress.com/), rebranded and distributed as a **manually-installed** plugin.

- **Author:** Dukkan — https://dukkanwoocommerce.com
- **License:** GPLv2 or later

## Why this fork exists

This fork keeps the full TranslatePress feature set (visual front-end translation, WooCommerce support, AI translation, gettext strings) but is distributed **manually**. It is **not** tied to WordPress.org or any auto-update channel.

## Slim build

The plugin is deliberately slimmed down for distribution:

- **Translation packs:** only **Arabic (`ar`)** is bundled (plus the English source). All other 42 locale packs (`.po`/`.mo`/`.l10n.php`) are removed.
- **Translation format:** ships `.l10n.php` only (requires WordPress 6.0+).
- **Flags:** the 45 most important language flags are bundled (SVG + PNG).
- **Dev sources:** the `assets/src/` Vue source tree and all `.po` source files are removed.

If you need another language, re-add its locale pack from the upstream TranslatePress release before building.

## Distribution

The plugin has **no updater**. To deploy:

1. Build the zip (excluding `.git`):
   ```bash
   cd ..  # parent of dukkan-translatepress/
   rm -f dukkan-translatepress.zip
   zip -rq dukkan-translatepress.zip dukkan-translatepress \
     -x "dukkan-translatepress/.git/*" "dukkan-translatepress/.DS_Store"
   ```
2. Upload `dukkan-translatepress.zip` to the site and install it normally.

## Installing on a site

Install the built `dukkan-translatepress.zip` as a normal WordPress plugin.

> **Migrating from upstream:** a site already running the upstream Cozmoslabs build (currently `3.2.6`) can install this fork alongside or over it — the internal `TRP_PLUGIN_VERSION` is kept at `3.2.6`, so no database migrations are re-triggered. Because this fork uses a different folder name, it will not collide with the upstream plugin's slug.

## Internal version constants

The plugin uses two distinct constants — do not confuse them:

| Constant | Value | Purpose |
|---|---|---|
| `TRP_PLUGIN_VERSION` | `3.2.6` (do not change) | Internal DB schema version — gates database migrations. |
| Plugin header `Version` | informational | Shown in the Plugins screen; does not drive anything. |

> **Never lower `TRP_PLUGIN_VERSION`.** It must stay at (or above) the upstream base (`3.2.6`). Lowering it re-runs every one-time database migration on each page load.

## Attribution

Based on TranslatePress by Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban, distributed under GPLv2. Forked and maintained by Dukkan.
