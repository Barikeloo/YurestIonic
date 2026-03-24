<?php

namespace App\Product\Infrastructure\Persistence\Models;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentProduct extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'family_id',
        'tax_id',
        'image_src',
        'name',
        'price',
        'stock',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'active' => 'boolean',
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
}
