<?php

namespace Tests\Unit;

use App\DTOs\ParsedBonus;
use App\Models\LineItem;
use App\Models\Product;
use App\Services\BonusMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonusMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BonusMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BonusMatchingService();
    }

    public function test_normalize_removes_ah_prefix(): void
    {
        $this->assertEquals('PAPRIKAROOD', $this->service->normalize('AH PAPRIKAROOD'));
        $this->assertEquals('PAPRIKAROOD', $this->service->normalize('AHPAPRIKAROOD'));
    }

    public function test_normalize_removes_spaces_and_special_chars(): void
    {
        $this->assertEquals('PAPRIKAROOD500G', $this->service->normalize('PAPRIKA ROOD 500G'));
        $this->assertEquals('KOMKOMMER', $this->service->normalize('KOM-KOMMER'));
    }

    public function test_normalize_converts_to_uppercase(): void
    {
        $this->assertEquals('PAPRIKAROOD', $this->service->normalize('paprikarood'));
    }

    public function test_calculate_similarity_returns_1_for_exact_match(): void
    {
        $similarity = $this->service->calculateSimilarity('PAPRIKA', 'PAPRIKA');
        $this->assertEquals(1.0, $similarity);
    }

    public function test_calculate_similarity_returns_high_score_for_containment(): void
    {
        // "PAPRIKA" is contained in "AHPAPRIKAROO"
        $similarity = $this->service->calculateSimilarity('PAPRIKA', 'AHPAPRIKAROO');
        $this->assertGreaterThan(0.85, $similarity);
    }

    public function test_calculate_similarity_returns_low_score_for_different_strings(): void
    {
        $similarity = $this->service->calculateSimilarity('PAPRIKA', 'KOMKOMMER');
        $this->assertLessThan(0.5, $similarity);
    }

    public function test_calculate_similarity_handles_empty_strings(): void
    {
        $this->assertEquals(0.0, $this->service->calculateSimilarity('', 'PAPRIKA'));
        $this->assertEquals(0.0, $this->service->calculateSimilarity('PAPRIKA', ''));
        $this->assertEquals(0.0, $this->service->calculateSimilarity('', ''));
    }

    public function test_match_bonus_to_product_returns_match_for_similar_names(): void
    {
        $product = Product::create([
            'name' => 'PAPRIKA',
            'normalized_name' => 'paprika',
        ]);

        $lineItem = new LineItem([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1.99,
            'total_price' => 1.99,
            'is_bonus' => true,
        ]);
        $lineItem->setRelation('product', $product);

        $bonus = new ParsedBonus(
            rawName: 'AHPAPRIKAROO',
            discountAmount: 0.58
        );

        $lineItems = collect([$lineItem]);

        $result = $this->service->matchBonusToProduct($bonus, $lineItems);

        $this->assertNotNull($result);
        $this->assertGreaterThanOrEqual(0.80, $result->confidence);
        $this->assertEquals('auto', $result->matchType);
    }

    public function test_match_bonus_to_product_returns_null_for_no_match(): void
    {
        $product = Product::create([
            'name' => 'KOMKOMMER',
            'normalized_name' => 'komkommer',
        ]);

        $lineItem = new LineItem([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 2.50,
            'total_price' => 2.50,
            'is_bonus' => true,
        ]);
        $lineItem->setRelation('product', $product);

        $bonus = new ParsedBonus(
            rawName: 'BEEMSTERKAAS',
            discountAmount: 1.50
        );

        $lineItems = collect([$lineItem]);

        $result = $this->service->matchBonusToProduct($bonus, $lineItems);

        $this->assertNull($result);
    }

    public function test_match_bonus_only_considers_bonus_line_items(): void
    {
        $bonusProduct = Product::create([
            'name' => 'PAPRIKA',
            'normalized_name' => 'paprika',
        ]);

        $regularProduct = Product::create([
            'name' => 'PAPRIKAPOEDER',
            'normalized_name' => 'paprikapoeder',
        ]);

        $bonusItem = new LineItem([
            'product_id' => $bonusProduct->id,
            'quantity' => 1,
            'unit_price' => 1.99,
            'total_price' => 1.99,
            'is_bonus' => true,
        ]);
        $bonusItem->setRelation('product', $bonusProduct);

        $regularItem = new LineItem([
            'product_id' => $regularProduct->id,
            'quantity' => 1,
            'unit_price' => 2.50,
            'total_price' => 2.50,
            'is_bonus' => false,
        ]);
        $regularItem->setRelation('product', $regularProduct);

        $bonus = new ParsedBonus(
            rawName: 'AHPAPRIKAPOEDER',
            discountAmount: 0.50
        );

        // The regular item has a better match but should be ignored
        $lineItems = collect([$bonusItem, $regularItem]);

        $result = $this->service->matchBonusToProduct($bonus, $lineItems);

        // Should match to bonus item (PAPRIKA) not regular item (PAPRIKAPOEDER)
        if ($result !== null) {
            $this->assertEquals($bonusProduct->id, $result->lineItem->product_id);
        }
    }

    public function test_confidence_threshold_can_be_configured(): void
    {
        $this->assertEquals(0.80, $this->service->getConfidenceThreshold());

        $this->service->setConfidenceThreshold(0.90);
        $this->assertEquals(0.90, $this->service->getConfidenceThreshold());
    }
}
