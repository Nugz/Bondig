# Story 1.3: Receipt List & Detail Views

Status: done

## Story

As a **user**,
I want **to view my uploaded receipts and see the parsed line items**,
So that **I can verify the data was captured correctly and debug any issues**.

## Acceptance Criteria

1. **Given** I have uploaded receipts
   **When** I navigate to the Receipts list
   **Then** I see all receipts sorted by date (newest first)
   **And** each receipt shows: date, store, total amount, item count

2. **Given** I am viewing the receipt list
   **When** I click on a receipt
   **Then** I see the receipt detail page
   **And** I see all line items with product name, quantity, unit price, total price
   **And** bonus items are visually indicated
   **And** the receipt total is displayed

3. **Given** I am on a receipt detail page
   **When** I click "View original text" on a line item
   **Then** I see the original parsed text from the PDF
   **And** I can close the debug view

## Tasks / Subtasks

- [x] Task 1: Create ReceiptController.index() (AC: #1)
  - [x] 1.1: Add `index()` method to ReceiptController
  - [x] 1.2: Query receipts sorted by `purchased_at` desc with eager loading (lineItems count)
  - [x] 1.3: Paginate results (20 per page per architecture.md)

- [x] Task 2: Create receipts/index.blade.php (AC: #1, #2)
  - [x] 2.1: Create receipt list view with daisyUI table or cards layout
  - [x] 2.2: Display date (formatted with Carbon), store, total amount, item count
  - [x] 2.3: Make each row/card clickable to navigate to detail page
  - [x] 2.4: Add pagination controls
  - [x] 2.5: Handle empty state: "No receipts yet. Upload your first receipt!"

- [x] Task 3: Enhance receipts/show.blade.php (AC: #2, #3)
  - [x] 3.1: Verify receipt header shows store, date, total (already exists from 1.2)
  - [x] 3.2: Verify line items table shows product name, quantity, unit price, total price (already exists)
  - [x] 3.3: Verify bonus items have visual indicator (already exists from 1.6)
  - [x] 3.4: Verify discount amounts are shown (already exists from 1.6)
  - [x] 3.5: Add/verify "View original text" toggle per line item using Alpine.js x-collapse

- [x] Task 4: Add navigation links (AC: #1, #2)
  - [x] 4.1: Add "Receipts" link to main navigation if not present
  - [x] 4.2: Add "Back to Receipts" link on receipt detail page
  - [x] 4.3: Add breadcrumb navigation (optional, depends on UX consistency) - Skipped: simple back link used instead

- [x] Task 5: Add routes if needed (AC: #1, #2)
  - [x] 5.1: Verify `GET /receipts` route for index page exists
  - [x] 5.2: Verify `GET /receipts/{receipt}` route for detail page exists

- [x] Task 6: Write tests (AC: #1, #2, #3)
  - [x] 6.1: Feature test for receipt list page with multiple receipts
  - [x] 6.2: Feature test for receipt list pagination
  - [x] 6.3: Feature test for receipt list empty state
  - [x] 6.4: Feature test for receipt detail shows all line items
  - [x] 6.5: Verify existing ReceiptDetailTest.php covers raw_text toggle

## Dev Notes

### Critical Architecture Compliance

**MANDATORY - Follow these patterns exactly from project-context.md and architecture.md:**

1. **Controller Pattern** - Thin controllers, business logic in services:
   ```php
   // ReceiptController.index() - minimal logic
   public function index()
   {
       $receipts = Receipt::with(['lineItems'])
           ->withCount('lineItems')
           ->latest('purchased_at')
           ->paginate(20);

       return view('receipts.index', compact('receipts'));
   }
   ```

2. **View Organization** - Follow existing patterns:
   - Use daisyUI components (cards, table, badge)
   - Use Tailwind utilities for spacing/layout
   - Include flash-messages component for notifications

3. **Date Formatting** - Use Carbon in Blade:
   ```blade
   {{ $receipt->purchased_at->format('d M Y') }}
   {{ $receipt->purchased_at->format('H:i') }}
   ```

### Existing Implementation Status

From Story 1.2 and 1.6 completion, the following already exists:

**Already Implemented:**
- `Receipt` model with relationships (lineItems, unmatchedBonuses)
- `LineItem` model with relationships and `effective_price` accessor
- `ReceiptController` with `show()` method
- `receipts/show.blade.php` with:
  - Receipt header (store, date, total)
  - Line items table (product, quantity, prices, bonus indicator)
  - Discount amounts and effective price from Story 1.6
  - Raw text toggle using Alpine.js x-collapse
  - Total savings display
  - Unmatched bonuses indicator
- Route: `GET /receipts/{receipt}`
- Tests: `tests/Feature/ReceiptDetailTest.php`

**Needs Implementation:**
- `ReceiptController.index()` method
- `receipts/index.blade.php` view
- Route: `GET /receipts`
- Navigation links to receipts list
- Feature tests for list page

### Receipt Model Methods Available

From existing implementation:
```php
// Receipt model
$receipt->lineItems;                    // HasMany LineItem
$receipt->purchased_at;                 // Carbon datetime
$receipt->total_amount;                 // decimal
$receipt->store;                        // string
$receipt->totalDiscount;                // accessor - sum of line item discounts
$receipt->unmatchedBonuses;             // HasMany UnmatchedBonus
$receipt->pendingUnmatchedBonuses;      // HasMany (pending only)
```

### UI Design Requirements

From UX Design Specification and existing patterns:

**Receipt List Card Layout:**
```html
<!-- Each receipt as a card -->
<div class="card bg-base-100 shadow hover:shadow-md transition-shadow">
    <div class="card-body">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="card-title text-lg">{{ $receipt->store }}</h3>
                <p class="text-sm text-base-content/70">
                    {{ $receipt->purchased_at->format('d M Y') }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold">{{ number_format($receipt->total_amount, 2) }}</p>
                <p class="text-sm text-base-content/70">
                    {{ $receipt->line_items_count }} items
                </p>
            </div>
        </div>
    </div>
</div>
```

**Alternative Table Layout:**
```html
<table class="table table-zebra">
    <thead>
        <tr>
            <th>Date</th>
            <th>Store</th>
            <th>Items</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($receipts as $receipt)
        <tr class="hover cursor-pointer" onclick="...">
            <td>{{ $receipt->purchased_at->format('d M Y H:i') }}</td>
            <td>{{ $receipt->store }}</td>
            <td>{{ $receipt->line_items_count }}</td>
            <td class="text-right font-bold">
                {{ number_format($receipt->total_amount, 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

### Navigation Integration

Add to app layout navigation:
```blade
<!-- In layouts/app.blade.php or nav component -->
<a href="{{ route('receipts.index') }}"
   class="btn btn-ghost {{ request()->routeIs('receipts.*') ? 'btn-active' : '' }}">
    Receipts
</a>
```

### Empty State Pattern

```blade
@forelse($receipts as $receipt)
    {{-- receipt card/row --}}
@empty
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-base-content/40" ...>...</svg>
        <h3 class="mt-2 text-lg font-medium">No receipts yet</h3>
        <p class="mt-1 text-sm text-base-content/60">
            Get started by uploading your first receipt.
        </p>
        <div class="mt-6">
            <a href="{{ route('upload.index') }}" class="btn btn-primary">
                Upload Receipt
            </a>
        </div>
    </div>
@endforelse
```

### Pagination Pattern

```blade
<!-- After the receipts list -->
<div class="mt-6">
    {{ $receipts->links() }}
</div>
```

### Previous Story Intelligence

**From Story 1.2:**
- All core models and migrations created
- ReceiptController.show() implemented
- receipts/show.blade.php created with full detail view
- Alpine.js x-collapse for raw_text toggle working
- 40 tests passing

**From Story 1.6:**
- Bonus discount display added to show.blade.php
- Total savings calculated and displayed
- Unmatched bonuses indicator added
- 70 tests passing

**Key Learnings:**
- Use `withCount('lineItems')` for efficient item counting
- daisyUI + Tailwind combination works well for consistent styling
- Alpine.js x-collapse requires @alpinejs/collapse plugin (already installed)

### Git Intelligence

Recent commits show:
- Story 1.6 added bonus matching functionality
- Extended Receipt and LineItem models with discount-related accessors
- Added routes for bonus matching

Files relevant to this story:
- `app/Http/Controllers/ReceiptController.php` - needs index() method
- `resources/views/receipts/show.blade.php` - already complete, may need minor polish
- `routes/web.php` - need to add/verify receipts.index route

### Testing Approach

```php
// Feature test - receipt list with pagination
public function test_receipts_index_shows_receipts_sorted_by_date(): void
{
    $oldReceipt = Receipt::factory()->create(['purchased_at' => now()->subDays(10)]);
    $newReceipt = Receipt::factory()->create(['purchased_at' => now()]);

    $response = $this->get(route('receipts.index'));

    $response->assertStatus(200);
    $response->assertSeeInOrder([$newReceipt->store, $oldReceipt->store]);
}

public function test_receipts_index_shows_item_count(): void
{
    $receipt = Receipt::factory()
        ->has(LineItem::factory()->count(5))
        ->create();

    $response = $this->get(route('receipts.index'));

    $response->assertStatus(200);
    $response->assertSee('5 items');
}

public function test_receipts_index_paginates_results(): void
{
    Receipt::factory()->count(25)->create();

    $response = $this->get(route('receipts.index'));

    $response->assertStatus(200);
    // Should show 20 per page
    $this->assertEquals(20, $response->viewData('receipts')->count());
}

public function test_receipts_index_shows_empty_state(): void
{
    $response = $this->get(route('receipts.index'));

    $response->assertStatus(200);
    $response->assertSee('No receipts yet');
    $response->assertSee('Upload Receipt');
}
```

### Project Structure Notes

Files to create/modify:
```
app/Http/Controllers/
└── ReceiptController.php           # Add index() method

resources/views/receipts/
├── index.blade.php                 # NEW - Receipt list view
└── show.blade.php                  # Verify/polish existing

routes/
└── web.php                         # Add receipts.index route

tests/Feature/
├── ReceiptDetailTest.php           # Already exists, may need updates
└── ReceiptListTest.php             # NEW - List page tests
```

### References

- [Source: architecture.md#View Organization] - View structure patterns
- [Source: architecture.md#Routes Overview] - Route naming conventions
- [Source: project-context.md#View Components] - Blade component usage
- [Source: epics.md#Story 1.3] - Original story requirements
- [Source: 1-2-single-receipt-upload-parsing.md] - Previous story implementation
- [Source: 1-6-bonus-discount-parsing.md] - Most recent story learnings

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References

None - implementation proceeded without issues.

### Completion Notes List

- Implemented `ReceiptController.index()` with eager loading, item count, and pagination (20 per page)
- Created `receipts/index.blade.php` with card-based layout showing store, date, total, and item count
- Added per-line-item raw text toggle using Alpine.js x-collapse (AC #3 requirement)
- Added "Receipts" navigation link to both mobile and desktop menus
- Added "Back to Receipts" button on receipt detail page
- Added `receipts.index` route before `receipts.show` to avoid route conflict
- Created comprehensive test suite: ReceiptListTest.php with 6 tests
- Extended ReceiptDetailTest.php with 3 additional tests (back link, raw text, discount)
- All 79 tests passing, no regressions

### File List

**New Files:**
- `resources/views/receipts/index.blade.php` - Receipt list view
- `tests/Feature/ReceiptListTest.php` - Receipt list tests

**Modified Files:**
- `app/Http/Controllers/ReceiptController.php` - Added index() method
- `routes/web.php` - Added receipts.index route
- `resources/views/receipts/show.blade.php` - Added per-line-item raw text toggle, back button
- `resources/views/components/layouts/app.blade.php` - Added Receipts nav link
- `tests/Feature/ReceiptDetailTest.php` - Added 3 new tests
- `_bmad-output/implementation-artifacts/sprint-status.yaml` - Updated story status

### Change Log

- 2026-01-04: Implemented Story 1.3 - Receipt List & Detail Views (all ACs satisfied, 79 tests passing)
- 2026-01-04: Code Review fixes applied:
  - Removed unnecessary `with(['lineItems'])` eager loading from index() - only count needed
  - Added `loadCount('lineItems')` to show() for reliable count access
  - Changed show.blade.php to use `line_items_count` attribute instead of `->count()` method
  - Added singular/plural handling for "product/products" in show.blade.php
  - Added aria-label to raw text toggle button for accessibility
  - Added max-height with overflow to raw text display
  - Fixed tfoot colspan alignment (6 columns)
  - Added 2 new tests for singular/plural grammar (81 tests passing)

