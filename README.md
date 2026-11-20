# Library Lending: DDD in Laravel + SvelteKit

A small library-lending system I built during my 2026 summer internship to practise domain-driven design in Laravel and to learn Svelte.

- **`library-api/`** is a Laravel 13 JSON API. The lending rules live in a domain layer that doesn't depend on the framework.
- **`library-desk/`** is a SvelteKit (Svelte 5) front-desk app for looking up members and lending or returning book copies.

## Lending rules

All of these are enforced in the domain layer (`Member` aggregate):

- A member can have at most **3** active loans
- A loan lasts **14 days** and is not overdue on its due date itself
- A member with any overdue loan can't borrow anything new
- A copy can only be on loan to one member at a time. This rule spans members, so a partial unique index in the database (on active loans only) guarantees it, and the use case checks it first so the user gets a clear message instead of an SQL error
- A copy can't be returned twice, and a late return is flagged as late
- Borrowing and returning produce `BookBorrowed` / `BookReturned` events, which are dispatched only after the member has been saved

## Structure

```
library-api/app/
├── Domain/Lending/          # Member, Loan, value objects, events, rule violations
│                            # no Laravel imports
├── Application/Lending/     # use cases: BorrowBookCopy, ReturnBookCopy
├── Infrastructure/Lending/  # Eloquent repository, system clock
└── Http/Controllers/        # thin controllers that call the use cases
```

The domain layer gets the time through a `Clock` interface, so tests can set the date instead of using the real one.

## API

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/members` | List members |
| GET | `/api/members/{memberId}` | Member with loans and remaining allowance |
| POST | `/api/members/{memberId}/loans` | Borrow a copy |
| DELETE | `/api/members/{memberId}/loans/{copyId}` | Return a copy |

A rule violation (e.g. hitting the loan limit) returns HTTP 422 with a specific error code that the front end shows to the user.

## Run locally

**API** (PHP 8.3+, Composer, SQLite)

```bash
cd library-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

**Front end** (Node 20+)

```bash
cd library-desk
npm install
npm run dev
```

The front end expects the API at `http://library-api.test/api` (Laravel Herd). If you use `php artisan serve`, change `BASE` in `library-desk/src/lib/api.js` to `http://127.0.0.1:8000/api`.

## Domain checks

```bash
cd library-api
php domain_test.php
```

This runs 13 checks against the domain layer using plain PHP and a small autoloader, without Composer or Laravel. It shows that the business rules don't depend on the framework.

## Scope

This is a learning project. It has no authentication and no book catalogue (copies are referenced by ID).
