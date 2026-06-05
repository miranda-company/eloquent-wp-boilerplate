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

## Blueprint Editing

When editing templates, template parts, or GenerateBlocks patterns in WordPress, the first save lands in the database. Use the workflow in `docs/blueprint-editing-workflow.md` to save those editor changes back into the theme files before committing.

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
