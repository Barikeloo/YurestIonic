<?php

namespace App\Zone\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentZone extends Model
{
    use SoftDeletes;

    protected $table = 'zones';

    protected $fillable = [
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
