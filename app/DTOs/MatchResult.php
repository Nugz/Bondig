<?php

namespace App\DTOs;

use App\Models\LineItem;

/**
 * Result of a bonus matching operation.
 */
class MatchResult
{
    public function __construct(
        public LineItem $lineItem,
        public float $confidence,
        public string $matchType, // 'auto' or 'manual'
    ) {}
}
