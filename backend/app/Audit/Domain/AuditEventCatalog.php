<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Audit\Domain\Exception\UnknownAuditActionException;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\Category;
use App\Audit\Domain\ValueObject\Severity;

final class AuditEventCatalog
{
    /**
     * Central registry of every audit event the system can emit.
     * Adding a new event = adding one entry here + emitting from the corresponding use case.
     * Slug not present here = UnknownAuditActionException at record time.
     *
     * @var array<string, array{category: string, severity: string, summary: string}>
     */
    private const EVENTS = [
        // Auth
        'auth.login_pin_ok' => [
            'category' => 'auth',
            'severity' => 'success',
            'summary' => 'Login con PIN correcto desde dispositivo {device_id}.',
        ],
        'auth.login_pin_failed' => [
            'category' => 'auth',
            'severity' => 'critical',
            'summary' => 'Intento de login fallido con PIN incorrecto desde dispositivo {device_id}.',
        ],
        'auth.device_link' => [
            'category' => 'auth',
            'severity' => 'info',
            'summary' => 'Dispositivo {device_id} vinculado al usuario.',
        ],

        // Order
        'order.created' => [
            'category' => 'order',
            'severity' => 'info',
            'summary' => 'Pedido {entity_id} creado.',
        ],
        'order.marked_to_charge' => [
            'category' => 'order',
            'severity' => 'warning',
            'summary' => 'Pedido {entity_id} marcado para cobrar.',
        ],
        'order.reopened' => [
            'category' => 'order',
            'severity' => 'danger',
            'summary' => 'Pedido {entity_id} reabierto tras estar marcado para cobrar.',
        ],
        'order.transferred' => [
            'category' => 'order',
            'severity' => 'info',
            'summary' => 'Pedido {entity_id} transferido a otra mesa.',
        ],
        'order.cancelled' => [
            'category' => 'order',
            'severity' => 'danger',
            'summary' => 'Pedido {entity_id} cancelado.',
        ],

        // Caja
        'caja.opened' => [
            'category' => 'caja',
            'severity' => 'info',
            'summary' => 'Apertura de sesión de caja con fondo inicial de {metadata.opening_float_formatted}.',
        ],
        'caja.closed' => [
            'category' => 'caja',
            'severity' => 'success',
            'summary' => 'Cierre de sesión de caja con delta final de {metadata.delta_final_formatted}.',
        ],
        'caja.force_closed' => [
            'category' => 'caja',
            'severity' => 'critical',
            'summary' => 'Cierre forzado de sesión de caja con delta final de {metadata.delta_final_formatted}.',
        ],
        'caja.cash_movement' => [
            'category' => 'caja',
            'severity' => 'warning',
            'summary' => 'Movimiento de caja ({metadata.movement_type}) por {metadata.amount_formatted}.',
        ],

        // Sale
        'sale.created' => [
            'category' => 'sale',
            'severity' => 'success',
            'summary' => 'Venta {entity_id} registrada por {metadata.total_formatted}.',
        ],
        'sale.credit_note_issued' => [
            'category' => 'sale',
            'severity' => 'danger',
            'summary' => 'Abono emitido sobre venta {entity_id} por {metadata.amount_formatted}.',
        ],

        // Catalog
        'product.activated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Producto {metadata.product_name} activado.',
        ],
        'product.deactivated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Producto {metadata.product_name} desactivado.',
        ],
        'product.price_changed' => [
            'category' => 'catalog',
            'severity' => 'warning',
            'summary' => 'Precio del producto {metadata.product_name} actualizado de {metadata.price_before_formatted} a {metadata.price_after_formatted}.',
        ],

        // Tables
        'table.merged' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Fusión de mesas {metadata.tables_label}.',
        ],
    ];

    /**
     * @param  array<string, mixed>  $context  Top-level keys (entity_id, device_id...) + nested metadata/before/after.
     * @return array{category: Category, severity: Severity, summary: string}
     */
    public static function resolve(ActionSlug $slug, array $context): array
    {
        $slugValue = $slug->value();

        if (! isset(self::EVENTS[$slugValue])) {
            throw UnknownAuditActionException::withSlug($slugValue);
        }

        $entry = self::EVENTS[$slugValue];

        return [
            'category' => Category::create($entry['category']),
            'severity' => Severity::create($entry['severity']),
            'summary' => self::renderTemplate($entry['summary'], $context),
        ];
    }

    public static function isKnown(string $slug): bool
    {
        return isset(self::EVENTS[$slug]);
    }

    /**
     * @return list<string>
     */
    public static function allSlugs(): array
    {
        return array_keys(self::EVENTS);
    }

    /**
     * Replaces {key} and {prefix.nested.key} placeholders. Missing keys render as "—".
     */
    private static function renderTemplate(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{([a-z_]+(?:\.[a-z_]+)*)\}/',
            static function (array $match) use ($context): string {
                $path = explode('.', $match[1]);
                $value = $context;
                foreach ($path as $segment) {
                    if (! is_array($value) || ! array_key_exists($segment, $value)) {
                        return '—';
                    }
                    $value = $value[$segment];
                }

                return is_scalar($value) ? (string) $value : '—';
            },
            $template
        );
    }
}
