# uni wise

Helps students in every region research universities, compare schools and keep track of their applications.

> 👋 **New to the project? Start with [PROGRESS.md](PROGRESS.md)**: what's built, what's a placeholder, and what to do next.

**Stack:** PHP 8 + MySQL/MariaDB (XAMPP), plain HTML/CSS/JS — no framework, no build step.

## Run it locally

1. Install [XAMPP](https://www.apachefriends.org) and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Put this folder in `C:\xampp\htdocs\`.
3. Open **http://localhost/phpmyadmin** → **Import** tab and import, in this order:
   1. `database/schema.sql` — creates the `uniwise` database and its tables (this **deletes** existing tables, including user accounts)
   2. `database/seed.sql` — world regions + application portals (reference data)
4. (Optional) copy `config/config.example.php` to `config/config.php` if your MySQL user or password isn't the XAMPP default (`root`, no password).
5. Open **http://localhost/Uni%20Wise/** in your browser.

## Folder layout

```
index.php, universities.php, …   pages (one file per page)
account.php, logout.php          account page + log out
includes/header.php, footer.php  shared top and bottom of every page
includes/auth.php                sessions, CSRF, current_user(), require_login()
includes/tool_page.php           shared layout for the 4 personal tools
includes/db.php                  database connection: db()
includes/functions.php           helpers: e() for escaping, money(), pct(), icon() …
config/config.example.php        database settings template
assets/css/style.css             all styling (light + dark theme tokens at the top)
assets/js/main.js                theme toggle, mobile menu, dropdown
assets/img/logo.svg              logo + browser tab icon
database/schema.sql              table structure
database/seed.sql                regions + portals
```

## Accounts

- Sign up stores a `password_hash()` hash, never the plain password. Emails are stored in lower case and must be unique.
- Every form uses a CSRF token, and log out only works through a POST request.
- After 5 failed log-ins in one browser session, log-in is locked for 5 minutes.
- To protect a page, put `$user = require_login();` at the top. Guests are sent to log in and then brought back to that page.

## Sign in with Google

1. Go to https://console.cloud.google.com and create a project.
2. Go to **APIs & Services → OAuth consent screen**. Choose *External*, fill in the app name and your email, and add yourself as a test user.
3. Go to **APIs & Services → Credentials → Create credentials → OAuth client ID** and choose *Web application*.
   - **Authorised redirect URI:** `http://localhost/Uni%20Wise/google-callback.php`. If you rename the folder, change this to match, e.g. `http://localhost/uniwise/google-callback.php`.
4. Copy the client ID and client secret into `config/config.php` (`google_client_id`, `google_client_secret`).

How it works:
- The first time someone signs in with Google, a new account is created.
- If an account with that email already exists, the two are linked.
- Google-only accounts have no password, so they can only sign in with Google.

## Languages

The site is in English, Español, Français, Deutsch, 中文 and Bahasa Indonesia. People can switch language from the footer picker or the Settings page. The choice is saved in a cookie, and for logged-in users on their account too, so it follows them to other devices.

- Write every visible piece of text in English and wrap it in `t('…')`, or use `te('…')` to translate and HTML-escape in one go. Placeholders work like this: `t('Hi, {name}', ['name' => $first])`.
- Translations live in `lang/xx.php`, each one mapping the English text to the translated text. Anything missing falls back to English.
- After adding text, run `C:\xampp\php\php.exe tools\i18n-keys.php`. It lists every string each language file is missing.
- To add a language, add it to `LANGUAGES` in `includes/i18n.php`, then copy `lang/es.php` to `lang/<code>.php` and translate it.
- Data from the database (university names, portal descriptions) is not translated. Region names and a few fixed values are.

## Settings

`settings.php` has these sections:
- **Language** and **Appearance** (light/dark): available to everyone.
- **Profile:** name, region, start year.
- **Password:** change it. Google-only accounts can add a password here.
- **Delete account:** needs the current password and a confirmation tick.

## Profile pictures

- Uploads are handled by `avatar.php` and `includes/avatar.php`. Accepted formats are JPG, PNG, WebP and GIF, up to 2 MB.
- The type is checked from the file contents, not the name. Files are saved as `uploads/avatars/<random>.<ext>`.
- `uploads/.htaccess` blocks scripts and directory listing in that folder, and `uploads/` is git-ignored.
- **Optional:** enable PHP's GD extension to crop and resize every picture to a 256 px square. In `C:\xampp\php\php.ini`, remove the `;` from `;extension=gd`, then restart Apache. Without GD, pictures are stored as uploaded and CSS crops them into a circle.

## Light / dark mode

The site follows the operating system's setting by default. The moon/sun button in the header overrides it and remembers the choice in `localStorage`. All colours are CSS variables at the top of `style.css`: the light values are in `:root`, and the dark values are in the two `[data-theme="dark"]` blocks.

## Which pages use the database

| Page | Data |
|---|---|
| `universities.php` | search + filters (region, major, language, tuition), sort, pagination |
| `university.php?u=<slug>` | one university profile |
| `compare.php?s1=<id>&s2=<id>…` | side-by-side comparison (up to 4) |
| `portals.php` | application portals grouped by region |

With no rows in a table, these pages show a "coming soon" placeholder.

`deadlines.php`, `milestones.php`, `portfolio.php` and `recommendations.php` ask guests to log in. Logged-in users see an empty placeholder until each tool's table is built.

## Adding real data

Each university needs **one row in `universities`**. Its majors, facilities and key dates go in the `majors`, `facilities` and `university_deadlines` tables, linked by `university_id`.

- `slug` must be unique and URL-safe (`imperial`, `eth-zurich`): it becomes the profile link.
- `region_id` must match a row in `regions` (1 North America … 9 Africa).
- `portal_id` is the matching `portals` row, or `NULL` if students apply directly.
- Money is **USD per year**. `employment_rate` is a percentage, e.g. `92.5`.
- Leave anything you don't know as `NULL`; the site shows "—".
- Mark top majors with `is_top = 1`, put them in order with `top_rank` (1 = strongest), and add an optional `rank_label` such as `Top 10`.

For bulk data, use phpMyAdmin → select the table → **Import** → CSV. Make the CSV columns match the table columns.

## Roadmap

- [x] Phase 1: front-end design for every page
- [x] Database structure
- [x] User accounts (sign up / log in / log out), light + dark mode
- [ ] Real data for the top 500 QS universities
- [ ] Save each user's deadlines, milestones, portfolio and recommendation letters
