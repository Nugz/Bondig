<?php

namespace App\DTOs;

class ParsedLine
{
    public function __construct(
        public string $name,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
        public bool $isBonus,
        public ?string $rawText = null,
    ) {}
}
