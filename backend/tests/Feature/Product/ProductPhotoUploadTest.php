<?php

namespace Tests\Feature\Product;

use App\Product\Infrastructure\Broadcasting\ProductPhotoUploaded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    private function realPhoto(string $name = 'plato.png'): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAGQAAABQCAIAAABga0e4AAAACXBIWXMAAA7EAAAOxAGVKw4bAAABQ0lEQVR4nO3aQQ6CMBQAUTCeSBZcSE7FhWTBnVyQmIa2wACKTeattIjBSb+ysH49H5W2uV19ASUxFmAswFiAsQBjAcYCjAUYCzAWYCzAWICxAGMBxgKMBRgLMBZgLMBYgLEAYwHGAowFGAswFmAswFiAsQBjAcYCjAXclw+3/ZhcH7rmCxfz7+qN//ybqp3V6Nx3+xnHEFgZw1XhnIY7JZ7f6Wi4Xtz+OjSG4WLucfL04jJN9o/h7APHGyc0dE1xaWJnjuHH0DVtP84OGSubIB7Yth9L77V/DJNf2NPTeFstQC++1tH7rNys5X4NkyeWsuO2xlLlTSliLMBYgLEAYwHGAowFGAswFmAswFiAsQBjAcYCjAUYCzAWYCzAWICxAGMBxgKMBRgLMBZgLMBYgLEAYwHGAowFGAswFmAswFiAsYA3LSBdlaS7LM0AAAAASUVORK5CYII='
        );
        $path = tempnam(sys_get_temp_dir(), 'photo').'.png';
        file_put_contents($path, $png);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function createProduct(): array
    {
        $tenant = $this->createTenantSession('admin');

        $familyId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->json('id');

        $taxId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA General', 'percentage' => 21])
            ->json('id');

        $productId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/products', [
                'family_id' => $familyId,
                'tax_id' => $taxId,
                'image_src' => null,
                'name' => 'Coca Cola',
                'price' => 250,
                'stock' => 10,
                'active' => true,
            ])
            ->json('id');

        return ['session' => $tenant['session'], 'productId' => $productId];
    }

    public function test_full_qr_photo_upload_flow(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');

        ['session' => $session, 'productId' => $productId] = $this->createProduct();

        $tokenResponse = $this->withSession($session)
            ->postJson("/api/admin/products/{$productId}/photo-upload-token");

        $tokenResponse->assertStatus(201)
            ->assertJsonStructure(['token', 'upload_url', 'expires_at']);

        $token = $tokenResponse->json('token');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertStringContainsString("/u/foto/{$token}", $tokenResponse->json('upload_url'));

        $this->getJson("/api/public/photo-upload/{$token}")
            ->assertStatus(200)
            ->assertJson(['product_name' => 'Coca Cola', 'image_src' => null]);

        $upload = $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => $this->realPhoto(),
        ]);

        $upload->assertStatus(200)
            ->assertJson(['product_name' => 'Coca Cola']);
        $imageSrc = $upload->json('image_src');
        $this->assertNotNull($imageSrc);

        $this->assertNotEmpty(Storage::disk('public')->allFiles('products'));

        $this->withSession($session)->getJson("/api/admin/products/{$productId}")
            ->assertStatus(200)
            ->assertJson(['image_src' => $imageSrc]);

        $this->getJson("/api/public/photo-upload/{$token}")->assertStatus(409);
        $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => $this->realPhoto('otra.png'),
        ])->assertStatus(409);
    }

    public function test_upload_broadcasts_realtime_event_on_the_token_channel(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');
        Event::fake([ProductPhotoUploaded::class]);

        ['session' => $session, 'productId' => $productId] = $this->createProduct();

        $token = $this->withSession($session)
            ->postJson("/api/admin/products/{$productId}/photo-upload-token")
            ->json('token');

        $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => $this->realPhoto(),
        ])->assertStatus(200);

        Event::assertDispatched(
            ProductPhotoUploaded::class,
            static fn (ProductPhotoUploaded $event): bool => $event->token === $token
                && $event->productUuid === $productId
                && $event->imageUrl !== '',
        );
    }

    public function test_unknown_token_returns_404(): void
    {
        $token = str_repeat('a', 64);

        $this->getJson("/api/public/photo-upload/{$token}")->assertStatus(404);
    }

    public function test_upload_rejects_non_image_file(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');

        ['session' => $session, 'productId' => $productId] = $this->createProduct();

        $token = $this->withSession($session)
            ->postJson("/api/admin/products/{$productId}/photo-upload-token")
            ->json('token');

        $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_expired_token_returns_410(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');

        $tenant = $this->createTenantSession('admin');
        $familyId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])->json('id');
        $taxId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA', 'percentage' => 21])->json('id');
        $productId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/products', [
                'family_id' => $familyId, 'tax_id' => $taxId,
                'image_src' => null, 'name' => 'Test', 'price' => 100, 'stock' => 1,
            ])->json('id');

        config()->set('product_photos.token_ttl_minutes', 0);

        $token = $this->withSession($tenant['session'])
            ->postJson("/api/admin/products/{$productId}/photo-upload-token")
            ->json('token');

        $this->getJson("/api/public/photo-upload/{$token}")->assertStatus(410);
        $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => $this->realPhoto(),
        ])->assertStatus(410);
    }

    public function test_upload_without_photo_returns_422(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');

        ['session' => $session, 'productId' => $productId] = $this->createProduct();

        $token = $this->withSession($session)
            ->postJson("/api/admin/products/{$productId}/photo-upload-token")
            ->json('token');

        $this->postJson("/api/public/photo-upload/{$token}", [])->assertStatus(422);
    }

    public function test_upload_oversize_file_returns_422(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');

        ['session' => $session, 'productId' => $productId] = $this->createProduct();

        $token = $this->withSession($session)
            ->postJson("/api/admin/products/{$productId}/photo-upload-token")
            ->json('token');

        $oversize = UploadedFile::fake()->create('huge.jpg', 10241, 'image/jpeg');

        $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => $oversize,
        ])->assertStatus(422);
    }

    public function test_context_returns_correct_json_structure(): void
    {
        Storage::fake('public');
        config()->set('product_photos.disk', 'public');

        ['session' => $session, 'productId' => $productId] = $this->createProduct();

        $token = $this->withSession($session)
            ->postJson("/api/admin/products/{$productId}/photo-upload-token")
            ->json('token');

        $this->getJson("/api/public/photo-upload/{$token}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'product_name',
                'image_src',
                'expires_at',
                'restaurant_name',
            ]);
    }

    public function test_upload_to_nonexistent_token_returns_404(): void
    {
        $token = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        $this->postJson("/api/public/photo-upload/{$token}", [
            'photo' => $this->realPhoto(),
        ])->assertStatus(404);
    }

    public function test_context_with_invalid_token_format_returns_404(): void
    {
        $this->getJson('/api/public/photo-upload/not-a-hex-token')->assertStatus(404);
    }
}
