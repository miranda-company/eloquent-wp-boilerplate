# Maintenance

Every WordPress website needs ongoing care. Llummio sites should be maintained with a predictable routine so they stay secure, fast, and easy to work on.

## Maintenance Goals

- Keep WordPress, plugins, and themes updated.
- Prevent avoidable security issues.
- Preserve backups before risky changes.
- Keep performance stable.
- Keep the editing experience clean.
- Catch broken forms, links, and layouts early.

## Regular Checks

Recommended monthly checks:

- WordPress core updates.
- Plugin updates.
- Theme updates.
- Backup status.
- Security alerts.
- Form submissions and notifications.
- Broken links.
- Basic page speed.
- Mobile layout sanity check.
- Error logs if something looks wrong.

## Before Updating Plugins

1. Confirm a recent backup exists.
2. Review plugin changelogs when the update is large.
3. Update on staging first for important client sites.
4. Test the main pages, forms, menus, and editable content.
5. Apply to production after checks pass.

## Backup Standard

Backups should cover:

- Database.
- Uploads.
- Theme and plugin files.
- Production configuration when available.

Backups should be restorable, not just enabled. Periodically verify that the backup process is working.

## Performance Standard

Keep sites lean:

- Use only necessary plugins.
- Compress and size images properly.
- Avoid heavy page-builder behavior.
- Keep animations purposeful.
- Avoid loading scripts on pages that do not need them where practical.
- Review caching and optimization settings after launch.

## Security Standard

Basic expectations:

- Unique admin accounts.
- Strong passwords.
- No shared production credentials in Git.
- No unused admin users.
- No abandoned plugins.
- No editable theme/plugin files from the WordPress admin when avoidable.

## Change Notes

For meaningful maintenance changes, record:

- Date.
- What changed.
- Why it changed.
- Any follow-up needed.

This can live in the client project notes, maintenance system, or issue tracker.
