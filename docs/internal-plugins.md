# Internal Plugins

This document describes plugins maintained inside the Llummio blueprint.

Internal plugins should stay small, focused, and easy to remove. They are used when the blueprint needs a reusable behavior that should not live in the theme.

## Llummio Editor Helpers

Folder: `app/public/wp-content/plugins/llummio-editor-helpers/`

Main file: `app/public/wp-content/plugins/llummio-editor-helpers/llummio-editor-helpers.php`

Version: `0.6.2`

### Purpose

`Llummio Editor Helpers` collects small editing tools used in Llummio blueprint sites.

It currently includes lightweight SEO fields, global business schema settings, page-level schema tools, and an editor-only wireframe preview toggle.

The SEO tools replace the small part of RankMath that Llummio normally uses on simple sites: custom SEO titles, custom SEO descriptions, robots controls, automatic canonical tags, and simple structured data. They do not try to replace a full SEO suite.

### What It Does

- Adds a `Llummio Editor Helpers` icon to the block editor toolbar for public editable content types.
- Opens a `Llummio Editor Helpers` sidebar panel in the editor.
- Stores a custom SEO title.
- Stores a custom SEO description.
- Stores page-level robots controls for `noindex` and `nofollow`.
- Overrides the frontend document title on singular content when an SEO title exists.
- Outputs one `<meta name="description">` tag on singular content when an SEO description exists.
- Outputs one `<meta name="robots">` tag on singular content when robots controls are enabled.
- Outputs one canonical `<link rel="canonical">` tag on singular content.
- Outputs JSON-LD structured data for singular content when schema output is enabled.
- Adds a top-level `Global Schema` admin menu item below `Llummio Forms`, using the Llummio SVG icon.
- Adds automatic `Organization` or `LocalBusiness`, `WebSite`, `WebPage`, and `BlogPosting` schema.
- Adds page-level schema controls for `Service`, `Article`, `FAQPage`, and disabled schema.
- Registers the fields with the WordPress REST API for future editor integrations.
- Adds an editor-only wireframe toggle for the theme `.wire` helper class.

### What It Does Not Do

- XML sitemaps.
- Open Graph or social images.
- Redirects.
- Canonical URL override controls.
- Keyword analysis.
- Search Console integrations.
- Advanced schema builders or custom JSON-LD editing.

If a project needs those features, use a full SEO plugin such as RankMath.

### Fields

The plugin stores data in post meta:

- `_llummio_seo_title`
- `_llummio_seo_description`
- `_llummio_seo_noindex`
- `_llummio_seo_nofollow`
- `_llummio_schema_type`
- `_llummio_schema_name`
- `_llummio_schema_description`
- `_llummio_schema_url`
- `_llummio_schema_image_url`
- `_llummio_schema_service_area`
- `_llummio_schema_faq_items`

The plugin also stores global business schema settings in the `llummio_editor_helpers_settings` option.

Empty fields are deleted from post meta instead of saved as empty strings.
Disabled robots controls are deleted from post meta instead of saved as false values.

### How To Use It

1. Open a page in WordPress admin.
2. Click the `Llummio Editor Helpers` icon in the top-right editor toolbar, near the other editor plugin icons.
3. Add an `SEO Title` when the browser/search title should be different from the page title.
4. Add an `SEO Description` when the page needs a custom meta description.
5. Update the page.
6. View the page on the frontend and check the browser title and page source.

Leave either field empty when the page should use WordPress or theme defaults.

The editor shows soft character recommendations:

- SEO titles should ideally stay within 60 characters.
- SEO descriptions should ideally stay within 160 characters.

The plugin does not block longer text. The counter turns red when a field exceeds the recommendation.

SEO titles support two RankMath-style tokens:

- `%sep%` is replaced with the WordPress document title separator.
- `%sitename%` is replaced with the site name.

When the SEO title field is empty in the editor, it displays a default title pattern using the current page title plus `%sep% %sitename%`. Editors can keep, change, or remove those tokens before saving.

### Robots Controls

The `SEO Tools` section includes two page-level robots checkboxes:

- `No index`
- `No follow`

When either option is enabled, the plugin outputs a single `<meta name="robots">` tag on the frontend for that page.

Examples:

```html
<meta name="robots" content="noindex" />
<meta name="robots" content="noindex, nofollow" />
```

Leave both options unchecked for normal indexable pages.

### Canonical Tags

The plugin outputs one automatic canonical tag on singular content:

```html
<link rel="canonical" href="https://example.com/page/" />
```

It uses WordPress' canonical URL helper, so the canonical URL normally matches the page permalink.

WordPress already outputs a basic canonical tag for singular content. When `Llummio Editor Helpers` is handling SEO output, it removes the WordPress default canonical tag and replaces it with its own single canonical tag. This avoids duplicate canonical tags.

There is no canonical override field in the editor. If a project needs custom canonical URLs, use a full SEO plugin such as RankMath.

### Schema Tools

Schema is split between global business settings and page-level controls.

Global business schema is managed in WordPress admin at `Global Schema`. Use that screen to set:

- business schema type: `Organization` or `Local Business`;
- business name;
- logo URL;
- website URL;
- telephone;
- email;
- address;
- service area;
- price range.

By default, the plugin outputs:

- `Organization` or `LocalBusiness` using the global settings.
- `WebSite` using the WordPress site name and homepage URL.
- `WebPage` for pages and public custom post types.
- `BlogPosting` for standard posts.

The page editor sidebar only controls what the current page is:

- `Default` keeps the automatic behavior.
- `None` disables schema output for that page.
- `Service` adds a page-level `Service` node with optional name, description, URL, image URL, and area served fields.
- `Article` turns the page node into `Article`.
- `FAQ Page` adds editable FAQ questions and answers.

Service schema should only be used for real service pages. FAQ schema should only be used when the same questions and answers are visible on the page. The plugin stores up to 10 complete FAQ items and ignores incomplete items on save.

Product, review, person, professional service, and breadcrumb schema are intentionally excluded to keep the plugin focused on service-business websites. Use a full SEO plugin when a project needs those schema types.

The plugin skips schema output when:

- a full SEO plugin such as RankMath, Yoast SEO, All in One SEO, or SEOPress is active;
- the page schema type is set to `None`;
- the page has `No index` enabled.

### Wireframe Toggle

The sidebar includes a `Wireframe Tools` section with a `Show wire borders` checkbox.

When checked, the editor temporarily shows `.wire` elements with a 1px border. The toggle is editor-only and does not save anything to the page, theme, or starter database.

Editors can choose one of these wire colors:

- Chroma blue: `#1100ff`
- Chroma green: `#26ff00`
- White: `#ffffff`

The enabled state and selected color are stored in the browser, so each team member can keep their own wireframe setting.

### Supported Content

By default, fields appear on public editable post types, excluding attachments.

To change the supported post types:

```php
add_filter(
	'llummio_editor_helpers_post_types',
	function() {
		return array( 'page', 'post' );
	}
);
```

### Compatibility With Full SEO Plugins

The plugin avoids frontend title and description output when common full SEO plugins are active:

- RankMath
- Yoast SEO
- All in One SEO
- SEOPress

The fields can still exist in the editor, but frontend output is skipped to avoid duplicate SEO tags, duplicate canonical tags, and duplicate structured data.

To force output anyway:

```php
add_filter( 'llummio_editor_helpers_skip_frontend_output', '__return_false' );
```

To force schema output while still letting a full SEO plugin control the title, description, and robots output:

```php
add_filter( 'llummio_editor_helpers_skip_schema_output', '__return_false' );
```

### WordPress APIs Used

The plugin depends on these WordPress APIs:

- `register_post_meta`
- `enqueue_block_editor_assets`
- `wp.plugins.registerPlugin`
- `wp.editor.PluginSidebar`
- `register_setting`
- `add_menu_page`
- `get_option`
- `save_post`
- `pre_get_document_title`
- `wp_head`
- `wp_get_canonical_url`
- `add_theme_support( 'title-tag' )`
- WordPress image and post helpers for JSON-LD output

These are stable WordPress extension points. Still, check the plugin when the blueprint target WordPress version changes.

### Upgrade Checklist

When upgrading the blueprint to a new major WordPress version:

1. Confirm the `Llummio Editor Helpers` sidebar opens on pages.
2. Confirm the `Llummio Editor Helpers` icon appears in the top-right block editor toolbar.
3. Open the sidebar and save an SEO title and SEO description.
4. Confirm both values stay saved after reload.
5. Confirm the frontend `<title>` uses the SEO title.
6. Confirm the frontend has one meta description tag.
7. Enable `No index` and confirm the frontend has one robots meta tag.
8. Enable `No follow` and confirm the robots meta tag includes both selected rules.
9. Clear both fields and controls, then confirm the plugin stops outputting custom SEO data.
10. Confirm the wireframe checkbox turns `.wire` borders on and off in the editor.
11. Confirm a singular page outputs one canonical tag.
12. Confirm no duplicate title/description/robots/canonical output appears if a full SEO plugin is active.
13. Open `Global Schema` in the WordPress admin sidebar and confirm the global schema settings page loads.
14. Save `Organization` settings and confirm the saved values stay after reload.
15. Switch the global schema type to `Local Business`, save contact/address details, and confirm the saved values stay after reload.
16. Confirm a normal page outputs one JSON-LD block with the global `Organization` or `LocalBusiness`, `WebSite`, and `WebPage`.
17. Confirm a post outputs `BlogPosting`.
18. Set a page to `Service`, add visible service details, and confirm the `Service` node appears.
19. Set a page to `Article` and confirm the page node changes to `Article`.
20. Set a page to `FAQ Page`, add one visible FAQ, and confirm `FAQPage` output appears.
21. Set schema to `None` and confirm schema output stops on that page.
22. Enable `No index` and confirm schema output stops on that page.
23. Confirm no duplicate schema output appears if a full SEO plugin is active.
24. Confirm no PHP warnings appear in WordPress admin.

### When To Replace It

Replace this plugin with RankMath or another full SEO plugin when a project needs:

- sitemap control;
- advanced schema control;
- custom JSON-LD editing;
- Open Graph/social metadata;
- redirect management;
- advanced robots/canonical controls;
- SEO scoring or content analysis;
- integrations with external SEO tools.

### Commit Notes

If this plugin changes:

- Update the version in the plugin header.
- Update `docs/plugin-stack.md` if the version changes.
- Update this document if behavior or compatibility assumptions change.
- Confirm `app/sql/starter.sql` still activates `llummio-editor-helpers/llummio-editor-helpers.php`.

## Llummio Forms

Folder: `app/public/wp-content/plugins/llummio-forms/`

Main file: `app/public/wp-content/plugins/llummio-forms/llummio-forms.php`

Version: `0.3.3`

### Purpose

`Llummio Forms` provides lightweight lead forms for Llummio service-business websites.

It replaces the need to ship a full form-builder plugin in the blueprint when the project only needs standard contact or lead forms.

### What It Does

- Adds a top-level `Llummio Forms` admin menu using the Llummio SVG icon.
- Adds a small dashboard with submission counts by form.
- Adds a forms list and form editor.
- Adds the `[llummio_form id="form-id"]` shortcode.
- Supports multiple reusable form configurations.
- Renders fixed, configurable lead forms.
- Supports these common fields:
  - name;
  - second name;
  - last name;
  - phone number;
  - email address;
  - address;
  - country;
  - comments.
- Lets administrators choose which fields are shown and required.
- Lets administrators edit field labels, the submit button label, legal consent text, privacy/terms link text, and form error messages.
- Provides simple desktop layout widths per field: full, half, or third.
- Keeps email address required.
- Lets administrators enable or disable the privacy consent checkbox per form.
- Uses WordPress nonce validation.
- Adds a honeypot field.
- Adds a minimum time check before submission.
- Adds basic rate limiting by visitor IP.
- Optionally verifies Google reCAPTCHA v2 checkbox.
- Validates email addresses server-side.
- Validates Spanish-style phone numbers, including optional `+34`.
- Sends an admin notification email.
- Sends an optional user confirmation email.
- Shows a confirmation message or redirects to a thank-you URL.
- Counts successful submissions per form.
- Supports terms and privacy policy URLs for the consent checkbox.
- Inherits the website font.
- Provides simple color settings for fields and the submit button.

### What It Does Not Do

- Drag-and-drop form building.
- Conditional logic.
- Multi-step forms.
- File uploads.
- Payment forms.
- Entry storage.
- Viewing individual submissions.
- CSV exports.
- CRM or marketing integrations.
- SMTP delivery.

The plugin sends mail through WordPress' normal `wp_mail()` function. Configure SMTP at the server level or with a dedicated SMTP plugin when a project needs authenticated mail delivery.

Use a full form plugin when a project needs advanced form behavior.

### How To Use It

1. Go to `Llummio Forms > Forms`.
2. Create a form or edit the default form.
3. Choose which fields should appear.
4. Edit labels when the default Spanish text is not right for the project.
5. Choose a desktop width for each field: full, half, or third.
6. Edit the Spanish error messages if the project needs different wording.
7. Set the submit button label and legal consent text.
8. Set confirmation behavior.
9. Set admin and user email notification text.
10. Add terms and privacy policy URLs.
11. Add reCAPTCHA keys when the project needs reCAPTCHA.
12. Copy the shortcode from the form list or dashboard and add it to a page or pattern.

Example:

```text
[llummio_form id="default"]
```

The form ID is generated from the form name when a form is created. Keep the ID stable because changing shortcodes in pages and patterns is a manual content task.

### Layout Control

The plugin intentionally avoids a drag-and-drop builder. Layout is controlled from the field table with simple desktop widths:

- `Full`: the field takes the full form width.
- `Half`: the field takes half of the form width on desktop.
- `Third`: the field takes one third of the form width on desktop.

All fields stack to one column on smaller screens. This keeps the form responsive and predictable while still allowing common layouts such as two-column name/contact rows.

### Legal Consent

Privacy consent is enabled by default, but it can be disabled per form.

The consent text is editable and supports two tokens:

- `{terms}` inserts the terms link.
- `{privacy}` inserts the privacy policy link.

Example:

```text
Acepto los {terms} y la {privacy}.
```

The link labels and URLs are also editable. If the text does not include tokens, the plugin appends the configured legal links after the consent text.

### Security Model

The plugin is designed for simple lead capture on trusted Llummio-managed sites.

It does not store submissions in the database. It only stores a numeric submission count per form. This keeps the blueprint lighter and reduces the amount of personal data stored by default.

Every submission is checked with:

- a WordPress nonce;
- a hidden honeypot field;
- a minimum time-to-submit check;
- basic rate limiting;
- server-side required field validation;
- server-side email validation;
- optional reCAPTCHA verification.

### Dashboard

The `Llummio Forms` dashboard shows:

- form name;
- shortcode;
- number of successful submissions;
- quick edit and count reset actions.

The counter increments only after a valid submission passes validation and notification handling starts. It is meant as a lightweight activity signal, not analytics and not an entry database.

### WordPress APIs Used

The plugin depends on these WordPress APIs:

- `add_menu_page`
- `add_submenu_page`
- `admin_post_*`
- `add_shortcode`
- `get_option`
- `update_option`
- `wp_nonce_field`
- `wp_verify_nonce`
- `wp_mail`
- `wp_remote_post`
- `set_transient`
- `get_transient`
- `wp_enqueue_style`

These are stable WordPress extension points. Still, check the plugin when the blueprint target WordPress version changes.

### Upgrade Checklist

When upgrading the blueprint to a new major WordPress version:

1. Confirm the top-level `Llummio Forms` menu appears in WordPress admin.
2. Confirm the dashboard opens and lists every form.
3. Confirm `Llummio Forms > Forms` opens.
4. Create a second form and confirm it appears in the forms list.
5. Confirm each form shows a shortcode with its own ID.
6. Confirm field visibility and required settings save per form.
7. Confirm field label edits save and render on the frontend.
8. Confirm editable error messages save and render on invalid submissions.
9. Confirm the submit button label edit saves and renders on the frontend.
10. Confirm full, half, and third field widths render correctly on desktop and stack on mobile.
11. Confirm `[llummio_form id="default"]` renders on a page.
12. Confirm a second form shortcode renders that form's labels and settings.
13. Submit a valid form and confirm the success message appears.
14. Confirm the dashboard count increases for the submitted form only.
15. Switch confirmation to redirect and confirm the thank-you URL works.
16. Confirm the admin notification email is sent.
17. Confirm the user confirmation email is sent.
18. Submit with an invalid email and confirm it is rejected.
19. Submit with an invalid phone number and confirm it is rejected.
20. Submit without privacy consent while consent is enabled and confirm it is rejected.
21. Disable privacy consent and confirm the checkbox is not rendered.
22. Add `{terms}` and `{privacy}` to the consent text and confirm the links render in the right place.
23. Enable reCAPTCHA with valid keys and confirm submission still works.
24. Confirm no submission entries or personal data are stored in the database.
25. Confirm no PHP warnings appear in WordPress admin.

### When To Replace It

Replace this plugin with a full form plugin when a project needs:

- complex custom forms;
- conditional logic;
- multi-step forms;
- file uploads;
- payments;
- stored entries;
- CSV exports;
- CRM integrations;
- advanced email routing;
- visual form building by the client.

### Commit Notes

If this plugin changes:

- Update the version in the plugin header.
- Update `docs/plugin-stack.md` if the version changes.
- Update this document if behavior or compatibility assumptions change.
- Confirm `app/sql/starter.sql` still activates `llummio-forms/llummio-forms.php`.

## Llummio SVG Uploads

Folder: `app/public/wp-content/plugins/llummio-svg-uploads/`

Main file: `app/public/wp-content/plugins/llummio-svg-uploads/llummio-svg-uploads.php`

Version: `0.1.0`

### Purpose

`Llummio SVG Uploads` replaces the heavier third-party SVG upload plugin that used to ship with the blueprint.

It exists because WordPress does not normally allow SVG uploads by default, and the blueprint often needs SVG logos and icons.

### What It Does

- Allows `.svg` files in the Media Library for users who can upload media.
- Helps WordPress validate SVG file type and extension checks.
- Rejects SVG files that contain obvious unsafe markup.
- Adds SVG dimensions to attachment metadata when possible.
- Improves SVG previews in the Media Library.

### Security Model

This plugin is for trusted Llummio-managed sites where SVG files usually come from designers, developers, or reviewed brand assets.

It does not fully sanitize SVG files. Instead, it blocks common dangerous patterns before the file is saved:

- `<script>`
- `<foreignObject>`
- `<iframe>`
- `<object>`
- `<embed>`
- inline event attributes such as `onclick=`
- `javascript:` URLs
- `data:text/html`
- XML entities and doctypes
- XML stylesheet processing instructions

This is intentionally lighter than a complete sanitizer. If a project needs untrusted users to upload SVGs, replace this plugin with a full sanitizer-based solution.

### Who Can Upload SVGs

By default, any user who has the WordPress `upload_files` capability can upload SVG files.

To restrict SVG uploads to another capability, filter `llummio_svg_uploads_capability`:

```php
add_filter(
	'llummio_svg_uploads_capability',
	function() {
		return 'manage_options';
	}
);
```

With that example, only administrators can upload SVGs.

### WordPress APIs Used

The plugin depends on these WordPress hooks:

- `upload_mimes`
- `wp_check_filetype_and_ext`
- `wp_handle_upload_prefilter`
- `wp_generate_attachment_metadata`
- `wp_prepare_attachment_for_js`

These are standard WordPress extension points. That makes the plugin likely to survive normal WordPress updates, but it still needs a quick check when the blueprint target WordPress version changes.

### Upgrade Checklist

When upgrading the blueprint to a new major WordPress version:

1. Confirm SVG upload still works for an administrator.
2. Confirm a normal PNG/JPG upload still works.
3. Confirm an SVG with `<script>` is rejected.
4. Confirm an SVG with `onclick=` is rejected.
5. Confirm a clean SVG appears in the Media Library.
6. Confirm the uploaded SVG can be selected in an Image block or Site Logo flow.
7. Confirm no PHP warnings appear in WordPress admin.

### Test SVGs

Clean SVG:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
	<circle cx="50" cy="50" r="40" fill="black"/>
</svg>
```

Unsafe SVG:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
	<script>alert('test')</script>
</svg>
```

The clean SVG should upload. The unsafe SVG should be rejected.

### Known Limits

- It is not a full sanitizer.
- It does not rewrite SVG markup.
- It does not remove unsafe nodes and keep the rest of the file.
- It rejects the whole SVG when blocked markup is found.
- It does not support SVGZ files.

### When To Replace It

Replace this plugin with a full SVG sanitizer plugin when:

- clients or public users can upload SVGs;
- many team members upload unreviewed SVG files;
- a project needs SVGZ support;
- a project needs fine-grained role settings in WordPress admin;
- a compliance or security review requires a maintained sanitizer library.

### Commit Notes

If this plugin changes:

- Update the version in the plugin header.
- Update `docs/plugin-stack.md` if the version changes.
- Update this document if behavior or security assumptions change.
- Confirm `app/sql/starter.sql` still activates `llummio-svg-uploads/llummio-svg-uploads.php`.
