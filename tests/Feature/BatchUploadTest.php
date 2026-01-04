<?php

namespace Tests\Feature;

use App\DTOs\ParsedBonus;
use App\DTOs\ParsedLine;
use App\DTOs\ParseResult;
use App\Services\ReceiptParsingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_single_upload_returns_json_when_requested(): void
    {
        $this->mockParsingService(new ParseResult(
            success: true,
            lines: [
                new ParsedLine(
                    name: 'Test Product',
                    quantity: 1,
                    unitPrice: 2.50,
                    totalPrice: 2.50,
                    rawText: 'Test Product 2.50',
                    isBonus: false,
                ),
            ],
            bonuses: [],
            total: 2.50,
            purchasedAt: now(),
            rawText: 'Test receipt',
        ));

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $response = $this->postJson(route('upload.store'), [
            'pdf' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'filename',
                'receipt_id',
                'item_count',
                'total_amount',
            ]);

        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_batch_upload_processes_multiple_valid_pdfs(): void
    {
        // Each receipt needs unique date/total to avoid duplicate detection
        $files = [
            UploadedFile::fake()->create('receipt1.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('receipt2.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('receipt3.pdf', 100, 'application/pdf'),
        ];

        $mockService = $this->mock(ReceiptParsingService::class);

        // Use instance variable to avoid static counter test isolation issues
        $callCount = 0;

        // Each file gets different purchase date to avoid duplicate detection
        $mockService->shouldReceive('parseFromPdf')
            ->andReturnUsing(function ($file) use (&$callCount) {
                $callCount++;

                return new ParseResult(
                    success: true,
                    lines: [
                        new ParsedLine(
                            name: 'Product ' . $callCount,
                            quantity: 1,
                            unitPrice: 5.00 + $callCount,
                            totalPrice: 5.00 + $callCount,
                            rawText: 'Product ' . $callCount . ' ' . (5.00 + $callCount),
                            isBonus: false,
                        ),
                    ],
                    bonuses: [],
                    total: 5.00 + $callCount, // Different total for each
                    purchasedAt: now()->subDays($callCount), // Different date for each
                    rawText: 'Test receipt ' . $callCount,
                );
            });

        // Upload files one by one via AJAX (simulating client-side batch)
        $results = [];
        foreach ($files as $file) {
            $response = $this->postJson(route('upload.store'), [
                'pdf' => $file,
            ]);

            $response->assertStatus(200);
            $results[] = $response->json();
        }

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertEquals('success', $result['status']);
        }

        $this->assertDatabaseCount('receipts', 3);
    }

    public function test_batch_upload_handles_partial_failures(): void
    {
        // First file succeeds
        $successResult = new ParseResult(
            success: true,
            lines: [
                new ParsedLine(
                    name: 'Product 1',
                    quantity: 1,
                    unitPrice: 5.00,
                    totalPrice: 5.00,
                    rawText: 'Product 1 5.00',
                    isBonus: false,
                ),
            ],
            bonuses: [],
            total: 5.00,
            purchasedAt: now(),
            rawText: 'Test receipt',
        );

        // Second file fails
        $failedResult = new ParseResult(
            success: false,
            lines: [],
            bonuses: [],
            total: 0,
            purchasedAt: null,
            rawText: '',
            errors: ['Could not extract data from PDF'],
        );

        $mockService = $this->mock(ReceiptParsingService::class);
        $mockService->shouldReceive('parseFromPdf')
            ->once()
            ->andReturn($successResult);
        $mockService->shouldReceive('parseFromPdf')
            ->once()
            ->andReturn($failedResult);

        // Upload first file - should succeed
        $response1 = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('valid.pdf', 100, 'application/pdf'),
        ]);

        $response1->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Upload second file - should fail
        $response2 = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('invalid.pdf', 100, 'application/pdf'),
        ]);

        $response2->assertStatus(422)
            ->assertJson(['status' => 'failed']);

        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseCount('import_logs', 2);
    }

    public function test_batch_upload_detects_duplicates(): void
    {
        $parseResult = new ParseResult(
            success: true,
            lines: [
                new ParsedLine(
                    name: 'Product 1',
                    quantity: 1,
                    unitPrice: 10.00,
                    totalPrice: 10.00,
                    rawText: 'Product 1 10.00',
                    isBonus: false,
                ),
            ],
            bonuses: [],
            total: 10.00,
            purchasedAt: now()->startOfDay(),
            rawText: 'Test receipt',
        );

        $this->mockParsingService($parseResult);

        // Upload first file
        $response1 = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt1.pdf', 100, 'application/pdf'),
        ]);

        $response1->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Upload second file with same store, date, and total - should be duplicate
        $response2 = $this->postJson(route('upload.store'), [
            'pdf' => UploadedFile::fake()->create('receipt2.pdf', 100, 'application/pdf'),
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'status' => 'duplicate',
                'message' => 'Duplicate receipt detected - already imported',
            ]);

        // Only 1 receipt should be in database
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_batch_upload_validation_rejects_non_pdf(): void
    {
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->postJson(route('upload.store'), [
            'pdf' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pdf']);
    }

    public function test_batch_upload_validation_rejects_oversized_file(): void
    {
        // Create a file larger than 10MB (10240KB)
        $file = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf');

        $response = $this->postJson(route('upload.store'), [
            'pdf' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pdf']);
    }

    public function test_json_response_includes_unmatched_bonus_count(): void
    {
        $this->mockParsingService(new ParseResult(
            success: true,
            lines: [
                new ParsedLine(
                    name: 'Test Product',
                    quantity: 1,
                    unitPrice: 2.50,
                    totalPrice: 2.50,
                    rawText: 'Test Product 2.50',
                    isBonus: false,
                ),
            ],
            bonuses: [
                new ParsedBonus(
                    rawName: 'Unknown Bonus',
                    discountAmount: 0.50,
                ),
            ],
            total: 2.00,
            purchasedAt: now(),
            rawText: 'Test receipt',
        ));

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $response = $this->postJson(route('upload.store'), [
            'pdf' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unmatched_bonus_count' => 1,
            ]);
    }

    public function test_server_error_returns_proper_json_for_client_handling(): void
    {
        // Simulate a server-side exception that the client would need to handle
        $this->mock(ReceiptParsingService::class)
            ->shouldReceive('parseFromPdf')
            ->andThrow(new \RuntimeException('Database connection failed'));

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $response = $this->postJson(route('upload.store'), [
            'pdf' => $file,
        ]);

        // Service catches exception and returns proper JSON error response
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'failed',
            ])
            ->assertJsonStructure([
                'status',
                'filename',
                'message',
                'errors',
            ]);

        // Verify the error was logged to import_logs
        $this->assertDatabaseHas('import_logs', [
            'status' => 'failed',
            'error_count' => 1,
        ]);
    }

    protected function mockParsingService(ParseResult $result): void
    {
        $this->mock(ReceiptParsingService::class)
            ->shouldReceive('parseFromPdf')
            ->andReturn($result);
    }
}
