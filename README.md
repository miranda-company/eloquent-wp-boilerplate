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
5. Activate the plugins listed in `docs/07-plugin-stack.md`.
6. Activate the `llummio-blueprint` theme.
7. Add premium plugin license keys inside WordPress admin after setup.

## Documentation

Read these documents in order:

1. `docs/01-project-structure.md` explains what belongs in the blueprint and what stays out.
2. `docs/02-setup.md` gives the quick setup checklist.
3. `docs/03-client-site-workflow.md` describes how to start and launch a client site.
4. `docs/04-editing-model.md` defines what clients should edit and what Llummio should manage.
5. `docs/05-blueprint-editing-workflow.md` explains how to save Site Editor and GenerateBlocks changes back to theme files before committing.
6. `docs/06-plugin-policy.md` defines how plugins are selected, added, and removed.
7. `docs/07-plugin-stack.md` lists the approved plugin stack.
8. `docs/08-internal-plugins.md` documents the internal Llummio plugins.
9. `docs/09-maintenance.md` describes the ongoing care routine for client sites.
10. `docs/10-ideal-architecture-roadmap.md` explains the long-term architecture for updating many client sites professionally.

Keep this repository private.
