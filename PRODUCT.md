# Product

## Register

brand

## Users

Business owners, procurement leads, office administrators, and technical decision-makers in Kenya who need reliable business technology without a consumer-style checkout experience. They are usually buying for a company, comparing practical specifications, checking trust signals, and expecting staff to confirm fit, availability, delivery, tax, and invoicing details before payment.

## Product Purpose

Uninet Kenya is a WooCommerce-powered B2B technology retail webstore for laptops, desktops, monitors, CCTV and security, networking equipment, printers and office equipment, accessories, and selected product bundles. The site exists to present products professionally, support SEO-friendly product pages, and convert product interest into staff-followed pending WooCommerce orders through a Call to Order flow.

Success means the site feels credible from day one, works cleanly on mobile and desktop, avoids cart-first consumer patterns, and gives staff clear order metadata for follow-up.

## Brand Personality

Professional, practical, and high-trust. The tone should feel like an experienced IT solutions provider helping a business make the right procurement decision, not a flashy gadget shop pushing impulse buys.

## Contact Details

- Sales phone: 0770 313 200
- Facebook: https://www.facebook.com/UniNietTechnologies

## Anti-references

Do not make an exact clone of shidirect.com. Use it only as structural and stock-UX inspiration.

Avoid flashy consumer electronics styling, oversized decorative landing-page sections, generic card-heavy marketing layouts, cart-first marketplace behavior, dark sci-fi technology aesthetics, and vague "innovation" copy that does not help a buyer choose the right product.

## Design Principles

Procurement confidence over impulse shopping.

Product information before order pressure.

Human confirmation is part of the conversion promise.

Mobile-first browsing with a professional desktop experience.

SEO, product structure, and business metadata are foundational, not later cleanup.

## Accessibility & Inclusion

Aim for WCAG 2.2 AA fundamentals: strong contrast, readable type, keyboard-friendly controls, visible focus states, clear form labels and errors, reduced-motion support, and mobile layouts that do not hide required information. Forms should be understandable for Kenyan business buyers, including county, town, delivery or pickup location, business name, conditional email requirement, and optional KRA PIN fields.

## Phase 4 Sitewide Experience

- Poppins is the default typeface. Normal copy begins at weight 500, with interface and heading weights moved up one supported Poppins weight step.
- The footer provides product-category navigation, buying help, warranty, delivery, invoicing, contact, WhatsApp, privacy, and social links.
- Contact-page submissions are stored privately as Contact Messages in the WordPress dashboard.
- The contact form asks for the buyer's biggest challenge, what they have already tried, and why now is the right time to address it.
- After a successful dashboard submission, the customer can review and send a bounded WhatsApp summary to +254770313200. WhatsApp never sends automatically, and complete answers remain in WordPress.
- Contact submissions use nonce validation, a honeypot, maximum field lengths, and a one-minute browser/IP fingerprint throttle to limit accidental duplicates and basic spam without storing the raw IP address.
- Poppins is delivered through Google Fonts in phase one. The privacy notice must remain aligned with Google Site Kit and any later analytics, security, search, backup, cookie, email, or marketing integrations.

## Operational Guardrails

- The coded footer is the source of truth; adding Storefront footer widgets will not make them visible unless the footer implementation is deliberately changed.
- Existing Contact page copy is preserved, and the contact form is appended when its shortcode is absent.
- Managed page creation runs on an administrator request after deployment. Deploying files alone does not guarantee that missing database pages have been created.
- Contact records require a deliberate retention policy before public launch. Until that policy is approved, staff should periodically remove enquiries that no longer have an operational, accounting, warranty, support, security, or legal purpose.
- The Privacy Policy is a technical starter notice, not a substitute for legal review. Recheck it whenever data collection or third-party services change.
- Staff must test with clearly synthetic contact details and delete test records afterward.
