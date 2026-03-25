<?php

namespace App\Tax\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentTax extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'taxes';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'name',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
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
