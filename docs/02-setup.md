# Setup

## New Client Site

1. Create a fresh Local WordPress site.
2. Copy only the blueprint theme, approved plugins, starter SQL, and docs into the Local site folder.
3. Keep Local's generated WordPress core files and `wp-config.php`.
4. Import `app/sql/starter.sql` from Local's `Site shell`.
5. Confirm `DB_HOST` matches Local's Database tab, including the port when Local uses one.
6. Confirm the site URL matches the new Local domain.
7. Activate the included plugin stack.
8. Activate the `Llummio Blueprint` theme.
9. Add client-specific branding, content, uploads, and plugin license activations.

## Repository Rules

- Keep the repo private.
- Do not commit uploads, logs, cache, or generated runtime files.
- Do not commit plugin license keys or account-specific credentials.
- When editing the blueprint in the Site Editor, use Create Block Theme to save changes back to theme files before committing.
- Update `docs/07-plugin-stack.md` when plugin versions change.
