<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentGuestSession extends Model
{
    protected $table = 'guest_sessions';

    protected $fillable = [
        'uuid',
        'table_qr_token_id',
        'order_id',
        'restaurant_id',
        'session_token',
        'identity_mode',
        'guest_name',
        'opened_table',
        'diners_count',
        'check_requested_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_table'        => 'boolean',
            'diners_count'        => 'integer',
            'check_requested_at'  => 'datetime',
            'expires_at'          => 'datetime',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }

    public function tableQrToken(): BelongsTo
    {
        return $this->belongsTo(EloquentTableQrToken::class, 'table_qr_token_id');
    }
}
