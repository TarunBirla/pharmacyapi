<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'generic_name',
        'generic_composition_id',
        'brand_id',
        'manufacturer_id',
        'category_id',
        'dosage_form',
        'strength',
        'hsn_code',
        'gst_rate',
        'primary_unit_id',
        'secondary_unit_id',
        'unit_conversion_factor',
        'packaging_type',
        'rack_location',
        'shelf_location',
        'prescription_type',
        'storage_temperature',
        'min_stock_level',
        'max_stock_level',
        'reorder_level',
        'reorder_quantity',
        'is_active',
        'is_narcotic',
        'notes',
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'unit_conversion_factor' => 'decimal:4',
        'min_stock_level' => 'decimal:4',
        'max_stock_level' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
        'is_active' => 'boolean',
        'is_narcotic' => 'boolean',
    ];

    protected $appends = [
        'mrp',
        'purchase_rate',
        'retail_price',
        'barcode',
    ];

    public function genericComposition(): BelongsTo
    {
        return $this->belongsTo(GenericComposition::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function primaryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'primary_unit_id');
    }

    public function secondaryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'secondary_unit_id');
    }

    public function compositions(): HasMany
    {
        return $this->hasMany(ProductComposition::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getMrpAttribute(): float
    {
        $batch = $this->batches->sortByDesc('created_at')->first();
        return $batch ? (float)$batch->mrp : 0.00;
    }

    public function getPurchaseRateAttribute(): float
    {
        $batch = $this->batches->sortByDesc('created_at')->first();
        return $batch ? (float)$batch->purchase_rate : 0.00;
    }

    public function getRetailPriceAttribute(): float
    {
        $batch = $this->batches->sortByDesc('created_at')->first();
        return $batch ? (float)($batch->sale_price ?: $batch->mrp) : 0.00;
    }

    public function getBarcodeAttribute(): ?string
    {
        return $this->barcodes->first()?->barcode ?? null;
    }

    /**
     * Scope search across name, code, generic, brand, manufacturer, or barcodes.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('generic_name', 'like', "%{$term}%")
              ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$term}%"))
              ->orWhereHas('manufacturer', fn ($m) => $m->where('name', 'like', "%{$term}%"))
              ->orWhereHas('barcodes', fn ($bc) => $bc->where('barcode', $term));
        });
    }

    /**
     * Fetch medically equivalent alternative brands with same active composition.
     */
    public function getSubstitutes()
    {
        if (!$this->generic_composition_id) {
            return collect();
        }

        return static::where('generic_composition_id', $this->generic_composition_id)
            ->where('id', '!=', $this->id)
            ->where('is_active', true)
            ->with(['brand', 'manufacturer', 'primaryUnit'])
            ->withSum(['batches as total_stock' => function ($q) {
                $q->where('status', 'active')->where('expiry_date', '>', now()->toDateString());
            }], 'quantity_available')
            ->get();
    }
}
