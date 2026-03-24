<?php

namespace App\Family\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentFamily extends Model
{
    use SoftDeletes;

    protected $table = 'families';

    protected $fillable = [
        'uuid',
        'name',
        'active',
    ];

    protected function casts(): array
    {
        return [
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
}
