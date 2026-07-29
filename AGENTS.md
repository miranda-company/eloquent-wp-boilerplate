# AGENTS.md

This repository is the private Llummio WordPress blueprint for creating lean service-business websites.

## Start Here

Before making changes, read the relevant docs:

1. `README.md`
2. `docs/01-project-structure.md`
3. `docs/02-setup.md`
4. `docs/03-client-site-workflow.md`
5. `docs/05-blueprint-editing-workflow.md` for Site Editor, pattern, template, or starter database changes
6. `docs/06-plugin-policy.md`, `docs/07-plugin-stack.md`, and `docs/08-internal-plugins.md` for plugin changes

## Project Rules

- Keep this repo private.
- Keep the blueprint local-tool agnostic. Local WP, WordPress Studio, DDEV, DevKinsta, or another tool may be used as long as WordPress files, a database, and WP-CLI access are available.
- Do not commit WordPress core files.
- Do not commit uploads, generated media, logs, cache, runtime folders, plugin license keys, credentials, session tokens, or client-private content.
- Keep `app/sql/starter.sql` clean and intentional. It should contain the starter WordPress state for new sites, not temporary build state.
- Save Site Editor, template, template-part, and GenerateBlocks pattern changes back to theme files before committing.
- Clear database-only template customizations when they are no longer needed, so the starter database does not override committed theme files.
- Keep theme CSS, `functions.php`, and JavaScript focused on reusable blueprint behavior.
- Keep internal Llummio plugins lean and documented.
- Do not add new plugins unless they pass the plugin policy.

## Verification

When PHP files change, run PHP lint on the edited files.

Before committing blueprint changes, run:

```bash
php tools/check-blueprint.php
```

For setup or starter database changes, smoke test a fresh local site from `app/sql/starter.sql` and confirm the starter `Demo Page` loads.

## Commit Hygiene

- Review the Git diff before committing.
- Commit related theme, plugin, docs, and starter SQL changes together when they depend on each other.
- Do not revert unrelated user changes.
