# Ideal Architecture Roadmap

This document explains where the Llummio blueprint is today, what architecture we should move toward, and how to get there without making the system heavy too early.

## Current State

The current repository works as a reusable WordPress blueprint.

It includes:

- the `Llummio Blueprint` theme;
- internal Llummio plugins;
- the approved plugin stack;
- Local configuration;
- a clean starter database export;
- documentation for setup, editing, plugin policy, and maintenance.

This is a good foundation for creating new service-business websites quickly.

However, the current blueprint is mostly a starter copy. When a new client site is created from it, that site becomes independent. Future changes to the blueprint do not automatically reach existing client sites.

That is fine while the number of client sites is small. It becomes harder when Llummio is maintaining many websites.

If we have 80 client sites, we should not rely on manually copying new plugin files, theme changes, CSS updates, or `functions.php` changes into each site.

## Main Problem To Solve

The blueprint is currently good at starting websites.

It is not yet a professional update delivery system for many existing websites.

These are different jobs:

- The blueprint creates a clean starting point.
- Plugins deliver reusable functionality.
- A parent theme delivers reusable visual and technical foundation.
- A child theme protects client-specific design and overrides.
- A maintenance workflow safely updates existing client sites.

The long-term architecture should separate those jobs clearly.

## Target Architecture

The ideal Llummio architecture should look like this:

```text
Llummio Blueprint
Used to create new websites.

Llummio Parent Theme
Shared theme foundation updated across client sites.

Client Child Theme
Client-specific styles, functions, template overrides, and brand decisions.

Llummio Internal Plugins
Shared functionality updated across client sites.

Site-Specific Plugin
Optional. Used only when one client needs unique custom functionality.

Maintenance Dashboard
Used to monitor, back up, stage, update, and verify all client websites.
```

In this architecture, the blueprint remains important, but it is not responsible for updating every existing site.

## What The Blueprint Should Do

The blueprint should stay focused on new website creation.

It should provide:

- the current recommended WordPress structure;
- the current recommended theme and child theme setup;
- the current recommended plugin stack;
- the current starter database;
- default Llummio settings;
- clean docs for creating a new client site;
- examples that are safe to reuse.

The blueprint should not become a dumping ground for every client-specific solution.

## What The Parent Theme Should Do

The parent theme should contain the reusable Llummio foundation.

Good parent theme responsibilities:

- base theme setup;
- shared WordPress cleanup;
- global layout helpers;
- shared CSS tokens and utility classes;
- GenerateBlocks-friendly foundation styles;
- shared JavaScript loading rules;
- optional GSAP, ScrollTrigger, and SplitText loading;
- reusable template structure where appropriate;
- stable hooks and filters for child themes.

The parent theme should avoid:

- client-specific colors;
- client-specific fonts;
- one-off landing page CSS;
- client-specific animation code;
- client-specific custom post types;
- client-specific integrations.

If a change should benefit most Llummio websites, it belongs in the parent theme.

If a change only belongs to one website, it belongs in the child theme or a site-specific plugin.

## What The Child Theme Should Do

Each client website should have its own child theme.

The child theme should contain:

- brand colors;
- brand typography adjustments;
- client-specific CSS;
- client-specific template overrides;
- client-specific `theme.json` changes, if needed;
- small client-specific functions;
- page-specific animation code when it does not belong in the shared parent theme.

This protects client work when the parent theme is updated.

Without a child theme, updating the shared theme can overwrite client-specific edits. With a child theme, the shared foundation and the client layer stay separate.

## What Internal Plugins Should Do

Internal plugins should deliver shared functionality that is not purely visual.

Current examples:

- `Llummio Editor Helpers`
- `Llummio Forms`
- `Llummio SVG Uploads`

These plugins should become proper versioned products inside Llummio.

That means:

- clear version numbers;
- changelogs;
- release notes;
- compatibility notes;
- upgrade checklists;
- zipped release packages;
- a private update mechanism;
- rollback instructions.

The goal is for WordPress to know when a Llummio plugin update is available, just like it does for normal plugins.

## Private Update System

To update many sites professionally, Llummio needs a private update system.

Possible options:

- private GitHub Releases plus a WordPress updater library;
- a private plugin and theme update server;
- a commercial update service for private WordPress plugins and themes;
- Composer-based deployment for more technical hosting setups.

The simplest professional path is usually:

1. Package each internal plugin and parent theme as a versioned ZIP.
2. Host release ZIPs privately.
3. Add update-checking code so WordPress can detect available Llummio updates.
4. Use a maintenance dashboard to apply updates across sites.

This lets each site stay independent while still receiving controlled Llummio updates.

## Maintenance Dashboard

At 80 client sites, manual updates become risky and slow.

Llummio should use a central maintenance dashboard to:

- see which sites need updates;
- see which sites are outdated;
- run backups before updates;
- update staging sites first;
- update production after staging passes;
- monitor failed updates;
- monitor PHP errors or admin warnings;
- confirm plugin and theme versions across the portfolio.

Examples of this category include tools like MainWP, ManageWP, WP Umbrella, or hosting-level management dashboards.

The specific tool can be chosen later. The important decision is that updates should be managed centrally, not manually site by site.

## Staging-First Rule

Shared updates should not go directly to production across many sites.

The ideal flow:

1. Build the update locally.
2. Test it on the blueprint.
3. Release a new version of the plugin or parent theme.
4. Apply it to one internal staging site.
5. Apply it to a small number of client staging sites.
6. Check key pages, forms, admin screens, SEO output, schema, and performance.
7. Roll out to production in batches.
8. Monitor errors after release.

This workflow protects client sites from unexpected breakage.

## Versioning Rules

Llummio plugins and the parent theme should use predictable versioning.

Recommended pattern:

```text
MAJOR.MINOR.PATCH
```

Use:

- `PATCH` for small fixes that should be safe.
- `MINOR` for new features that should be backward-compatible.
- `MAJOR` for breaking changes or changes that require manual migration.

Examples:

```text
0.6.7 -> 0.6.8
Small fix.

0.6.7 -> 0.7.0
New feature.

0.6.7 -> 1.0.0
Stable release or breaking milestone.
```

Every release should explain what changed and what the team should test.

## Code Ownership Rules

A simple rule should guide every change:

If the change should be reused across many Llummio sites, put it in a shared plugin or the parent theme.

If the change belongs to one client, put it in the child theme or a site-specific plugin.

If the change is only needed to start future websites, put it in the blueprint.

This avoids mixing reusable infrastructure with client-specific work.

## Proposed Roadmap

### Phase 1: Stabilize The Current Blueprint

Goal: make the current blueprint clean and predictable before changing the architecture.

Tasks:

- Keep docs numbered and easy to follow.
- Clean starter content and starter database.
- Keep internal plugin docs updated.
- Keep `style.css`, `functions.php`, and theme JavaScript focused on reusable behavior.
- Remove test-only animation examples from shared files when no longer needed.
- Confirm the blueprint can create a new site without manual cleanup surprises.

Current Phase 1 status:

- Docs are numbered and listed in reading order from `01` to `10`.
- Starter database comments and pingbacks are closed by default.
- Starter database active plugins match the approved lean stack.
- Starter database default inactive widget blocks have been cleared.
- `style.css`, `generic.js`, and `animations.js` are focused on reusable foundation behavior.
- Test-only GSAP, ScrollTrigger, and SplitText animation examples have been removed from `animations.js`.

### Phase 2: Define Shared vs Client-Specific Boundaries

Goal: stop client-specific code from entering shared files.

Tasks:

- Document what belongs in the parent theme.
- Document what belongs in a child theme.
- Document what belongs in an internal plugin.
- Document what belongs in a site-specific plugin.
- Audit current theme and plugin code for anything that should move later.

### Phase 3: Create A Child Theme Template

Goal: make every new client site use a parent theme plus a client child theme.

Tasks:

- Create a clean child theme template.
- Include a minimal `style.css`.
- Include a minimal `functions.php`.
- Add a short child theme README.
- Document where to place client CSS, client functions, and client overrides.
- Update the new client site checklist to include activating the child theme.

### Phase 4: Treat The Current Theme As A Parent Theme

Goal: make the shared theme updateable without overwriting client work.

Tasks:

- Rename or position `Llummio Blueprint` as the shared parent theme.
- Keep reusable foundation CSS and functions in the parent theme.
- Move client-specific changes to child themes in future client builds.
- Add stable hooks and filters where child themes may need to extend behavior.
- Test that a child theme can safely inherit the parent.

### Phase 5: Prepare Internal Plugins For Releases

Goal: make internal plugins ready for controlled updates.

Tasks:

- Add consistent plugin headers.
- Maintain changelogs.
- Keep readmes current.
- Add release checklists.
- Decide what counts as patch, minor, and major changes.
- Package each plugin as a ZIP during release.
- Test each plugin update on a staging site before production.

### Phase 6: Add A Private Update Mechanism

Goal: allow WordPress to detect Llummio plugin and parent theme updates.

Tasks:

- Choose an update delivery method.
- Host private release ZIPs.
- Add update-checking code to internal plugins.
- Add update-checking code to the parent theme.
- Test updates from one version to the next.
- Confirm rollback is possible.

### Phase 7: Add Centralized Maintenance

Goal: manage many client sites without logging into each one manually.

Tasks:

- Choose a maintenance dashboard.
- Add every active client site.
- Track plugin and theme versions.
- Enable backup-before-update rules.
- Define staging-first update policy.
- Create a release checklist for batch updates.
- Create a rollback checklist.

### Phase 8: Migrate Existing Client Sites Gradually

Goal: move old sites toward the ideal architecture without risky rewrites.

Tasks:

- List all existing client sites.
- Identify which sites already match the new structure.
- Identify sites with custom code inside shared files.
- Move client-specific code into child themes or site-specific plugins when each site is next touched.
- Do not force a full migration unless there is a clear maintenance benefit.

## Recommended Next Step

The best next step is to create the child theme template.

That gives Llummio a clean boundary for all new client websites without needing to solve the private update system immediately.

After that, the parent theme and internal plugins can be prepared for proper versioned releases.

## Final Principle

The blueprint starts websites.

The parent theme and internal plugins update websites.

The child theme protects each client's custom work.

The maintenance dashboard keeps the whole portfolio manageable.
