<?php

namespace App\Tables\Infrastructure\Persistence\Models;

use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentTable extends Model
{
    use SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'uuid',
        'zone_id',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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
