# Laravel API — Regional Government CMS (code sample)

Portfolio excerpt from backend work on a **unified Laravel platform** for Astrakhan region executive bodies and municipalities. The production site family includes [astrobl.ru](https://astrobl.ru).

This repository contains the **`app/` layer only** (application code from a full Laravel project). It is **not a runnable project** by itself — there is no `composer.json`, `routes/`, or `database/` here. Use it to review architecture, domain modules, and API patterns.

**Related:** [kanasensei](https://github.com/CheSergo/kanasensei) (personal Go API) · [rust_archiver](https://github.com/CheSergo/rust_archiver) (backup tooling)

---

## What this codebase represents

A **modular monolith** that powers:

- **Admin REST API** — content and configuration for many sites from one backend
- **Public “front” API** (`api/front` in the full app) — JSON for SPAs / headless pages
- **Multi-site tenancy** — users, roles, and abilities scoped per `Site` and `active_site_id`

Rough scale in this snapshot: **35+ domain modules**, shared filters/traits, queue jobs, mail, and audit logging.

---

## Architecture (high level)

```
┌─────────────────────────────────────────────────────────────┐
│  Admin SPA / public frontends                               │
└───────────────────────────┬─────────────────────────────────┘
                            │ REST (JSON)
┌───────────────────────────▼─────────────────────────────────┐
│  Laravel — routes/api.php + routes/front.php (not in repo)   │
│  • Sanctum token auth + role/ability checks                   │
│  • ApiResponse envelope (status, message, meta, data)         │
│  • QueryFilter pipeline for list/search/sort                  │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│  Modules/* — domain-centric packages                          │
│  • *AdminController — CRUD, validation (Form Requests)        │
│  • FrontComponents/* — published content for public API       │
│  • *Filter — query-string filters per resource                │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│  MariaDB — sites, departments, documents, users, …            │
└─────────────────────────────────────────────────────────────┘
```

---

## Tech stack (from application code)

| Area | Implementation in this repo |
|------|-----------------------------|
| Framework | Laravel (HTTP Kernel, Form Requests, Eloquent, queues, mail) |
| API auth | **Laravel Sanctum** — personal access tokens, `abilities` middleware, token expiry via `TokenHelper` |
| Authorization | Roles & abilities per site (`Modules/Users/Roles`, `HasAbilities` trait) |
| Public API guard | `FrontHashValidator` middleware on `front` route group |
| JSON responses | `Helpers/Api/ApiResponse` — consistent success/error shape |
| List endpoints | `Http/Filters/QueryFilter` + per-resource filters (`search`, `sort`, `published`, …) |
| Media | Spatie-style media collections in `Traits/Actions` (posters, galleries, base64 upload) |
| Background work | `Jobs/*Builder` (e.g. department/direction tree rebuilds) |
| Email | `Mail/NotifyMail` |
| Audit | `Modules/Logs` — `LogService` with attribute/relationship diff tracking |
| Ops (full deploy) | Docker Compose, Nginx, MariaDB, Node build — described in resume, not in this repo |

> **Note:** Older resume text mentioned “JWT”; in this published sample, API sessions use **Sanctum tokens**, not `tymon/jwt-auth`. That is still token-based API authentication suitable for SPAs.

---

## Domain modules (sample)

Each folder under `Modules/` typically includes models, admin controllers, optional `FrontComponents`, and a `*Filter` class.

| Module | Purpose (government / portal) |
|--------|-------------------------------|
| **Sites** | Multi-site configuration, contracts, modules |
| **Users** | Accounts, site switching, roles/abilities, token lifecycle |
| **Departments** | Executive structure, contacts, hierarchy |
| **Documents** | Legal/normative docs, types, intervals, anti-corruption subsets |
| **Articles** | News / publications with categories |
| **Sections** | Page builder blocks tied to components |
| **Workers** | Officials / staff profiles |
| **Meetings**, **PublicHearings** | Events and citizen hearings |
| **Commissions**, **Directions**, **Districts** | Organizational metadata |
| **Banners**, **Menus**, **Links**, **Tags** | Site chrome & navigation |
| **GovernmentInformations**, **OperationalInformations** | Disclosure content |
| **Municipalities**, **MunicipalServices**, **Institutions** | Local government entities |
| **Vacancies**, **Contests**, **Contracts** | HR and procurement surfaces |
| **Logs** | Administrative change history |
| … | Additional modules: Basket, Birth, Changelogs, Components, InformationSystems, Smis, Sources, etc. |

---

## Patterns worth highlighting

### 1. Standard API envelope

`Helpers/Api/ApiResponse.php` returns JSON with `status`, `message`, `meta` (pagination), and `data` — used across admin and front controllers.

### 2. Reusable CRUD traits

`Traits/Actions/ActionsSaveEditItem.php` centralizes slug generation, tag sync, and media gallery handling for admin saves.

### 3. Front vs admin split

Example: `Modules/Departments/DepartmentAdminController.php` (management) vs `FrontComponents/Departments.php` (published departments, workers, documents for the public site).

### 4. Multi-site scoping

Front queries use scopes such as `thisSiteFront()`; users carry `active_site_id` and many-to-many `sites` / `roles` with pivot `site_id`.

### 5. Filtering & search

`Http/Filters/QueryFilter.php` maps query-string parameters to filter methods (including multi-value and `sort`).

---

## Project layout (this repo)

```
app/                          # ← contents of this repository (root = app/)
├── Console/Commands/         # Scheduled/maintenance (e.g. basket cleanup)
├── Exceptions/
├── Helpers/                  # ApiResponse, HRequest, encoding/conversion helpers
├── Http/
│   ├── Filters/              # QueryFilter + resource-specific filters
│   ├── Middleware/           # CORS, FrontHashValidator, Sanctum, …
│   └── Requests/             # BaseRequest validation
├── Jobs/                     # Async tree/index builders
├── Mail/
├── Models/                   # User, ActivityLog, Roles, …
├── Modules/                  # Domain modules (main business logic)
├── Providers/                # Routes: api + api/front (in full project)
└── Traits/                   # Actions, relations, search, change tracking
```

In a complete Laravel tree, map this folder to **`/app`** and wire `composer.json`, `routes/`, `config/`, and `database/migrations/` separately.

---

## What is intentionally omitted

- Routes, `.env`, migrations, tests, frontend assets
- Proprietary deployment secrets and full production configuration
- Some helper files may reference classes defined outside this snapshot

---

## Author

Backend sample from production government portal work — [CheSergo](https://github.com/CheSergo).

For a **runnable** open-source API example, see [kanasensei](https://github.com/CheSergo/kanasensei) (Go + PostgreSQL).

---

## License

Code published for **portfolio review**. Contact the author before reuse in other products.
