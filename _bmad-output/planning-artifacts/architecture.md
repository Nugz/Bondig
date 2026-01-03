---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
status: 'complete'
completedAt: '2026-01-02'
inputDocuments:
  - "_bmad-output/planning-artifacts/product-brief-bondig-2026-01-02.md"
  - "_bmad-output/planning-artifacts/prd.md"
  - "_bmad-output/planning-artifacts/research/technical-bondig-pdf-ai-research-2026-01-02.md"
workflowType: 'architecture'
project_name: 'Bondig'
user_name: 'Bram'
date: '2026-01-02'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
40 requirements across 10 capability areas:
- Receipt Ingestion (4 FRs): PDF upload, batch processing, manual entry, duplicate detection
- Data Parsing (7 FRs): Extract products, quantities, prices, dates, totals, discounts, debug view
- Product Categorization (6 FRs): AI auto-categorization, confidence display, override, learning, review workflow
- Product Management (4 FRs): View, merge, edit products, purchase history
- Spending Analytics (6 FRs): Time ranges, category breakdown, drill-down, comparison, trends, store filter
- Price Tracking (3 FRs): Track changes, view history, identify increases
- Budget Management (4 FRs): Set target, view progress, remaining budget, history
- Data Correction (4 FRs): Edit, delete, add line items, delete receipts
- System Administration (2 FRs): Manage categories, view import history

**Non-Functional Requirements:**
- Performance: Page load < 2s, PDF parsing < 5s, AI < 10s batch, queries < 1s
- Data Integrity: No silent loss, exportable backups, audit trail, atomic imports
- External Dependencies: Gemini API with graceful degradation and retry queue
- Security: Minimal - private hosting, no auth required

**Scale & Complexity:**
- Primary domain: Web application (Laravel MPA)
- Complexity level: Low
- Estimated architectural components: ~8-10 (receipts, products, categories, analytics, budget, parsing, AI integration, file handling)

### Technical Constraints & Dependencies

| Constraint | Impact |
|------------|--------|
| Single user | No auth system, no multi-tenancy overhead |
| Private hosting | No SSL complexity, no public API exposure |
| AH PDF format | Text-based (no OCR), consistent structure |
| Gemini free tier | 1000 req/day limit, batch requests to conserve |
| Personal tool | Can optimize for simplicity over scalability |

### Cross-Cutting Concerns Identified

| Concern | Affects | Architectural Implication |
|---------|---------|---------------------------|
| Product Identity | Parsing, categorization, analytics, price tracking | Need robust product matching/merging strategy |
| Error Visibility | Parsing, categorization, imports | Errors must surface, never silently fail |
| Category Taxonomy | Categorization, analytics, budget | Consistent category model across all features |
| Original Data Audit | Parsing, correction | Store raw parsed text for debugging |

## Starter Template Evaluation

### Primary Technology Domain

Laravel 12 MPA (Multi-Page Application) based on PRD requirements analysis.

### Starter Options Considered

| Option | Pros | Cons | Decision |
|--------|------|------|----------|
| Bare Laravel 12 | Clean, no unused code, full control | Manual setup of styling | Selected |
| Laravel Breeze | Pre-built Tailwind + Alpine, auth ready | Includes auth we don't need | Rejected |
| TALL Stack Preset | Full reactive stack | More than needed for MPA | Rejected |
| Jetstream | Complete auth + teams | Massive overkill | Rejected |

### Selected Starter: Bare Laravel 12 with Tailwind CSS

**Rationale for Selection:**
- Bondig requires NO authentication (single-user personal tool)
- MPA architecture doesn't need SPA scaffolding
- Tailwind can be added with 2 commands
- Keeps codebase minimal and focused
- Full control over project structure

**Initialization Commands:**

```bash
# Create new Laravel project
composer create-project laravel/laravel bondig

# Navigate to project
cd bondig

# Install Tailwind CSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Install application dependencies
composer require spatie/pdf-to-text
composer require google-gemini-php/laravel

# Optional: Add Livewire for interactive components
composer require livewire/livewire
```

### Architectural Decisions Provided by Starter

**Language & Runtime:**
- PHP 8.2+ with Laravel 12
- Node.js for frontend asset compilation (Vite)

**Styling Solution:**
- Tailwind CSS via Vite
- PostCSS for processing

**Build Tooling:**
- Vite for asset bundling
- Laravel Mix deprecated in favor of Vite

**Testing Framework:**
- PHPUnit (included with Laravel)
- Pest can be added if preferred

**Code Organization:**
- Laravel's standard MVC structure
- app/Models, app/Http/Controllers, resources/views

**Development Experience:**
- `php artisan serve` for local development
- Vite hot reloading for CSS/JS changes
- Laravel Telescope available for debugging

**Note:** Project initialization using these commands should be the first implementation story.

## Core Architectural Decisions

### Decision Summary

| Category | Decision | Choice | Rationale |
|----------|----------|--------|-----------|
| Database | Engine | SQLite | Single-user, easy backup, zero config |
| Database | Caching | Database (products table) | Natural product catalog, minimizes API calls |
| Database | Product Matching | Normalized + manual merge | Covers 80% automatically, UI for edge cases |
| Security | API Key Storage | .env file | Laravel standard, not committed to git |
| Errors | Handling Strategy | Flash + database log | Immediate feedback + historical tracking |
| Frontend | Interactivity | Alpine.js only | Lightweight, sufficient for MPA needs |
| Frontend | Charts | Chart.js | Well-supported, covers all chart types |
| Dev | Local Development | artisan serve | Zero setup, sufficient for personal project |
| Hosting | Production | Local machine | Private hosting, no external dependencies |

### Data Architecture

**Database Engine: SQLite**
- Single file database, trivial to backup (copy the file)
- No server process to manage
- Sufficient performance for single-user workload
- Location: `database/database.sqlite`

**Caching Strategy: Database-backed Product Catalog**
- Products table stores: `name`, `normalized_name`, `category_id`, `confidence`, `user_confirmed`
- On receipt parse: check if `normalized_name` exists before calling Gemini API
- Only new/unknown products trigger AI categorization
- Dramatically reduces API calls after initial data load

**Product Matching: Normalized Match + Manual Merge**
- Normalization: lowercase, trim whitespace, collapse multiple spaces
- Automatic matching on normalized name
- Manual merge UI (FR19) for edge cases like "BEEMSTER 48+" vs "BEEMSTER 48+ KAAS"
- Merge operation updates all historical line items to reference canonical product

### Authentication & Security

**Authentication: None**
- Single-user personal tool
- Access controlled by host machine access
- No user accounts, sessions, or login flows

**API Key Storage: Environment File**
- Gemini API key stored in `.env`
- Referenced via `config('services.gemini.key')`
- `.env` excluded from version control via `.gitignore`

### Error Handling

**Strategy: Flash Messages + Database Logging**

*Immediate Feedback (Flash Messages):*
- Parsing errors shown immediately after upload
- Category review prompts for low-confidence items
- Validation errors on forms

*Historical Tracking (Database Log):*
- `import_logs` table: receipt imports with status, error count
- `parsing_errors` table: individual line item issues with original text
- Supports FR40: view parsing/import history
- Enables pattern detection for recurring parse issues

### Frontend Architecture

**Interactivity: Alpine.js**
- Inline category dropdowns for quick selection
- Modal dialogs for product merge, receipt deletion confirmation
- Collapsible sections on dashboard
- No build step required - CDN or npm install

**Charts: Chart.js**
- Category breakdown: Doughnut/pie chart
- Spending trends: Line chart over time
- Budget progress: Bar chart (actual vs target)
- Loaded via CDN or npm, rendered in Blade templates

### Infrastructure & Deployment

**Local Development:**
```bash
php artisan serve    # http://localhost:8000
npm run dev          # Vite for CSS/JS hot reload
```

**Production Hosting: Local Machine**
- Run on personal computer or home server/NAS
- Access via localhost or local network IP
- No external hosting costs or dependencies
- Easy migration to VPS later if needed

### Decision Impact Analysis

**Implementation Sequence:**
1. Project initialization (Laravel + Tailwind + Alpine)
2. Database schema (SQLite + migrations)
3. Core models (Receipt, Product, Category, LineItem)
4. PDF parsing service
5. AI categorization service with caching
6. Receipt upload + parsing flow
7. Category review UI
8. Dashboard + analytics
9. Budget management

**Cross-Component Dependencies:**
- Product matching affects: parsing, categorization, analytics, price tracking
- Error logging affects: parsing, imports, admin views
- Category taxonomy affects: categorization, analytics, budget breakdown

## Implementation Patterns & Consistency Rules

### Pattern Summary

| Category | Pattern | Decision |
|----------|---------|----------|
| Business Logic | Service Classes | `app/Services/` with domain-focused services |
| Views | Feature-based + Components | Nested by feature, reusable Blade components |
| Validation | FormRequest for complex | FormRequest classes for complex forms, inline for simple |
| Dates | Carbon + Local TZ | Store as datetime, Carbon for logic, Europe/Amsterdam |
| Naming | Laravel Defaults | snake_case tables/columns, PascalCase models |

### Naming Conventions

**Database Naming:**
```
Tables:     snake_case, plural     → receipts, line_items, categories, products
Columns:    snake_case             → product_id, normalized_name, created_at
Foreign Keys: {table}_id           → category_id, product_id, receipt_id
Indexes:    {table}_{column}_index → products_normalized_name_index
```

**Code Naming:**
```
Models:      PascalCase, singular  → Receipt, LineItem, Category, Product
Controllers: PascalCase + suffix   → ReceiptController, DashboardController
Services:    PascalCase + suffix   → ReceiptParsingService, CategorizationService
Requests:    PascalCase + prefix   → StoreReceiptRequest, UpdateProductRequest
```

**Route Naming:**
```
URLs:        kebab-case            → /receipts, /category-review, /price-trends
Route names: dot notation          → receipts.index, receipts.store, dashboard.show
```

**View Naming:**
```
Views:       kebab-case in folders → receipts/index.blade.php, receipts/show.blade.php
Components:  kebab-case            → components/receipt-card.blade.php, components/category-badge.blade.php
Layouts:     kebab-case            → layouts/app.blade.php
```

### Structure Patterns

**Service Layer Organization:**
```
app/Services/
├── ReceiptParsingService.php    # PDF text extraction + line parsing
├── CategorizationService.php    # Gemini API + caching logic
├── AnalyticsService.php         # Spending calculations + trends
└── ProductMatchingService.php   # Normalization + merge logic
```

**View Organization:**
```
resources/views/
├── layouts/
│   └── app.blade.php
├── components/
│   ├── receipt-card.blade.php
│   ├── category-badge.blade.php
│   ├── price-display.blade.php
│   └── chart-container.blade.php
├── receipts/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── create.blade.php         # Manual entry
│   └── upload.blade.php         # PDF upload
├── products/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── merge.blade.php
├── dashboard/
│   └── index.blade.php
├── categories/
│   ├── index.blade.php
│   └── review.blade.php         # Low-confidence items
└── budget/
    └── index.blade.php
```

### Format Patterns

**Controller Response Pattern:**
```php
// List view - return view with paginated data
public function index()
{
    $receipts = Receipt::latest('purchased_at')->paginate(20);
    return view('receipts.index', compact('receipts'));
}

// Form submission - redirect with flash message
public function store(StoreReceiptRequest $request)
{
    $receipt = $this->receiptService->createFromUpload($request->file('pdf'));
    return redirect()->route('receipts.show', $receipt)
        ->with('success', 'Receipt imported successfully');
}
```

**Error Flash Pattern:**
```php
// Success
return redirect()->back()->with('success', 'Product category updated');

// Warning (non-blocking)
return redirect()->back()->with('warning', '3 items need category review');

// Error
return redirect()->back()->with('error', 'Failed to parse receipt: invalid format');
```

**Date Handling Pattern:**
```php
// Model: Cast to datetime
protected $casts = [
    'purchased_at' => 'datetime',
];

// Service: Use Carbon for logic
$startOfMonth = Carbon::now()->startOfMonth();
$receipts = Receipt::where('purchased_at', '>=', $startOfMonth)->get();

// Blade: Format for display
{{ $receipt->purchased_at->format('d M Y H:i') }}
{{ $receipt->purchased_at->diffForHumans() }}

// Timezone: Set in config/app.php
'timezone' => 'Europe/Amsterdam',
```

### Process Patterns

**Service Method Pattern:**
```php
class ReceiptParsingService
{
    public function parseFromPdf(UploadedFile $file): ParseResult
    {
        // 1. Extract text
        $text = Pdf::getText($file->path());

        // 2. Parse into structured data
        $lines = $this->parseLines($text);

        // 3. Return result object (not model)
        return new ParseResult(
            lines: $lines,
            total: $this->extractTotal($text),
            date: $this->extractDate($text),
            errors: $this->errors
        );
    }
}
```

**Error Logging Pattern:**
```php
// Log parsing errors to database for review
ImportLog::create([
    'receipt_id' => $receipt->id,
    'status' => 'completed_with_errors',
    'error_count' => count($errors),
    'errors' => json_encode($errors),  // Store details
]);

// Also flash for immediate feedback
session()->flash('warning', count($errors) . ' items could not be parsed');
```

**Product Matching Pattern:**
```php
class ProductMatchingService
{
    public function normalize(string $name): string
    {
        return strtolower(
            preg_replace('/\s+/', ' ', trim($name))
        );
    }

    public function findOrCreate(string $rawName): Product
    {
        $normalized = $this->normalize($rawName);

        return Product::firstOrCreate(
            ['normalized_name' => $normalized],
            ['name' => $rawName, 'category_id' => null]
        );
    }
}
```

### Enforcement Guidelines

**All AI Agents MUST:**
1. Follow Laravel naming conventions exactly (snake_case DB, PascalCase PHP)
2. Place business logic in Service classes, not controllers
3. Use FormRequest for any form with 3+ fields
4. Store dates as datetime, display with Carbon formatting
5. Flash errors/success messages, never return raw error pages
6. Log all import/parsing errors to database

**Anti-Patterns to Avoid:**
```php
// DON'T: Logic in controller
public function store(Request $request) {
    $text = Pdf::getText($request->file('pdf')->path());
    // ... 50 lines of parsing logic
}

// DO: Delegate to service
public function store(Request $request) {
    $result = $this->parsingService->parseFromPdf($request->file('pdf'));
    // ... handle result
}

// DON'T: camelCase in database
Schema::create('lineItems', ...);  // Wrong

// DO: snake_case in database
Schema::create('line_items', ...);  // Correct

// DON'T: Silent failures
if (!$parsed) return;  // User has no idea what happened

// DO: Visible errors
if (!$parsed) {
    Log::warning('Parse failed', ['file' => $file->name]);
    return back()->with('error', 'Could not parse receipt');
}
```

## Project Structure & Boundaries

### Complete Project Directory Structure

```
bondig/
├── .env                          # Environment variables (Gemini API key)
├── .env.example                  # Template for environment setup
├── .gitignore
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
├── phpunit.xml
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php      # FR22-27: Analytics views
│   │   │   ├── ReceiptController.php        # FR1-4: Upload, list, show, delete
│   │   │   ├── ProductController.php        # FR18-21: Product management, merge
│   │   │   ├── CategoryController.php       # FR39: Manage categories
│   │   │   ├── BudgetController.php         # FR31-34: Budget CRUD
│   │   │   ├── LineItemController.php       # FR35-37: Edit/delete line items
│   │   │   └── ImportLogController.php      # FR40: View import history
│   │   │
│   │   └── Requests/
│   │       ├── StoreReceiptRequest.php      # Manual receipt validation
│   │       ├── UpdateProductRequest.php     # Product edit validation
│   │       ├── MergeProductsRequest.php     # Product merge validation
│   │       └── StoreBudgetRequest.php       # Budget validation
│   │
│   ├── Models/
│   │   ├── Receipt.php                      # Receipt with line items relation
│   │   ├── LineItem.php                     # Individual purchase line
│   │   ├── Product.php                      # Canonical product with category
│   │   ├── Category.php                     # Spending category
│   │   ├── Budget.php                       # Monthly budget target
│   │   └── ImportLog.php                    # Import audit trail
│   │
│   ├── Services/
│   │   ├── ReceiptParsingService.php        # PDF extraction + line parsing
│   │   ├── CategorizationService.php        # Gemini API + caching
│   │   ├── ProductMatchingService.php       # Normalization + find/create
│   │   └── AnalyticsService.php             # Spending calculations + trends
│   │
│   ├── DTOs/
│   │   ├── ParseResult.php                  # Parsing result object
│   │   ├── ParsedLine.php                   # Single parsed line item
│   │   └── SpendingSummary.php              # Analytics summary object
│   │
│   └── Providers/
│       └── AppServiceProvider.php           # Service bindings
│
├── config/
│   ├── app.php                              # Timezone: Europe/Amsterdam
│   └── services.php                         # Gemini API configuration
│
├── database/
│   ├── database.sqlite                      # SQLite database file
│   ├── migrations/
│   │   ├── 0001_create_categories_table.php
│   │   ├── 0002_create_products_table.php
│   │   ├── 0003_create_receipts_table.php
│   │   ├── 0004_create_line_items_table.php
│   │   ├── 0005_create_budgets_table.php
│   │   └── 0006_create_import_logs_table.php
│   │
│   └── seeders/
│       └── CategorySeeder.php               # Default category taxonomy
│
├── resources/
│   ├── css/
│   │   └── app.css                          # Tailwind imports
│   │
│   ├── js/
│   │   └── app.js                           # Alpine.js + Chart.js setup
│   │
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php                # Main layout with nav
│       │
│       ├── components/
│       │   ├── receipt-card.blade.php       # Receipt summary card
│       │   ├── category-badge.blade.php     # Colored category pill
│       │   ├── price-display.blade.php      # Formatted price with delta
│       │   ├── flash-messages.blade.php     # Success/warning/error alerts
│       │   ├── category-dropdown.blade.php  # Alpine-powered selector
│       │   └── chart-container.blade.php    # Chart.js wrapper
│       │
│       ├── dashboard/
│       │   └── index.blade.php              # Main analytics dashboard
│       │
│       ├── receipts/
│       │   ├── index.blade.php              # Receipt list with filters
│       │   ├── show.blade.php               # Receipt detail + line items
│       │   ├── upload.blade.php             # PDF upload form (single + batch)
│       │   └── create.blade.php             # Manual receipt entry form
│       │
│       ├── products/
│       │   ├── index.blade.php              # Product catalog
│       │   ├── show.blade.php               # Product detail + price history
│       │   └── merge.blade.php              # Product merge interface
│       │
│       ├── categories/
│       │   ├── index.blade.php              # Category management
│       │   └── review.blade.php             # Low-confidence item review
│       │
│       ├── budget/
│       │   └── index.blade.php              # Budget overview + history
│       │
│       └── admin/
│           └── import-logs.blade.php        # Import history + errors
│
├── routes/
│   └── web.php                              # All application routes
│
├── storage/
│   └── app/
│       └── receipts/                        # Uploaded PDF storage
│
├── tests/
│   ├── Feature/
│   │   ├── ReceiptUploadTest.php
│   │   ├── CategorizationTest.php
│   │   ├── ProductMergeTest.php
│   │   └── AnalyticsTest.php
│   │
│   └── Unit/
│       ├── ReceiptParsingServiceTest.php
│       ├── ProductMatchingServiceTest.php
│       └── AnalyticsServiceTest.php
│
└── public/
    └── build/                               # Vite compiled assets
```

### Architectural Boundaries

**Controller Boundaries:**
Controllers handle HTTP concerns only - validation, routing, view rendering. All business logic delegated to Services.

| Controller | Responsibility | Services Used |
|------------|----------------|---------------|
| `DashboardController` | Analytics views, date range handling | `AnalyticsService` |
| `ReceiptController` | Upload, CRUD, batch processing | `ReceiptParsingService`, `ProductMatchingService` |
| `ProductController` | Product management, merge operations | `ProductMatchingService`, `CategorizationService` |
| `CategoryController` | Category CRUD | None (simple CRUD) |
| `BudgetController` | Budget CRUD, progress calculation | `AnalyticsService` |

**Service Boundaries:**

| Service | Responsibility | Dependencies |
|---------|----------------|--------------|
| `ReceiptParsingService` | PDF text extraction, line parsing, date/total extraction | spatie/pdf-to-text |
| `CategorizationService` | Gemini API calls, response caching, confidence scoring | google-gemini-php/laravel |
| `ProductMatchingService` | Name normalization, product lookup/create, merge logic | None |
| `AnalyticsService` | Spending aggregation, trends, budget calculations | None |

**Data Flow:**

```
PDF Upload → ReceiptController
    ↓
ReceiptParsingService (extract text, parse lines)
    ↓
ProductMatchingService (normalize names, find/create products)
    ↓
CategorizationService (if new product → Gemini API)
    ↓
Database (Receipt, LineItems, Products)
    ↓
AnalyticsService (aggregation queries)
    ↓
Dashboard View
```

### Database Schema Overview

```
categories
├── id
├── name
├── color (hex for UI badges)
└── timestamps

products
├── id
├── name (original from first occurrence)
├── normalized_name (lowercase, trimmed)
├── category_id (FK → categories)
├── confidence (0.0-1.0 from AI)
├── user_confirmed (boolean)
└── timestamps

receipts
├── id
├── store (default: "Albert Heijn")
├── purchased_at (datetime)
├── total_amount (decimal)
├── pdf_path (storage path or null)
├── raw_text (original parsed text for debug)
└── timestamps

line_items
├── id
├── receipt_id (FK → receipts)
├── product_id (FK → products)
├── quantity (integer)
├── unit_price (decimal)
├── total_price (decimal)
├── is_bonus (boolean)
├── raw_text (original line for debug)
└── timestamps

budgets
├── id
├── month (date, first of month)
├── target_amount (decimal)
└── timestamps

import_logs
├── id
├── receipt_id (FK → receipts, nullable)
├── filename
├── status (success, partial, failed)
├── error_count
├── errors (JSON)
└── timestamps
```

### Routes Overview

```php
// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Receipts
Route::resource('receipts', ReceiptController::class);
Route::get('receipts/upload', [ReceiptController::class, 'showUpload'])->name('receipts.upload');
Route::post('receipts/upload', [ReceiptController::class, 'upload'])->name('receipts.upload.store');

// Products
Route::resource('products', ProductController::class)->except(['create', 'store']);
Route::get('products/{product}/merge', [ProductController::class, 'showMerge'])->name('products.merge');
Route::post('products/{product}/merge', [ProductController::class, 'merge'])->name('products.merge.store');

// Categories
Route::resource('categories', CategoryController::class);
Route::get('categories/review', [CategoryController::class, 'review'])->name('categories.review');

// Budget
Route::resource('budgets', BudgetController::class)->except(['show']);

// Line Items (nested under receipts)
Route::patch('receipts/{receipt}/line-items/{lineItem}', [LineItemController::class, 'update']);
Route::delete('receipts/{receipt}/line-items/{lineItem}', [LineItemController::class, 'destroy']);

// Admin
Route::get('admin/import-logs', [ImportLogController::class, 'index'])->name('admin.import-logs');
```

### Default Category Taxonomy

```php
// database/seeders/CategorySeeder.php
$categories = [
    ['name' => 'Dairy', 'color' => '#3B82F6'],           // Blue
    ['name' => 'Meat & Fish', 'color' => '#EF4444'],     // Red
    ['name' => 'Produce', 'color' => '#22C55E'],         // Green
    ['name' => 'Bread & Bakery', 'color' => '#F59E0B'],  // Amber
    ['name' => 'Beverages', 'color' => '#06B6D4'],       // Cyan
    ['name' => 'Snacks', 'color' => '#F97316'],          // Orange
    ['name' => 'Frozen', 'color' => '#8B5CF6'],          // Purple
    ['name' => 'Household', 'color' => '#6B7280'],       // Gray
    ['name' => 'Personal Care', 'color' => '#EC4899'],   // Pink
    ['name' => 'Other', 'color' => '#9CA3AF'],           // Light gray
];
```

## Architecture Validation Results

### Coherence Validation

**Decision Compatibility:** All technology choices are compatible and version-aligned.
- Laravel 12 + PHP 8.2+ + SQLite: Native support
- spatie/pdf-to-text + google-gemini-php/laravel: PHP 8.2+ compatible
- Tailwind + Alpine.js + Chart.js + Vite: Standard modern stack

**Pattern Consistency:** All patterns follow Laravel conventions and support the chosen stack.

**Structure Alignment:** Project structure follows Laravel 12 conventions with proper separation of concerns.

### Requirements Coverage Validation

**Functional Requirements:** All 40 FRs mapped to specific controllers, services, and models.

| Category | Count | Coverage |
|----------|-------|----------|
| Receipt Ingestion | 4 FRs | 100% |
| Data Parsing | 7 FRs | 100% |
| Product Categorization | 6 FRs | 100% |
| Product Management | 4 FRs | 100% |
| Spending Analytics | 6 FRs | 100% |
| Price Tracking | 3 FRs | 100% |
| Budget Management | 4 FRs | 100% |
| Data Correction | 4 FRs | 100% |
| System Administration | 2 FRs | 100% |

**Non-Functional Requirements:** All NFRs addressed through architectural decisions.

### Implementation Readiness Validation

**Decision Completeness:** High - all critical decisions documented with versions and examples.

**Structure Completeness:** High - complete directory tree with file-level detail.

**Pattern Completeness:** High - comprehensive patterns with good/bad examples.

### Gap Analysis Results

**Critical Gaps:** None identified.

**Minor Gaps (to address during implementation):**
- AH receipt regex patterns: Develop iteratively with real receipt samples
- Gemini prompt engineering: Refine based on categorization accuracy

### Architecture Completeness Checklist

**Requirements Analysis**
- [x] Project context thoroughly analyzed
- [x] Scale and complexity assessed (Low complexity)
- [x] Technical constraints identified (Single user, AH PDFs, Gemini free tier)
- [x] Cross-cutting concerns mapped (Product identity, Error visibility, Categories)

**Architectural Decisions**
- [x] Critical decisions documented with versions (Laravel 12, PHP 8.2+, SQLite)
- [x] Technology stack fully specified
- [x] Integration patterns defined (Service layer, Gemini API)
- [x] Performance considerations addressed (< 2s pages, < 5s parsing)

**Implementation Patterns**
- [x] Naming conventions established (Laravel defaults)
- [x] Structure patterns defined (Services, DTOs, Blade components)
- [x] Communication patterns specified (Flash messages, Controller→Service)
- [x] Process patterns documented (Error logging, Product matching)

**Project Structure**
- [x] Complete directory structure defined
- [x] Component boundaries established (Controller/Service/Model)
- [x] Integration points mapped (Gemini API, PDF parsing)
- [x] Requirements to structure mapping complete

### Architecture Readiness Assessment

**Overall Status:** READY FOR IMPLEMENTATION

**Confidence Level:** High

**Key Strengths:**
- Simple, focused architecture appropriate for personal tool
- Clear separation of concerns with service layer
- Comprehensive error visibility and audit trail
- Well-documented patterns prevent AI agent conflicts
- Database-backed product catalog minimizes API costs

**Areas for Future Enhancement:**
- Photo OCR for non-AH stores (V2+)
- Recurring purchase detection (V2+)
- Year-over-year comparison (needs 12+ months data)

### Implementation Handoff

**AI Agent Guidelines:**
1. Follow all architectural decisions exactly as documented
2. Use implementation patterns consistently across all components
3. Respect project structure and boundaries
4. Refer to this document for all architectural questions
5. Never put business logic in controllers - always use services
6. Always log errors to database, never fail silently

**First Implementation Priority:**
```bash
composer create-project laravel/laravel bondig
cd bondig
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
composer require spatie/pdf-to-text
composer require google-gemini-php/laravel
```

## Architecture Completion Summary

### Workflow Completion

**Architecture Decision Workflow:** COMPLETED
**Total Steps Completed:** 8
**Date Completed:** 2026-01-02
**Document Location:** `_bmad-output/planning-artifacts/architecture.md`

### Final Architecture Deliverables

**Complete Architecture Document**
- All architectural decisions documented with specific versions
- Implementation patterns ensuring AI agent consistency
- Complete project structure with all files and directories
- Requirements to architecture mapping
- Validation confirming coherence and completeness

**Implementation Ready Foundation**
- 15+ architectural decisions made
- 6 implementation pattern categories defined
- 6 database tables specified
- 7 controllers with clear responsibilities
- 4 service classes with defined boundaries
- 40 functional requirements fully supported

**AI Agent Implementation Guide**
- Technology stack with verified versions
- Consistency rules that prevent implementation conflicts
- Project structure with clear boundaries
- Integration patterns and communication standards

### Development Sequence

1. Initialize project using `composer create-project laravel/laravel bondig`
2. Set up Tailwind CSS and Alpine.js
3. Install dependencies (spatie/pdf-to-text, google-gemini-php/laravel)
4. Create database migrations in order (categories → products → receipts → line_items → budgets → import_logs)
5. Implement services (ReceiptParsingService first, then ProductMatchingService, CategorizationService, AnalyticsService)
6. Build controllers and views following the established patterns
7. Seed default categories

### Quality Assurance Checklist

**Architecture Coherence**
- [x] All decisions work together without conflicts
- [x] Technology choices are compatible
- [x] Patterns support the architectural decisions
- [x] Structure aligns with all choices

**Requirements Coverage**
- [x] All 40 functional requirements are supported
- [x] All non-functional requirements are addressed
- [x] Cross-cutting concerns are handled
- [x] Integration points are defined

**Implementation Readiness**
- [x] Decisions are specific and actionable
- [x] Patterns prevent agent conflicts
- [x] Structure is complete and unambiguous
- [x] Examples are provided for clarity

---

**Architecture Status:** READY FOR IMPLEMENTATION

**Next Phase:** Begin implementation using the architectural decisions and patterns documented herein.

**Document Maintenance:** Update this architecture when major technical decisions are made during implementation.

