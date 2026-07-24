# Jiidaa

A GPS-powered marketplace that connects buyers with nearby vendors in real time — discover vendors around you, browse their products, and reach them straight through WhatsApp. Built with Laravel 12, React, TypeScript, and Inertia.js.

> The repository/package name is `shopping-complex`; **Jiidaa** is the product brand.

## Features

- **GPS vendor discovery** — find vendors near you, ranked by distance, rating, or relevance
- **WhatsApp bot** — an AI-assisted bot lets buyers search for products and reach vendors over WhatsApp
- **Product catalog** — vendors list products with images/video, organised by category
- **Reviews & ratings** — customer reviews with helpful-vote support
- **Payments** — vendor subscriptions via Paystack and Stellar (SEP-24 / on-chain recurring charges)
- **Vendor onboarding** — self-service registration, onboarding flow, and a vendor dashboard
- **Notifications & support** — in-app notifications, email fallback, and a support conversation channel
- **Analytics** — product- and profile-view tracking for vendors and admins
- **Real-time** — live updates over Laravel Reverb (WebSockets)

## Tech Stack

### Backend
- **Laravel 12** — PHP framework
- **MySQL** — database
- **Laravel Reverb** — WebSockets / broadcasting
- **PHPStan** + **Laravel Pint** — static analysis & formatting

### Frontend
- **React 18** — UI library
- **TypeScript** — type safety
- **Inertia.js 2** — SPA without a separate API
- **TailwindCSS 3** — styling

## Architecture

Jiidaa is a **modular monolith** organised into domain-oriented vertical slices. `app/` holds framework
wiring only (providers, middleware, Inertia); all domain code lives in `modules/` under the
`ModulesShoppingComplex\` namespace (PSR-4 → `modules/`). Each domain owns its full stack —
`Models/ Enums/ Services/ Repositories/ Http/ Jobs/ Events/ Listeners/ Policies/` — and domains depend
on one another only through published contracts and events, never by reaching into another domain's
repositories or models.

Domains:

| Domain | Responsibility |
| --- | --- |
| `Shared/` | Cross-cutting kernel — base repository/request, pagination, AI chat clients, helpers |
| `Identity/` | Users, addresses, auth, profiles, vendor registration & onboarding |
| `Catalog/` | Products, categories, attributes |
| `Discovery/` | GPS/geolocation search, nearby-vendor ranking, follow |
| `Media/` | Polymorphic image/video storage (`morphs('model')`) |
| `Reviews/` | Reviews, votes |
| `Support/` | Support conversations, escalation, support bot |
| `Notifications/` | In-app + email notifications and preferences |
| `Analytics/` | Product- and profile-view tracking |
| `Billing/` | Subscriptions and payments (Paystack + Stellar) |
| `WhatsApp/` | WhatsApp API integration and AI bot |

## Prerequisites

- **PHP** >= 8.2
- **Composer** >= 2.0
- **Node.js** >= 18.0 (or **Bun** >= 1.0)
- **MySQL** >= 8.0
- **Git**

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd shopping-complex
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

Using Bun (recommended):
```bash
bun install
```

Or using npm:
```bash
npm install
```

### 4. Environment Setup

Copy the environment file:
```bash
cp .env.example .env
```

Generate application key:
```bash
php artisan key:generate
```

### 5. Database Setup

Update `.env` with your MySQL credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopping_complex
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Create the database:
```bash
mysql -u your_username -p
CREATE DATABASE shopping_complex;
exit;
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Seed Database (Optional)

```bash
php artisan db:seed
```

### 8. Link Storage

Media (product images, avatars) is served from the public disk, so create the symlink:
```bash
php artisan storage:link
```

### 9. Build Frontend Assets

Development mode with hot reload:
```bash
bun run dev   # or: npm run dev
```

Production build:
```bash
bun run build   # or: npm run build
```

## Running the Application

### Option 1: Using the Composer Dev Script (Recommended)

Starts every service at once — Laravel server, queue worker, log tail (Pail), Vite, and Reverb:
```bash
composer dev
```

The application will be available at **http://localhost:8000**.

### Option 2: Manual Start

In separate terminals:

```bash
php artisan serve        # Laravel server
bun run dev              # Vite dev server
php artisan queue:work   # Queue worker (payments, notifications, jobs)
php artisan reverb:start  # WebSocket server (real-time)
```

## Project Structure

```
shopping-complex/
├── app/                         # Framework wiring only
│   ├── Http/                    # Middleware, Inertia request handling
│   └── Providers/               # Service providers (bindings, morph map, events)
├── modules/                     # Domain-oriented modular monolith (ModulesShoppingComplex\)
│   ├── Shared/                  # Cross-cutting kernel (base classes, AI clients, helpers)
│   ├── Identity/                # Users, auth, profiles, vendor onboarding
│   ├── Catalog/                 # Products, categories, attributes
│   ├── Discovery/               # GPS search, nearby vendors, follow
│   ├── Media/                   # Polymorphic media storage
│   ├── Reviews/                 # Reviews & votes
│   ├── Support/                 # Support conversations & bot
│   ├── Notifications/           # Notifications & preferences
│   ├── Analytics/               # Product/profile view tracking
│   ├── Billing/                 # Subscriptions & payments (Paystack, Stellar)
│   └── WhatsApp/                # WhatsApp API + AI bot
│       ├── Models/  Enums/  Services/  Repositories/
│       └── Http/  Jobs/  Events/  Listeners/  Policies/  Contracts/
├── database/
│   ├── migrations/              # Database migrations
│   ├── factories/               # Model factories
│   └── seeders/                 # Database seeders
├── resources/
│   ├── css/app.css              # Global styles
│   ├── ts/                      # TypeScript source
│   │   ├── components/          # React components (incl. Layout/)
│   │   ├── pages/               # Inertia page components
│   │   ├── types/               # Shared TypeScript types
│   │   ├── app.tsx              # React entry point
│   │   └── layouts.tsx          # Layout wrapper
│   └── views/app.blade.php      # Root Blade template
├── routes/
│   ├── web.php                  # Web routes
│   ├── api.php                  # API routes
│   └── channels.php             # Broadcast channel authorization
├── tailwind.config.js
├── tsconfig.json
├── vite.config.js
├── phpstan.neon
└── composer.json
```

## TypeScript Configuration

### Path Aliases

The project uses path aliases for cleaner imports:

```typescript
// Instead of: import { User } from '../../../types/user'
import { User } from '@/types';

import Header from '@/components/Header';
import { BaseLayout } from '@/layouts/BaseLayout';
import { Product } from '@/types';
```

### Configured Aliases

- `@/*` → `resources/ts/*`
- `@/components/*` → `resources/ts/components/*`
- `@/types` → `resources/ts/types`
- `@/layouts/*` → `resources/ts/components/Layout/*`
- `@/pages/*` → `resources/ts/pages/*`

## Code Quality

### PHPStan (static analysis)

```bash
composer phpstan
```

### Laravel Pint (formatting)

```bash
vendor/bin/pint          # format
vendor/bin/pint --dirty  # only changed files
```

### Tests

```bash
php artisan test
```

### TypeScript Type Checking

```bash
bun run tsc --noEmit   # or: npm run tsc --noEmit
```

## Development Workflow

1. **Start dev servers**: `composer dev`
2. **Make changes**:
   - Backend: `modules/<Domain>/`, `app/`, `routes/`
   - Frontend: `resources/ts/`
3. **Hot reload**: Vite reloads the frontend automatically; restart the server for backend changes.
4. **Database changes**:
   ```bash
   php artisan make:migration create_something_table
   php artisan migrate
   ```

## Brand Palette

The rebrand uses the `brand.*` Tailwind tokens (see `tailwind.config.js`):

- **Ink**: `#0B1F3A`
- **Green**: `#25D366` (primary action) / **Green-dark**: `#1EB85A`
- **Surface**: `#F8FAFC`
- **Muted**: `#667085`
- **Line**: `#E4E7EC`
- **Danger**: `#F04438`
- **Star**: `#F5C518`

## Troubleshooting

### Images not displaying

Ensure the storage symlink exists: `php artisan storage:link`.

### Database connection errors

Check MySQL is running:
```bash
sudo service mysql status
sudo service mysql start
```
