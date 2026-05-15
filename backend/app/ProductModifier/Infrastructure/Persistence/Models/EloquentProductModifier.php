<?php

namespace App\ProductModifier\Infrastructure\Persistence\Models;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentProductModifier extends Model
{
    use SoftDeletes;

    protected $table = 'product_modifiers';

    protected $fillable = [
        'product_id',
        'uuid',
        'name',
        'type',
        'is_required',
        'selection_type',
        'price',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_required' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }
}
