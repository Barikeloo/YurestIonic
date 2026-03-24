<?php

namespace App\Restaurant\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentRestaurant extends Model
{
    use SoftDeletes;

    protected $table = 'restaurants';

    protected $fillable = [
        'uuid',
        'name',
        'legal_name',
        'tax_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
