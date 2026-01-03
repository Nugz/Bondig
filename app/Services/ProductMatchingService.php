<?php

namespace App\Services;

use App\Models\Product;

class ProductMatchingService
{
    public function normalize(string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }

    public function findOrCreate(string $rawName): Product
    {
        $normalized = $this->normalize($rawName);

        return Product::firstOrCreate(
            ['normalized_name' => $normalized],
            [
                'name' => $rawName,
                'category_id' => null,
                'confidence' => null,
                'user_confirmed' => false,
            ]
        );
    }
}
