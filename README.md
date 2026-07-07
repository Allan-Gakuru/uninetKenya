# Uninet Kenya WooCommerce Custom Code

This repository contains the custom WordPress plugin and child theme for the Uninet Kenya WooCommerce site.

## Structure

- `wp-content/plugins/uninet-core/`: custom WooCommerce and business functionality.
- `wp-content/themes/uninet-child/`: child theme styling, layouts, and template overrides.

WordPress core, uploads, cache files, secrets, and third-party plugin folders should not be committed here.

## Deployment Flow

```text
Local code
-> GitHub
-> cPanel Git repository
-> Deploy HEAD Commit
-> /home/uninetke/public_html/wp-content/
```

The cPanel deployment file copies only:

```text
wp-content/plugins/uninet-core
wp-content/themes/uninet-child
```

into the live WordPress install.

## Plugin Architecture

`uninet-core` is organized by feature area:

```text
includes/Admin/
includes/CallToOrder/
includes/Helpers/
includes/Tracking/
includes/WooCommerce/
```

The plugin currently provides the Phase 2 foundation:

- settings page shell under `Settings -> Uninet Core`
- frontend asset loading
- Call to Order module boundaries
- WooCommerce UI module boundaries
- tracking event bridge

The full Call to Order order-creation flow is implemented in later phases.

## Theme Architecture

`uninet-child` extends Storefront and provides:

- Uninet brand color tokens
- theme CSS foundation
- theme JS entrypoint
- WooCommerce override folders for later phases
