# uni wise: progress report

_Last updated: 14 November 2026_

A quick catch-up for anyone joining the project: what's built, what's only a placeholder, and what to do next. For the full setup steps see [README.md](README.md).

---

## TL;DR

- **Works now:** the full front end (19 pages, light and dark mode, 6 languages), real user accounts (sign up, log in, settings, profile pictures, delete account) and a MySQL database.
- **Waiting on data:** the universities, profile and compare pages are fully coded but the database has **no universities yet**, so they show "coming soon".
- **Placeholders:** the 10 personal tools (deadlines, essays, budget…) have pages but no features yet.
- **Needs you first:** setting up **Sign in with Google** (steps below).

---

## 1. Run it on your machine (≈5 min)

1. Install [XAMPP](https://www.apachefriends.org). In the XAMPP Control Panel, start **Apache** and **MySQL**.
2. Clone this repo into `C:\xampp\htdocs\`. The folder name becomes the URL, so `uniwise` gives you `http://localhost/uniwise/`.
3. Open **http://localhost/phpmyadmin** → **Import** and import, in order:
   1. `database/schema.sql` (creates the `uniwise` database and all its tables)
   2. `database/seed.sql` (9 regions and 12 application portals)
4. Copy `config/config.example.php` to `config/config.php`. With XAMPP defaults you don't need to change anything. This file is git-ignored, so put your secrets here.
5. Open `http://localhost/<folder-name>/` in your browser.

---

## 2. What's done ✅

| Area | Status |
|---|---|
| **Design** | Purple colour scheme, works on phone/tablet/desktop, light + dark mode (light is the default), decorative SVG backgrounds, logo as the browser tab icon |
| **Languages** | English, Español, Français, Deutsch, 中文, Bahasa Indonesia, switchable from the footer or Settings. 388 strings, none missing. Translations are machine-quality, so get a native speaker to check them |
| **Accounts** | Sign up, log in, log out, hashed passwords, CSRF protection on every form, log-in lockout after 5 failed attempts, safe redirects |
| **Account page** | Profile first (cover banner, editable profile picture, bio, tags), quick settings on the right, all 10 tools below |
| **Settings** | Profile + photo, password & connected accounts, notification preferences, study preferences, language, appearance, download my data (JSON), delete account |
| **Profile pictures** | Upload/remove; the type is checked from the file contents, max 2 MB, random file names, scripts blocked in `uploads/` |
| **University pages** | `universities.php` (search, filters, sort, pagination), `university.php?u=<slug>` (profile), `compare.php` (up to 4 side by side), `portals.php`. All read from the database |
| **Google sign-in** | Code done and tested with fake data. **Needs Google keys** (see section 4) |

---

## 3. Placeholders: built as pages, no features yet 🚧

These pages ask guests to log in, and logged-in users see a "coming soon" message:

| Tool | File | Idea |
|---|---|---|
| Saved universities | `shortlist.php` | Save button on university profiles → list here |
| Deadline tracker | `deadlines.php` | User deadlines + deadlines pulled from saved universities |
| Application checklist | `checklist.php` | Steps per university (forms, documents, fees, tests) |
| Essays & statements | `essays.php` | Drafts with word count |
| Test scores | `test-scores.php` | Scores compared with university requirements |
| Skill milestones | `milestones.php` | Goals → steps with progress |
| Portfolio | `portfolio.php` | File uploads (reuse the avatar upload code) |
| Recommendation letters | `recommendations.php` | Recommenders + status of each request |
| Scholarships | `scholarships.php` | Saved scholarships + deadlines |
| Budget planner | `budget.php` | Tuition + living costs − funding per university |

Other things that aren't wired up yet:
- **Notification emails:** the settings are saved, but nothing sends email yet.
- **Forgot password:** this needs email sending first.
- **"Save to shortlist" and "Save comparison" buttons:** these do nothing yet.
- **Footer links:** Privacy, Terms and Contact go to `#`.

---

## 4. Next steps (suggested order) 📋

1. **Set up Sign in with Google** (about 10 minutes):
   1. Go to https://console.cloud.google.com, create a project, and open **Google Auth Platform**.
   2. **Get started:** app name `uni wise`, audience **External**, your email as the contact. Under **Audience → Test users**, add your Gmail.
   3. **Clients → Create client → Web application.** Add this redirect URI: `http://localhost/<folder-name>/google-callback.php`. If the folder name has a space, write it as `%20`.
   4. Paste the Client ID and secret into `config/config.php`. **Never commit them.**
2. **Load real university data** (top 500 QS). See "Adding real data" in the README for every column. Each university is one row in `universities`, and its majors, facilities and key dates go in `majors`, `facilities` and `university_deadlines`. You can import CSVs in phpMyAdmin.
3. **Saved universities:** a `saved_universities (user_id, university_id, note)` table, the save button on the profile page, and the list in `shortlist.php`.
4. **Deadline tracker:** a `user_deadlines` table with add/edit/done and a list sorted by date.
5. Then the other tools, one table each, following the same pattern.
6. **Email:** pick a provider (e.g. SMTP via PHPMailer) for password resets and reminders.

---

## 5. How the code works (conventions)

- **Plain PHP 8 + MySQL**, no framework or build step. Every page is a `.php` file in the root folder.
- **Shared pieces** in `includes/`:
  - `header.php` / `footer.php`: the top and bottom of every page.
  - `auth.php`: sessions, `current_user()`, `require_login()`, `csrf_field()` / `csrf_valid()`, `flash()`.
  - `db.php`: the database connection, `db()`. **Always use prepared statements.**
  - `functions.php`: `e()` to escape output, plus `icon()`, `money()`, `pct()`.
  - `i18n.php`: `t()` / `te()` for translations.
  - `tools.php`: the list of tools.
  - `tool_page.php`: the shared layout for the tool pages.
- **Every bit of text you add:** write it in English inside `te('…')`. Then run `C:\xampp\php\php.exe tools\i18n-keys.php` to see which translations are missing in `lang/*.php`.
- **Every form:** `method="post"`, include `<?= csrf_field() ?>`, and check `csrf_valid()` before doing anything.
- **Every value you print:** use `e($value)` or `te('text')`.
- **Colours:** use the CSS variables at the top of `assets/css/style.css`. Light values are in `:root`, dark values in `[data-theme="dark"]`.

---

## 6. Folder map

```
*.php                   pages (index, universities, university, compare, portals, settings, account, 10 tools, auth)
includes/               shared PHP (header, footer, auth, db, i18n, google, avatar, tools)
lang/                   translations (es, fr, de, zh, id)
assets/css/style.css    all styling
assets/js/main.js       menu, dropdown, theme switch, photo preview
assets/img/             logo + background artwork (SVG)
database/               schema.sql (structure) + seed.sql (regions, portals)
config/                 config.example.php (copy it to config.php, which is git-ignored)
uploads/                user uploads (git-ignored; .htaccess blocks scripts)
tools/i18n-keys.php     checks for missing translations
```
