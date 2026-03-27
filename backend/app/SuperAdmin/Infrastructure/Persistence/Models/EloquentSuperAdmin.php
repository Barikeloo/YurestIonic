<?php

namespace App\SuperAdmin\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class EloquentSuperAdmin extends Model
{
    protected $table = 'super_admins';

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
    ];
}
