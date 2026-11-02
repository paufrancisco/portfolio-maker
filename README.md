# CampusCraft

A web app where customers upload a resume, review the extracted information, pick a template, preview their portfolio, pay by QR code, and receive a public portfolio URL after the owner manually approves the payment.

**Stack:** Laravel 12, Vue 3 + Inertia, Tailwind CSS 3, Supabase (PostgreSQL + Storage), Docker on Render.

## How it works

1. Customer uploads a PDF or DOCX resume. The parser pre-fills the form.
2. Customer reviews and edits the form, uploads photos, and picks a template.
3. Customer previews the portfolio (watermarked) and submits a payment reference and proof.
4. Admin reviews the proof at `/admin/payments` and approves or rejects it.
5. On approval, the portfolio is published at `/p/{slug}` and the customer is emailed the link.
6. Later edits stay in a draft. The customer clicks **Request update**, and the admin publishes it.

## Local setup

Requirements: PHP 8.2+, Composer, Node 20+, extensions `pdo_pgsql`, `zip`, `xml`, `gd`, `mbstring`, `intl` (and `sqlite3` for tests).

```bash
git clone https://github.com/paufrancisco/portfolio-maker.git
cd portfolio-maker
composer install
npm install
cp .env.example .env
php artisan key:generate
# fill in .env (see below)
php artisan migrate
php artisan db:seed --class=TemplateSeeder
npm run dev        # or npm run build
php artisan serve
```

## Supabase setup

1. Create a Supabase project. Use the **Session pooler** connection string for the database (Render is IPv4-only). The username is `postgres.<project-ref>`.
2. Create two Storage buckets:
   - `portfolio-maker`: **private**. Holds resumes and payment proofs.
   - `portfolio-images`: **public**. Holds profile photos, project images, and the payment QR image.
3. Create S3 access keys under Storage settings. Both buckets use the same keys and endpoint.
4. Upload your payment QR image to `portfolio-images/site/` and set `PORTFOLIO_QR_PATH` to its public URL.

## Environment variables

Never commit `.env`. Set these in `.env` locally and in the Render dashboard for production.

| Variable | Purpose |
|---|---|
| `APP_ENV` | `local` or `production` |
| `APP_DEBUG` | `false` in production |
| `APP_KEY` | Generate with `php artisan key:generate --show` |
| `APP_URL` | Public site URL. Used in email links, so it must be exact |
| `LOG_CHANNEL` | `stderr` on Render |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Supabase session pooler credentials |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `s3` |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | Supabase S3 keys |
| `AWS_DEFAULT_REGION` | Your Supabase project region |
| `AWS_ENDPOINT` | `https://<project-ref>.storage.supabase.co/storage/v1/s3` |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |
| `AWS_BUCKET` | `portfolio-maker` |
| `IMAGES_BUCKET` | `portfolio-images` |
| `IMAGES_URL` | `https://<project-ref>.supabase.co/storage/v1/object/public/portfolio-images` |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP. For Gmail, use an App Password |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender shown to customers |
| `PORTFOLIO_PRICE` | Price in PHP (default 500) |
| `PORTFOLIO_QR_PATH` | Public URL of the payment QR image |
| `PORTFOLIO_WALLET_LABEL` | Wallet name shown on the payment page, e.g. `GCash` |

## Deploy to Render

1. Render dashboard, **New**, **Web Service**, connect this repository.
2. **Language: Docker.** Leave build and start commands empty. The `Dockerfile` handles them.
3. Add all environment variables above. Set `APP_URL` to the service URL, then redeploy.
4. On startup, `docker/start.sh` caches config, runs migrations, and seeds templates.
5. Free plan services sleep when idle, so the first request can take 30-60 seconds.

## Create the first admin

Register an account on the site, then run this in the Supabase SQL Editor:

```sql
update users set is_admin = true where email = 'you@example.com';
```

## Tests

```bash
php artisan test
```

Tests run on in-memory SQLite and fake storage. They never touch the real database.

## Security notes

- Resumes and payment proofs are in a private bucket and are only served by short-lived signed URLs.
- The public page only serves `published` portfolios and strips internal fields.
- Admin routes require `is_admin`.
- Local development and production may share one database. Run migrations carefully, and never point tests at it.
