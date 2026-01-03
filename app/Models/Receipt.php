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
}
