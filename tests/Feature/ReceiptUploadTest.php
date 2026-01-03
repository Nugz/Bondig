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
        $this->mock(ReceiptParsingService::class)
            ->shouldReceive('parseFromPdf')
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
                rawText: 'Full receipt text here'
            ));

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
        $this->mock(ReceiptParsingService::class)
            ->shouldReceive('parseFromPdf')
            ->once()
            ->andReturn(new ParseResult(
                success: false,
                lines: [],
                total: null,
                purchasedAt: null,
                rawText: null,
                errors: ['Could not extract text from PDF']
            ));

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
}
