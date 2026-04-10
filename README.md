# CV Builder Pro — Project Structure

```
cv-builder-pro/
│
├── includes/                  # Core PHP — included everywhere
│   ├── bootstrap.php          # Single require at top of every page
│   ├── config.php             # App constants (DB, paths, branding)
│   ├── Database.php           # PDO singleton + query helpers
│   └── helpers.php            # Session, CSRF, auth, flash, sanitize
│
├── auth/                      # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── forgot-password.php
│
├── pages/                     # Main app pages (auth required)
│   ├── dashboard.php          # CV list + stats
│   ├── builder.php            # Multi-step CV builder
│   ├── preview.php            # Full CV preview
│   └── settings.php          # Account settings
│
├── api/                       # AJAX endpoints (return JSON)
│   ├── cv/
│   │   ├── create.php
│   │   ├── save.php
│   │   ├── delete.php
│   │   └── get.php
│   ├── personal/save.php
│   ├── experience/
│   │   ├── save.php
│   │   ├── delete.php
│   │   └── reorder.php
│   ├── education/
│   │   ├── save.php
│   │   └── delete.php
│   ├── skills/
│   │   ├── save.php
│   │   └── delete.php
│   ├── languages/
│   │   ├── save.php
│   │   └── delete.php
│   ├── certificates/
│   │   ├── save.php
│   │   └── delete.php
│   ├── upload/photo.php
│   └── export/
│       ├── pdf.php
│       └── docx.php
│
├── admin/                     # Admin panel (admin role required)
│   ├── index.php              # Dashboard stats
│   ├── users.php              # User management
│   └── exports.php            # Export log
│
├── templates/                 # CV HTML templates for preview + PDF
│   ├── classic.php
│   ├── modern.php
│   └── minimal.php
│
├── assets/
│   ├── css/
│   │   ├── app.css            # SC-branded global styles + animations
│   │   └── templates.css      # CV template print styles
│   ├── js/
│   │   ├── app.js             # Global JS (CSRF header, toasts, theme)
│   │   ├── builder.js         # Step navigation, auto-save, preview sync
│   │   ├── sortable.js        # Drag-and-drop via Sortable.js
│   │   └── cropper.js         # Photo crop logic
│   └── img/
│       └── logo.svg           # SC logo
│
├── uploads/
│   └── photos/                # User-uploaded CV photos (gitignored)
│
├── exports/                   # Generated PDF/DOCX files (gitignored)
│
├── schema.sql                 # Full DB schema — run once on server
├── .htaccess                  # URL rewriting + security headers
└── index.php                  # Redirects to login or dashboard
```

## Setup Instructions

1. Create MySQL database: `cv_builder_pro`
2. Run `schema.sql` to create all tables
3. Edit `includes/config.php` — set `DB_USER`, `DB_PASS`, `APP_URL`
4. Upload all files to `public_html/cv-builder-pro/` on your host
5. Make `uploads/` and `exports/` writable: `chmod 755`
6. Visit `https://abubakr.rf.gd/cv-builder-pro/` and log in with:
   - Email: `admin@abubakr.rf.gd`
   - Password: `Admin@1234` **(change immediately after first login)**

## Phase Progress
- [x] Phase 1 — Foundation (schema, config, DB, helpers)
- [ ] Phase 2 — Auth + UI shell + Dashboard
- [ ] Phase 3 — CV Builder (all steps + AJAX)
- [ ] Phase 4 — Preview + Export + Arabic RTL
- [ ] Phase 5 — Admin + Animations + Security + Deploy
