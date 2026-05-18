<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeSessionLineAssignmentModel extends Model
{
    protected $table = 'charge_session_line_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'charge_session_id',
        'order_line_id',
        'diner_number',
    ];

    protected $casts = [
        'diner_number' => 'integer',
    ];
}
