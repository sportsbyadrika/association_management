# Habitract — Membership Management System for Associations

Habitract is a production-quality PHP application for managing association
memberships: members, subscriptions/demands, receipts & expenditure, projects
with milestones, bank-account ledgers, and CSV/PDF reports — with role-based
access control and strict multi-tenant isolation.

Built with a hand-rolled MVC architecture (no heavy framework), MySQL via PDO
(prepared statements only), Tailwind CSS, Dompdf for PDFs, and PHPMailer for
password-reset email.

---

## Requirements

- PHP **8.2+** with extensions: `pdo_mysql`, `gd`, `mbstring`, `openssl`, `fileinfo`
- MySQL **8** (or MariaDB 10.4+)
- [Composer](https://getcomposer.org/)
- Node.js + npm (only to build the Tailwind CSS)

## 1. Install dependencies

```bash
composer install          # Dompdf, PHPMailer, phpdotenv
npm install               # Tailwind toolchain (dev only)
```

## 2. Configure the environment

```bash
cp .env.example .env
```

Edit `.env` and set at least:

- `APP_URL`, `APP_ENV` (`local`|`production`), `APP_DEBUG` (`false` in production)
- `APP_KEY` — generate one with `php bin/console.php key:generate`
- `DB_*` — database host, name, user, password
- `MAIL_*` — SMTP credentials (used for password-reset email; without them,
  reset links are written to `storage/logs/` so the flow still works in dev)
- `SUPER_ADMIN_*` — the super admin seeded on first run

**Never commit `.env`.**

## 3. Create the database + run migrations + seed

Create an empty database matching `DB_DATABASE`, then:

```bash
php bin/console.php migrate   # run schema migrations
php bin/console.php seed       # seed super admin + a demo association
# or, to reset everything:
php bin/console.php fresh      # drop all tables, migrate, seed
```

## 4. Build the CSS

```bash
npm run build:css     # compiles resources/css/app.css -> public/assets/css/app.css
# during development:
npm run watch:css
```

A compiled `public/assets/css/app.css` is committed so the app renders even
before you run the build.

## 5. Run locally

**PHP built-in server** (web root is `public/`):

```bash
php -S localhost:8000 -t public
```

Visit <http://localhost:8000>.

**Apache/Nginx:** point the document root at `public/`. `public/.htaccess`
routes all requests to `index.php`. For Nginx, use
`try_files $uri /index.php?$query_string;`.

---

## Default logins

After seeding:

| Role                | Email                        | Password        |
|---------------------|------------------------------|-----------------|
| Super Admin         | *(from `SUPER_ADMIN_EMAIL`)* | *(from `.env`)* |
| Association Admin   | `admin@greenvalley.example`  | `Password!123`  |
| Staff               | `staff@greenvalley.example`  | `Password!123`  |
| Member              | `member@greenvalley.example` | `Password!123`  |

> The super admin is created with **must-change-password on first login**, so
> you'll be asked to set a new password immediately after signing in.

---

## Roles

- **Super Admin** — manage associations, association admin/staff accounts, and
  subscription periods (expired/inactive associations are blocked from login).
- **Association Admin** — full control within their association: masters,
  members (with photos), demands, receipts, expenditure, projects, bank
  accounts, and reports.
- **Staff** — day-to-day data entry & viewing (members, demands, receipts,
  expenditure, projects, reports); no access to masters, bank accounts, or
  account management.
- **Member** — read-only self-service: own profile and own ledger.

## Architecture

```
public/            web root — front controller (index.php) + assets
app/
  Core/            Router, Database (PDO), View, Session, Csrf, Validator,
                   Auth, Mailer, Logger, Request/Response, base Controller/Model
  Controllers/     one per module
  Models/          data access (prepared statements only)
  Services/        MemberLedger, ImageUploader, CsvExporter, PdfReport
  Middleware/      Auth, Role, Csrf
  Views/           layouts, partials, per-module templates
  Helpers/         global template helpers (e(), url(), csrf_field(), …)
config/            config.php (reads from .env)
database/
  migrations/      ordered .sql schema files
  seeds/           super admin + demo data
routes/web.php     route definitions
storage/           logs, uploaded photos (outside web root)
bin/console.php    CLI: migrate | seed | fresh | key:generate
```

## Security

- PDO **prepared statements** everywhere — no string-concatenated SQL.
- Passwords hashed with `password_hash()` (bcrypt/argon2); transparent rehash.
- Session-based auth: session ID regenerated on login; idle + absolute
  timeouts; `HttpOnly` / `SameSite=Lax` / `Secure` (under HTTPS) cookies. Only
  the user id + role live in the session; the user is reloaded from the DB per
  request.
- **RBAC** enforced in middleware *and* controllers — never by hiding menu
  items alone.
- **Multi-tenant isolation**: every association-scoped query filters by
  `association_id`; cross-tenant reads 404 and cross-tenant writes are rejected.
- Per-session **CSRF** token required on every POST/PUT/DELETE.
- All output escaped via the `e()` helper.
- Server-side validation on every field (`Validator`).
- Uploaded images validated by content (not extension), re-encoded/resized via
  GD (strips EXIF/payloads), stored **outside** the web root, and served
  through an authorization-gated controller.
- Password reset uses a cryptographically-random, hashed, single-use,
  time-limited token, with neutral responses to prevent account enumeration.
- Login + reset throttling per email and per IP.
- Security headers: CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options`,
  `Referrer-Policy`, `Permissions-Policy`.
- Financial amounts stored as `DECIMAL(12,2)`; multi-step writes wrapped in
  transactions. `ON DELETE` rules preserve financial history (restrict /
  soft-delete rather than cascade).

## Reports

Members directory, member ledger, income (by head / by project), and
expenditure (by category / project / head) — each available as **CSV**
(`fputcsv`) and **PDF** (Dompdf, with a common header showing the association
logo + name, title, date range, and generated-on timestamp). Income and
expenditure reports support a date-range filter.

---

© SportsByA Tech (OPC) Private Limited
