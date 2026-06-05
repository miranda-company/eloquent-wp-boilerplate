# Internal Plugins

This document describes plugins maintained inside the Llummio blueprint.

Internal plugins should stay small, focused, and easy to remove. They are used when the blueprint needs a reusable behavior that should not live in the theme.

## Llummio Editor Helpers

Folder: `app/public/wp-content/plugins/llummio-editor-helpers/`

Main file: `app/public/wp-content/plugins/llummio-editor-helpers/llummio-editor-helpers.php`

Version: `0.1.0`

### Purpose

`Llummio Editor Helpers` collects small editing tools used in Llummio blueprint sites.

It currently includes lightweight SEO fields and an editor-only wireframe preview toggle.

The SEO tools replace the small part of RankMath that Llummio normally uses on simple sites: custom SEO titles and custom SEO descriptions. They do not try to replace a full SEO suite.

### What It Does

- Adds a `Llummio Editor Helpers` icon to the block editor toolbar for public editable content types.
- Opens a `Llummio Editor Helpers` sidebar panel in the editor.
- Stores a custom SEO title.
- Stores a custom SEO description.
- Overrides the frontend document title on singular content when an SEO title exists.
- Outputs one `<meta name="description">` tag on singular content when an SEO description exists.
- Registers the fields with the WordPress REST API for future editor integrations.
- Adds an editor-only wireframe toggle for the theme `.wire` helper class.

### What It Does Not Do

- XML sitemaps.
- Schema markup.
- Open Graph or social images.
- Redirects.
- Breadcrumbs.
- Robots meta controls.
- Canonical URL controls.
- Keyword analysis.
- Search Console integrations.

If a project needs those features, use a full SEO plugin such as RankMath.

### Fields

The plugin stores data in post meta:

- `_llummio_seo_title`
- `_llummio_seo_description`

Empty fields are deleted from post meta instead of saved as empty strings.

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

The fields can still exist in the editor, but frontend output is skipped to avoid duplicate SEO tags.

To force output anyway:

```php
add_filter( 'llummio_editor_helpers_skip_frontend_output', '__return_false' );
```

### WordPress APIs Used

The plugin depends on these WordPress APIs:

- `register_post_meta`
- `enqueue_block_editor_assets`
- `wp.plugins.registerPlugin`
- `wp.editor.PluginSidebar`
- `save_post`
- `pre_get_document_title`
- `wp_head`
- `add_theme_support( 'title-tag' )`

These are stable WordPress extension points. Still, check the plugin when the blueprint target WordPress version changes.

### Upgrade Checklist

When upgrading the blueprint to a new major WordPress version:

1. Confirm the `Llummio Editor Helpers` sidebar opens on pages.
2. Confirm the `Llummio Editor Helpers` icon appears in the top-right block editor toolbar.
3. Open the sidebar and save an SEO title and SEO description.
4. Confirm both values stay saved after reload.
5. Confirm the frontend `<title>` uses the SEO title.
6. Confirm the frontend has one meta description tag.
7. Clear both fields and confirm the plugin stops outputting custom SEO data.
8. Confirm the wireframe checkbox turns `.wire` borders on and off in the editor.
9. Confirm no duplicate title/description output appears if RankMath is active.
10. Confirm no PHP warnings appear in WordPress admin.

### When To Replace It

Replace this plugin with RankMath or another full SEO plugin when a project needs:

- sitemap control;
- schema markup;
- Open Graph/social metadata;
- redirect management;
- robots/canonical controls;
- SEO scoring or content analysis;
- integrations with external SEO tools.

### Commit Notes

If this plugin changes:

- Update the version in the plugin header.
- Update `docs/plugin-stack.md` if the version changes.
- Update this document if behavior or compatibility assumptions change.
- Confirm `app/sql/starter.sql` still activates `llummio-editor-helpers/llummio-editor-helpers.php`.

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
