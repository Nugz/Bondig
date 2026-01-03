<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'category_id',
        'confidence',
        'user_confirmed',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'user_confirmed' => 'boolean',
    ];

    /**
     * Normalize a product name: lowercase, collapse whitespace, trim.
     */
    public static function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }

    /**
     * Mutator: automatically set normalized_name when name is set.
     */
    protected function name(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: function (string $value) {
                return [
                    'name' => $value,
                    'normalized_name' => self::normalizeName($value),
                ];
            },
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class);
    }
}
