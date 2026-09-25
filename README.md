# TechAid

Internal ticketing application for optimusbank.com. Lets staff outside the
Technology department raise support tickets that route through an approval
and resolution chain (Requester → Line Manager → Head of Service Management →
Application Support), with full audit history, notifications, and reporting.

> **Status:** early/stage one. Authentication (session login + email OTP) and
> the dashboard shell are built; ticketing, approvals, and reporting are not
> yet implemented. Expect this README to grow as those land.

## Stack

- Laravel 12, PHP 8.3+
- PostgreSQL, Redis (cache/session/queue)
- Blade + Tailwind CSS (CDN, no build step) + Alpine.js
- Spatie `laravel-permission` for roles, Laravel Policies for record-level checks
- Pest for testing

See [docs/04-tech-stack.md](docs/04-tech-stack.md) for the full rationale.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for a local Postgres database and Redis instance, then:

```bash
php artisan migrate
php artisan serve
```

Run a queue worker in a separate terminal — OTP emails and notifications are
queued, not sent synchronously:

```bash
php artisan queue:work
```

Locally, `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log` instead
of sending them — check there for the OTP code during login.

## Testing

```bash
php artisan test
```
