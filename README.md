# Nasiia's Gallery

A modern art gallery web application built with **Laravel 13**, **React 19**, **Inertia.js**, and **Filament v5**.

## Features

### Public Gallery
- **Homepage** — Featured artworks and gallery introduction
- **Gallery browsing** — Filterable, paginated artwork grid with categories
- **Artwork detail pages** — High-resolution images, descriptions, view tracking
- **Interactions** — Like artworks, post comments (authenticated users)
- **Contact form** — Visitor messages emailed to admins, stored for dashboard review
- **Multi-language** — English & Ukrainian (extensible via Laravel localization)

### Admin Panel (Filament v5)
- **Dashboard** — Real-time statistics: total artworks, categories, likes, views, comments, users, messages
- **Growth charts** — Daily trend lines per metric (7/30/90-day ranges, clickable from stat cards)
- **Latest emails widget** — Recent contact submissions with full-message modal, auto-polling
- **Artwork management** — CRUD with media library (Spatie), image editor, categories
- **Category management** — Slugged, ordered categories
- **User management** (super-admin only) — Searchable table, role promotion/demotion with safety guards
- **Activity log** — Spatie Activitylog audit trail

### Authentication & Security
- Laravel Fortify: email verification, 2FA, passkeys
- Role-based access: `User` | `Admin` | `SuperAdmin`
- Super-admin protections: cannot be created/demoted via panel, self-demotion blocked
- Rate-limited contact form, CSRF, security headers middleware

### Developer Experience
- **Type-safe** — PHP 8.3 + strict types, TypeScript, PHPStan level 7
- **Testing** — Pest (142+ tests), browser tests with Pest Browser
- **Code quality** — Laravel Pint, pre-commit hooks
- **Observability** — Laravel Telescope, Pail
- **Containerized** — Docker Compose for local dev

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | React 19, Inertia.js, TypeScript |
| Styling | Tailwind CSS v4, Radix UI, Framer Motion |
| Admin | Filament v5 |
| Database | SQLite (dev), MySQL/PostgreSQL (prod) |
| Media | Spatie Laravel MediaLibrary |
| Email | Resend |
| Queue | Database (dev), Redis (prod) |
| Auth | Laravel Fortify (2FA, passkeys, verification) |

## Project Structure

```
app/
├── Actions/              # Single-responsibility service classes
├── Enums/                # UserRole, GalleryMetric
├── Filament/
│   ├── Pages/            # Custom dashboard
│   ├── Resources/        # UserResource (super-admin)
│   └── Widgets/          # GalleryStats, LatestEmails, GalleryGrowthChart
├── Http/
│   ├── Controllers/      # Gallery, Contact, Home, Locale, Settings
│   └── Requests/         # Form requests
├── Models/               # Artwork, Category, User, Like, Comment, ContactMessage, ArtworkView
├── Policies/             # UserPolicy
├── Providers/            # Filament AdminPanelProvider
└── Rules/                # ValidArtworkImage
resources/
├── js/                   # React components, pages, hooks
│   ├── Components/       # UI primitives (Radix-based)
│   ├── Layouts/          # Public & admin layouts
│   └── Pages/            # Inertia pages (Home, Gallery, Settings)
├── css/                  # Tailwind entry
└── views/                # Blade emails, app shell
database/
├── migrations/
├── factories/
└── seeders/
tests/
├── Feature/              # Integration tests
└── Browser/              # Pest Browser tests
```

## Getting Started

### Prerequisites
- PHP 8.3+
- Node.js 20+
- Composer
- SQLite (default) or MySQL/PostgreSQL

### Installation

```bash
# Clone & enter
git clone <repository-url>
cd nasiias-gallery

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite by default)
touch database/database.sqlite
php artisan migrate --seed

# Build assets
npm run build

# Start dev servers
composer run dev          # Laravel + Vite (concurrent)
# or separately:
php artisan serve
npm run dev
```

Visit `http://localhost:8000` for the gallery, `http://localhost:8000/admin` for Filament.

### Docker (Alternative)

```bash
docker compose up -d --build
```

## Available Scripts

| Command | Description |
|---------|-------------|
| `composer run dev` | Start Laravel + Vite dev servers |
| `composer run lint` | Run Pint code style fixer |
| `composer run lint:check` | Check code style (CI) |
| `composer run types:check` | Run PHPStan static analysis |
| `composer run test` | Full CI pipeline (lint + types + Pest) |
| `npm run build` | Production Vite build |
| `npm run build:ssr` | Production build with SSR |
| `npm run check` | TypeScript type-check + lint |
| `npm run types:check` | TypeScript only |

## Testing

```bash
# All tests (unit + feature + browser)
composer run test

# Feature only
php artisan test --filter=Feature

# Browser (requires Chrome)
php artisan test --filter=Browser

# Specific test file
php artisan test tests/Feature/Filament/UserResourceTest.php
```

## Configuration

Key `.env` variables:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite          # or mysql/pgsql
MAIL_MAILER=resend            # or smtp/log
RESEND_KEY=re_...
QUEUE_CONNECTION=database     # or redis
TELESCOPE_ENABLED=true
```

## Admin Panel Access

1. Register a user at `/register`
2. Manually promote to super-admin in DB:
   ```sql
   UPDATE users SET role = 'SuperAdmin' WHERE email = 'your@email.com';
   ```
3. Visit `/admin` — super-admins see **Users** resource, dashboard widgets

## Deployment

1. Set production `.env` values
2. Run migrations: `php artisan migrate --force`
3. Build assets: `npm run build`
4. Optimize: `php artisan optimize`
5. Queue workers: `php artisan queue:work --sleep=3 --tries=3`
6. Schedule: `* * * * * php artisan schedule:run`

### Recommended Production Services
- **Database**: MySQL 8+ or PostgreSQL 15+
- **Queue**: Redis + Supervisor
- **Mail**: Resend, Mailgun, or SMTP
- **Storage**: S3-compatible (MediaLibrary)
- **Monitoring**: Telescope (optional), Pail for logs

## License

MIT License — see [LICENSE](LICENSE) for details.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit with [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `chore:`)
4. Push and open a PR against `Dev`
5. Ensure CI passes (Pint, PHPStan, Pest)

---

Built with ❤️ using Laravel, React, and Filament.