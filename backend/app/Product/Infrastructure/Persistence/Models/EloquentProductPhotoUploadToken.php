<?php

namespace App\Product\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentProductPhotoUploadToken extends Model
{
    protected $table = 'product_photo_upload_tokens';

    protected $fillable = [
        'uuid',
        'token',
        'product_id',
        'restaurant_id',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id')->withoutGlobalScopes();
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }
}
