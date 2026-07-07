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

The Phase 3 Call to Order flow now:

- renders a product-page Call to Order button
- collects buyer and business invoice details in a modal form
- creates WooCommerce `Pending payment` orders
- stores readable order metadata and an internal staff note
- leaves stock untouched until staff confirms the order
- replaces product-card Add to Cart buttons with `View Details`
- keeps public buyers out of cart and checkout pages during phase one

The product/category UX foundation now:

- uses visible WooCommerce attributes or short descriptions for product-card spec lines
- adds product-card availability language and a `View Details` action
- adds product archive search with FiboSearch support when available
- adds top-level product category navigation on shop/category pages
- adds category-specific buying guidance for core Uninet categories
- adds product-page business buyer sections for specifications, warranty, delivery, invoice, payment, and availability notes

The homepage foundation now:

- adds a selectable `Uninet Homepage` page template in the child theme
- organizes the homepage around business use cases, featured products, category entry points, procurement process, and trust details
- uses real WooCommerce products/categories where available, with safe fallbacks to the shop page

## Theme Architecture

`uninet-child` extends Storefront and provides:

- Uninet brand color tokens
- theme CSS foundation
- theme JS entrypoint
- WooCommerce override folders for later phases
