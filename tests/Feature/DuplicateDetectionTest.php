<?php

namespace Tests\Feature;

use App\DTOs\ParsedLine;
use App\DTOs\ParseResult;
use App\Models\Receipt;
use App\Services\ReceiptParsingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_single_upload_rejects_duplicate(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Mock parser to return same date/total
        $this->mock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')->andReturn(
                new ParseResult(
                    success: true,
                    purchasedAt: Carbon::parse('2026-01-15'),
                    total: 127.43,
                    lines: [
                        new ParsedLine(
                            name: 'Test Product',
                            quantity: 1,
                            unitPrice: 127.43,
                            totalPrice: 127.43,
                            rawText: 'Test Product 127.43',
                            isBonus: false,
                        ),
                    ],
                    bonuses: [],
                    rawText: 'Test receipt',
                )
            );
        });

        $response = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'duplicate');
        $response->assertJsonPath('message', 'Duplicate receipt detected - already imported');

        // No new receipt created
        $this->assertDatabaseCount('receipts', 1);

        // Import log created with duplicate status
        $this->assertDatabaseHas('import_logs', [
            'status' => 'duplicate',
        ]);
    }

    public function test_single_upload_accepts_same_date_different_total(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Mock parser to return same date but different total (multiple trips same day)
        $this->mock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')->andReturn(
                new ParseResult(
                    success: true,
                    purchasedAt: Carbon::parse('2026-01-15'),
                    total: 45.99, // Different total
                    lines: [
                        new ParsedLine(
                            name: 'Another Product',
                            quantity: 1,
                            unitPrice: 45.99,
                            totalPrice: 45.99,
                            rawText: 'Another Product 45.99',
                            isBonus: false,
                        ),
                    ],
                    bonuses: [],
                    rawText: 'Test receipt',
                )
            );
        });

        $response = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        // New receipt created
        $this->assertDatabaseCount('receipts', 2);
    }

    public function test_batch_upload_with_mixed_duplicates_and_new(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 50.00,
        ]);

        $callCount = 0;
        $this->mock(ReceiptParsingService::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('parseFromPdf')
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;

                    // First and third are new, second is duplicate
                    if ($callCount === 2) {
                        return new ParseResult(
                            success: true,
                            purchasedAt: Carbon::parse('2026-01-15'),
                            total: 50.00, // Same as existing - duplicate
                            lines: [
                                new ParsedLine(
                                    name: 'Product',
                                    quantity: 1,
                                    unitPrice: 50.00,
                                    totalPrice: 50.00,
                                    rawText: 'Product 50.00',
                                    isBonus: false,
                                ),
                            ],
                            bonuses: [],
                            rawText: 'Test receipt',
                        );
                    }

                    return new ParseResult(
                        success: true,
                        purchasedAt: Carbon::parse('2026-01-' . (10 + $callCount)),
                        total: 30.00 + $callCount,
                        lines: [
                            new ParsedLine(
                                name: 'Product ' . $callCount,
                                quantity: 1,
                                unitPrice: 30.00 + $callCount,
                                totalPrice: 30.00 + $callCount,
                                rawText: 'Product ' . (30.00 + $callCount),
                                isBonus: false,
                            ),
                        ],
                        bonuses: [],
                        rawText: 'Test receipt ' . $callCount,
                    );
                });
        });

        $results = [];

        // Upload 3 files
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson(route('upload.store'), [
                'pdf' => UploadedFile::fake()->create("receipt{$i}.pdf", 100, 'application/pdf'),
            ]);
            $results[] = $response->json();
        }

        // Verify results
        $this->assertEquals('success', $results[0]['status']); // New
        $this->assertEquals('duplicate', $results[1]['status']); // Duplicate
        $this->assertEquals('success', $results[2]['status']); // New

        // Count results
        $successCount = collect($results)->where('status', 'success')->count();
        $duplicateCount = collect($results)->where('status', 'duplicate')->count();

        $this->assertEquals(2, $successCount);
        $this->assertEquals(1, $duplicateCount);

        // Original + 2 new = 3 total receipts
        $this->assertDatabaseCount('receipts', 3);
    }

    public function test_database_constraint_catches_race_condition_duplicate(): void
    {
        // This test verifies that the database unique constraint catches duplicates
        // when they bypass the isDuplicate() check (simulating a race condition)

        // Create existing receipt with specific date/total
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-20 10:00:00',
            'purchased_date' => '2026-01-20',
            'total_amount' => 75.00,
        ]);

        // Verify the database constraint works by trying to insert directly
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        // Try to insert a duplicate directly - bypassing isDuplicate() check
        // This simulates what happens in a race condition
        Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-20 15:00:00', // Same date
            'purchased_date' => '2026-01-20',
            'total_amount' => 75.00, // Same total
            'pdf_path' => 'receipts/test.pdf',
            'raw_text' => 'Test receipt',
        ]);
    }

    public function test_duplicate_detected_in_full_upload_flow(): void
    {
        // This test verifies duplicates are properly detected and handled in the full upload flow
        // Note: isDuplicate() catches this before the database constraint fires

        // Create existing receipt - this is the "first" request that completed
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-20 10:00:00',
            'purchased_date' => '2026-01-20',
            'total_amount' => 75.00,
        ]);

        // Mock parser to return same date/total
        $this->mock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')->andReturn(
                new ParseResult(
                    success: true,
                    purchasedAt: Carbon::parse('2026-01-20 15:00:00'),
                    total: 75.00,
                    lines: [
                        new ParsedLine(
                            name: 'Product',
                            quantity: 1,
                            unitPrice: 75.00,
                            totalPrice: 75.00,
                            rawText: 'Product 75.00',
                            isBonus: false,
                        ),
                    ],
                    bonuses: [],
                    rawText: 'Test receipt',
                )
            );
        });

        // Note: isDuplicate() will catch this in normal flow
        // but the constraint handler code path (lines 226-234) exists as backup
        $response = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        // Should be detected as duplicate (by isDuplicate or constraint)
        $response->assertOk();
        $response->assertJsonPath('status', 'duplicate');

        // Still only one receipt
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_form_submission_shows_duplicate_warning(): void
    {
        // Create existing receipt
        Receipt::factory()->create([
            'store' => 'Albert Heijn',
            'purchased_at' => '2026-01-15 14:30:00',
            'total_amount' => 127.43,
        ]);

        // Mock parser
        $this->mock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')->andReturn(
                new ParseResult(
                    success: true,
                    purchasedAt: Carbon::parse('2026-01-15'),
                    total: 127.43,
                    lines: [],
                    bonuses: [],
                    rawText: 'Test receipt',
                )
            );
        });

        $response = $this->post(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Duplicate receipt detected - already imported');
    }

    public function test_different_dates_same_total_both_accepted(): void
    {
        // Mock parser to return different dates with same total
        $callCount = 0;
        $this->mock(ReceiptParsingService::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('parseFromPdf')
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;

                    return new ParseResult(
                        success: true,
                        purchasedAt: Carbon::parse('2026-01-' . (10 + $callCount)),
                        total: 100.00, // Same total
                        lines: [
                            new ParsedLine(
                                name: 'Product',
                                quantity: 1,
                                unitPrice: 100.00,
                                totalPrice: 100.00,
                                rawText: 'Product 100.00',
                                isBonus: false,
                            ),
                        ],
                        bonuses: [],
                        rawText: 'Test receipt',
                    );
                });
        });

        // Upload first receipt
        $response1 = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt1.pdf', 100, 'application/pdf'),
        ]);
        $response1->assertJsonPath('status', 'success');

        // Upload second receipt - different date
        $response2 = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt2.pdf', 100, 'application/pdf'),
        ]);
        $response2->assertJsonPath('status', 'success');

        // Both receipts created
        $this->assertDatabaseCount('receipts', 2);
    }
}
