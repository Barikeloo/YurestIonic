<?php

declare(strict_types=1);

namespace Tests\Unit\Printer\Infrastructure;

use App\Printer\Infrastructure\Printing\EscPosTicketBuilder;
use PHPUnit\Framework\TestCase;

class EscPosTicketBuilderTest extends TestCase
{
    private EscPosTicketBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new EscPosTicketBuilder();
    }

    public function test_sanitize_replaces_accented_and_special_characters(): void
    {
        $bytes = $this->builder->buildTest('Café Ñoño Açaí Ángel Ü', 48);
        $text  = $this->stripEscPos($bytes);

        $this->assertStringContainsString('Cafe Nono Acai Angel U', $text);
        $this->assertStringNotContainsString('á', $text);
        $this->assertStringNotContainsString('ñ', $text);
        $this->assertStringNotContainsString('ç', $text);
    }

    public function test_build_test_contains_required_lines(): void
    {
        $bytes = $this->builder->buildTest('Sala Principal', 48);
        $text  = $this->stripEscPos($bytes);

        $this->assertStringContainsString('TEST DE IMPRESORA', $text);
        $this->assertStringContainsString('Sala Principal', $text);
        $this->assertStringContainsString('Impresion correcta', $text);
    }

    public function test_build_test_divider_matches_char_width(): void
    {
        $bytes32 = $this->builder->buildTest('T', 32);
        $bytes48 = $this->builder->buildTest('T', 48);
        $text32  = $this->stripEscPos($bytes32);
        $text48  = $this->stripEscPos($bytes48);

        $this->assertStringContainsString(str_repeat('-', 32), $text32);
        $this->assertStringContainsString(str_repeat('-', 48), $text48);
        $this->assertStringNotContainsString(str_repeat('-', 33), $text32);
    }

    public function test_build_full_ticket_contains_all_sections(): void
    {
        $bytes = $this->builder->build($this->makeTicketData(), 48);
        $text  = $this->stripEscPos($bytes);

        $this->assertStringContainsString('BAR MANOLO',          $text);
        $this->assertStringContainsString('FACTURA SIMPLIFICADA', $text);
        $this->assertStringContainsString('N. 42',               $text);
        $this->assertStringContainsString('Mesa: B4',            $text);
        $this->assertStringContainsString('Cam.: Juan',          $text);
        $this->assertStringContainsString('Arroz negro',         $text);
        $this->assertStringContainsString('TOTAL',               $text);
        $this->assertStringContainsString('12,50 EUR',           $text);
        $this->assertStringContainsString('EFECTIVO',            $text);
        $this->assertStringContainsString('GRACIAS POR SU VISITA', $text);
    }

    public function test_build_full_ticket_no_line_exceeds_char_width(): void
    {
        $bytes = $this->builder->build($this->makeTicketData(), 32);
        $text  = $this->stripEscPos($bytes);

        foreach (explode("\n", $text) as $line) {
            $this->assertLessThanOrEqual(32, strlen($line), "Line too long ({$line})");
        }
    }

    public function test_build_full_ticket_with_variant_and_modifier(): void
    {
        $data = $this->makeTicketData();
        $data['order_lines'][] = [
            'name'         => 'Carne a la brasa',
            'quantity'     => 1,
            'total_cents'  => 1800,
            'variant_name' => 'Termino medio',
            'modifiers'    => [['name' => 'Salsa alioli']],
        ];

        $bytes = $this->builder->build($data, 48);
        $text  = $this->stripEscPos($bytes);

        $this->assertStringContainsString('Carne a la brasa', $text);
        $this->assertStringContainsString('Termino medio',    $text);
        $this->assertStringContainsString('Salsa alioli',     $text);
    }

    public function test_build_plain_text_preserves_content(): void
    {
        $raw   = "Aceite de oliva\n2 x 3,50\n-------\nTotal  7,00";
        $bytes = $this->builder->buildPlainText($raw, 48);
        $text  = $this->stripEscPos($bytes);

        $this->assertStringContainsString('Aceite de oliva', $text);
        $this->assertStringContainsString('Total  7,00',     $text);
    }

    public function test_build_ends_with_full_cut_command(): void
    {
        $bytes = $this->builder->buildTest('T', 48);
        $this->assertStringContainsString("\x1d\x56\x00", $bytes);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function stripEscPos(string $bytes): string
    {
        $pattern =
            '/\x1b\x40'           // ESC @ init
            . '|\x1b\x61.'        // ESC a n  align
            . '|\x1b\x45.'        // ESC E n  bold
            . '|\x1b\x64.'        // ESC d n  feed
            . '|\x1d\x21.'        // GS ! n   size
            . '|\x1d\x56.'        // GS V n   cut
            . '|\r/';

        return preg_replace($pattern, '', $bytes) ?? $bytes;
    }

    private function makeTicketData(): array
    {
        return [
            'restaurant'           => [
                'name'       => 'Bar Manolo',
                'legal_name' => 'Bar Manolo S.L.',
                'tax_id'     => 'B12345678',
            ],
            'ticket_number'        => '42',
            'created_at'           => '2026-06-17T09:31:00Z',
            'created_time'         => '09:31',
            'table'                => ['id' => 'uuid-table', 'name' => 'B4'],
            'operator'             => 'Juan',
            'order_lines'          => [
                ['name' => 'Arroz negro', 'quantity' => 2, 'total_cents' => 1250],
            ],
            'tax_breakdown'        => [
                ['rate' => 10, 'base_cents' => 1136, 'tax_cents' => 114],
            ],
            'total_consumed_cents' => 1250,
            'payments_snapshot'    => [
                ['method' => 'cash', 'amount_cents' => 1250],
            ],
        ];
    }
}
