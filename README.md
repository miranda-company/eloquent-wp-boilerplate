# Llummio Blueprint

Private company-owned WordPress blueprint for starting Llummio client websites.

This repository is intended as internal build infrastructure. It includes the reusable Llummio block theme, the standard plugin stack, Local configuration, and a cleaned starter database export.

## Included

- Llummio Blueprint block theme.
- Standard Llummio plugin stack, including premium plugins used internally.
- Local app server configuration.
- Cleaned starter database export at `app/sql/starter.sql`.

## Not Included

- WordPress core files.
- Uploads and generated media.
- Logs, cache, WPO generated files, and runtime folders.
- Plugin license keys, update-cache rows, or WordPress session tokens.

## Use

1. Create a new Local WordPress site.
2. Copy or clone this blueprint into the site folder.
3. Install WordPress core if needed.
4. Import `app/sql/starter.sql`.
5. Activate the plugins listed in `docs/plugin-stack.md`.
6. Activate the `llummio-blueprint` theme.
7. Add premium plugin license keys inside WordPress admin after setup.

Keep this repository private.
