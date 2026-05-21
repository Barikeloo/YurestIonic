<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Persistence\Models;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentMenuItem extends Model
{
    protected $table = 'menu_items';

    protected $fillable = [
        'section_id',
        'uuid',
        'product_id',
        'variant_id',
        'extra_price',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'extra_price' => 'integer',
            'position' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(EloquentMenuSection::class, 'section_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(EloquentProductVariant::class, 'variant_id');
    }
}
