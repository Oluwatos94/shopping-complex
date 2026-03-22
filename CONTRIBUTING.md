# Contributing to Shopping Complex

Thank you for your interest in contributing! See [development.md](development.md) for the full development plan.

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Bun (JavaScript runtime)
- MySQL/PostgreSQL
- Git

### Setup
```bash
git clone https://github.com/your-org/shopping-complex.git
cd shopping-complex
composer install
bun install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## 📋 Available Issues

Check the [Issues](https://github.com/your-org/shopping-complex/issues) page for tasks to contribute to. Issues are labeled by:
- **Difficulty**: good-first-issue, ⭐⭐, ⭐⭐⭐, etc.
- **Type**: backend, frontend, Inertia, UI, etc.
- **Phase**: Phase 1-5

## 🔀 Contribution Workflow

1. Comment on an issue to claim it
2. Create branch: `git checkout -b feature/issue-number-description`
3. Develop with tests
4. Commit using conventional commits
5. Push and create PR
6. Address review comments

## ✅ Code Quality

- **Backend**: PSR-12, PHPDoc, 80% coverage
- **Frontend**: ESLint, Prettier, TypeScript strict

Run tests: `php artisan test` and `bun test`
