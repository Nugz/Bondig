---
project_name: 'Bondig'
user_name: 'Bram'
date: '2026-01-02'
sections_completed: ['technology_stack', 'language_rules', 'framework_rules', 'testing_rules', 'code_quality', 'anti_patterns']
status: 'complete'
rule_count: 25
optimized_for_llm: true
---

# Project Context for AI Agents

_Critical rules for implementing Bondig. Follow these exactly._

---

## Technology Stack

| Technology | Version | Notes |
|------------|---------|-------|
| PHP | 8.2+ | Required for Laravel 12 |
| Laravel | 12 | Latest stable |
| SQLite | Latest | Single file at `database/database.sqlite` |
| Tailwind CSS | Latest | Via Vite |
| Alpine.js | Latest | For dropdowns, modals |
| Chart.js | Latest | For analytics charts |
| spatie/pdf-to-text | Latest | PDF text extraction |
| google-gemini-php/laravel | Latest | AI categorization |

---

## Critical Implementation Rules

### PHP/Laravel Rules

- **Timezone:** `Europe/Amsterdam` - set in `config/app.php`
- **Database:** SQLite only - no MySQL migrations
- **No authentication:** Single user, no auth middleware
- **Eloquent casts:** Always cast dates to `datetime`

### Naming Conventions (STRICT)

```
Database:    snake_case, plural    → receipts, line_items
Columns:     snake_case            → product_id, normalized_name
Models:      PascalCase, singular  → Receipt, LineItem
Controllers: PascalCase + suffix   → ReceiptController
Services:    PascalCase + suffix   → ReceiptParsingService
Routes:      kebab-case            → /category-review
Views:       kebab-case in folders → receipts/index.blade.php
```

### Service Layer Pattern (MANDATORY)

**NEVER put business logic in controllers.**

```php
// WRONG - logic in controller
public function store(Request $request) {
    $text = Pdf::getText($request->file('pdf')->path());
    // ... parsing logic here
}

// CORRECT - delegate to service
public function store(Request $request) {
    $result = $this->parsingService->parseFromPdf($request->file('pdf'));
}
```

**Services:**
- `ReceiptParsingService` - PDF extraction, line parsing
- `CategorizationService` - Gemini API, caching
- `ProductMatchingService` - normalization, find/create
- `AnalyticsService` - spending calculations

### Error Handling (CRITICAL)

**NEVER fail silently. Always:**
1. Flash message to user
2. Log to database for audit

```php
// WRONG
if (!$parsed) return;

// CORRECT
if (!$parsed) {
    Log::warning('Parse failed', ['file' => $file->name]);
    ImportLog::create([...]);
    return back()->with('error', 'Could not parse receipt');
}
```

**Flash message types:**
- `success` - operation completed
- `warning` - partial success, needs attention
- `error` - operation failed

### Product Matching

**Normalization function:**
```php
public function normalize(string $name): string
{
    return strtolower(preg_replace('/\s+/', ' ', trim($name)));
}
```

**Always check existing products before Gemini API call:**
```php
$product = Product::where('normalized_name', $normalized)->first();
if (!$product) {
    // Only then call Gemini
}
```

### FormRequest Usage

- Use FormRequest for forms with 3+ fields
- Use inline `$request->validate()` for simple forms
- Location: `app/Http/Requests/`

### View Components

Use Blade components for reusable UI:
- `<x-receipt-card :receipt="$receipt" />`
- `<x-category-badge :category="$category" />`
- `<x-flash-messages />`

---

## Testing Rules

- **Location:** `tests/Feature/` and `tests/Unit/`
- **Service tests:** Unit tests in `tests/Unit/`
- **Controller tests:** Feature tests in `tests/Feature/`
- **Mock Gemini API** in tests - never make real API calls

---

## Anti-Patterns to AVOID

| Don't | Do Instead |
|-------|------------|
| `Schema::create('lineItems')` | `Schema::create('line_items')` |
| Logic in controllers | Delegate to services |
| Silent failures | Flash + log errors |
| Direct Gemini calls | Check product cache first |
| `return;` on error | `return back()->with('error', ...)` |
| Fat controllers | Thin controllers, fat services |
| `userId` columns | `user_id` columns |

---

## File Locations

| What | Where |
|------|-------|
| Services | `app/Services/` |
| DTOs | `app/DTOs/` |
| Form Requests | `app/Http/Requests/` |
| Blade Components | `resources/views/components/` |
| Uploaded PDFs | `storage/app/receipts/` |
| SQLite DB | `database/database.sqlite` |

---

## Gemini API Usage

- **Free tier:** 1000 requests/day limit
- **Batch categorize:** Send multiple products in one request when possible
- **Cache results:** Store in `products` table, check before API call
- **Handle failures:** Queue for retry, show warning to user

---

## Date Handling

```php
// Model
protected $casts = ['purchased_at' => 'datetime'];

// Service
$start = Carbon::now()->startOfMonth();

// Blade
{{ $receipt->purchased_at->format('d M Y') }}
```

---

## Usage Guidelines

**For AI Agents:**
- Read this file before implementing any code
- Follow ALL rules exactly as documented
- When in doubt, prefer the more restrictive option
- Reference `architecture.md` for detailed decisions

**For Humans:**
- Keep this file lean and focused on agent needs
- Update when technology stack changes
- Review quarterly for outdated rules

---

_Last Updated: 2026-01-02_
