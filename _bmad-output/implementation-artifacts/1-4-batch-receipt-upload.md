# Story 1.4: Batch Receipt Upload

Status: done

## Story

As a **user**,
I want **to upload multiple PDF receipts at once**,
So that **I can quickly import my historical receipt data**.

## Acceptance Criteria

1. **Given** I am on the Upload page
   **When** I drag and drop multiple PDF files onto the drop zone
   **Then** all files are queued for processing
   **And** I see a progress indicator showing "Processing X of Y receipts"
   **And** each receipt is parsed individually
   **And** I see a summary showing successful and failed imports

2. **Given** I upload 10 receipts and 2 fail to parse
   **When** processing completes
   **Then** I see "8 receipts imported successfully, 2 failed"
   **And** I can view details of the failures
   **And** the 8 successful receipts are saved

3. **Given** I select files using the file picker (click to browse)
   **When** I select multiple files
   **Then** they are processed the same as drag-and-drop

## Tasks / Subtasks

- [x] Task 1: Extend upload-dropzone.blade.php for multiple files (AC: #1, #3)
  - [x] 1.1: Add `multiple` attribute to file input
  - [x] 1.2: Update Alpine.js state to track array of files (`files: []`)
  - [x] 1.3: Update `handleDrop()` to collect all dropped files
  - [x] 1.4: Update `handleFileSelect()` to handle multiple files
  - [x] 1.5: Update `validateAndSetFile()` to validate each file and add to array
  - [x] 1.6: Display file count and list of selected files

- [x] Task 2: Add batch progress UI (AC: #1)
  - [x] 2.1: Add processing state (`currentIndex`, `totalFiles`, `results: []`)
  - [x] 2.2: Show "Processing X of Y receipts" during batch upload
  - [x] 2.3: Show per-file progress indicator (spinner for current, checkmark/X for completed)
  - [x] 2.4: Update UI dynamically as each file completes

- [x] Task 3: Create batch upload controller method (AC: #1, #2)
  - [x] 3.1: Modified existing `store()` to return JSON when `Accept: application/json` header present
  - [x] 3.2: Accept single PDF file with validation (client-side sequential approach)
  - [x] 3.3: Process files sequentially (client-side for better progress UX)
  - [x] 3.4: Return results for each file (success/failure/duplicate with details)
  - [x] 3.5: Return JSON response with status, receipt_id, item_count, total_amount

- [x] Task 4: Add batch upload route (AC: #1)
  - [x] 4.1: Reused existing `POST /upload` route with JSON response capability
  - [x] 4.2: No separate batch route needed (client-side sequential approach)

- [x] Task 5: Implement AJAX batch upload (AC: #1, #2)
  - [x] 5.1: Submit files via fetch to existing upload endpoint
  - [x] 5.2: Process files one-by-one client-side for progress tracking
  - [x] 5.3: Update UI after each file response
  - [x] 5.4: Handle network errors gracefully

- [x] Task 6: Add results summary view (AC: #2)
  - [x] 6.1: Display summary with stats cards: Imported, Duplicates, Failed
  - [x] 6.2: Show list of successful imports (with links to receipts)
  - [x] 6.3: Show list of failed imports with error details
  - [x] 6.4: Add "View All Receipts" and "Upload More" actions

- [x] Task 7: Handle duplicate detection in batch (AC: #2)
  - [x] 7.1: Detect duplicates based on store, date, and total amount
  - [x] 7.2: Include duplicate count in summary with warning color
  - [x] 7.3: Show which files were duplicates in detailed results

- [x] Task 8: Write tests (AC: #1, #2, #3)
  - [x] 8.1: Feature test for batch upload endpoint with multiple valid PDFs
  - [x] 8.2: Feature test for partial success (some files fail)
  - [x] 8.3: Feature test for validation rejection of non-PDF files
  - [x] 8.4: Feature test for duplicate detection in batch
  - [x] 8.5: Feature test for oversized file validation
  - [x] 8.6: Feature test for JSON response with unmatched bonus count

### Review Follow-ups (AI) - Round 1

- [x] [AI-Review][HIGH] Fix duplicate detection false positive when purchasedAt is null - fallback to now() causes false matches [UploadController.php:76-78]
- [x] [AI-Review][HIGH] Fix file list showing green checkmark for failed uploads during batch processing [upload-dropzone.blade.php:215-219]
- [x] [AI-Review][HIGH] Refactor store() method to service layer per project-context.md "thin controllers" rule [UploadController.php]
- [x] [AI-Review][MEDIUM] Add maximum file count limit (e.g., 50 files) to prevent server overload [upload-dropzone.blade.php:26-41]
- [x] [AI-Review][MEDIUM] Add test coverage for network error handling scenario [BatchUploadTest.php]
- [x] [AI-Review][MEDIUM] Clear files array after upload completes to free memory [upload-dropzone.blade.php:100-103]
- [x] [AI-Review][MEDIUM] Document sprint-status.yaml in File List (missing from git diff) [1-4-batch-receipt-upload.md]
- [x] [AI-Review][LOW] Fix static counter test isolation risk - use instance variable instead [BatchUploadTest.php:79]
- [x] [AI-Review][LOW] Add ARIA labels to dropzone and remove buttons for accessibility [upload-dropzone.blade.php]

### Review Follow-ups (AI) - Round 2

- [x] [AI-Review][MEDIUM] Fix incomplete error response when all line items fail - add errors array to return [ReceiptUploadService.php:183-222]
- [x] [AI-Review][MEDIUM] Add data loss warning to migration rollback for duplicate/skipped status records [migration:39-75]
- [x] [AI-Review][MEDIUM] Move file storage outside transaction to prevent orphan files on rollback [ReceiptUploadService.php:133-247]
- [x] [AI-Review][LOW] Add aria-live region for upload completion screen reader announcements [upload-dropzone.blade.php:312]

## Dev Notes

### Critical Architecture Compliance

**MANDATORY - Follow these patterns exactly from project-context.md and architecture.md:**

1. **Controller Pattern** - Thin controllers, business logic in services:
   ```php
   // UploadController.storeBatch() - delegate to service for each file
   public function storeBatch(Request $request): JsonResponse
   {
       $results = [];
       foreach ($request->file('pdfs') as $file) {
           $results[] = $this->processSingleFile($file);
       }
       return response()->json(['results' => $results]);
   }
   ```

2. **Error Handling** - Never fail silently:
   - Each file processed independently
   - Failures don't stop batch
   - All errors logged to `import_logs` table
   - Flash summary message at end

3. **Memory Management**:
   - Process files sequentially, not in parallel
   - Release file handles after each parse
   - Don't load all files into memory at once

### Existing Implementation Analysis

**From Story 1.2 (UploadController.store):**
The existing single-file upload flow:
1. Validate single PDF file
2. Parse via `ReceiptParsingService`
3. Create receipt and line items in transaction
4. Process bonuses via `BonusMatchingService`
5. Log to `import_logs`
6. Redirect with flash message

**Key reuse opportunity:** Extract the core processing logic from `store()` into a reusable method that both `store()` and `storeBatch()` can use.

```php
protected function processSingleFile(UploadedFile $file): array
{
    // Existing logic from store(), returning result array
    return [
        'filename' => $file->getClientOriginalName(),
        'status' => 'success', // or 'failed', 'duplicate'
        'receipt_id' => $receipt?->id,
        'message' => $message,
        'errors' => $errors,
    ];
}
```

### Upload Dropzone Enhancement

**Current dropzone.blade.php state:**
```javascript
x-data="{
    isDragging: false,
    isUploading: false,
    fileName: '',
    fileError: '',
    ...
}"
```

**Enhanced for batch:**
```javascript
x-data="{
    isDragging: false,
    isUploading: false,
    files: [],           // Array of selected files
    fileErrors: [],      // Validation errors per file
    currentIndex: 0,     // Currently processing file index
    results: [],         // Results from server for each file
    mode: 'single',      // 'single' or 'batch' - auto-detect based on file count
    ...
}"
```

### Batch Upload API Design

**Endpoint:** `POST /upload/batch`

**Request:**
```
Content-Type: multipart/form-data
pdfs[]: file1.pdf
pdfs[]: file2.pdf
...
```

**Response:**
```json
{
    "summary": {
        "total": 10,
        "success": 7,
        "duplicates": 1,
        "failed": 2
    },
    "results": [
        {
            "filename": "receipt1.pdf",
            "status": "success",
            "receipt_id": 123,
            "item_count": 15,
            "total_amount": 45.67
        },
        {
            "filename": "receipt2.pdf",
            "status": "duplicate",
            "message": "Duplicate receipt detected - already imported"
        },
        {
            "filename": "receipt3.pdf",
            "status": "failed",
            "errors": ["Could not extract date from PDF"]
        }
    ]
}
```

### Alternative: Client-Side Sequential Upload

For better UX (real-time progress), consider client-side sequential upload:

```javascript
async submitBatch() {
    this.isUploading = true;
    for (let i = 0; i < this.files.length; i++) {
        this.currentIndex = i;
        const formData = new FormData();
        formData.append('pdf', this.files[i]);

        try {
            const response = await fetch('{{ route("upload.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });
            const result = await response.json();
            this.results.push(result);
        } catch (error) {
            this.results.push({
                filename: this.files[i].name,
                status: 'failed',
                error: error.message
            });
        }
    }
    this.isUploading = false;
}
```

**Decision:** Recommend client-side sequential for better UX. Modify existing `store()` to return JSON when `Accept: application/json` header present.

### Duplicate Detection Consideration

**From Story 1.5 (upcoming):** Full duplicate detection is Story 1.5. For batch upload, implement simple duplicate detection:

```php
// Check before processing
$isDuplicate = Receipt::where('store', $store)
    ->where('purchased_at', $purchasedAt)
    ->where('total_amount', $total)
    ->exists();

if ($isDuplicate) {
    return [
        'status' => 'duplicate',
        'message' => 'Duplicate receipt detected - already imported'
    ];
}
```

This provides immediate duplicate feedback in batch without full Story 1.5 implementation.

### UI/UX Design

**File List Display:**
```html
<div class="space-y-2 mt-4" x-show="files.length > 0">
    <p class="font-medium">Selected files: <span x-text="files.length"></span></p>
    <template x-for="(file, index) in files" :key="index">
        <div class="flex items-center gap-2 py-1">
            <!-- Status icon -->
            <template x-if="index < currentIndex">
                <svg class="w-5 h-5 text-success"><!-- checkmark --></svg>
            </template>
            <template x-if="index === currentIndex && isUploading">
                <span class="loading loading-spinner loading-sm"></span>
            </template>
            <template x-if="index > currentIndex || !isUploading">
                <svg class="w-5 h-5 text-base-content/40"><!-- pending --></svg>
            </template>

            <!-- Filename -->
            <span x-text="file.name" class="text-sm"></span>
        </div>
    </template>
</div>
```

**Results Summary:**
```html
<div class="card bg-base-100 shadow" x-show="results.length > 0 && !isUploading">
    <div class="card-body">
        <h3 class="card-title">Upload Complete</h3>
        <div class="stats stats-vertical lg:stats-horizontal shadow">
            <div class="stat">
                <div class="stat-title">Imported</div>
                <div class="stat-value text-success" x-text="successCount"></div>
            </div>
            <div class="stat" x-show="duplicateCount > 0">
                <div class="stat-title">Duplicates</div>
                <div class="stat-value text-warning" x-text="duplicateCount"></div>
            </div>
            <div class="stat" x-show="failedCount > 0">
                <div class="stat-title">Failed</div>
                <div class="stat-value text-error" x-text="failedCount"></div>
            </div>
        </div>
    </div>
</div>
```

### Previous Story Intelligence

**From Story 1.3:**
- 81 tests passing
- All receipts views working
- Navigation in place
- daisyUI + Tailwind patterns established

**From Story 1.6:**
- Bonus matching integrated into upload flow
- UnmatchedBonus model and handling exists
- `processBonuses()` method available

**Key Learning:** The existing `store()` method is already well-structured. Main extension is:
1. Support JSON responses
2. Extract reusable `processSingleFile()` method
3. Enhance dropzone for multiple files

### Git Intelligence

Recent commits:
- `feat(story-1.3)`: Receipt list and detail views
- `feat(story-1.6)`: Bonus/discount parsing
- `feat(story-1.2)`: Single receipt upload & parsing

Files to modify:
- `app/Http/Controllers/UploadController.php` - Add JSON support, extract method
- `resources/views/components/upload-dropzone.blade.php` - Multi-file support
- `routes/web.php` - Add batch route (optional, can use existing route)

### Testing Approach

```php
// Feature test - batch upload success
public function test_batch_upload_processes_multiple_valid_pdfs(): void
{
    Storage::fake('local');

    $files = [
        UploadedFile::fake()->create('receipt1.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('receipt2.pdf', 100, 'application/pdf'),
    ];

    // Mock parsing service to return valid results
    $this->mock(ReceiptParsingService::class, function ($mock) {
        $mock->shouldReceive('parseFromPdf')
            ->times(2)
            ->andReturn(new ParseResult(success: true, ...));
    });

    $response = $this->postJson(route('upload.store'), ['pdfs' => $files]);

    $response->assertStatus(200);
    $response->assertJsonPath('summary.success', 2);
    $this->assertDatabaseCount('receipts', 2);
}

// Feature test - partial success
public function test_batch_upload_handles_partial_failures(): void
{
    // One valid, one invalid
    $this->mock(ReceiptParsingService::class, function ($mock) {
        $mock->shouldReceive('parseFromPdf')
            ->andReturnUsing(fn($file) =>
                $file->getClientOriginalName() === 'valid.pdf'
                    ? new ParseResult(success: true, ...)
                    : new ParseResult(success: false, errors: ['Parse error'])
            );
    });

    // ... assert 1 success, 1 failure
}
```

### Project Structure Notes

**Files to create:**
- (None - enhance existing files)

**Files to modify:**
```
app/Http/Controllers/
└── UploadController.php           # Add JSON response, extract method

resources/views/components/
└── upload-dropzone.blade.php      # Multi-file support, progress UI

routes/
└── web.php                        # Optionally add batch route (or use existing)

tests/Feature/
└── BatchUploadTest.php            # NEW - Batch upload tests
```

### References

- [Source: epics.md#Story 1.4] - Original story requirements
- [Source: architecture.md#Error Handling] - Flash + database log pattern
- [Source: project-context.md#Service Layer Pattern] - Thin controllers
- [Source: 1-3-receipt-list-detail-views.md] - Previous story learnings
- [Source: upload-dropzone.blade.php] - Existing upload component patterns

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References

N/A - No debugging issues encountered during implementation.

### Completion Notes List

1. **Client-Side Sequential Approach**: Instead of creating a separate batch endpoint, implemented client-side sequential upload for better real-time progress UX. Each file is uploaded via AJAX to the existing endpoint with `Accept: application/json` header.

2. **JSON Response Support**: Enhanced `UploadController::store()` to detect `$request->wantsJson()` and return JSON responses with status, receipt_id, item_count, total_amount, and unmatched_bonus_count.

3. **Duplicate Detection**: Added simple duplicate detection based on store + date + total_amount matching. Duplicates are logged with status='duplicate' and return appropriate JSON response.

4. **Migration for Import Logs**: Created migration to add 'duplicate' and 'skipped' to the import_logs status enum (required table recreation due to SQLite constraints).

5. **Enhanced Dropzone UI**: Complete rewrite of upload-dropzone.blade.php with:
   - Multi-file selection via drag-drop and file picker
   - File validation with error display
   - Remove file capability before upload
   - Progress bar showing "Processing X of Y"
   - Per-file status indicators (pending/spinner/checkmark/error)
   - Results summary with stats cards (Imported/Duplicates/Failed)
   - Detailed results with links to view each imported receipt
   - "Upload More" and "View All Receipts" actions

6. **Test Suite**: Created BatchUploadTest.php with 7 comprehensive tests covering:
   - Single upload JSON response
   - Multiple valid PDFs processing
   - Partial failure handling
   - Duplicate detection
   - Validation rejection (non-PDF, oversized)
   - Unmatched bonus count in response

### File List

**New Files:**
- tests/Feature/BatchUploadTest.php
- database/migrations/2026_01_04_094341_add_duplicate_and_skipped_status_to_import_logs.php
- app/Services/ReceiptUploadService.php

**Modified Files:**
- app/Http/Controllers/UploadController.php
- resources/views/components/upload-dropzone.blade.php
- _bmad-output/implementation-artifacts/sprint-status.yaml

### Change Log

- 2026-01-04: Story 1.4 implementation complete - Batch receipt upload with multi-file support, progress tracking, duplicate detection, and comprehensive test coverage (88 tests passing)
- 2026-01-04: **Code Review Round 1 (AI)** - Found 9 issues (3 HIGH, 4 MEDIUM, 2 LOW). Action items created in Review Follow-ups section. Status → in-progress pending fixes.
- 2026-01-04: **Addressed 9 code review findings** - All action items resolved: refactored UploadController to use ReceiptUploadService (thin controllers pattern), fixed duplicate detection false positive, corrected status icons for failed uploads, added 50-file limit, added accessibility ARIA labels, added server error test, cleared files array after upload, fixed test isolation. All 89 tests passing.
- 2026-01-04: **Code Review Round 2 (AI)** - Found 4 additional issues (3 MEDIUM, 1 LOW). Fixed: incomplete error response when all line items fail, migration rollback data loss warning, orphan file cleanup on transaction failure, aria-live for screen readers. All 89 tests passing. Status → done.
