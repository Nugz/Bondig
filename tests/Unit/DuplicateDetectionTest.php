<?php

namespace Tests\Unit;

use App\DTOs\ParsedLine;
use App\DTOs\ParseResult;
use App\Models\ImportLog;
use App\Models\Receipt;
use App\Services\ReceiptUploadService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected ReceiptUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReceiptUploadService::class);
    }

    /**
     * Call the protected isDuplicate method using reflection.
     */
    protected function callIsDuplicate(ParseResult $result): bool
    {
        $method = new ReflectionMethod(ReceiptUploadService::class, 'isDuplicate');

        return $method->invoke($this->service, $result);
    }

    public function test_exact_match_is_duplicate(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        $result = new ParseResult(
            success: true,
            purchasedAt: Carbon::parse('2026-01-15'),
            total: 127.43,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertTrue($this->callIsDuplicate($result));
    }

    public function test_same_date_different_total_is_not_duplicate(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Same date but different total
        $result = new ParseResult(
            success: true,
            purchasedAt: Carbon::parse('2026-01-15'),
            total: 89.99,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertFalse($this->callIsDuplicate($result));
    }

    public function test_different_date_same_total_is_not_duplicate(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Different date but same total
        $result = new ParseResult(
            success: true,
            purchasedAt: Carbon::parse('2026-01-16'),
            total: 127.43,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertFalse($this->callIsDuplicate($result));
    }

    public function test_null_date_is_not_duplicate(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Receipt with null date should not be flagged as duplicate
        $result = new ParseResult(
            success: true,
            purchasedAt: null,
            total: 127.43,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertFalse($this->callIsDuplicate($result));
    }

    public function test_same_date_different_time_is_duplicate(): void
    {
        // Create existing receipt at morning time
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 09:00:00',
            'total_amount' => 50.00,
        ]);

        // Same date but different time (evening) - should still be duplicate
        $result = new ParseResult(
            success: true,
            purchasedAt: Carbon::parse('2026-01-15 18:30:00'),
            total: 50.00,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertTrue($this->callIsDuplicate($result));
    }

    public function test_empty_database_is_not_duplicate(): void
    {
        // No existing receipts
        $result = new ParseResult(
            success: true,
            purchasedAt: Carbon::parse('2026-01-15'),
            total: 127.43,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertFalse($this->callIsDuplicate($result));
    }

    public function test_very_similar_total_is_not_duplicate(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Same date but slightly different total (0.01 difference)
        $result = new ParseResult(
            success: true,
            purchasedAt: Carbon::parse('2026-01-15'),
            total: 127.44,
            lines: [],
            bonuses: [],
            rawText: 'Test receipt',
        );

        $this->assertFalse($this->callIsDuplicate($result));
    }

    public function test_database_constraint_prevents_duplicate_receipts(): void
    {
        // Create existing receipt with unique constraint columns set
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'purchased_date' => '2026-01-15',
            'total_amount' => 127.43,
        ]);

        // Attempt to create a duplicate directly (bypasses isDuplicate check)
        $this->expectException(UniqueConstraintViolationException::class);

        Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 09:00:00', // Different time, same date
            'purchased_date' => '2026-01-15',
            'total_amount' => 127.43,
            'pdf_path' => 'receipts/test.pdf',
            'raw_text' => 'Test receipt',
        ]);
    }

    public function test_constraint_allows_different_store_same_date_total(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'purchased_date' => '2026-01-15',
            'total_amount' => 127.43,
        ]);

        // Different store should be allowed (if we ever support other stores)
        // This verifies the constraint is on (store, date, total) not just (date, total)
        $receipt = Receipt::create([
            'store' => 'Jumbo', // Different store
            'purchased_at' => '2026-01-15 14:30:00',
            'purchased_date' => '2026-01-15',
            'total_amount' => 127.43,
            'pdf_path' => 'receipts/test.pdf',
            'raw_text' => 'Test receipt',
        ]);

        $this->assertNotNull($receipt->id);
        $this->assertDatabaseCount('receipts', 2);
    }

    public function test_constraint_allows_same_store_date_different_total(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'purchased_date' => '2026-01-15',
            'total_amount' => 127.43,
        ]);

        // Same store and date but different total should be allowed
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 18:00:00',
            'purchased_date' => '2026-01-15',
            'total_amount' => 45.99, // Different total
            'pdf_path' => 'receipts/test.pdf',
            'raw_text' => 'Test receipt',
        ]);

        $this->assertNotNull($receipt->id);
        $this->assertDatabaseCount('receipts', 2);
    }
}
