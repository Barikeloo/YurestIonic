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

        // Create superadmin test user
        $this->superAdminUuid = 'a0000000-0000-0000-0000-000000000001';
        $this->restaurantUuid = '11111111-1111-4111-8111-111111111111';
        EloquentSuperAdmin::create([
            'uuid' => $this->superAdminUuid,
            'name' => 'Developer (Test)',
            'email' => 'dev@tpv.local',
            'password' => bcrypt('dev123456'),
        ]);

        // Create test restaurant
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
        // 1. Login as superadmin
        $response = $this->post('/api/superadmin/login', [
            'email' => 'dev@tpv.local',
            'password' => 'dev123456',
        ]);

        $response->assertStatus(200);

        // 2. Update restaurant
        $response = $this->withSession(['super_admin_id' => $this->superAdminUuid])
            ->put('/api/superadmin/restaurants/' . $this->restaurantUuid, [
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

        // 3. Verify in database
        $restaurant = EloquentRestaurant::where('uuid', $this->restaurantUuid)->first();
        $this->assertEquals('Restaurant Zentral Updated', $restaurant->name);
        $this->assertEquals('Restaurant Zentral SL Updated', $restaurant->legal_name);
    }

    public function test_superadmin_gets_401_when_not_logged_in(): void
    {
        $response = $this->put('/api/superadmin/restaurants/' . $this->restaurantUuid, [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_superadmin_gets_404_for_nonexistent_restaurant(): void
    {
        // Login first
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
        // Test that we can access GET route to confirm routes are loaded
        $response = $this->withSession(['super_admin_id' => $this->superAdminUuid])
            ->get('/api/superadmin/restaurants/' . $this->restaurantUuid);

        // Should not be 404 (not found route) but 200 or 50x (server error)
        $this->assertNotEquals(404, $response->status(), 'Route does not exist. Get: ' . $response->status() . ' - ' . $response->getContent());
    }
}
