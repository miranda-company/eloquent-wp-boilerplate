# Plugin Policy

Llummio websites should use a small, intentional plugin stack. Every plugin should earn its place.

## Principle

Use plugins for stable, reusable functionality. Avoid plugins for small visual tweaks, temporary shortcuts, or features that can be handled cleanly in the theme.

## Approved Plugin Stack

The current approved stack is listed in `docs/plugin-stack.md`.

When plugin versions change, update that file.

## Adding A Plugin

Before adding a plugin, confirm:

- The client actually needs the feature.
- The plugin is actively maintained.
- The plugin works with the current WordPress version.
- The plugin has a clear owner or reputable vendor.
- The plugin does not duplicate existing functionality.
- The plugin does not add excessive frontend weight.
- The plugin can be removed later without trapping critical content.

## Premium Plugins

Premium plugin code may be included in this private internal blueprint when it is part of Llummio's standard workflow.

Do not commit:

- License keys.
- Account credentials.
- Update tokens.
- Client-specific license data.

Activate licenses inside WordPress admin per client site.

## Custom Functionality

Use theme code for presentation and template behavior.

Use a custom plugin or mu-plugin for functionality that should survive a theme change, such as:

- Custom post types.
- Custom taxonomies.
- Shortcodes.
- Integrations.
- Data transformations.
- Site-specific behavior.

Avoid putting business-critical functionality directly into the theme unless it is clearly presentation-only.

## Removing Plugins

Before removing a plugin:

- Check whether it stores content.
- Check whether pages depend on its blocks or shortcodes.
- Check whether templates depend on it.
- Check whether forms, redirects, SEO, or performance settings depend on it.
- Export or migrate data when needed.

Plugin removal should leave the site cleaner, not broken.
