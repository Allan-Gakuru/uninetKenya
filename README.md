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
includes/Contact/
includes/Helpers/
includes/Quote/
includes/Setup/
includes/Tracking/
includes/WooCommerce/
```

The plugin foundation includes:

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

The Phase 4 sitewide experience now:

- loads Poppins from Google Fonts with preconnect hints and heavier approved type weights
- replaces Storefront's generic footer with code-owned category, trust, privacy, contact, WhatsApp, and social navigation
- stores public contact submissions as private `Contact Messages` in the WordPress dashboard
- uses nonce validation, a honeypot, field limits, and a one-minute browser/IP fingerprint throttle to reduce spam and duplicate submissions
- preserves complete enquiry answers in WordPress while sending only a bounded summary to WhatsApp
- creates missing Contact and Privacy Policy pages on the first administrator request after deployment
- preserves existing Contact page copy and only updates the original generated privacy wording when it can identify that exact legacy version

## Phase 5 Conversion and Procurement System

The Build a Quote procurement workflow now:

- creates and restores the managed `/build-a-quote/` page and adds tracked entry points in the header, footer, product pages, and product archives
- carries a selected product from its product page into the quote automatically without duplicating an existing saved line
- searches current published WooCommerce products by product name, model, SKU, or top-level category, so future catalogue products appear without code changes
- supports multiple catalogue products, quantities, and per-line procurement notes
- shows indicative pre-tax prices and totals while clearly separating products that require staff pricing
- recalculates every price from WooCommerce on the server before storing the request
- stores each submission privately under `Quote Requests` with buyer, KRA, delivery, product, price, source, and workflow details
- gives staff a filterable status queue, private follow-up note, and workflow-update timestamp
- never creates a WooCommerce order, reserves stock, or reduces inventory
- offers a bounded WhatsApp handoff only after the dashboard record is saved
- protects submissions with a nonce, honeypot, field and line limits, catalogue validation, and a one-minute duplicate throttle
- tracks quote entry, prefill, catalogue search, product addition, successful submission, and bounded failure events without sending form answers to analytics

The wider Phase 5 conversion layer also tracks Call to Order failures and successful Contact form views. These events use the defensive `window.uninetTrack` bridge and remain harmless when Google Analytics is unavailable.

## Theme Architecture

`uninet-child` extends Storefront and provides:

- Uninet brand color tokens
- theme CSS foundation
- theme JS entrypoint
- WooCommerce override folders for later phases

## Phase 4 Post-Deployment Checklist

1. In cPanel, run **Update from Remote** and **Deploy HEAD Commit**.
2. Visit `wp-admin` once as an administrator. This runs the managed-page and legacy social-URL migrations.
3. Confirm that `/contact-us/` and `/privacy-policy/` load and that the footer links reach the intended pages and category archives.
4. Review the Privacy Policy before launch, especially after enabling or changing Site Kit, analytics, cookie, backup, search, security, email, or marketing services.
5. Submit one clearly labelled test contact message. Confirm it appears under `Contact Messages`, verify the WhatsApp summary, then delete the test record.
6. Test the footer, contact form, Poppins loading, focus states, and navigation at mobile and desktop widths.
7. Clear any page, object, CDN, or browser cache if the theme version shown is older than `0.6.6` or the plugin version is older than `0.5.0`.

## Phase 5 Post-Deployment Checklist

1. In cPanel, run **Update from Remote** and **Deploy HEAD Commit**, then visit `wp-admin` once as an administrator so WordPress creates or restores the managed pages.
2. Open a product page and use **Add to multi-product quote**. Confirm `/build-a-quote/` opens with that product already present once.
3. Open a product archive and confirm the multi-product procurement entry point reaches the quote builder. Test the header and footer links as well.
4. Search by product name, model, SKU, and category. Confirm newly published WooCommerce products appear without code changes.
5. Submit one clearly labelled synthetic request containing a priced and, when available, an unpriced product. Check the desktop review dialog and mobile review bar.
6. Confirm the record appears under `Quote Requests`, the pre-tax totals match current WooCommerce prices, and staff can filter by status, change status, and save an internal note.
7. Confirm the submission did not create a WooCommerce order, reserve inventory, or change product stock.
8. Review the generated WhatsApp summary and test Call to Order and Contact once with synthetic data.
9. In Site Kit/Analytics debug tools, verify the quote entry, prefill, search, product-add, submission, Contact submission, and Call to Order events. Do not expect form answers in event parameters.
10. Delete all synthetic records and clear page, object, CDN, and browser caches if the new page or assets do not appear after deployment.

## Contact Data Operations

- Contact messages are private WordPress records, but administrators and other roles with the relevant post capabilities may be able to view them.
- Do not place passwords, payment credentials, identity documents, medical information, or other unnecessary sensitive data in test submissions.
- Review Contact Messages and Quote Requests at least quarterly. Delete or anonymize records when the enquiry is closed and the details are no longer reasonably needed for follow-up, support, warranty, security, dispute, or legal purposes.
- Do not automatically delete records in code. A quote or enquiry that becomes an order, invoice, contract, or tax record must move into the applicable accounting and legal retention process before the original lead record is removed.
- Kenyan data-protection rules require a retention schedule, periodic review, and deletion, erasure, anonymization, or pseudonymization after the purpose ends. KRA tax records are generally retained for five years; confirm the final business schedule with the organisation's accountant or legal adviser.
- Reference material: [ODPC Data Protection (General) Regulations, 2021](https://www.odpc.go.ke/wp-content/uploads/2024/03/THE-DATA-PROTECTION-GENERAL-REGULATIONS-2021-1.pdf) and [KRA Tax Procedures Act](https://kra.go.ke/images/publications/TPA-2015-Revised-2021-1.pdf).
- WhatsApp is optional and customer-initiated. A website submission is stored before the WhatsApp link is offered, and WhatsApp never sends automatically.
- Poppins is currently delivered by Google Fonts. Google Site Kit and any enabled analytics/search integrations must be reflected accurately in the live privacy and cookie configuration.
- Quote requests contain procurement and business identity details. Staff should apply the same approved retention and access rules used for contact and order records.
