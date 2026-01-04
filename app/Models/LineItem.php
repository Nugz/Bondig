<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineItem extends Model
{
    protected $fillable = [
        'receipt_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'is_bonus',
        'discount_amount',
        'raw_text',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_bonus' => 'boolean',
        'discount_amount' => 'decimal:2',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->total_price - ($this->discount_amount ?? 0);
    }
}
