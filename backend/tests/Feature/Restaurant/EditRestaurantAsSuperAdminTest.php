<?php

namespace Tests\Feature\Restaurant;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EditRestaurantAsSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private string $superAdminUuid;

    private string $restaurantUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminUuid = 'a0000000-0000-4000-8000-000000000001';
        $this->restaurantUuid = '11111111-1111-4111-8111-111111111111';
        EloquentSuperAdmin::create([
            'uuid' => $this->superAdminUuid,
            'name' => 'Developer (Test)',
            'email' => 'dev@tpv.local',
            'password' => bcrypt('dev123456'),
        ]);

        EloquentRestaurant::create([
            'uuid' => $this->restaurantUuid,
            'name' => 'Restaurant Zentral',
            'legal_name' => 'Restaurant Zentral SL',
            'tax_id' => 'B12345678',
            'email' => 'zentral@restaurant.com',
            'password' => bcrypt('restaurant123456'),
        ]);
    }

    public function test_superadmin_can_edit_restaurant(): void
    {

        $response = $this->post('/api/superadmin/login', [
            'email' => 'dev@tpv.local',
            'password' => 'dev123456',
        ]);

        $response->assertStatus(200);

        $response = $this->withSession(['super_admin_id' => $this->superAdminUuid])
            ->put('/api/superadmin/restaurants/'.$this->restaurantUuid, [
                'name' => 'Restaurant Zentral Updated',
                'legal_name' => 'Restaurant Zentral SL Updated',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'legal_name',
            'tax_id',
            'email',
        ]);

        $restaurant = EloquentRestaurant::where('uuid', $this->restaurantUuid)->first();
        $this->assertEquals('Restaurant Zentral Updated', $restaurant->name);
        $this->assertEquals('Restaurant Zentral SL Updated', $restaurant->legal_name);
    }

    public function test_superadmin_gets_401_when_not_logged_in(): void
    {
        $response = $this->put('/api/superadmin/restaurants/'.$this->restaurantUuid, [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_superadmin_gets_404_for_nonexistent_restaurant(): void
    {

        $this->post('/api/superadmin/login', [
            'email' => 'dev@tpv.local',
            'password' => 'dev123456',
        ]);

        $response = $this->put('/api/superadmin/restaurants/nonexistent-uuid', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(404);
    }

    public function test_superadmin_restaurants_route_exists(): void
    {

        $response = $this->withSession(['super_admin_id' => $this->superAdminUuid])
            ->get('/api/superadmin/restaurants/'.$this->restaurantUuid);

        $this->assertNotEquals(404, $response->status(), 'Route does not exist. Get: '.$response->status().' - '.$response->getContent());
    }
}
