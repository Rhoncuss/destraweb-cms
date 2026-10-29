# Changelog

All notable changes to DESTRA CMS are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/) and the
project uses [Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-10-29

First public release, codename **Aurora**.

### Added

**Platform**
- CodeIgniter 4.7 application with a guided web installer (requirements, database, site, admin)
- Multi-site-ready settings store, file cache with automatic flushing, maintenance mode
- Audit log, notifications centre, dashboard search and admin calendar
- REST API v1 with scoped, hashed tokens and an OpenAPI 3.0 document

**Content**
- Posts, pages and nested documentation with sections, categories, tags and custom fields
- Multilingual content (English, Indonesian, Arabic seeded) with locale-prefixed URLs
- TinyMCE, CKEditor 5 and Summernote editors (switchable in Settings)
- Scheduling, expiry, sticky posts, revisions-friendly workflow, trash and restore
- Bulk actions, bulk upload, attachments, featured images, reading time and view counter
- Comments with threading, honeypot, captcha, rate limits and moderation queue
- Forms builder with inbox, orders / quotes flow, newsletter with double opt-in

**Appearance**
- Theme system (manifest, screenshots, live preview) with Modern, Classic and Velocity
- Three frontend layouts sharing a fallback view tree; dark mode and RTL support
- AdminLTE 4 admin with four color schemes, command palette and mobile app-style navigation
- Menu builder, banners, popups, ad slots, home slider, preloader toggle
- Custom CSS and custom head/footer code, protected by CSP and purification

**Security**
- Argon2id password hashing with transparent rehashing
- AES-256-GCM envelope encryption for OAuth, SMTP and plugin secrets
- Session-bound CSRF tokens in every form and automatic AJAX header injection
- Global input guard: traversal/null-byte blocking, sensitive-path blocking, recursive sanitisation
- HTMLPurifier for rich text; strict output escaping; nonce-based CSP
- MIME-sniffed uploads, WebP re-encoding, thumbnails, isolated upload directory
- Login throttling, session fingerprinting, audit trail, granular RBAC
- Branded error pages for 400/403/404/413 and permission denials

**SEO & integrations**
- Per-content SEO fields, canonical URLs, Open Graph, JSON-LD, hreflang
- XML sitemaps, robots.txt, RSS/Atom feeds, redirect manager with hit counts
- Google Analytics/GTM, Google Maps, Mailchimp, Mailcoach, SMTP mailer
- reCAPTCHA v3, hCaptcha and Cloudflare Turnstile adapters
- OAuth sign-in for Google, Facebook, GitHub, LinkedIn and X (configurable in admin)

**Packaging**
- Bundled vendor libraries — no CDN and no Composer/Node.js required on the server
- XAMPP and cPanel installation guides, theme/plugin/API/security documentation
- Root `.htaccess` fallback for shared hosting and a packaging script for release zips

### Notes
- PHP 8.2+ is required (8.3 recommended).
- The installer creates the first Super Admin; there are no default credentials.
- Changing `destra.encryptionKey` after secrets are stored makes them unreadable.

---

Version history starts at 1.0.0. Future releases will list Added / Changed /
Fixed / Security entries here.
