<?php

namespace Tests\Unit\Audit\Domain;

use App\Audit\Domain\AuditEventCatalog;
use App\Audit\Domain\Exception\UnknownAuditActionException;
use App\Audit\Domain\ValueObject\ActionSlug;
use PHPUnit\Framework\TestCase;

class AuditEventCatalogTest extends TestCase
{
    public function test_resolve_known_event(): void
    {
        $result = AuditEventCatalog::resolve(
            ActionSlug::create('caja.opened'),
            ['metadata' => ['opening_float_formatted' => '500,00 €']],
        );

        $this->assertSame('caja', $result['category']->value());
        $this->assertSame('info', $result['severity']->value());
        $this->assertStringContainsString('500,00 €', $result['summary']);
    }

    public function test_resolve_unknown_event_throws_exception(): void
    {
        $this->expectException(UnknownAuditActionException::class);

        AuditEventCatalog::resolve(
            ActionSlug::create('unknown.event'),
            [],
        );
    }

    public function test_resolve_renders_top_level_template_variables(): void
    {
        $result = AuditEventCatalog::resolve(
            ActionSlug::create('order.created'),
            ['entity_id' => 'order-42'],
        );

        $this->assertSame('Pedido order-42 creado.', $result['summary']);
    }

    public function test_resolve_renders_nested_template_variables(): void
    {
        $result = AuditEventCatalog::resolve(
            ActionSlug::create('order.line_added'),
            [
                'metadata' => [
                    'quantity' => 2,
                    'product_name' => 'Café',
                    'order_id' => 'order-42',
                ],
            ],
        );

        $this->assertSame('Añadida 2× Café al pedido order-42.', $result['summary']);
    }

    public function test_resolve_renders_nested_before_after(): void
    {
        $result = AuditEventCatalog::resolve(
            ActionSlug::create('order.diners_updated'),
            [
                'entity_id' => 'order-1',
                'before' => ['diners' => 2],
                'after' => ['diners' => 4],
            ],
        );

        $this->assertSame('Comensales del pedido order-1 actualizados: 2 → 4.', $result['summary']);
    }

    public function test_resolve_missing_template_variable_uses_placeholder(): void
    {
        $result = AuditEventCatalog::resolve(
            ActionSlug::create('caja.opened'),
            [], // no context at all
        );

        // Should use '—' for missing placeholders
        $this->assertStringContainsString('—', $result['summary']);
    }

    public function test_is_known(): void
    {
        $this->assertTrue(AuditEventCatalog::isKnown('caja.opened'));
        $this->assertTrue(AuditEventCatalog::isKnown('order.created'));
        $this->assertFalse(AuditEventCatalog::isKnown('nonexistent.event'));
    }

    public function test_all_slugs_returns_list(): void
    {
        $slugs = AuditEventCatalog::allSlugs();

        $this->assertContains('caja.opened', $slugs);
        $this->assertContains('order.created', $slugs);
        $this->assertContains('user.deleted', $slugs);
        $this->assertContains('product.created', $slugs);
    }
}
