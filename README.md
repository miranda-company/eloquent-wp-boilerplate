# Llummio Blueprint

Private company-owned WordPress blueprint for starting Llummio client websites.

Llummio designs and develops clean, lean, modern WordPress websites for service businesses. This repository is internal build infrastructure for that work. It includes the reusable Llummio block theme, the standard plugin stack, Local configuration, and a cleaned starter database export.

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

## Documentation

- `docs/project-structure.md` explains what belongs in the blueprint and what stays out.
- `docs/blueprint-editing-workflow.md` explains how to save Site Editor and GenerateBlocks changes back to theme files before committing.
- `docs/client-site-workflow.md` describes how to start and launch a client site.
- `docs/editing-model.md` defines what clients should edit and what Llummio should manage.
- `docs/maintenance.md` describes the ongoing care routine for client sites.
- `docs/plugin-policy.md` defines how plugins are selected, added, and removed.
- `docs/plugin-stack.md` lists the approved plugin stack.
- `docs/setup.md` gives the quick setup checklist.

Keep this repository private.
