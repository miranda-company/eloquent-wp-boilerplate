# Project Structure

This repository is the reusable Llummio WordPress blueprint. It is not a finished client website. It exists to provide a clean starting point for service-business websites.

## Purpose

The blueprint should provide:

- A lean WordPress foundation.
- A controlled editing experience.
- A reusable visual system.
- A standard plugin stack.
- A clean starter database.
- Clear setup and maintenance rules.

Each client project should start from this foundation, then receive its own brand, content, media, integrations, and production configuration.

## Repository Layout

| Path | Purpose |
| --- | --- |
| `app/public/wp-content/themes/llummio-blueprint/` | Llummio block theme foundation. |
| `app/public/wp-content/plugins/` | Approved internal plugin stack. |
| `app/sql/starter.sql` | Canonical clean starter database export. |
| `conf/` | Local app server configuration. |
| `docs/` | Internal setup, workflow, policy, and maintenance documentation. |
| `logs/` | Local runtime logs. Ignored by Git. |

## Theme Conventions

The theme stylesheet should stay small and readable. Keep reusable tokens, base resets, layout helpers, navigation adjustments, and page-building utilities in `style.css`. Avoid moving one-off client styling into the reusable blueprint unless it will be useful across future service-business sites.

Theme JavaScript is split by purpose:

- `assets/js/generic.js` loads across the site for small behavior that does not require GSAP.
- `assets/js/animations.js` loads only on pages that opt in to GSAP from the Llummio Editor Helpers sidebar. Keep it free of test-only animation examples; add only reusable animation patterns or project code that has been intentionally promoted back into the shared blueprint.
- `assets/vendor/gsap/ScrollTrigger.min.js` is loaded only on pages that opt in to ScrollTrigger from the same sidebar.
- `assets/vendor/gsap/SplitText.min.js` is loaded only on pages that opt in to SplitText from the same sidebar.

Create extra page-specific JavaScript files only when a page has a large or unusual interaction that would make `animations.js` harder to maintain.

## Blueprint Editing

When editing templates, template parts, or GenerateBlocks patterns in WordPress, the first save lands in the database. Use the workflow in `docs/05-blueprint-editing-workflow.md` to save those editor changes back into the theme files before committing.

## What Belongs In Git

- Theme files.
- Approved plugin code used by the blueprint.
- Documentation.
- Local configuration templates.
- One clean starter database export.
- Small reusable assets required by the theme.

## What Does Not Belong In Git

- WordPress core files.
- Uploads and generated media.
- Cache folders.
- Logs.
- Local-only database exports.
- Plugin license keys.
- Production credentials.
- WordPress session tokens.
- Client-private content unless there is a specific project reason.

## Core Rule

Code and reusable structure belong in Git. Runtime state, private credentials, generated files, and client-specific content usually do not.
