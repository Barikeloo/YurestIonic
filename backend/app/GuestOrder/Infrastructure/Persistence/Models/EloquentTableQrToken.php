<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentTableQrToken extends Model
{
    protected $table = 'table_qr_tokens';

    protected $fillable = [
        'uuid',
        'table_id',
        'restaurant_id',
        'token',
        'catalog_version',
    ];

    protected function casts(): array
    {
        return [
            'catalog_version' => 'integer',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(EloquentTable::class, 'table_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }
}
