# Blueprint Editing Workflow

Use this workflow when improving the reusable blueprint itself.

## Why Editor Changes Do Not Appear In Git

WordPress saves Site Editor changes in the database first. This includes block templates, template parts, and synced patterns built with GenerateBlocks.

Git only sees file changes. If the header or footer is edited in WordPress and nothing exports those database changes back to the theme, there is nothing new to commit except a database export.

For this blueprint, the durable source should be:

- Theme templates and parts in `app/public/wp-content/themes/llummio-blueprint/`.
- Theme patterns in `app/public/wp-content/themes/llummio-blueprint/patterns/`, when patterns are exported.
- Theme assets in `app/public/wp-content/themes/llummio-blueprint/assets/`.
- The clean starter database at `app/sql/starter.sql`, only when starter content, menus, plugin settings, users, or other database state should change.

Do not rely on `app/sql/starter.sql` as the only place where reusable header, footer, or pattern markup lives.

## Editing Header, Footer, Templates, Or Patterns

1. Make the change in the WordPress Site Editor.
2. In the Site Editor, open the Create Block Theme panel.
3. Choose `Save Changes to Theme`.
4. Use these options:

| Option | Use |
| --- | --- |
| `Save Template Changes` | On when header, footer, or templates changed. |
| `Process Only Modified Templates` | On for normal blueprint work. |
| `Save Patterns` | On when synced patterns or pattern library items changed. |
| `Save Style Changes` | On when global styles changed. |
| `Save Fonts` | On when theme fonts changed. |
| `Localize Images` | On when templates or patterns use Media Library images that should become theme assets. |
| `Remove Navigation Refs` | Off unless intentionally resetting navigation references. |

5. Save and let the editor reload.
6. Check Git. Expected changed files usually include one or more of:

- `app/public/wp-content/themes/llummio-blueprint/parts/header.html`
- `app/public/wp-content/themes/llummio-blueprint/parts/footer.html`
- `app/public/wp-content/themes/llummio-blueprint/templates/*.html`
- `app/public/wp-content/themes/llummio-blueprint/patterns/*.php`
- `app/public/wp-content/themes/llummio-blueprint/theme.json`
- `app/public/wp-content/themes/llummio-blueprint/assets/*`

7. Review the diff before committing.

## Updating The Starter Database

`app/sql/starter.sql` is the database that a new Local site imports when starting from the blueprint. Updating it means replacing the old committed SQL export with a fresh, clean export from your current blueprint site.

This is separate from saving header, footer, template, or pattern changes to the theme. Those changes should live in the theme files first. The database export should only capture the WordPress starter state that cannot live cleanly in theme files.

Only update `app/sql/starter.sql` when the blueprint database state should change. Examples:

- Starter pages or posts changed.
- Menus changed.
- Plugin settings changed.
- Forms changed.
- Starter users changed.
- The exported theme files have been saved and the database should be cleaned of editor-only template customizations.

Do not update `app/sql/starter.sql` just because the header, footer, templates, or patterns changed. For those changes, use `Create Block Theme > Save Changes to Theme` and commit the changed theme files.

### Before Exporting

1. Save editor changes to the theme:
   - Open the Site Editor.
   - Open the Create Block Theme panel.
   - Use `Save Changes to Theme`.
   - Confirm the expected theme files changed in Git.
2. Confirm WordPress is no longer carrying editor-only template customizations:
   - If the admin warning says template changes are still saved in the database, do not export `starter.sql` yet.
   - Go back to the Site Editor and make sure template/template-part changes have been saved to the theme files.
   - Reset or clear database-only template customizations when they are no longer needed, so the starter database does not override the committed theme files.
3. Clean private or temporary state:
   - Remove license keys, API keys, and update tokens.
   - Remove temporary users.
   - Remove client-specific content.
   - Empty trash, spam, drafts, and temporary test content.
   - Clear plugin caches and transient/runtime data where possible.
4. Keep the starter login intentional:
   - The starter admin should remain `llummio-admin`.
   - Do not export personal or client credentials.

### Exporting With Local And Adminer

Use this process when you are ready to replace `app/sql/starter.sql`.

1. Start the blueprint site in Local.
2. Open Local's `Database` tab.
3. Open `Adminer`.
4. Select the WordPress database, usually `local`.
5. Open `Export`.
6. Export all WordPress tables.
7. Use SQL format.
8. Include both table structure and table data.
9. Include `DROP TABLE` statements, so imports can replace old tables cleanly.
10. Save/download the export.
11. Rename the downloaded SQL file to `starter.sql`.
12. Replace the existing file at `app/sql/starter.sql`.
13. Check Git and review the diff before committing.

The exported file should be a full SQL dump, not a partial table export. It should include `CREATE TABLE` statements, `INSERT INTO` statements, and the starter WordPress options/content needed for a new site.

### After Exporting

Before committing, check the new `app/sql/starter.sql` for:

- The expected starter pages, menus, forms, and plugin settings.
- No license keys or private tokens.
- No personal users or client users.
- No client-specific posts, media, or URLs.
- No database-only header/footer/template customizations that would override the committed theme files.

Then test the export when the database change is important:

1. Create or use a separate throwaway Local site.
2. Import the new `app/sql/starter.sql`.
3. Confirm WordPress loads.
4. Confirm the starter admin works.
5. Confirm the theme, header, footer, menus, forms, and settings look right.
6. Confirm no unexpected admin warning appears about unsaved template customizations.

If the exported database depends on theme file changes, commit `app/sql/starter.sql` and the changed theme files together.

## Commit Standard

Commit the exported theme files and database export together when they depend on each other.

For a small follow-up to the latest blueprint commit, amend the previous commit after reviewing the diff. For independent work, create a new commit with a clear message such as:

```text
Update blueprint header pattern
```

The key rule: edit visually in WordPress, save changes to the theme files, then commit the files Git can see.
