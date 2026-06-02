<?php

namespace Tests\Unit\Audit\Domain;

use App\Audit\Domain\AuditChainHasher;
use PHPUnit\Framework\TestCase;

class AuditChainHasherTest extends TestCase
{
    private AuditChainHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new AuditChainHasher;
    }

    public function test_compute_returns_deterministic_hash(): void
    {
        $hash1 = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'order.created',
            entityType: 'order',
            entityId: 'order-1',
            userUuid: null,
            summary: 'Pedido creado',
            metadata: ['key' => 'value'],
            before: null,
            after: null,
        );

        $hash2 = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'order.created',
            entityType: 'order',
            entityId: 'order-1',
            userUuid: null,
            summary: 'Pedido creado',
            metadata: ['key' => 'value'],
            before: null,
            after: null,
        );

        $this->assertSame($hash2, $hash1);
    }

    public function test_compute_different_inputs_produce_different_hashes(): void
    {
        $hash1 = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'order.created',
            entityType: 'order',
            entityId: 'order-1',
            userUuid: null,
            summary: 'Pedido creado',
            metadata: [],
            before: null,
            after: null,
        );

        $hash2 = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'order.created',
            entityType: 'order',
            entityId: 'order-2', // different
            userUuid: null,
            summary: 'Pedido creado',
            metadata: [],
            before: null,
            after: null,
        );

        $this->assertNotSame($hash2, $hash1);
    }

    public function test_compute_chains_with_prev_hash(): void
    {
        $prevHash = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'order.created',
            entityType: 'order',
            entityId: 'order-1',
            userUuid: null,
            summary: 'First',
            metadata: [],
            before: null,
            after: null,
        );

        $chainedHash = $this->hasher->compute(
            prevHash: $prevHash,
            uuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:01:00',
            actionSlug: 'order.closed',
            entityType: 'order',
            entityId: 'order-1',
            userUuid: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            summary: 'Second',
            metadata: ['amount' => 100],
            before: ['status' => 'open'],
            after: ['status' => 'closed'],
        );

        $this->assertNotNull($chainedHash);
        $this->assertNotSame($prevHash, $chainedHash);

        // tampered prevHash should produce different result
        $tamperedHash = $this->hasher->compute(
            prevHash: 'tampered',
            uuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:01:00',
            actionSlug: 'order.closed',
            entityType: 'order',
            entityId: 'order-1',
            userUuid: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            summary: 'Second',
            metadata: ['amount' => 100],
            before: ['status' => 'open'],
            after: ['status' => 'closed'],
        );

        $this->assertNotSame($chainedHash, $tamperedHash);
    }

    public function test_compute_with_sorted_metadata(): void
    {
        $hash1 = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'test.event',
            entityType: 'test',
            entityId: '1',
            userUuid: null,
            summary: 'Test',
            metadata: ['b' => 2, 'a' => 1],
            before: null,
            after: null,
        );

        $hash2 = $this->hasher->compute(
            prevHash: null,
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantUuid: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            createdAtIso: '2026-06-01 10:00:00',
            actionSlug: 'test.event',
            entityType: 'test',
            entityId: '1',
            userUuid: null,
            summary: 'Test',
            metadata: ['a' => 1, 'b' => 2],
            before: null,
            after: null,
        );

        // Canonical JSON sorts keys, so both should produce the same hash
        $this->assertSame($hash2, $hash1);
    }
}
