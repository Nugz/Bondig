<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'store',
        'purchased_at',
        'total_amount',
        'pdf_path',
        'raw_text',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function unmatchedBonuses(): HasMany
    {
        return $this->hasMany(UnmatchedBonus::class);
    }

    public function pendingUnmatchedBonuses(): HasMany
    {
        return $this->hasMany(UnmatchedBonus::class)->where('status', 'pending');
    }

    /**
     * Get the total discount amount for all line items on this receipt.
     *
     * @return float Total discount in euros
     */
    public function getTotalDiscountAttribute(): float
    {
        return (float) $this->lineItems->sum('discount_amount');
    }
}
