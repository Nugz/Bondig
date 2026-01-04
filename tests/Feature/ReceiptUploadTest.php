<?php

namespace Tests\Feature;

use App\DTOs\ParsedBonus;
use App\DTOs\ParsedLine;
use App\DTOs\ParseResult;
use App\Models\Receipt;
use App\Services\ReceiptParsingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_upload_valid_pdf_creates_receipt(): void
    {
        // Mock the parsing service
        $this->partialMock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')
                ->once()
                ->andReturn(new ParseResult(
                    success: true,
                    lines: [
                        new ParsedLine(
                            name: 'AH Melk Halfvol',
                            quantity: 2,
                            unitPrice: 1.25,
                            totalPrice: 2.50,
                            isBonus: false,
                            rawText: 'AH Melk Halfvol  2 x 1.25  2.50'
                        ),
                        new ParsedLine(
                            name: 'AH Appels Elstar',
                            quantity: 1,
                            unitPrice: 2.99,
                            totalPrice: 2.99,
                            isBonus: true,
                            rawText: 'AH Appels Elstar  2.99'
                        ),
                    ],
                    total: 5.49,
                    purchasedAt: Carbon::parse('2025-12-29 11:56:00'),
                    rawText: 'Full receipt text here',
                    errors: [],
                    bonuses: []
                ));
        });

        $response = $this->post('/upload', [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('receipts', ['store' => 'Albert Heijn']);
        $this->assertDatabaseHas('products', ['normalized_name' => 'ah melk halfvol']);
        $this->assertDatabaseHas('products', ['normalized_name' => 'ah appels elstar']);
        $this->assertDatabaseHas('line_items', ['quantity' => 2, 'is_bonus' => false]);
        $this->assertDatabaseHas('line_items', ['quantity' => 1, 'is_bonus' => true]);
        $this->assertDatabaseHas('import_logs', ['status' => 'success']);
    }

    public function test_upload_rejects_non_pdf(): void
    {
        $response = $this->post('/upload', [
            'pdf' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertSessionHasErrors('pdf');
        $this->assertDatabaseMissing('receipts', ['store' => 'Albert Heijn']);
    }

    public function test_upload_rejects_missing_file(): void
    {
        $response = $this->post('/upload', []);

        $response->assertSessionHasErrors('pdf');
    }

    public function test_upload_handles_parsing_failure(): void
    {
        $this->partialMock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')
                ->once()
                ->andReturn(new ParseResult(
                    success: false,
                    lines: [],
                    total: null,
                    purchasedAt: null,
                    rawText: null,
                    errors: ['Could not extract text from PDF'],
                    bonuses: []
                ));
        });

        $response = $this->post('/upload', [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('receipts', ['store' => 'Albert Heijn']);
        $this->assertDatabaseHas('import_logs', ['status' => 'failed']);
    }

    public function test_upload_page_loads_successfully(): void
    {
        $response = $this->get('/upload');

        $response->assertStatus(200);
        $response->assertSee('Upload Receipt');
    }

    public function test_upload_rejects_file_exceeding_size_limit(): void
    {
        // Create a fake PDF that exceeds 10MB (10240 KB)
        $response = $this->post('/upload', [
            'pdf' => UploadedFile::fake()->create('large-receipt.pdf', 15000, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('pdf');
        $this->assertDatabaseMissing('receipts', ['store' => 'Albert Heijn']);
    }

    public function test_upload_with_matching_bonus_auto_links_discount(): void
    {
        // Mock parsing service to return a receipt with a bonus item and matching bonus discount
        $this->partialMock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')
                ->once()
                ->andReturn(new ParseResult(
                    success: true,
                    lines: [
                        new ParsedLine(
                            name: 'Paprika Rood',
                            quantity: 4,
                            unitPrice: 0.89,
                            totalPrice: 3.56,
                            isBonus: true,
                            rawText: '4        PAPRIKA                    0,89     3,56 B'
                        ),
                        new ParsedLine(
                            name: 'AH Melk',
                            quantity: 1,
                            unitPrice: 1.50,
                            totalPrice: 1.50,
                            isBonus: false,
                            rawText: 'AH Melk  1.50'
                        ),
                    ],
                    total: 5.06,
                    purchasedAt: Carbon::parse('2025-12-29 11:56:00'),
                    rawText: 'Full receipt text here',
                    errors: [],
                    bonuses: [
                        new ParsedBonus(
                            rawName: 'AHPAPRIKAROO',
                            discountAmount: 0.58
                        ),
                    ]
                ));
        });

        $response = $this->post('/upload', [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();

        // Verify receipt was created
        $this->assertDatabaseHas('receipts', ['store' => 'Albert Heijn']);

        // Verify the bonus item has the discount amount linked
        $this->assertDatabaseHas('line_items', [
            'is_bonus' => true,
            'discount_amount' => 0.58,
        ]);

        // Verify no unmatched bonus was created (it was auto-matched)
        $this->assertDatabaseMissing('unmatched_bonuses', [
            'raw_name' => 'AHPAPRIKAROO',
            'status' => 'pending',
        ]);
    }

    public function test_upload_with_unmatched_bonus_creates_unmatched_record(): void
    {
        // Mock parsing service with a bonus that won't match any product
        $this->partialMock(ReceiptParsingService::class, function ($mock) {
            $mock->shouldReceive('parseFromPdf')
                ->once()
                ->andReturn(new ParseResult(
                    success: true,
                    lines: [
                        new ParsedLine(
                            name: 'AH Melk',
                            quantity: 1,
                            unitPrice: 1.50,
                            totalPrice: 1.50,
                            isBonus: false,
                            rawText: 'AH Melk  1.50'
                        ),
                    ],
                    total: 1.50,
                    purchasedAt: Carbon::parse('2025-12-29 11:56:00'),
                    rawText: 'Full receipt text here',
                    errors: [],
                    bonuses: [
                        new ParsedBonus(
                            rawName: 'UNKNOWNPRODUCT',
                            discountAmount: 1.25
                        ),
                    ]
                ));
        });

        $response = $this->post('/upload', [
            'pdf' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning'); // Should show warning about unmatched bonus

        // Verify unmatched bonus was created
        $this->assertDatabaseHas('unmatched_bonuses', [
            'raw_name' => 'UNKNOWNPRODUCT',
            'discount_amount' => 1.25,
            'status' => 'pending',
        ]);
    }
}
