<?php

namespace App\User\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class EloquentUserQuickAccess extends Model
{
    protected $table = 'user_quick_accesses';

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'device_id',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }
}
