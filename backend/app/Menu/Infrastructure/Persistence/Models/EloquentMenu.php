<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentMenu extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'menus';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'tax_id',
        'name',
        'description',
        'price',
        'active',
        'validity_from',
        'validity_to',
        'available_days',
        'available_from_time',
        'available_to_time',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'active' => 'boolean',
            'validity_from' => 'date',
            'validity_to' => 'date',
            'available_days' => 'integer',
            // available_from_time / available_to_time: dejados como string "HH:MM:SS"
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(EloquentTax::class, 'tax_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(EloquentMenuSection::class, 'menu_id')->orderBy('position');
    }
}
