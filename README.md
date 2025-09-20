# Repo “About” (paste in GitHub → About)
Tiny Laravel + Vue expense splitter. Create groups, add members & expenses (JPY), see equal-split balances + suggested settlements, and export JSON. Built to learn.

---

# README.md

## Expense Splitter (Laravel + Vue)
Tiny app to split group expenses **equally**. Backend: Laravel 11 + SQLite. Frontend: Vue 3 (Vite). 

### Features
- Groups → Members → Expenses (CRUD)
- Equal-split **balances** + suggested **settlements**
- **Export JSON** (balances & expenses)
- Seed data: “Kyoto Trip”, “Roommates”

### Tech
Laravel 11, SQLite, Vue 3, vue-router, axios, Vite  
Core logic: `app/Services/BalanceCalculator.php`

### Quick Start
    # deps
    composer install
    npm install

    # env + sqlite
    cp .env.example .env
    mkdir -p database && touch database/database.sqlite
    # in .env set:
    # DB_CONNECTION=sqlite
    # DB_DATABASE=/ABSOLUTE/PATH/TO/project/database/database.sqlite

    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed

    # run (two terminals)
    npm run dev
    php artisan serve
    # open http://127.0.0.1:8000

If you see “Vite manifest not found”, make sure `npm run dev` is running.

### API (reference)
    GET  /api/groups
    GET  /api/groups/{group}
    GET  /api/groups/{group}/expenses
    POST /api/groups/{group}/members     { name }
    POST /api/groups/{group}/expenses    { payer_id, description, amount, spent_at? }
    GET  /api/groups/{group}/balances

SPA catch-all excludes `/api/*` in `routes/web.php`:
`Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');`

### Project Layout (short)
    app/Http/Controllers/Api/...
    app/Services/BalanceCalculator.php
    resources/views/app.blade.php
    resources/js/{app.js,App.vue,router.js,api.js}
    resources/js/views/{GroupsIndex.vue,GroupShow.vue}
    database/seeders/DemoDataSeeder.php
