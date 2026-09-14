# TranslatePress (Dukkan fork)

A Dukkan-maintained fork of [TranslatePress – Multilingual](https://translatepress.com/), rebranded and updated **exclusively from our own GitHub repository**.

- **Version:** 1.0.0
- **Author:** Dukkan — https://dukkanwoocommerce.com
- **License:** GPLv2 or later

## Why this fork exists

This fork keeps the full TranslatePress feature set (visual front-end translation, WooCommerce support, AI translation, gettext strings) but replaces the update channel. Updates come **only** from this repository — never from WordPress.org.

## How updates work

The plugin ships with a self-updater (`includes/class-dukkan-updater.php`) that:

1. Fetches [`version.json`](version.json) from this repo's `main` branch.
2. Compares its `version` against the installed plugin version.
3. If newer, injects the GitHub release into the native WordPress **Updates** screen (with an "Enable auto-updates" toggle).
4. **Suppresses the WordPress.org update check** for the `translatepress-multilingual` slug, so the upstream Cozmoslabs release is never offered.

No background cron, no silent installs — an update happens only when an admin clicks **update now** (or has auto-updates enabled).

## Releasing a new version

To publish `X.Y.Z`:

1. **Bump the version in three places:**
   - `index.php` → plugin header `Version: X.Y.Z`
   - `class-translate-press.php` → `define( 'TRP_PLUGIN_VERSION', 'X.Y.Z' );`
   - `readme.txt` → `Stable tag: X.Y.Z`
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

> **Important:** if a site already runs the upstream Cozmoslabs build (currently `3.2.6`), do **not** try to "update" to this fork over it — `1.0.0` is lower than `3.2.6`, so WordPress would treat it as a downgrade. Replace the plugin folder / install fresh instead.

## Attribution

Based on TranslatePress by Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban, distributed under GPLv2. Forked and maintained by Dukkan.
