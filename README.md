# Shopping Complex

A modern marketplace platform connecting customers with vendors in real-time. Built with Laravel 11, React, TypeScript, and Inertia.js.

## Features

- 🛍️ **Real-time vendor connections** - Connect with vendors instantly like Uber/Bolt
- **Category-based browsing** - Browse vendors by product categories
- **Live chat** - Direct messaging between customers and vendors
- **Order tracking** - Real-time order status updates
- **Review system** - Customer reviews and ratings
- **Modern UI** - Responsive design with TailwindCSS

## Tech Stack

### Backend
- **Laravel 11** - PHP framework
- **MySQL** - Database

### Frontend
- **React 18** - UI library
- **TypeScript** - Type safety
- **Inertia.js** - SPA without API
- **TailwindCSS** - Styling

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

#### Option A: MySQL (Recommended for Production)

Update `.env` file with your MySQL credentials:
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
# MySQL command line
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

### 8. Build Frontend Assets

Development mode with hot reload:
```bash
bun run dev
# or
npm run dev
```

Production build:
```bash
bun run build
# or
npm run build
```

## Running the Application

### Option 1: Using Composer Dev Script (Recommended)

This starts all services (Laravel server, Vite, queue worker, and logs):
```bash
composer dev
```

The application will be available at: **http://localhost:8000**

### Option 2: Manual Start

In separate terminal windows:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Vite Dev Server:**
```bash
bun run dev
```

**Terminal 3 - Queue Worker (Optional):**
```bash
php artisan queue:work
```

## Project Structure

```
shopping-complex/
├── app/
│   └── Http/
│       ├── Controllers/      # Controllers
│       └── Middleware/        # Middleware
├── modules/                   # Modular architecture
│   ├── User/
│   │   ├── Models/           # User model
│   │   ├── Controllers/      # User controllers
│   │   ├── Services/         # Business logic
│   │   └── Repositories/     # Data access
│   ├── Product/
│   ├── Order/
│   ├── Customer/
│   ├── Review/
│   ├── Category/
│   ├── Media/
│   └── Notification/
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── resources/
│   ├── css/
│   │   └── app.css           # Global styles
│   ├── ts/                   # TypeScript source
│   │   ├── components/       # React components
│   │   │   ├── Layout/       # Layout components
│   │   │   ├── Header.tsx
│   │   │   ├── Footer.tsx
│   │   │   └── ...
│   │   ├── pages/            # Page components
│   │   │   └── index.tsx     # Home page
│   │   ├── types/            # TypeScript types
│   │   │   ├── index.ts      # Central export
│   │   │   ├── user.ts       # User types
│   │   │   ├── product.ts    # Product types
│   │   │   ├── common.ts     # Shared types
│   │   │   └── landing.ts    # Landing types
│   │   ├── app.tsx           # React entry point
│   │   └── layouts.tsx       # Layout wrapper
│   └── views/
│       └── app.blade.php     # Main Blade template
├── routes/
│   ├── web.php               # Web routes
│   └── api.php               # API routes
├── public/                   # Public assets
├── tailwind.config.js        # Tailwind configuration
├── tsconfig.json             # TypeScript configuration
├── vite.config.js            # Vite configuration
├── phpstan.neon              # PHPStan configuration
└── composer.json             # PHP dependencies
```

## TypeScript Configuration

### Path Aliases

The project uses path aliases for cleaner imports:

```typescript
// Instead of: import { User } from '../../../types/user'
import { User } from '@/types';

// Available aliases:
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

### Run PHPStan

```bash
composer phpstan
```

### TypeScript Type Checking

```bash
bun run tsc --noEmit
# or
npm run tsc --noEmit
```

## Development Workflow

### 1. Start Development Servers

```bash
composer dev
```

### 2. Make Changes

- **Backend**: Edit files in `app/`, `modules/`, `routes/`
- **Frontend**: Edit files in `resources/ts/`

### 3. Hot Reload

Vite will automatically reload changes. For backend changes, restart the server.

### 4. Database Changes

Create migration:
```bash
php artisan make:migration create_something_table
```

Run migrations:
```bash
php artisan migrate
```
## Color Scheme

The application uses a custom color palette:

- **Primary Olive**: `#86885e`
- **Primary Dark**: `#272518`
- **Primary Light**: `#cacfca`
- **Primary Brown**: `#523026`
- **Primary Peach**: `#d49f89`

### Database connection errors

Check MySQL is running:
```bash
sudo service mysql status
sudo service mysql start
```
