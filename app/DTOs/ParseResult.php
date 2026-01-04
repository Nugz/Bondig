<?php

namespace App\DTOs;

use Carbon\Carbon;

class ParseResult
{
    public function __construct(
        public bool $success,
        public array $lines,
        public ?float $total,
        public ?Carbon $purchasedAt,
        public ?string $rawText,
        public array $errors = [],
        public array $bonuses = [],
    ) {}
}
