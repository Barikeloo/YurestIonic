<?php

namespace App\Zone\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentZone extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'zones';

    protected $fillable = [
        'restaurant_id',
        'uuid',
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
}
