# Afrihost Deployment Guide

Step-by-step deploy of GreenCash to Afrihost shared hosting (cPanel). Assumes the domain `greencash.co.za` is registered with Afrihost (or pointed to Afrihost nameservers).

The deploy is split into **6 phases**. Do them in order. Each phase is a checkpoint — if something is wrong, stop and fix before continuing.

---

## Phase 1 — cPanel: Create the MySQL database

1. Log in to cPanel (Afrihost client zone → your hosting service → **Manage** → **Login to cPanel**).
2. Find **MySQL® Databases** (under "Databases").
3. **Create a new database:**
   - Name: `greencash` (cPanel will prepend your cPanel username, so the full DB name becomes e.g. `youracct_greencash`)
   - Click **Create Database**.
4. **Create a database user:**
   - Username: `gc_app` (full username will be e.g. `youracct_gc_app`)
   - Password: generate a strong one (cPanel has a generator)
   - Click **Create User**
   - **Write down**: DB name, username, password.
5. **Grant privileges:**
   - Scroll to **Add User to Database**.
   - Pick your new user + new database → **Add**.
   - Tick **ALL PRIVILEGES** → **Make Changes**.

You now have a MySQL DB ready. Note the three values for `env.php` in Phase 4.

---

## Phase 2 — Upload the code

You have two options. **Git is cleaner** if cPanel supports it on your plan; **FTP is universal**.

### Option A — Git (recommended if available)

1. cPanel → **Git Version Control** → **Create**.
2. **Clone URL:** `https://github.com/oluwatosindot/greencash.git`
3. **Repository path:** `/home/youracct/repositories/greencash`
4. **Repository name:** `greencash`
5. Click **Create**. cPanel clones the `staging` branch by default — change to `main` later when you're ready to merge.
6. Deploy to `public_html/`:
   - In the Git interface, click **Manage** on the repository
   - Set **Deployment path** to `/home/youracct/public_html`
   - Click **Update from Remote** → **Deploy HEAD Commit**

This pulls the latest code into `public_html/` and you can re-deploy with one click when you push new commits.

### Option B — FTP / cPanel File Manager

1. Compress the project locally (exclude `.git/`, `node_modules/`, `.firecrawl/`):
   ```bash
   # From C:\xampp\htdocs\greencash (PowerShell)
   $exclude = @('.git', 'node_modules', '.firecrawl', '.claude', 'uploads/*')
   Compress-Archive -Path * -DestinationPath greencash-deploy.zip -Force
   ```
2. cPanel → **File Manager** → navigate to `public_html/`.
3. **Upload** `greencash-deploy.zip`.
4. Right-click the zip → **Extract** → into `public_html/`.
5. Delete the zip.

**Verify:** the `public_html/` directory now contains `index.php`, `apply.php`, `assets/`, `includes/`, etc. directly — NOT inside a `greencash/` subfolder.

---

## Phase 3 — Import the database schema

1. cPanel → **phpMyAdmin**.
2. Select your `youracct_greencash` database in the left sidebar.
3. Top tabs → **Import**.
4. **Choose File** → select `database/greencash.sql` from your local machine (or download it from the deployed `public_html/database/greencash.sql` — `.htaccess` blocks HTTP access to it, but phpMyAdmin reads it server-side fine).
5. Leave the defaults (UTF-8, SQL format) → **Import** at the bottom.
6. Verify: the left sidebar should now show ~11 tables (users, salary_advance_applications, application_documents, contact_messages, etc.).

**If you see errors** about character set or collation, your Afrihost MySQL version may be older than what XAMPP uses locally. Tell me the error and I'll patch the SQL.

---

## Phase 4 — Create `includes/env.php` on the server

`env.php` is gitignored, so it's NOT in the deployed code. You create it once on the server.

1. cPanel → **File Manager** → `public_html/includes/`.
2. **Copy** `env.example.php` → name the copy `env.php`.
3. Right-click `env.php` → **Edit**.
4. Fill in the real values (use the DB credentials from Phase 1):
   - `DB_NAME` → `youracct_greencash`
   - `DB_USER` → `youracct_gc_app`
   - `DB_PASS` → the strong password you wrote down
   - `APP_URL` → `https://greencash.co.za` (no trailing slash)
   - `MAIL_HOST` → `mail.greencash.co.za` (Afrihost default for cPanel mail)
   - `MAIL_USER` / `MAIL_PASS` → the noreply@ mailbox creds (Phase 5)
   - `APP_ENV` → `'production'`
5. Save.

---

## Phase 5 — Create email mailboxes

For sending application confirmations + receiving partnership enquiries:

1. cPanel → **Email Accounts** → **Create**.
2. Create each mailbox:
   - `noreply@greencash.co.za` — used by the app to send emails. Quota: 100MB is plenty.
   - `admin@greencash.co.za` — receives new-application notifications.
   - `info@greencash.co.za` — shown on the contact page.
   - `partners@greencash.co.za` — receives the Employers band mailto enquiries.
3. **Write down each mailbox password** — `MAIL_PASS` in env.php uses `noreply@`'s password.
4. Update `env.php` (Phase 4) with the noreply mailbox password.

---

## Phase 6 — SSL + HTTPS redirect

**Do not enable HTTPS redirect before SSL is active** — users will get cert warnings on the first visit.

1. cPanel → **SSL/TLS Status** (or **SSL/TLS** → **Let's Encrypt™**).
2. Find `greencash.co.za` + `www.greencash.co.za` → **Run AutoSSL** (or **Issue** for Let's Encrypt directly).
3. Wait 1-2 minutes. Status should show a green padlock.
4. **Test:** visit `https://greencash.co.za` in a browser. Padlock visible, no warnings.
5. **Now enable the HTTPS redirect** in `.htaccess`:
   - cPanel → File Manager → `public_html/.htaccess` → Edit
   - Find the block that starts `# HTTPS redirect — UNCOMMENT AFTER SSL...`
   - Remove the `#` from these three lines:
     ```
     RewriteCond %{HTTPS} off
     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
     ```
   - Save.
6. Visit `http://greencash.co.za` (force HTTP). It should 301 to `https://`.

---

## Phase 7 — Permissions & final checks

1. cPanel File Manager → right-click `uploads/` → **Change Permissions** → set to **755** (or 775 if PHP runs as a different user — Afrihost is usually 755).
2. Visit `https://greencash.co.za/` → home page loads with the new dark nav + logo.
3. Visit `https://greencash.co.za/track-application.php` → form renders.
4. Visit `https://greencash.co.za/admin/` → admin login (you may need to seed an admin user manually via phpMyAdmin if none exists — see "First admin user" below).
5. **Apply form end-to-end test:**
   - Fill out the form with a fake but valid SA ID (e.g. `9001011234084`)
   - Upload three test PDFs as ID / payslip / bank statements
   - Submit
   - Confirm: you land on `application-submitted.php` with a reference number
   - Check the DB: phpMyAdmin → `salary_advance_applications` should have a new row + `application_documents` should have 3 rows
   - Check `admin@greencash.co.za` for the notification email (if mail is wired up)

---

## First admin user

If the deployed DB has no admin user yet, create one via phpMyAdmin:

1. phpMyAdmin → `users` table → **Insert**.
2. Fill in:
   - `email`: `your-email@greencash.co.za`
   - `password_hash`: open a terminal and run `php -r "echo password_hash('YourStrongPassword', PASSWORD_BCRYPT);"` — paste the hash here
   - `role`: `admin`
   - `created_at`: leave (it auto-fills)
3. Save. You can now log in at `/admin/` with that email + password.

---

## Common gotchas

| Symptom | Cause | Fix |
|---|---|---|
| "Database connection failed" | Wrong DB creds in env.php | Re-check Phase 1 + Phase 4 |
| Forms 500 error on submit | `uploads/` not writable | Phase 7 — chmod 755 (or 775) |
| Images / CSS look broken | APP_URL has trailing slash or `www.` mismatch | Edit env.php — set to `https://greencash.co.za` exactly |
| Emails don't arrive | Wrong MAIL_PASS or SMTP host | Re-check Phase 5 — mailbox password is NOT your cPanel password |
| HTTPS cert error on first visit | HTTPS redirect enabled before AutoSSL finished | Phase 6 — comment out the redirect, wait for AutoSSL, then re-enable |
| 404.php doesn't show on missing pages | Apache `AllowOverride` setting | Afrihost has AllowOverride All by default. If not, ErrorDocument works server-side either way |
| Logo file 404 | Case mismatch in filename | `GreenCash_Logo_Dark.png` is case-sensitive on Linux. Verify the actual filename in `assets/img/` |

---

## After deploy: maintenance

- **Update code:** push to GitHub `staging` → in cPanel Git, click **Update from Remote** → **Deploy HEAD Commit**.
- **Backups:** cPanel → **Backup Wizard** weekly. Database + home directory.
- **NCR registration:** when you receive your NCR number, add `define('NCR_NUMBER', 'NCRCPXXXX');` to `env.php` — the footer "Responsible lending" block automatically becomes visible.
- **Monitor:** cPanel → **Errors** shows PHP error log. Check after any deploy.

---

## Rollback

If a deploy breaks production:

1. cPanel Git → previous commit → **Deploy** (rolls back code).
2. Or restore from backup (cPanel → **Backup Wizard** → Restore).
3. `env.php` is NOT in git, so it survives all rollbacks.
