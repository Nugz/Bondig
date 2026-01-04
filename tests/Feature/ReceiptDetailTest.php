<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LineItem;
use App\Models\Product;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_detail_page_displays_correctly(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 25.50,
            'pdf_path' => 'receipts/test.pdf',
            'raw_text' => 'Test receipt text',
        ]);

        $product = Product::create([
            'name' => 'AH Melk Halfvol',
            'normalized_name' => 'ah melk halfvol',
            'category_id' => null,
            'confidence' => null,
            'user_confirmed' => false,
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1.25,
            'total_price' => 2.50,
            'is_bonus' => false,
            'raw_text' => 'AH Melk Halfvol  2 x 1.25  2.50',
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('Receipt Details');
        $response->assertSee('Albert Heijn');
        $response->assertSee('AH Melk Halfvol');
        $response->assertSee('25,50');
    }

    public function test_receipt_detail_shows_bonus_items(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 10.00,
            'raw_text' => 'Test receipt',
        ]);

        $product = Product::create([
            'name' => 'AH Appels Bonus',
            'normalized_name' => 'ah appels bonus',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 2.99,
            'total_price' => 2.99,
            'is_bonus' => true,
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('BONUS');
    }

    public function test_receipt_detail_shows_multiple_line_items(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 20.00,
        ]);

        $product1 = Product::create([
            'name' => 'Product 1',
            'normalized_name' => 'product 1',
        ]);

        $product2 = Product::create([
            'name' => 'Product 2',
            'normalized_name' => 'product 2',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'unit_price' => 5.00,
            'total_price' => 5.00,
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'unit_price' => 5.00,
            'total_price' => 15.00,
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('Product 1');
        $response->assertSee('Product 2');
        $response->assertSee('2 products');
    }

    public function test_receipt_not_found_returns_404(): void
    {
        $response = $this->get('/receipts/99999');

        $response->assertStatus(404);
    }

    public function test_receipt_detail_shows_back_to_receipts_link(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 25.50,
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('Back to Receipts');
        $response->assertSee(route('receipts.index'));
    }

    public function test_receipt_detail_shows_line_item_raw_text(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 10.00,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'normalized_name' => 'test product',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 5.00,
            'total_price' => 5.00,
            'raw_text' => 'TESTPRODUCT  1 x 5.00  5.00',
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('TESTPRODUCT  1 x 5.00  5.00');
    }

    public function test_receipt_detail_shows_discount_amount(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 8.00,
        ]);

        $product = Product::create([
            'name' => 'Discount Product',
            'normalized_name' => 'discount product',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
            'discount_amount' => 2.00,
            'is_bonus' => true,
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('2,00'); // Discount amount
        $response->assertSee('8,00'); // Effective price
    }

    public function test_receipt_detail_shows_singular_product_for_one_item(): void
    {
        $receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 5.00,
        ]);

        $product = Product::create([
            'name' => 'Single Item',
            'normalized_name' => 'single item',
        ]);

        LineItem::create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 5.00,
            'total_price' => 5.00,
        ]);

        $response = $this->get("/receipts/{$receipt->id}");

        $response->assertStatus(200);
        $response->assertSee('1 product');
        $response->assertDontSee('1 products');
    }
}
