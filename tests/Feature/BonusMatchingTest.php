<?php

namespace Tests\Feature;

use App\Models\LineItem;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\UnmatchedBonus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonusMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected Receipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 10.00,
            'raw_text' => 'Test receipt',
        ]);
    }

    public function test_receipt_shows_unmatched_bonuses_count(): void
    {
        UnmatchedBonus::create([
            'receipt_id' => $this->receipt->id,
            'raw_name' => 'AHPAPRIKAROO',
            'discount_amount' => 0.58,
            'status' => 'pending',
        ]);

        UnmatchedBonus::create([
            'receipt_id' => $this->receipt->id,
            'raw_name' => 'BEEMSTER',
            'discount_amount' => 1.50,
            'status' => 'pending',
        ]);

        $response = $this->get(route('receipts.show', $this->receipt));

        $response->assertStatus(200);
        $response->assertSee('2 unmatched bonuses');
        $response->assertSee('Match Now');
    }

    public function test_receipt_does_not_show_alert_when_no_unmatched_bonuses(): void
    {
        $response = $this->get(route('receipts.show', $this->receipt));

        $response->assertStatus(200);
        $response->assertDontSee('unmatched bonus');
    }

    public function test_match_bonuses_page_shows_pending_bonuses(): void
    {
        $product = Product::create([
            'name' => 'PAPRIKA',
            'normalized_name' => 'paprika',
        ]);

        LineItem::create([
            'receipt_id' => $this->receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1.99,
            'total_price' => 1.99,
            'is_bonus' => true,
        ]);

        UnmatchedBonus::create([
            'receipt_id' => $this->receipt->id,
            'raw_name' => 'AHPAPRIKAROO',
            'discount_amount' => 0.58,
            'status' => 'pending',
        ]);

        $response = $this->get(route('receipts.match-bonuses', $this->receipt));

        $response->assertStatus(200);
        $response->assertSee('AHPAPRIKAROO');
        $response->assertSee('0,58');
        $response->assertSee('PAPRIKA');
    }

    public function test_match_bonuses_page_shows_success_when_all_matched(): void
    {
        $response = $this->get(route('receipts.match-bonuses', $this->receipt));

        $response->assertStatus(200);
        $response->assertSee('All bonuses matched');
    }

    public function test_can_match_bonus_to_line_item(): void
    {
        $product = Product::create([
            'name' => 'PAPRIKA',
            'normalized_name' => 'paprika',
        ]);

        $lineItem = LineItem::create([
            'receipt_id' => $this->receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1.99,
            'total_price' => 1.99,
            'is_bonus' => true,
        ]);

        $bonus = UnmatchedBonus::create([
            'receipt_id' => $this->receipt->id,
            'raw_name' => 'AHPAPRIKAROO',
            'discount_amount' => 0.58,
            'status' => 'pending',
        ]);

        $response = $this->postJson(
            route('receipts.match-bonus', [$this->receipt, $bonus]),
            ['line_item_id' => $lineItem->id]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $bonus->refresh();
        $lineItem->refresh();

        $this->assertEquals('matched', $bonus->status);
        $this->assertEquals($lineItem->id, $bonus->matched_line_item_id);
        $this->assertEquals(0.58, $lineItem->discount_amount);
    }

    public function test_can_mark_bonus_as_not_applicable(): void
    {
        $bonus = UnmatchedBonus::create([
            'receipt_id' => $this->receipt->id,
            'raw_name' => 'STORE DISCOUNT',
            'discount_amount' => 2.00,
            'status' => 'pending',
        ]);

        $response = $this->postJson(
            route('receipts.match-bonus', [$this->receipt, $bonus]),
            ['not_applicable' => true]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $bonus->refresh();

        $this->assertEquals('not_applicable', $bonus->status);
        $this->assertNull($bonus->matched_line_item_id);
    }

    public function test_cannot_match_bonus_from_different_receipt(): void
    {
        $otherReceipt = Receipt::create([
            'store' => 'Albert Heijn',
            'purchased_at' => now(),
            'total_amount' => 20.00,
            'raw_text' => 'Other receipt',
        ]);

        $bonus = UnmatchedBonus::create([
            'receipt_id' => $otherReceipt->id,
            'raw_name' => 'AHPAPRIKAROO',
            'discount_amount' => 0.58,
            'status' => 'pending',
        ]);

        $response = $this->postJson(
            route('receipts.match-bonus', [$this->receipt, $bonus]),
            ['not_applicable' => true]
        );

        $response->assertStatus(403);
    }

    public function test_receipt_shows_discount_amounts_on_line_items(): void
    {
        $product = Product::create([
            'name' => 'PAPRIKA',
            'normalized_name' => 'paprika',
        ]);

        LineItem::create([
            'receipt_id' => $this->receipt->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1.99,
            'total_price' => 1.99,
            'is_bonus' => true,
            'discount_amount' => 0.58,
        ]);

        $response = $this->get(route('receipts.show', $this->receipt));

        $response->assertStatus(200);
        $response->assertSee('PAPRIKA');
        // Discount and effective price should be visible
        $response->assertSee('0,58'); // Discount amount
        $response->assertSee('1,41'); // Effective price (1.99 - 0.58)
    }

    public function test_receipt_shows_total_savings(): void
    {
        $product1 = Product::create([
            'name' => 'PAPRIKA',
            'normalized_name' => 'paprika',
        ]);

        $product2 = Product::create([
            'name' => 'KAAS',
            'normalized_name' => 'kaas',
        ]);

        LineItem::create([
            'receipt_id' => $this->receipt->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'unit_price' => 1.99,
            'total_price' => 1.99,
            'is_bonus' => true,
            'discount_amount' => 0.58,
        ]);

        LineItem::create([
            'receipt_id' => $this->receipt->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 5.00,
            'total_price' => 5.00,
            'is_bonus' => true,
            'discount_amount' => 1.50,
        ]);

        $response = $this->get(route('receipts.show', $this->receipt));

        $response->assertStatus(200);
        $response->assertSee('Total Savings');
        $response->assertSee('2,08'); // Total discount (0.58 + 1.50)
    }
}
