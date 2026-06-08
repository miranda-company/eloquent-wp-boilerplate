# Editing Model

Llummio websites should be easy for clients to update without making the site fragile.

## Principle

Clients should edit content, not rebuild the design system.

The editing experience should expose the parts of the site that change regularly while protecting the structure, spacing, typography, and responsive behavior that make the site feel professional.

## Recommended Editing Levels

### Safe For Clients

- Page copy.
- Images.
- Blog posts.
- Services.
- FAQs.
- Testimonials.
- Team members.
- Contact details.
- Form notification recipients when appropriate.

### Llummio Managed

- Global styles.
- Theme templates.
- Header and footer structure.
- Custom fields.
- Custom post type setup.
- Plugin configuration.
- Performance settings.
- SEO and redirect settings.

### Avoid Exposing Casually

- Raw custom CSS.
- Theme file editing.
- Plugin installation.
- Global layout settings.
- Database tools.
- Optimization plugin advanced settings.

## Blocks And Patterns

Use blocks and patterns to create a consistent editing system.

Good patterns are:

- Reusable.
- Clearly named.
- Responsive by default.
- Built from the approved design system.
- Hard to break during normal editing.

Avoid creating too many near-identical patterns. A smaller pattern library is easier to maintain and easier for clients to understand.

## Custom Fields

Use custom fields when content has a predictable structure.

Good use cases:

- Service details.
- Case study metadata.
- Team member profiles.
- Testimonials.
- Event dates.
- Location information.

Avoid custom fields when a normal page block would be simpler and more flexible.

## Client Handoff Standard

Each client should understand:

- Where to edit the content they own.
- Which parts are managed by Llummio.
- How to request structural changes.
- How to avoid breaking layouts.

The goal is not to teach the client all of WordPress. The goal is to help them confidently maintain their own content.
