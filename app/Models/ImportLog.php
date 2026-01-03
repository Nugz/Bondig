<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'receipt_id',
        'filename',
        'status',
        'error_count',
        'errors',
    ];

    protected $casts = [
        'errors' => 'array',
        'error_count' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
