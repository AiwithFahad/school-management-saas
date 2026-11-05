# School Management SaaS (EduPro)

Production-oriented, multi-tenant School Management SaaS built exactly to the specified stack:
**HTML5, CSS3, Bootstrap 5, JavaScript (AJAX/Fetch), Core PHP 8+ (no Laravel/React/Vue/Node), MySQL via PDO, Chart.js, Bootstrap Icons.**

The secure multi-tenant foundation (auth, subscriptions, Super Admin) is reused from the CRM / Project Management SaaS products and adapted for schools — here a **tenant = a school**.

## Features
- **Auth & roles:** register (creates a school tenant + School Admin + 14-day trial + a current academic session), login/logout, remember-me (hashed token cookie), forgot/reset password (hashed, expiring tokens), suspended account & tenant checks, `password_hash`, secure sessions, CSRF on every POST, XSS escaping via `e()`. Roles: **School Admin, Principal, Teacher, Accountant, Student, Parent** — enforced with `require_role()` checks.
- **Tenant isolation:** every query is scoped by `tenant_id` from the session.
- **Dashboard:** total students / teachers / classes / employees, today's attendance %, monthly fee collection, pending fees, recent admissions, Chart.js fee analytics, activity feed.
- **Students:** full CRUD — admission no, profile, DOB, class/section assignment, category, guardian info, status (active/transferred/graduated/suspended), search + class/status filters, pagination, CSV export, **plan student limit enforced**.
- **Teachers:** CRUD — staff ID, department, contact, salary, status, and subject assignment (written to `class_subjects`); **plan teacher limit enforced**.
- **Classes & Subjects:** classes CRUD with class-teacher assignment and student counts, sections CRUD, subjects CRUD, academic sessions (set current).
- **Attendance:** bulk daily marking per class + date (present/absent/late radio per student, upserted), recent-days summary by class with % present.
- **Fees:** fee structures CRUD, **invoice generation for a class** (one `fee_invoices` row per active student), payment recording (updates invoice paid/status + writes a `payments` row), pending/partial/paid filters, printable receipt view.
- **Exams & Results:** exams CRUD, per-student/per-subject marks entry grid, and a **report card** computing total, percentage, grade (A+–F) and rank in class.
- **Timetable:** period entries CRUD rendered as a weekly (Mon–Sat × period) grid per class.
- **Library:** books CRUD with copy counts, issue/return — return computes a **late fine ($0.50/day)** and restores availability.
- **Transport:** vehicles/drivers CRUD, routes CRUD, student transport assignment.
- **Homework:** teachers post homework by class/subject with due dates; students/parents can view it.
- **Portals:** `student_portal.php` (own attendance %, timetable, homework, results, fees — driven by the student record linked to the login via `students.user_id`, with an admin preview picker) and `parent_portal.php` (children from `parent_student` links, each child's attendance/fees/results/homework).
- **Reports:** attendance by class, fee collection by status (chart), average marks by subject, students by class, date filter, CSV export.
- **Subscriptions:** Free Trial / Basic / Standard / Premium, monthly & yearly, upgrade/downgrade writes subscription + payment + invoice in a transaction, coupon check + application (`WELCOME10`), invoices list, student/teacher/storage limits shown per plan.
- **Super Admin (`super-admin/`):** platform dashboard, schools (tenants) with suspend/reactivate, plans CRUD (users/students/teachers limits), subscriptions + payments, coupons CRUD, support tickets, announcements, platform settings, audit view.
- **Audit & activity logs** on key actions.

## Installation
1. Requirements: PHP 8.1+, MySQL 8+ (or MariaDB 10.4+), a web server (Apache/Nginx) or PHP built-in server.
2. Create the database and import the schema + demo data:
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates database `school_saas` (37 tables) with foreign keys, indexes and demo seed data.
3. Configure the DB connection via environment variables (or edit `config/config.php` defaults):
   `DB_HOST`, `DB_NAME` (default `school_saas`), `DB_USER`, `DB_PASS`.
4. Serve the folder:
   ```bash
   php -S localhost:8000 -t .
   ```
5. Open `http://localhost:8000/auth/login.php`.

## Demo logins (password for all: `password`)
| Role | Email | Lands on |
|---|---|---|
| Super Admin | `super@edupro.test` | `super-admin/index.php` |
| School Admin (Demo School) | `admin@demo.com` | `dashboard.php` |
| Principal | `principal@demo.com` | `dashboard.php` |
| Teacher | `teacher@demo.com` | `dashboard.php` |
| Accountant | `accountant@demo.com` | `dashboard.php` |
| Student | `student@demo.com` | portal-linked record (Ali Raza) |
| Parent | `parent@demo.com` | parent portal (Ali + Fatima Raza) |

Demo coupon: `WELCOME10` (10% off). Demo data: 3 classes, 4 sections, 7 subjects, 10 students, 4 teachers, attendance for today/yesterday, fee structures + 6 invoices (paid/partial/unpaid), a Mid-Term exam with marks for 5 students × 3 subjects, 5 library books (1 issued), 1 vehicle/route with 2 transport assignments, 2 homework items.

## Project structure
```
config/         DB + app config (PDO, env-based)
includes/       functions (CSRF, escaping, audit, tenant helpers, plan limits, grade helper), auth guards, header/footer/sidebar
auth/           login, register, logout, forgot/reset password
super-admin/    platform dashboard, schools, plans, subscriptions, coupons, tickets, announcements, settings
assets/         CSS + JS (theme, charts)
database.sql    complete schema (37 tables) + demo seed data
vercel-demo/    static in-browser demo (see below)
dashboard.php students.php teachers.php classes.php attendance.php fees.php
exams.php timetable.php library.php transport.php homework.php
student_portal.php parent_portal.php reports.php subscription.php settings.php
```

## Deployment notes
- **PHP/MySQL hosting (production app):** deploy to any PHP 8 + MySQL host / VPS (Apache/Nginx + PHP-FPM). Set env vars, import `database.sql`, point the web root at this folder, enable HTTPS (session cookie Secure flag activates automatically), and disable display_errors in production.
- **Payment integration:** subscription and fee payments are recorded in DB with a gateway-ready architecture — plug Stripe/PayPal into `subscription.php` / the fee payment flow in `fees.php` where payment rows are created, and mark payments `paid` only after gateway confirmation/webhook.
- **Vercel:** Vercel cannot run Core PHP/MySQL, so `vercel-demo/index.html` is a single self-contained static demo (Bootstrap 5 + Chart.js via CDN, data in localStorage) that mirrors EduPro — dashboard/stats/charts, students CRUD, teachers, attendance marking, fees with Pay action, exam report cards with rank/grade, library issue/return, and plans. Deploy that folder to Vercel for a live demo link; keep the full PHP app on PHP hosting / GitHub.

## Security notes
PDO prepared statements everywhere, `password_hash`/`password_verify`, CSRF tokens, output escaping, secure + HttpOnly + SameSite session cookies, tenant-scoped queries, audit logging. Change demo passwords immediately in production and remove/rotate seeded accounts.
