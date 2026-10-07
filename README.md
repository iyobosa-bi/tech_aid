# Tech Aid

Internal technology support and ticketing for optimusbank.com. Staff outside the
Technology department raise tickets. Each ticket moves through an approval and
resolution chain:

**Requester → Line Manager → Head of Service Management → Application Support**

---

### Built

| Area | What works |
|---|---|
| **Sign-in with email OTP** | Email and password first, then a 6-digit code entered in a modal (no page reload). OTP resend is limited to 3 per 10 minutes, and login attempts are rate-limited. Pages are sent with `Cache-Control: no-store`, so the back button can't reveal a signed-out session. |
| **Roles & permissions** | 5 roles (Requester, Line Manager, Head of Service Management, Application Support, Admin), each with 2 permissions, via Spatie. `TicketPolicy` handles per-ticket checks. A denied action redirects back with the policy's message (JSON 403 for API/AJAX). |
| **Raise a ticket**  | Title, description, category and priority. Up to 5 attachments via FilePond (10 MB each; jpg, png, pdf, doc, docx). The ticket starts as `pending_line_manager_approval`, the action is written to the audit history, and the Line Manager is notified by email and in-app (queued). |
| **Ticket list** | Live search as you type (ticket ID or title, plus requester name for handlers), a status filter, sortable columns and 15 per page. Each user sees only their own slice: Head of Service Management sees every ticket; everyone else sees tickets they raised, manage or are assigned to. On narrow screens the table scrolls sideways with the ID column pinned. |
| **Dashboard** | Four stat cards specific to the user's role (for example, *Pending My Approval* for Line Managers and *Avg Resolution Time* for Head of Service Management). Below them, the 5 most recent tickets in the same table as the Tickets page. |
| **Ticket page** | Opens from the ticket's title. Shows details, attachments (images and PDFs open in a quick-view popup; every file can be downloaded; Word files are download-only), a status-history timeline, and a conversation thread that everyone on the ticket can see and reply to. New messages notify the other participants. |
| **Approve / decline** (Flow 3) | Only the ticket's own Line Manager, only while it's pending. Both go through a confirmation popup, and both comments are posted to the ticket's conversation. An approval comment is optional; left blank, it's posted as *"Approved by me. No comments"*. A decline needs a comment and returns the ticket to the requester as *Returned* (not closed). Head of Service Management is notified on approval, and the requester on a decline. |
| **Edit & resubmit** (Flow 4) | The requester edits a returned ticket, optionally adds files (up to 5 in total) and a note, and sends it back to their Line Manager. |
| **Activity log** | `storage/logs/activity-YYYY-MM-DD.log`, kept for 90 days: sign-ins, OTP checks, and every ticket action, recorded as who and what. Never passwords, codes or message text. |
| **404 page** | Branded "Page not found" page. It offers *Back to dashboard* if you're signed in, or *Go to sign in* if you're not. |


---

## Tech stack

| | |
|---|---|
| Backend | Laravel 13, PHP 8.3+ (developed on 8.4). Standard controllers, no Livewire |
| Frontend | Blade + Tailwind CSS + Alpine.js + Lucide icons, all from CDNs. **No Node or build step** |
| Database | PostgreSQL (tests use in-memory SQLite) |
| Cache / sessions | Redis (via `predis`) |
| Queue | Database driver for now (see Known gaps) |
| Auth & roles | Laravel session auth, Spatie `laravel-permission`, Laravel Policies |
| File storage | Local disk in development; S3 planned for production |
| Tests | Pest |

Why each choice was made: [docs/04-tech-stack.md](docs/04-tech-stack.md).

---

## Setup

**You need:** PHP 8.3+, Composer, PostgreSQL and Redis. Node is *not* needed.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty Postgres database (e.g. `tech_aid`), then set these in `.env`:

```dotenv
APP_NAME="Tech Aid"
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tech_aid
DB_USERNAME=postgres
DB_PASSWORD=

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=database
REDIS_CLIENT=predis

MAIL_MAILER=log

# The login page only accepts emails ending in @ this domain (live check while typing).
# example.com matches the demo accounts; production uses optimusbank.com (the default).
STAFF_EMAIL_DOMAIN=example.com
```

Create the tables and the demo data, then start the app:

```bash
php artisan migrate --seed
php artisan serve          # http://localhost:8000
```

In a second terminal, start the queue worker. OTP emails and ticket notifications
are queued, never sent straight away:

```bash
php artisan queue:work
```

> Skip `composer run setup` and `composer run dev`. They come from the Laravel
> skeleton and expect a Vite build, which this project doesn't use.

### Demo accounts

All demo accounts use the password **`Test1234@@@`** (local development only).
The seeders are safe to re-run: they never duplicate accounts or reset passwords.

| Email | Role | Notes |
|---|---|---|
| `test@example.com` | Requester | Reports to the Line Manager below |
| `testtwo@example.com` | Requester | No line manager, so can't raise tickets (useful for testing that rule) |
| `linemanager@example.com` | Line Manager | |
| `hosm@example.com` | Head of Service Management | Sees every ticket |
| `support@example.com` | Application Support | |
| `admin@example.com` | Admin | |

### Getting the OTP code locally

When `APP_ENV=local` **and** `APP_DEBUG=true`, the code is printed in the
browser's developer console (`[Tech Aid dev] OTP code: 123456`). This works even
without the queue worker. Otherwise, with `MAIL_MAILER=log`, the email (and its
code) is written to `storage/logs/laravel.log` once the queue worker has sent it.

---

## Tests

```bash
php artisan test
```

## Useful commands

| Command | What it does |
|---|---|
| `php artisan route:list --except-vendor` | List the app's routes |
| `php artisan truss:open` | Browse the database tables and relationships in the browser |
| `php artisan db:seed` | Re-run the seeders (safe to repeat) |

---

## Where things live

```
app/
  Enums/            Status, priority, category, role, permission and audit-action names
  Http/Controllers/ Thin controllers: they authorise, call a service, return a view
  Http/Requests/    Form validation (StoreTicketRequest, ListTicketsRequest)
  Policies/         TicketPolicy — record-level checks (who may approve, assign, resolve…)
  Repositories/     Database queries (TicketRepository, DashboardRepository)
  Services/         Business logic (TicketCreationService, DashboardService, uploads)
resources/views/
  tickets/partials/ Shared ticket table, used by the Tickets page and the dashboard
  partials/         Shared status badge and flash messages
  errors/404        "Page not found" page
public/js/          Alpine components (login/OTP, ticket form, live search, table scrolling)
docs/               Requirements, features, user flows, tech stack, data model
design/             Style notes (locked theme) and reference screenshots
```
