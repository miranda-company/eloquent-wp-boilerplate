== Llummio Forms ==

Lightweight secure lead forms for trusted Llummio blueprint sites.

Use the `[llummio_form id="default"]` shortcode to render a form. Create and edit forms from the `Llummio Forms` admin menu. Configure fields, editable labels, optional legal consent, editable Spanish error messages, desktop field widths, confirmation behavior, email notifications, reCAPTCHA, terms/privacy URLs, and basic colors per form.

Legal consent text supports `{terms}` and `{privacy}` tokens so each form can choose exactly where those links appear.

The dashboard shows submission counts by form, but submissions are not stored in the database.

This plugin intentionally uses WordPress' normal email system through `wp_mail()`. Configure SMTP at the server level or with a dedicated SMTP plugin when a project needs authenticated mail delivery.

See `docs/internal-plugins.md` for maintenance notes and upgrade testing.
