<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnmatchedBonus extends Model
{
    protected $fillable = [
        'receipt_id',
        'raw_name',
        'discount_amount',
        'matched_line_item_id',
        'status',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function matchedLineItem(): BelongsTo
    {
        return $this->belongsTo(LineItem::class, 'matched_line_item_id');
    }
}
