# Setup

## New Client Site

1. Create a fresh Local WordPress site.
2. From the blueprint repository, prepare a clean package:

```bash
php tools/prepare-client-package.php --name=client-name
```

3. Copy the prepared package contents into the Local site folder.
4. Keep Local's generated WordPress core files and `wp-config.php`.
5. Open Local's `Site shell`.
6. Run the setup helper:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.local --yes
```

7. The helper tries to detect Local's custom database port automatically. If it still cannot connect, rerun it with the Local port:

```bash
php ..\..\tools\setup-client-site.php --url=http://client-name.local --db-host=localhost:10005 --yes
```

8. Add client-specific branding, content, uploads, and plugin license activations.

## Repository Rules

- Keep the repo private.
- Do not commit uploads, logs, cache, or generated runtime files.
- Do not commit plugin license keys or account-specific credentials.
- When editing the blueprint in the Site Editor, use Create Block Theme to save changes back to theme files before committing.
- Update `docs/07-plugin-stack.md` when plugin versions change.
- Run `php tools/check-blueprint.php` before committing blueprint changes.
