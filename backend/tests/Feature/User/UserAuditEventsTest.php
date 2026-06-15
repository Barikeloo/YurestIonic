<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class UserAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_via_admin_writes_user_created_audit_log(): void
    {
        $admin = $this->createSuperAdminSession();
        $restaurantUuid = (string) Str::uuid();
        DB::table('restaurants')->insert([
            'uuid' => $restaurantUuid,
            'name' => 'User Audit Restaurant',
            'legal_name' => 'User Audit S.L.',
            'tax_id' => 'B12345678',
            'email' => 'user-audit@restaurant.local',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($admin['session'])
            ->postJson("/api/admin/restaurants/{$restaurantUuid}/users", [
                'name' => 'Audit User',
                'email' => 'audit-user@example.com',
                'password' => 'password123',
                'role' => 'operator',
            ]);

        $response->assertStatus(201);
        $userUuid = $response->json('uuid');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'user',
            'entity_id' => $userUuid,
            'action' => 'user.created',
        ]);
    }

    public function test_updating_a_user_writes_user_updated_and_password_changed_audit_logs(): void
    {
        $admin = $this->createSuperAdminSession();
        $restaurantUuid = (string) Str::uuid();
        $restaurantId = (int) DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'Update User Audit Restaurant',
            'legal_name' => 'Update Audit S.L.',
            'tax_id' => 'B87654321',
            'email' => 'update-user-audit@restaurant.local',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $userUuid,
            'name' => 'Old Name',
            'email' => 'update-audit@example.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession($admin['session'])
            ->putJson("/api/admin/restaurants/{$restaurantUuid}/users/{$userUuid}", [
                'name' => 'New Name',
                'password' => 'newpassword123',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'user',
            'entity_id' => $userUuid,
            'action' => 'user.updated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'user',
            'entity_id' => $userUuid,
            'action' => 'auth.password_changed',
        ]);
    }

    public function test_deleting_a_user_writes_user_deleted_audit_log(): void
    {
        $admin = $this->createSuperAdminSession();
        $restaurantUuid = (string) Str::uuid();
        $restaurantId = (int) DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'Delete User Audit Restaurant',
            'legal_name' => 'Delete Audit S.L.',
            'tax_id' => 'B11223344',
            'email' => 'delete-user-audit@restaurant.local',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $userUuid,
            'name' => 'Delete Me',
            'email' => 'delete-audit@example.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession($admin['session'])
            ->deleteJson("/api/admin/restaurants/{$restaurantUuid}/users/{$userUuid}")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'user',
            'entity_id' => $userUuid,
            'action' => 'user.deleted',
        ]);
    }
}
