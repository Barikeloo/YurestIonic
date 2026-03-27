<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EditRestaurantAsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_operational_restaurant_fields(): void
    {
        $adminSession = $this->createTenantSession('admin');

        $response = $this->withSession($adminSession['session'])->putJson('/api/admin/restaurants/' . $adminSession['restaurant_uuid'], [
            'name' => 'Nombre Operativo Nuevo',
            'email' => 'operativo-nuevo@local.test',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Nombre Operativo Nuevo',
                'email' => 'operativo-nuevo@local.test',
            ]);

        $restaurant = DB::table('restaurants')->where('uuid', $adminSession['restaurant_uuid'])->first();

        $this->assertNotNull($restaurant);
        $this->assertSame('Nombre Operativo Nuevo', $restaurant->name);
        $this->assertSame('operativo-nuevo@local.test', $restaurant->email);
    }

    public function test_admin_cannot_update_tax_id_or_legal_name(): void
    {
        $adminSession = $this->createTenantSession('admin');

        $responseTax = $this->withSession($adminSession['session'])->putJson('/api/admin/restaurants/' . $adminSession['restaurant_uuid'], [
            'tax_id' => 'B11112222',
        ]);

        $responseTax->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. Only superadmins can update legal data.',
            ]);

        $responseLegal = $this->withSession($adminSession['session'])->putJson('/api/admin/restaurants/' . $adminSession['restaurant_uuid'], [
            'legal_name' => 'Nueva Razon Social S.L.',
        ]);

        $responseLegal->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. Only superadmins can update legal data.',
            ]);
    }
}
