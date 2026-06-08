# Setup

## New Client Site

1. Create a fresh Local WordPress site.
2. Clone or copy this blueprint into the Local site folder.
3. Import `app/sql/starter.sql` into the local database.
4. Confirm the site URL matches the new Local domain.
5. Activate the included plugin stack.
6. Activate the `Llummio Blueprint` theme.
7. Add client-specific branding, content, uploads, and plugin license activations.

## Repository Rules

- Keep the repo private.
- Do not commit uploads, logs, cache, or generated runtime files.
- Do not commit plugin license keys or account-specific credentials.
- When editing the blueprint in the Site Editor, use Create Block Theme to save changes back to theme files before committing.
- Update `docs/07-plugin-stack.md` when plugin versions change.
