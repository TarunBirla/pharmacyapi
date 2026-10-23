<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'active_ingredient',
        'strength',
        'unit',
    ];

    protected $casts = [
        'strength' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
