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

Only update `app/sql/starter.sql` when the blueprint database state should change. Examples:

- Starter pages or posts changed.
- Menus changed.
- Plugin settings changed.
- Forms changed.
- Starter users changed.
- The exported theme files have been saved and the database should be cleaned of editor-only template customizations.

Before exporting `app/sql/starter.sql`, remove private state such as license keys, sessions, cache rows, temporary users, and client-specific content.

## Commit Standard

Commit the exported theme files and database export together when they depend on each other.

For a small follow-up to the latest blueprint commit, amend the previous commit after reviewing the diff. For independent work, create a new commit with a clear message such as:

```text
Update blueprint header pattern
```

The key rule: edit visually in WordPress, save changes to the theme files, then commit the files Git can see.
