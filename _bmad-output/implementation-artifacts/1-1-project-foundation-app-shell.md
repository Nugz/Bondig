# Story 1.1: Project Foundation & App Shell

Status: done

## Story

As a **user**,
I want **the Bondig application set up and accessible**,
So that **I can navigate the app and access its features**.

## Acceptance Criteria

1. **Given** I have the project dependencies installed
   **When** I run `php artisan serve`
   **Then** the application loads at localhost:8000
   **And** I see a navigation bar with links to Dashboard, Upload, Products
   **And** the page uses the Bondig color scheme (teal primary, stone background)
   **And** daisyUI components render correctly

## Tasks / Subtasks

- [x] Task 1: Initialize Laravel 12 project (AC: #1)
  - [x] 1.1: Run `composer create-project laravel/laravel bondig`
  - [x] 1.2: Verify Laravel installation with `php artisan --version`
  - [x] 1.3: Configure SQLite database in `.env`
  - [x] 1.4: Create empty `database/database.sqlite` file
  - [x] 1.5: Set timezone to `Europe/Amsterdam` in `config/app.php`

- [x] Task 2: Install and configure Tailwind CSS + daisyUI (AC: #1)
  - [x] 2.1: Run `npm install -D tailwindcss postcss autoprefixer`
  - [x] 2.2: Run `npx tailwindcss init -p`
  - [x] 2.3: Install daisyUI: `npm install -D daisyui@latest`
  - [x] 2.4: Configure `tailwind.config.js` with content paths and daisyUI plugin
  - [x] 2.5: Set up `resources/css/app.css` with Tailwind directives
  - [x] 2.6: Configure custom theme colors in tailwind.config.js

- [x] Task 3: Install Alpine.js (AC: #1)
  - [x] 3.1: Run `npm install alpinejs`
  - [x] 3.2: Import and initialize Alpine in `resources/js/app.js`
  - [x] 3.3: Ensure Vite bundles Alpine correctly

- [x] Task 4: Install application dependencies (AC: #1)
  - [x] 4.1: Run `composer require spatie/pdf-to-text`
  - [x] 4.2: Run `composer require google-gemini-php/laravel`
  - [x] 4.3: Add `GEMINI_API_KEY` placeholder to `.env.example`

- [x] Task 5: Create app layout with navigation (AC: #1)
  - [x] 5.1: Create `resources/views/layouts/app.blade.php` base layout
  - [x] 5.2: Add top navigation bar with Bondig logo/name
  - [x] 5.3: Add nav links: Dashboard, Upload, Products
  - [x] 5.4: Style with teal primary (`#0D9488`) and stone background (`#FAFAF9`)
  - [x] 5.5: Add flash-messages component for success/warning/error

- [x] Task 6: Create placeholder pages and routes (AC: #1)
  - [x] 6.1: Create `DashboardController` with index method
  - [x] 6.2: Create `resources/views/dashboard/index.blade.php`
  - [x] 6.3: Set up routes in `routes/web.php`
  - [x] 6.4: Add placeholder content to each view
  - [x] 6.5: Verify navigation works between pages

- [x] Task 7: Final verification (AC: #1)
  - [x] 7.1: Run `npm run build` to compile assets
  - [x] 7.2: Run `php artisan serve`
  - [x] 7.3: Verify application loads at localhost:8000
  - [x] 7.4: Verify navigation bar displays correctly
  - [x] 7.5: Verify color scheme is applied
  - [x] 7.6: Verify daisyUI components render (test with a button)

### Review Follow-ups (AI)

- [x] [AI-Review][HIGH] Move Laravel application from `bondig/` subdirectory to project root `/Users/bramladestein/Projects/appiescan` - current structure requires running commands from subdirectory which is not ideal for project workflow
- [x] [AI-Review][LOW] Remove daisyUI Component Test section from dashboard view once styling is confirmed [resources/views/dashboard/index.blade.php:46-62]
- [x] [AI-Review][LOW] Update `.env.example` APP_NAME from "Laravel" to "Bondig" for consistency [.env.example:1]
- [x] [AI-Review][MEDIUM] Remove or document dead `tailwind.config.js` - Tailwind v4 uses CSS-based config in `resources/css/app.css`, JS config is unused [tailwind.config.js]
- [x] [AI-Review][MEDIUM] Investigate CSS build warnings from daisyUI (@property rule, "file" property) - build succeeds but warnings should be addressed [npm run build output]
- [x] [AI-Review][MEDIUM] Add tests for visual AC requirements - color scheme verification, daisyUI component rendering, responsive navigation [tests/Feature/NavigationTest.php]
- [x] [AI-Review][LOW] Update APP_NAME fallback from "Laravel" to "Bondig" in config [config/app.php:16]
- [x] [AI-Review][LOW] Add `.gitkeep` files to empty directories for version control tracking [app/Services/, app/DTOs/, app/Http/Requests/, storage/app/receipts/]

## Dev Notes

### Architecture Compliance

**CRITICAL - Follow these patterns exactly:**

1. **Project Structure** - Create these directories:
   ```
   app/Services/           # Business logic (will be used in later stories)
   app/DTOs/               # Data transfer objects
   app/Http/Requests/      # Form validation requests
   resources/views/components/  # Blade components
   storage/app/receipts/   # PDF uploads (future use)
   ```

2. **No Authentication** - This is a single-user personal tool. Do NOT add any auth middleware or login functionality.

3. **SQLite Only** - Configure `.env`:
   ```
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database/database.sqlite
   ```

4. **Timezone** - Set in `config/app.php`:
   ```php
   'timezone' => 'Europe/Amsterdam',
   ```

### Color System (from UX Spec)

| Role | Color | Hex |
|------|-------|-----|
| Primary | Teal 600 | `#0D9488` |
| Background | Stone 50 | `#FAFAF9` |
| Surface | White | `#FFFFFF` |
| Text | Slate 800 | `#1E293B` |
| Muted | Slate 500 | `#64748B` |

### tailwind.config.js Setup

```javascript
import daisyui from "daisyui"

export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0D9488',
          50: '#F0FDFA',
          100: '#CCFBF1',
          200: '#99F6E4',
          300: '#5EEAD4',
          400: '#2DD4BF',
          500: '#14B8A6',
          600: '#0D9488',
          700: '#0F766E',
          800: '#115E59',
          900: '#134E4A',
        },
      },
    },
  },
  plugins: [daisyui],
  daisyui: {
    themes: [
      {
        bondig: {
          "primary": "#0D9488",
          "secondary": "#475569",
          "accent": "#06B6D4",
          "neutral": "#1E293B",
          "base-100": "#FFFFFF",
          "base-200": "#FAFAF9",
          "info": "#0EA5E9",
          "success": "#10B981",
          "warning": "#F59E0B",
          "error": "#F43F5E",
        },
      },
    ],
  },
}
```

### resources/css/app.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### resources/js/app.js

```javascript
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

### Layout Template Structure

The `layouts/app.blade.php` should include:
- DOCTYPE and html with `data-theme="bondig"` for daisyUI
- Head with Vite CSS/JS includes
- Body with stone-50 background
- Top navigation bar (navbar component from daisyUI)
- Main content area with container
- Flash messages component
- Footer (optional)

### Navigation Bar Requirements

- Logo/brand "Bondig" on left (teal color)
- Nav items: Dashboard, Upload, Products
- Active state: teal underline or highlighted
- Responsive: horizontal on desktop, hamburger on mobile

### Flash Messages Component

Create `resources/views/components/flash-messages.blade.php`:
- Check for `session('success')`, `session('warning')`, `session('error')`
- Use daisyUI alert classes: `alert-success`, `alert-warning`, `alert-error`
- Auto-dismiss success messages after 4 seconds (Alpine.js)

### Routes Setup

```php
// routes/web.php
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
```

### Project Structure Notes

The foundation must align with the architecture document. Key directories to create now (even if empty):
- `app/Services/` - Will contain ReceiptParsingService, CategorizationService, etc.
- `app/DTOs/` - Will contain ParseResult, ParsedLine, SpendingSummary
- `resources/views/components/` - For Blade components

### Font Configuration

Use Inter font family with system fallback:
```css
/* In app.css or tailwind config */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
```

Update tailwind.config.js:
```javascript
theme: {
  extend: {
    fontFamily: {
      sans: ['Inter', 'system-ui', 'sans-serif'],
    },
  },
},
```

### References

- [Source: architecture.md#Starter Template Evaluation] - Laravel 12 + Tailwind selection
- [Source: architecture.md#Implementation Patterns] - Naming conventions, service pattern
- [Source: architecture.md#Project Structure] - Complete directory structure
- [Source: ux-design-specification.md#Design System Foundation] - daisyUI, color system
- [Source: ux-design-specification.md#Visual Design Foundation] - Typography, spacing
- [Source: project-context.md] - Critical implementation rules

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References

None

### Completion Notes List

- Created Laravel 12.44.0 project with SQLite database configured
- Configured Tailwind CSS v4 with daisyUI v5 using CSS-based configuration
- Created custom "bondig" daisyUI theme with teal primary (#0D9488) and stone background (#FAFAF9)
- Installed Alpine.js and configured in app.js
- Installed spatie/pdf-to-text and google-gemini-php/laravel packages
- Created responsive app layout with daisyUI navbar component
- Created flash-messages component with auto-dismiss (4s) for success, manual dismiss for warning/error
- Created placeholder pages for Dashboard, Upload, and Products
- Created DashboardController, UploadController, and ProductController
- Set up routes at /, /upload, /products
- Added NavigationTest.php with 4 passing tests (6 total tests pass)
- All acceptance criteria satisfied
- ✅ Resolved review finding [HIGH]: Moved Laravel application from bondig/ subdirectory to project root for improved workflow
- ✅ Resolved review finding [LOW]: Removed daisyUI Component Test section from dashboard view
- ✅ Resolved review finding [LOW]: Updated .env.example APP_NAME from "Laravel" to "Bondig"
- ✅ Resolved review finding [MEDIUM]: Removed dead tailwind.config.js (Tailwind v4 uses CSS-based config)
- ✅ Resolved review finding [MEDIUM]: Documented daisyUI build warnings as expected/safe in app.css
- ✅ Resolved review finding [MEDIUM]: Added 6 visual AC tests for theme, navbar, colors, background, and responsive navigation
- ✅ Resolved review finding [LOW]: Updated APP_NAME fallback from "Laravel" to "Bondig" in config/app.php
- ✅ Resolved review finding [LOW]: Added .gitkeep files to app/Services/, app/DTOs/, app/Http/Requests/, storage/app/receipts/

### File List

**New Files (now at project root):**
- .env
- .env.example
- config/app.php (modified timezone)
- database/database.sqlite (SQLite database file)
- resources/css/app.css
- resources/js/app.js
- resources/views/components/layouts/app.blade.php
- resources/views/components/flash-messages.blade.php
- resources/views/dashboard/index.blade.php
- resources/views/upload/index.blade.php
- resources/views/products/index.blade.php
- app/Http/Controllers/DashboardController.php
- app/Http/Controllers/UploadController.php
- app/Http/Controllers/ProductController.php
- app/Services/ (empty directory)
- app/DTOs/ (empty directory)
- routes/web.php
- tests/Feature/NavigationTest.php

**Modified Files (review follow-up):**
- resources/views/dashboard/index.blade.php (removed daisyUI test section)
- .env.example (updated APP_NAME to Bondig)
- resources/css/app.css (added documentation comment for build warnings)
- config/app.php (updated APP_NAME fallback to Bondig)
- tests/Feature/NavigationTest.php (added 6 visual AC tests)

**Deleted Files (review follow-up):**
- tailwind.config.js (unused Tailwind v3 config, v4 uses CSS-based config)

**New Files (review follow-up):**
- app/Services/.gitkeep
- app/DTOs/.gitkeep
- app/Http/Requests/.gitkeep
- storage/app/receipts/.gitkeep

## Change Log

- 2026-01-02: Story implementation completed - all tasks done, 6 tests passing
- 2026-01-03: Code review completed - 1 HIGH, 2 LOW action items added. Status → in-progress pending Laravel app relocation to project root
- 2026-01-03: Addressed code review findings - 3 items resolved (1 HIGH, 2 LOW). Moved Laravel app to project root, cleaned up dashboard view, fixed .env.example APP_NAME. All 6 tests passing. Status → review
- 2026-01-03: Second code review - Git initialized by user. Added 5 new action items (3 MEDIUM, 2 LOW): dead tailwind.config.js, CSS build warnings, visual test coverage, config fallback, .gitkeep files. Status → in-progress
- 2026-01-03: Addressed second code review findings - 5 items resolved (3 MEDIUM, 2 LOW). Removed dead tailwind.config.js, documented CSS warnings, added 6 visual AC tests (11 total tests now), updated config fallback, added .gitkeep files. All 11 tests passing. Status → review
- 2026-01-03: Third code review - Minor documentation issues found. Added database.sqlite to File List. Navigation active state verified as correct (uses highlighted bg-primary/10 per Dev Notes "highlighted" option). All ACs implemented, 11 tests passing. Status → done

