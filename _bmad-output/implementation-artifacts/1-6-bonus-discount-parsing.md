# Story 1.6: Bonus Discount Parsing & Matching

Status: done

## Story

As a **user**,
I want **bonus discount amounts to be correctly parsed from receipts and matched to their corresponding products**,
So that **I can see the actual savings I received and have accurate price tracking**.

## Background

AH receipts have a bonus section below the subtotal that lists actual discount amounts. The current implementation:
- Correctly identifies products marked with "B" (bonus indicator)
- Does NOT parse the actual discount amounts from the bonus section
- Does NOT match bonus lines to their corresponding products

Example from actual receipt:
```
Products section:
4        PAPRIKA                    0,89     3,56 B

Bonus section (below subtotal):
BONUS                  AHPAPRIKAROO                                           -0,58
```

The challenge: Product name in bonus section ("AHPAPRIKAROO") doesn't match the product line ("PAPRIKA") exactly.

## Acceptance Criteria

1. **Given** I upload an AH PDF receipt with bonus items
   **When** the system parses the receipt
   **Then** the bonus section below subtotal is extracted
   **And** each bonus line includes: abbreviated product name, discount amount
   **And** bonus discount amounts are stored in the database

2. **Given** a bonus line "BONUS AHPAPRIKAROO -0,58"
   **When** the system attempts to match it
   **Then** it tries to match "AHPAPRIKAROO" to products on the receipt
   **And** if confident match found (>80% similarity), it auto-links
   **And** the discount amount is stored on the line_item record

3. **Given** a bonus line cannot be confidently matched
   **When** the receipt is saved
   **Then** the unmatched bonus is flagged for manual review
   **And** the receipt shows "X unmatched bonuses" indicator

4. **Given** I view a receipt with unmatched bonuses
   **When** I click to resolve them
   **Then** I see each unmatched bonus with its discount amount
   **And** I can select which product on the receipt it belongs to
   **And** I can mark it as "not applicable" if it's a store-wide discount

5. **Given** bonuses are matched (auto or manually)
   **When** I view the receipt detail page
   **Then** I see the discount amount next to bonus items
   **And** the total discount savings is displayed
   **And** effective price (price - discount) is shown

## Tasks / Subtasks

- [x] Task 1: Database schema updates
  - [x] 1.1: Add `discount_amount` column to `line_items` table (decimal 8,2, nullable)
  - [x] 1.2: Create `unmatched_bonuses` table (id, receipt_id FK, raw_name, discount_amount, matched_line_item_id nullable, status enum, timestamps)
  - [x] 1.3: Run migrations

- [x] Task 2: Extend ReceiptParsingService for bonus section (AC: #1)
  - [x] 2.1: Add `extractBonusSection()` method to find bonus lines after subtotal
  - [x] 2.2: Add `parseBonusLine()` to extract name and discount amount
  - [x] 2.3: Add `bonuses` array to ParseResult DTO
  - [x] 2.4: Create ParsedBonus DTO (rawName, discountAmount)

- [x] Task 3: Create BonusMatchingService (AC: #2, #3)
  - [x] 3.1: Create `app/Services/BonusMatchingService.php`
  - [x] 3.2: Implement `matchBonusToProduct()` with fuzzy string matching
  - [x] 3.3: Implement similarity algorithm (Levenshtein or similar_text)
  - [x] 3.4: Configure confidence threshold (80%)
  - [x] 3.5: Return match result with confidence score

- [x] Task 4: Update UploadController for bonus processing (AC: #2, #3)
  - [x] 4.1: After line items created, process bonus matches
  - [x] 4.2: For confident matches: update line_item.discount_amount
  - [x] 4.3: For uncertain matches: create unmatched_bonus record
  - [x] 4.4: Add unmatched count to flash message

- [x] Task 5: Create manual bonus matching UI (AC: #4)
  - [x] 5.1: Create `resources/views/receipts/match-bonuses.blade.php`
  - [x] 5.2: Display unmatched bonuses with discount amounts
  - [x] 5.3: Show receipt products as matching options
  - [x] 5.4: Add "Not applicable" option for store discounts
  - [x] 5.5: Save matches via AJAX, update line_items

- [x] Task 6: Update receipt detail view (AC: #5)
  - [x] 6.1: Display discount amount next to bonus items
  - [x] 6.2: Show effective price (original - discount)
  - [x] 6.3: Add "Total savings" summary
  - [x] 6.4: Show "X unmatched bonuses" badge if any

- [x] Task 7: Write tests
  - [x] 7.1: Unit test for bonus section extraction regex
  - [x] 7.2: Unit test for fuzzy matching algorithm
  - [x] 7.3: Feature test for receipt with matched bonuses
  - [x] 7.4: Feature test for manual bonus matching

### Review Follow-ups (AI)

- [x] [AI-Review][MEDIUM] Move MatchResult class to separate DTO file `app/DTOs/MatchResult.php` [app/Services/BonusMatchingService.php:132-139]
- [x] [AI-Review][MEDIUM] Add feature test for auto-bonus-matching during upload with actual bonuses [tests/Feature/ReceiptUploadTest.php]
- [x] [AI-Review][MEDIUM] Add validation to prevent overwriting existing discount in manual matching [app/Http/Controllers/BonusMatchingController.php:64]
- [x] [AI-Review][MEDIUM] Add logging for manual bonus matching actions for audit trail [app/Http/Controllers/BonusMatchingController.php:65-73]
- [x] [AI-Review][MEDIUM] Document sprint-status.yaml in File List section
- [x] [AI-Review][MEDIUM] Clarify AC #2 threshold interpretation (>80% vs >=80%) [app/Services/BonusMatchingService.php:11]
- [x] [AI-Review][LOW] Move inline Alpine.js component to app.js [resources/views/receipts/match-bonuses.blade.php:131-173]
- [x] [AI-Review][LOW] Remove redundant null coalesce in getTotalDiscountAttribute [app/Models/Receipt.php:45]
- [x] [AI-Review][LOW] Add docblock to getTotalDiscountAttribute accessor [app/Models/Receipt.php:43]
- [x] [AI-Review][LOW] Use localization for error messages instead of hardcoded strings [app/Http/Controllers/BonusMatchingController.php]

## Dev Notes

### AH Receipt Bonus Section Format

From actual receipt analysis, the bonus section appears:
- After "Subtotaal" line
- Before "TOTAAL" line
- Format: `BONUS                  [ABBREVIATED_NAME]                           -[AMOUNT]`

Example bonus patterns:
```
BONUS                  AHPAPRIKAROO                                           -0,58
BONUS                  AHKOMKOM500G                                           -1,20
BONUS                  BEEMSTER                                               -2,50
```

### Matching Challenges

1. **Name abbreviation**: "PAPRIKA" becomes "AHPAPRIKAROO", "KOMKOMMER 500G" becomes "AHKOMKOM500G"
2. **AH prefix**: Bonus lines often have "AH" prefix stripped
3. **Length limits**: Names are truncated to fit receipt width
4. **Multiple matches**: Same product type might appear multiple times

### Proposed Matching Algorithm

```php
class BonusMatchingService
{
    public function matchBonusToProduct(string $bonusName, array $lineItems): ?MatchResult
    {
        // 1. Normalize bonus name (remove AH prefix, spaces)
        $normalizedBonus = $this->normalize($bonusName);

        // 2. For each line item with is_bonus = true
        foreach ($lineItems as $item) {
            if (!$item->is_bonus) continue;

            $normalizedProduct = $this->normalize($item->product->name);

            // 3. Calculate similarity
            $similarity = $this->calculateSimilarity($normalizedBonus, $normalizedProduct);

            if ($similarity >= 0.8) {
                return new MatchResult($item, $similarity, 'auto');
            }
        }

        return null; // No confident match
    }

    protected function calculateSimilarity(string $a, string $b): float
    {
        // Try multiple strategies:
        // 1. Check if one contains the other
        if (str_contains($a, $b) || str_contains($b, $a)) {
            return 0.9;
        }

        // 2. Levenshtein distance normalized
        $maxLen = max(strlen($a), strlen($b));
        $distance = levenshtein($a, $b);
        return 1 - ($distance / $maxLen);
    }
}
```

### Database Schema Additions

```sql
-- Add to line_items
ALTER TABLE line_items ADD COLUMN discount_amount DECIMAL(8,2) NULL;

-- New table for unmatched bonuses
CREATE TABLE unmatched_bonuses (
    id INTEGER PRIMARY KEY,
    receipt_id INTEGER NOT NULL REFERENCES receipts(id) ON DELETE CASCADE,
    raw_name VARCHAR(255) NOT NULL,
    discount_amount DECIMAL(8,2) NOT NULL,
    matched_line_item_id INTEGER NULL REFERENCES line_items(id) ON DELETE SET NULL,
    status ENUM('pending', 'matched', 'not_applicable') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### UI Considerations

Receipt detail page updates:
- Show discount amount in red/green next to bonus items: "PAPRIKA €3.56 -€0.58"
- Show effective price: "€2.98 after bonus"
- Total savings badge: "You saved €4.28 on this receipt"

Manual matching interface:
- List unmatched bonuses on left
- Drag-and-drop or click-to-select matching
- Show similarity scores as hints
- Allow "Not applicable" for store-wide discounts

### References

- [Source: Story 1.2] - Original receipt parsing implementation
- [Source: architecture.md] - Service layer patterns
- [Source: project-context.md] - Error handling patterns

## Dev Agent Record

### Implementation Plan
- Implemented database schema with discount_amount column on line_items and unmatched_bonuses table
- Extended ReceiptParsingService with extractBonusSection() and parseBonusLine() methods
- Created BonusMatchingService with fuzzy matching using Levenshtein and similar_text algorithms
- Updated UploadController to process bonus matches automatically with 80% confidence threshold
- Created manual bonus matching UI with Alpine.js for AJAX interactions
- Updated receipt detail view to show discounts, effective prices, and total savings

### Debug Log
- Fixed variable naming bug in UploadController where $result was used instead of $transactionResult
- Fixed bonus section extraction: regex now handles "46 SUBTOTAAL 145,99" format with item count prefix
- Added support for percentage discount lines: "35% K BEEMSTER -3,59"
- Fixed product parsing: "pin" keyword was incorrectly filtering products like "CAMPINA VLA" and "IGLO SPINAZ"
- Changed isNonProductLine() to use word boundaries for short keywords that could appear inside product names
- Fixed regex pattern to allow "35%" suffix on product lines (e.g., "1 BEEMSTER 10,27 35%")
- Products with percentage discounts (35%) are now marked as is_bonus=true for matching
- Fixed form feed character (0x0C) in PDF text causing LAY'S CHIPS line to not parse

### Completion Notes
- All 7 tasks completed successfully
- All 10 code review follow-up items addressed
- 68 tests pass (including 2 new auto-bonus-matching tests)
- Unit tests cover bonus extraction regex and fuzzy matching algorithm
- Feature tests cover receipt display with discounts, manual bonus matching, and auto-matching during upload

## File List

### New Files
- `app/DTOs/ParsedBonus.php` - DTO for parsed bonus data
- `app/DTOs/MatchResult.php` - DTO for bonus matching result (moved from BonusMatchingService)
- `app/Models/UnmatchedBonus.php` - Eloquent model for unmatched bonuses
- `app/Services/BonusMatchingService.php` - Fuzzy matching service with confidence threshold
- `app/Http/Controllers/BonusMatchingController.php` - Controller for manual bonus matching
- `resources/views/receipts/match-bonuses.blade.php` - UI for manual bonus matching
- `database/migrations/2026_01_03_153637_add_discount_amount_to_line_items_table.php`
- `database/migrations/2026_01_03_153637_create_unmatched_bonuses_table.php`
- `tests/Unit/BonusMatchingServiceTest.php` - Unit tests for matching service
- `tests/Feature/BonusMatchingTest.php` - Feature tests for bonus matching
- `lang/en/validation.php` - Custom validation messages for bonus matching

### Modified Files
- `_bmad-output/implementation-artifacts/sprint-status.yaml` - Updated story status
- `app/DTOs/ParseResult.php` - Added bonuses array
- `app/Models/LineItem.php` - Added discount_amount, effective_price accessor
- `app/Models/Receipt.php` - Added unmatchedBonuses, pendingUnmatchedBonuses, totalDiscount with docblock
- `app/Services/ReceiptParsingService.php` - Added extractBonusSection, parseBonusLine
- `app/Http/Controllers/UploadController.php` - Added bonus processing, processBonuses method
- `app/Http/Controllers/ReceiptController.php` - Added unmatchedBonusCount, totalDiscount
- `resources/views/receipts/show.blade.php` - Added discount columns, savings display
- `resources/js/app.js` - Added bonusMatching Alpine.js component
- `routes/web.php` - Added bonus matching routes
- `tests/Unit/ReceiptParsingServiceTest.php` - Added bonus extraction tests
- `tests/Feature/ReceiptUploadTest.php` - Added auto-bonus-matching and unmatched bonus tests

## Change Log

- 2026-01-03: Implemented Story 1.6 - Bonus Discount Parsing & Matching
  - Added bonus section extraction from AH receipts
  - Implemented fuzzy matching with 80% confidence threshold
  - Created manual bonus matching UI for unmatched bonuses
  - Updated receipt detail view with discounts and total savings
- 2026-01-03: Addressed code review findings - 10 items resolved
  - Moved MatchResult class to separate DTO file
  - Added feature tests for auto-bonus-matching during upload
  - Added discount overwrite validation with HTTP 409 response
  - Added audit logging for manual bonus matching actions
  - Documented threshold interpretation (>=80%)
  - Moved inline Alpine.js component to app.js
  - Fixed getTotalDiscountAttribute (removed null coalesce, added docblock)
  - Added localization for error messages (lang/en/validation.php)
- 2026-01-04: Final code review - 7 issues fixed
  - Added composite index on unmatched_bonuses (receipt_id, status) for query performance
  - Fixed duplicate sum calculation in ReceiptController - now uses total_discount accessor
  - Added division-by-zero guard in BonusMatchingService.calculateSimilarity()
  - Added CSRF token null-check with user-friendly error in bonusMatching component
  - Extracted redirect delay to configurable constant (redirectDelayMs)
  - Added TODO comment for future multi-store support in UploadController
  - All 70 tests pass
