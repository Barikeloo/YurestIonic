<?php

namespace App\Product\Infrastructure\Persistence\Models;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\ProductModifier\Infrastructure\Persistence\Models\EloquentProductModifier;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentProduct extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'family_id',
        'tax_id',
        'image_src',
        'name',
        'price',
        'stock',
        'active',
        'allergens',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'active' => 'boolean',
            'allergens' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(EloquentFamily::class, 'family_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(EloquentTax::class, 'tax_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(EloquentProductVariant::class, 'product_id')->orderBy('sort_order');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(EloquentProductModifier::class, 'product_id')->orderBy('sort_order');
    }
}
