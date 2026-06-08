<?php

namespace App\Product\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPhotoUploaded implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $token,
        public string $productUuid,
        public string $imageUrl,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('photo-upload.'.$this->token);
    }

    public function broadcastAs(): string
    {
        return 'photo.uploaded';
    }

    public function broadcastWith(): array
    {
        return [
            'product_uuid' => $this->productUuid,
            'image_src' => $this->imageUrl,
        ];
    }
}
