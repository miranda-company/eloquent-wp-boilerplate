# Client Site Workflow

This workflow describes how Llummio should create a new client website from the blueprint.

## 1. Start From The Blueprint

Use this process when creating a new client site from the Llummio blueprint.

### 1.1 Create The Local Site

1. Open Local (https://localwp.com/).
2. Create a new WordPress site for the client.
3. Name the site using this convention: `client-name-dev`.
4. Use the current Llummio standard PHP and MySQL versions unless the project requires something different.
5. When Local asks for the WordPress admin user, use the starter credentials:

```text
Username: llummio-admin
Email: info@llumm.io
Password: llummio
```

6. Finish the Local setup and confirm the empty WordPress site opens in the browser.

These credentials are only for local starter sites. Replace them before staging, production, client review, or handoff.

### 1.2 Add The Blueprint Files

1. Stop the Local site.
2. Open the Local site folder.
3. Copy the blueprint files into the Local site folder.
4. Keep the blueprint structure intact.
5. Do not copy old uploads, cache folders, logs, or another client's media into the new site.

The important blueprint folders are:

- `app/public/wp-content/themes/llummio-blueprint/`
- `app/public/wp-content/plugins/`
- `app/sql/starter.sql`
- `docs/`

WordPress core can be installed by Local. The blueprint does not need to track WordPress core files.

### 1.3 Import The Starter Database

Use this process for a fresh setup or when testing an updated `app/sql/starter.sql`.

1. Start the Local site.
2. In Local, open the site and go to the Database tab.
3. Open Adminer from Local.
4. Select the site's database, usually `local`.
5. If the site already has content you need to keep, export a backup before continuing.
6. For a re-import, select all existing WordPress tables and drop them before importing the starter database.
7. Confirm the database no longer shows old `wp_` tables.
8. Use Import to upload `app/sql/starter.sql`.
9. Wait for the import to finish before opening WordPress admin.
10. Log out of WordPress, or open the site in a private browser window.
11. Confirm the starter admin works:

```text
Username: llummio-admin
Password: llummio
```

12. Confirm old personal credentials do not work.

After import, the database may still contain the blueprint's original local URL. This is normal and should be corrected in the next step.

### 1.4 Update The Site URL

Confirm WordPress uses the new Local domain.

Check:

- `siteurl`
- `home`

Both values should match the new Local domain, for example:

```text
https://client-name.local
```

If the imported starter database uses another domain, replace it with the new Local domain before continuing.

### 1.5 Open WordPress Admin

1. Open the Local site admin.
2. Log in with the starter admin account: `llummio-admin`.
3. Confirm the admin email is correct for the local starter site.
4. Create or update the internal Llummio admin user.
5. Remove any temporary user accounts that should not remain.

Do not create client user accounts until the site is ready for review or handoff.

### 1.6 Activate Theme And Plugins

1. Go to Appearance > Themes.
2. Activate `Llummio Blueprint`.
3. Go to Plugins.
4. Activate the approved plugins listed in `docs/plugin-stack.md`.
5. Add premium plugin license keys inside WordPress admin.
6. Do not commit license keys, update tokens, or account-specific data.

If a plugin is not needed for the project, document the reason before removing it from the client build.

### 1.7 Verify The Starter Site

Before starting client-specific work, check:

- The homepage loads.
- The WordPress admin loads.
- The `Llummio Blueprint` theme is active.
- Required plugins are active.
- The header and footer render correctly.
- Forms, if included, are visible in the admin.
- No broken local URLs are visible on the frontend.
- No warnings appear in the WordPress admin that block work.

When this passes, the site is ready for client-specific structure, branding, content, and design work.

## 2. Define The Client Model

Before building pages, decide what the client needs to edit.

Common content types for service businesses:

- Services.
- Case studies.
- Testimonials.
- Team members.
- FAQs.
- Locations.
- Resources or blog posts.

Use regular pages when content is unique and page-specific. Use custom fields or custom post types when content repeats or needs structure.

## 3. Build The Website

Build from reusable patterns first:

- Header.
- Footer.
- Hero sections.
- Service grids.
- Process sections.
- Testimonial sections.
- FAQ sections.
- Calls to action.
- Contact sections.

Keep layouts simple enough for the client to update without breaking the site. Avoid adding plugins or custom code for one-off convenience unless it creates clear long-term value.

## 4. Prepare For Review

Before client review:

- Check desktop and mobile layouts.
- Check navigation and footer links.
- Check forms and notifications.
- Check image sizes and alt text.
- Check headings and page structure.
- Check basic performance.
- Check no temporary wireframe classes remain visible.

## 5. Prepare For Launch

Before launch:

- Confirm production URLs.
- Confirm SSL.
- Confirm redirects if replacing an existing website.
- Confirm forms send to the correct inbox.
- Confirm backups are active.
- Confirm caching and optimization settings.
- Confirm plugin licenses are active.
- Confirm admin users are correct.

## 6. Handoff

After launch, provide only the editing guidance the client actually needs:

- How to edit common page sections.
- How to add or update services.
- How to update forms.
- How to add posts or resources.
- What not to touch without Llummio support.

The handoff should protect the client from the parts of WordPress they do not need.
