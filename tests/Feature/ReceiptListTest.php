<?php

namespace Tests\Feature;

use App\Models\LineItem;
use App\Models\Product;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptListTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_index_page_displays_correctly(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 25.50,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'normalized_name' => 'test product',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1.25,
            'total_price' => 2.50,
        ]);

        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        $response->assertSee('Albert Heijn');
        $response->assertSee('25,50');
    }

    public function test_receipts_index_shows_receipts_sorted_by_date(): void
    {
        $oldReceipt = Receipt::create([
            'store' => 'Old Store',
            'purchased_at' => now()->subDays(10),
            'total_amount' => 10.00,
        ]);

        $newReceipt = Receipt::create([
            'store' => 'New Store',
            'purchased_at' => now(),
            'total_amount' => 20.00,
        ]);

        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['New Store', 'Old Store']);
    }

    public function test_receipts_index_shows_item_count(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 50.00,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $product = Product::create([
                'name' => "Product $i",
                'normalized_name' => "product $i",
            ]);

            LineItem::create([
                'receipt_id' => $receipt->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 10.00,
                'total_price' => 10.00,
            ]);
        }

        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        $response->assertSee('5 items');
    }

    public function test_receipts_index_paginates_results(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Receipt::create([
                'store' => "Store $i",
                'purchased_at' => now()->subDays($i),
                'total_amount' => 10.00 * $i,
            ]);
        }

        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        // Should show 20 per page (per architecture.md)
        $this->assertEquals(20, $response->viewData('receipts')->count());
        $this->assertTrue($response->viewData('receipts')->hasPages());
    }

    public function test_receipts_index_shows_empty_state(): void
    {
        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        $response->assertSee('No receipts yet');
        $response->assertSee('Upload');
    }

    public function test_receipt_row_links_to_detail_page(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 25.50,
        ]);

        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        $response->assertSee(route('receipts.show', $receipt));
    }

    public function test_receipts_index_shows_singular_item_for_one_product(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 10.00,
        ]);

        $product = Product::create([
            'name' => 'Single Product',
            'normalized_name' => 'single product',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
        ]);

        $response = $this->get(route('receipts.index'));

        $response->assertStatus(200);
        $response->assertSee('1 item');
        $response->assertDontSee('1 items');
    }
}
