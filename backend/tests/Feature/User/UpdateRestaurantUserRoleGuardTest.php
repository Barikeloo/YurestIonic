<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateRestaurantUserRoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_demote_self_from_admin_role(): void
    {
        $admin = $this->createTenantSession('admin');

        $response = $this->withSession($admin['session'])->putJson(
            '/api/admin/restaurants/'.$admin['restaurant_uuid'].'/users/'.$admin['user_uuid'],
            [
                'role' => 'supervisor',
            ],
        );
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No puedes cambiar tu propio rol de administrador.',
            ]);
    }
}
