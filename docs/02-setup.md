# Setup

## New Client Site

1. Create a fresh local WordPress site in the tool you prefer.
2. Copy only the blueprint theme, approved plugins, starter SQL, docs, and tools into the local site folder.
3. Keep the local tool's generated WordPress core files and `wp-config.php`.
4. Open the site's WP-CLI shell or terminal.
5. Run the setup helper:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.local --yes
```

6. The helper tries to detect common local database ports automatically. If it still cannot connect, rerun it with the database host and port shown by the local tool:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.local --db-host=localhost:10005 --yes
```

7. If WP-CLI is wrapped by the local tool, pass that wrapper with `--wp-command`:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.test --wp-command="ddev wp" --yes
```

8. Add client-specific branding, content, uploads, and plugin license activations.

Local WP, WordPress Studio, DDEV, DevKinsta, or another local tool are all acceptable as long as the site has WordPress files, a database, and WP-CLI access.

## Repository Rules

- Keep the repo private.
- Do not commit uploads, logs, cache, or generated runtime files.
- Do not commit plugin license keys or account-specific credentials.
- When editing the blueprint in the Site Editor, use Create Block Theme to save changes back to theme files before committing.
- Update `docs/07-plugin-stack.md` when plugin versions change.
- Run `php tools/check-blueprint.php` before committing blueprint changes.
