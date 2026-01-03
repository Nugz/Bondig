<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductMatchingService();
    }

    public function test_normalize_handles_whitespace_and_case(): void
    {
        $this->assertEquals('ah melk halfvol', $this->service->normalize('AH Melk  Halfvol'));
    }

    public function test_normalize_trims_leading_and_trailing_whitespace(): void
    {
        $this->assertEquals('product name', $this->service->normalize('  Product Name  '));
    }

    public function test_normalize_collapses_multiple_spaces(): void
    {
        $this->assertEquals('product with spaces', $this->service->normalize('Product   With    Spaces'));
    }

    public function test_normalize_converts_to_lowercase(): void
    {
        $this->assertEquals('uppercase product', $this->service->normalize('UPPERCASE PRODUCT'));
    }

    public function test_normalize_handles_empty_string(): void
    {
        $this->assertEquals('', $this->service->normalize(''));
    }

    public function test_normalize_handles_single_word(): void
    {
        $this->assertEquals('melk', $this->service->normalize('Melk'));
    }

    public function test_find_or_create_creates_new_product(): void
    {
        $this->assertDatabaseMissing('products', ['normalized_name' => 'ah melk halfvol']);

        $product = $this->service->findOrCreate('AH Melk  Halfvol');

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('AH Melk  Halfvol', $product->name);
        $this->assertEquals('ah melk halfvol', $product->normalized_name);
        $this->assertNull($product->category_id);
        $this->assertNull($product->confidence);
        $this->assertFalse($product->user_confirmed);
        $this->assertDatabaseHas('products', ['normalized_name' => 'ah melk halfvol']);
    }

    public function test_find_or_create_finds_existing_product(): void
    {
        $existingProduct = Product::create([
            'name' => 'AH Melk Halfvol',
            'normalized_name' => 'ah melk halfvol',
            'category_id' => null,
            'confidence' => null,
            'user_confirmed' => false,
        ]);

        $foundProduct = $this->service->findOrCreate('AH  Melk   Halfvol');

        $this->assertEquals($existingProduct->id, $foundProduct->id);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_find_or_create_matches_on_normalized_name(): void
    {
        $product1 = $this->service->findOrCreate('AH Melk');
        $product2 = $this->service->findOrCreate('ah melk');
        $product3 = $this->service->findOrCreate('AH  MELK');

        $this->assertEquals($product1->id, $product2->id);
        $this->assertEquals($product2->id, $product3->id);
        $this->assertDatabaseCount('products', 1);
    }
}
