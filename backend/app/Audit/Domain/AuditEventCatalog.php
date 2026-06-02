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
        'order.line_added' => [
            'category' => 'order',
            'severity' => 'info',
            'summary' => 'Añadida {metadata.quantity}× {metadata.product_name} al pedido {metadata.order_id}.',
        ],
        'order.line_removed' => [
            'category' => 'order',
            'severity' => 'warning',
            'summary' => 'Eliminada línea {metadata.quantity}× {metadata.product_name} del pedido {metadata.order_id}.',
        ],
        'order.comanda_sent' => [
            'category' => 'order',
            'severity' => 'info',
            'summary' => 'Comanda enviada: {metadata.total_lines} artículos ({metadata.items_summary}) al pedido {metadata.order_id}.',
        ],
        'order.diners_updated' => [
            'category' => 'order',
            'severity' => 'info',
            'summary' => 'Comensales del pedido {entity_id} actualizados: {before.diners} → {after.diners}.',
        ],
        'order.menu_line_added' => [
            'category' => 'order',
            'severity' => 'info',
            'summary' => 'Añadido menú {metadata.menu_name} al pedido {metadata.order_id}.',
        ],
        'order.deleted' => [
            'category' => 'order',
            'severity' => 'danger',
            'summary' => 'Pedido {entity_id} eliminado ({before.diners} comensales).',
        ],
        'sale.charge_session_created' => [
            'category' => 'sale',
            'severity' => 'info',
            'summary' => 'Sesión de cobro {entity_id} creada para pedido {metadata.order_id} con {metadata.diners_count} comensales.',
        ],
        'sale.payment_recorded' => [
            'category' => 'sale',
            'severity' => 'success',
            'summary' => 'Pago de {metadata.amount_formatted} registrado en sesión de cobro {entity_id} ({metadata.payment_method}).',
        ],
        'sale.charge_session_cancelled' => [
            'category' => 'sale',
            'severity' => 'danger',
            'summary' => 'Sesión de cobro {entity_id} cancelada. Motivo: {reason}.',
        ],
        'sale.line_refunded' => [
            'category' => 'sale',
            'severity' => 'danger',
            'summary' => 'Línea {entity_id} reembolsada en sesión de cobro {metadata.charge_session_id}. Motivo: {reason}.',
        ],
        'sale.lines_assigned' => [
            'category' => 'sale',
            'severity' => 'info',
            'summary' => 'Líneas asignadas a comensales en sesión de cobro {entity_id}: {metadata.assignments_summary}.',
        ],
        'sale.diners_updated' => [
            'category' => 'sale',
            'severity' => 'info',
            'summary' => 'Comensales de sesión de cobro {entity_id} actualizados: {before.diners_count} → {after.diners_count}.',
        ],
        'sale.closed' => [
            'category' => 'sale',
            'severity' => 'success',
            'summary' => 'Venta {entity_id} cerrada por {metadata.total_formatted}.',
        ],
        'sale.final_ticket_created' => [
            'category' => 'sale',
            'severity' => 'success',
            'summary' => 'Ticket final nº {metadata.ticket_number} generado para el pedido {metadata.order_id}.',
        ],
        'sale.line_added' => [
            'category' => 'sale',
            'severity' => 'info',
            'summary' => 'Línea {entity_id} añadida a venta {metadata.sale_id}: {metadata.quantity}×.',
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
        'caja.closing_started' => [
            'category' => 'caja',
            'severity' => 'warning',
            'summary' => 'Iniciado proceso de cierre de sesión de caja {entity_id}.',
        ],
        'caja.closing_cancelled' => [
            'category' => 'caja',
            'severity' => 'warning',
            'summary' => 'Cancelado proceso de cierre de sesión de caja {entity_id}.',
        ],
        'caja.z_report_generated' => [
            'category' => 'caja',
            'severity' => 'info',
            'summary' => 'Generado informe Z #{metadata.report_number} con total de {metadata.total_sales_formatted}.',
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
        'sale.cancelled' => [
            'category' => 'sale',
            'severity' => 'danger',
            'summary' => 'Venta {entity_id} cancelada. Motivo: {reason}.',
        ],

        // Config (gestión de usuarios y ajustes del restaurante)
        'user.created' => [
            'category' => 'config',
            'severity' => 'warning',
            'summary' => 'Usuario {metadata.user_name} ({metadata.role}) creado.',
        ],
        'user.updated' => [
            'category' => 'config',
            'severity' => 'warning',
            'summary' => 'Usuario {metadata.user_name} actualizado: {metadata.changed_fields}.',
        ],
        'user.deleted' => [
            'category' => 'config',
            'severity' => 'critical',
            'summary' => 'Usuario {metadata.user_name} ({metadata.role}) eliminado.',
        ],
        'tax.created' => [
            'category' => 'config',
            'severity' => 'warning',
            'summary' => 'Impuesto {metadata.tax_name} creado al {metadata.percentage}%.',
        ],
        'tax.updated' => [
            'category' => 'config',
            'severity' => 'warning',
            'summary' => 'Impuesto {metadata.tax_name} actualizado: {metadata.changed_fields}.',
        ],
        'tax.deleted' => [
            'category' => 'config',
            'severity' => 'critical',
            'summary' => 'Impuesto {metadata.tax_name} ({metadata.percentage}%) eliminado.',
        ],

        // Catalog
        'product.created' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Producto {metadata.product_name} creado con precio {metadata.price_formatted}.',
        ],
        'product.updated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Producto {metadata.product_name} actualizado.',
        ],
        'product.deleted' => [
            'category' => 'catalog',
            'severity' => 'warning',
            'summary' => 'Producto {metadata.product_name} eliminado del catálogo.',
        ],
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
        'family.created' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Familia {metadata.family_name} creada.',
        ],
        'family.updated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Familia {metadata.family_name} actualizada.',
        ],
        'family.deleted' => [
            'category' => 'catalog',
            'severity' => 'warning',
            'summary' => 'Familia {metadata.family_name} eliminada del catálogo.',
        ],
        'menu.created' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Menú {metadata.menu_name} creado con precio {metadata.price_formatted}.',
        ],
        'menu.updated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Menú {metadata.menu_name} actualizado.',
        ],
        'menu.activated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Menú {metadata.menu_name} activado.',
        ],
        'menu.deactivated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Menú {metadata.menu_name} desactivado.',
        ],
        'menu.archived' => [
            'category' => 'catalog',
            'severity' => 'warning',
            'summary' => 'Menú {metadata.menu_name} archivado.',
        ],

        'catalog.modifier_created' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Modificador {metadata.modifier_name} creado (producto {metadata.product_id}).',
        ],
        'catalog.modifier_updated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Modificador {metadata.modifier_name} actualizado.',
        ],
        'catalog.modifier_deleted' => [
            'category' => 'catalog',
            'severity' => 'warning',
            'summary' => 'Modificador {before.name} eliminado (producto {metadata.product_id}).',
        ],
        'catalog.variant_created' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Variante {metadata.variant_name} creada (producto {metadata.product_id}).',
        ],
        'catalog.variant_updated' => [
            'category' => 'catalog',
            'severity' => 'info',
            'summary' => 'Variante {metadata.variant_name} actualizada.',
        ],
        'catalog.variant_deleted' => [
            'category' => 'catalog',
            'severity' => 'warning',
            'summary' => 'Variante {before.name} eliminada (producto {metadata.product_id}).',
        ],

        // Auth
        'auth.login_successful' => [
            'category' => 'auth',
            'severity' => 'info',
            'summary' => 'Inicio de sesión correcto para usuario {entity_id}.',
        ],
        'auth.login_failed' => [
            'category' => 'auth',
            'severity' => 'warning',
            'summary' => 'Intento de inicio de sesión fallido para {metadata.email}.',
        ],
        'auth.password_changed' => [
            'category' => 'auth',
            'severity' => 'warning',
            'summary' => 'Contraseña cambiada para {entity_id}.',
        ],

        // Restaurant
        'restaurant.created' => [
            'category' => 'config',
            'severity' => 'info',
            'summary' => 'Restaurante {metadata.restaurant_name} creado.',
        ],
        'restaurant.updated' => [
            'category' => 'config',
            'severity' => 'info',
            'summary' => 'Restaurante {metadata.restaurant_name} actualizado.',
        ],

        // Tables
        'table.created' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Mesa {metadata.table_name} creada.',
        ],
        'table.updated' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Mesa {metadata.table_name} actualizada.',
        ],
        'table.deleted' => [
            'category' => 'table',
            'severity' => 'warning',
            'summary' => 'Mesa {metadata.table_name} eliminada.',
        ],
        'table.merged' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Fusión de mesas {metadata.tables_label}.',
        ],
        'table.unmerged' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Mesas {metadata.tables_label} separadas tras fusión.',
        ],
        'zone.created' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Zona {metadata.zone_name} creada.',
        ],
        'zone.updated' => [
            'category' => 'table',
            'severity' => 'info',
            'summary' => 'Zona {metadata.zone_name} actualizada.',
        ],
        'zone.deleted' => [
            'category' => 'table',
            'severity' => 'warning',
            'summary' => 'Zona {metadata.zone_name} eliminada.',
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
