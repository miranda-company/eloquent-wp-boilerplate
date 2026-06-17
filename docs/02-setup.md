# Setup

## New Client Site

1. Create a fresh Local WordPress site.
2. Copy only the blueprint theme, approved plugins, starter SQL, docs, and tools into the Local site folder.
3. Keep Local's generated WordPress core files and `wp-config.php`.
4. Open Local's `Site shell`.
5. Run the setup helper:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.local --yes
```

6. The helper tries to detect Local's custom database port automatically. If it still cannot connect, rerun it with the Local port:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.local --db-host=localhost:10005 --yes
```

7. Add client-specific branding, content, uploads, and plugin license activations.

## Repository Rules

- Keep the repo private.
- Do not commit uploads, logs, cache, or generated runtime files.
- Do not commit plugin license keys or account-specific credentials.
- When editing the blueprint in the Site Editor, use Create Block Theme to save changes back to theme files before committing.
- Update `docs/07-plugin-stack.md` when plugin versions change.
- Run `php tools/check-blueprint.php` before committing blueprint changes.
