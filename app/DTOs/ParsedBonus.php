<?php

namespace App\DTOs;

class ParsedBonus
{
    public function __construct(
        public string $rawName,
        public float $discountAmount,
    ) {}
}
