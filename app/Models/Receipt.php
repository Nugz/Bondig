<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;
    protected $fillable = [
        'store',
        'purchased_at',
        'purchased_date',
        'total_amount',
        'pdf_path',
        'raw_text',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'purchased_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Receipt $receipt) {
            // Automatically set purchased_date from purchased_at for duplicate constraint
            if ($receipt->purchased_at && !$receipt->purchased_date) {
                $receipt->purchased_date = $receipt->purchased_at->toDateString();
            }
        });

        static::updating(function (Receipt $receipt) {
            // Keep purchased_date in sync with purchased_at
            if ($receipt->isDirty('purchased_at') && $receipt->purchased_at) {
                $receipt->purchased_date = $receipt->purchased_at->toDateString();
            }
        });
    }

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
