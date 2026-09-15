# TranslatePress (Dukkan fork)

A Dukkan-maintained fork of [TranslatePress – Multilingual](https://translatepress.com/), rebranded and updated **exclusively from our own GitHub repository**.

- **Version:** 1.0.2
- **Author:** Dukkan — https://dukkanwoocommerce.com
- **License:** GPLv2 or later

## Why this fork exists

This fork keeps the full TranslatePress feature set (visual front-end translation, WooCommerce support, AI translation, gettext strings) but replaces the update channel. Updates come **only** from this repository — never from WordPress.org.

## Slim build

The plugin is deliberately slimmed down for distribution (~6 MB vs. the original ~27 MB):

- **Translation packs:** only **Arabic (`ar`)** is bundled (plus the English source). All other 42 locale packs (`.po`/`.mo`/`.l10n.php`) are removed.
- **Translation format:** ships `.l10n.php` only (requires WordPress 6.0+).
- **Flags:** the 45 most important language flags are bundled (SVG + PNG).
- **Dev sources:** the `assets/src/` Vue source tree and all `.po` source files are removed.

If you need another language, re-add its locale pack from the upstream TranslatePress release before building.

## How updates work

The plugin ships with a self-updater (`includes/class-dukkan-updater.php`) that:

1. Fetches [`version.json`](version.json) from this repo's `main` branch.
2. Compares its `version` against the installed plugin version.
3. If newer, injects the GitHub release into the native WordPress **Updates** screen (with an "Enable auto-updates" toggle).
4. **Suppresses the WordPress.org update check** for the `translatepress-multilingual` slug, so the upstream Cozmoslabs release is never offered.

No background cron, no silent installs — an update happens only when an admin clicks **update now** (or has auto-updates enabled).

## Releasing a new version

To publish `X.Y.Z`:

1. **Bump the Dukkan release version in three places:**
   - `index.php` → plugin header `Version: X.Y.Z`
   - `index.php` → `define( 'DUKKAN_TRP_RELEASE_VERSION', 'X.Y.Z' );`
   - `readme.txt` → `Stable tag: X.Y.Z`

   > **Do NOT change `TRP_PLUGIN_VERSION`.** It must stay at `3.2.6` (the upstream
   > database schema version). It is used internally to gate database migrations;
   > lowering it would re-run every upgrade routine on each page load. Only bump it
   > if you intentionally add a new database migration.
2. **Update `changelog.txt`** with a `= X.Y.Z =` entry.
3. **Update `version.json`:**
   ```json
   {
     "version": "X.Y.Z",
     "package": "https://github.com/jodukkan-max/translatepress/releases/download/vX.Y.Z/translatepress-multilingual.zip",
     "requires": "5.0",
     "tested": "7.0.2"
   }
   ```
4. **Commit & tag:**
   ```bash
   git add -A
   git commit -m "Release vX.Y.Z"
   git push origin main
   git tag vX.Y.Z
   git push origin vX.Y.Z
   ```
5. **Build the zip** (excluding `.git`):
   ```bash
   cd ..  # parent of translatepress-multilingual/
   rm -f translatepress-multilingual.zip
   zip -rq translatepress-multilingual.zip translatepress-multilingual \
     -x "translatepress-multilingual/.git/*" "translatepress-multilingual/.DS_Store"
   ```
6. **Create the release and upload the zip:**
   ```bash
   gh release create vX.Y.Z translatepress-multilingual.zip \
     --title "vX.Y.Z" --notes "Release notes here"
   ```

## Installing on a site

Install the built `translatepress-multilingual.zip` as a normal WordPress plugin.

> **Migrating from upstream:** if a site already runs the upstream Cozmoslabs build (currently `3.2.6`), it can safely install this fork over it — the internal `TRP_PLUGIN_VERSION` is kept at `3.2.6`, so no database migrations are re-triggered. Replace the plugin folder, then activate.

## Attribution

Based on TranslatePress by Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban, distributed under GPLv2. Forked and maintained by Dukkan.
