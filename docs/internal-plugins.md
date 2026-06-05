# Internal Plugins

This document describes plugins maintained inside the Llummio blueprint.

Internal plugins should stay small, focused, and easy to remove. They are used when the blueprint needs a reusable behavior that should not live in the theme.

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
