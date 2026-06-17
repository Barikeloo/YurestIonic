<?php

namespace App\Tables\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentTable extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'zone_id',
        'name',
        'merged_table_group_id',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'shape',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'pos_x'      => 'integer',
            'pos_y'      => 'integer',
            'width'      => 'integer',
            'height'     => 'integer',
        ];
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EloquentZone::class, 'zone_id');
    }
}
