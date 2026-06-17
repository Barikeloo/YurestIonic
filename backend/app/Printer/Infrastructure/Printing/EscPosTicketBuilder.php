<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Printing;

/**
 * Builds an ESC/POS byte string from ticket data.
 *
 * Compatible with any standard ESC/POS printer (Epson TM series, Star TSP series, etc.)
 * Encoding: CP437 — standard ASCII + common Latin characters.
 */
final class EscPosTicketBuilder
{
    // ── ESC/POS command constants ─────────────────────────────
    private const INIT         = "\x1b\x40";
    private const ALIGN_LEFT   = "\x1b\x61\x00";
    private const ALIGN_CENTER = "\x1b\x61\x01";
    private const BOLD_ON      = "\x1b\x45\x01";
    private const BOLD_OFF     = "\x1b\x45\x00";
    private const DOUBLE_SIZE  = "\x1d\x21\x11"; // double height + width
    private const NORMAL_SIZE  = "\x1d\x21\x00";
    private const LF           = "\x0a";
    private const FULL_CUT     = "\x1d\x56\x00";
    private const FEED_LINES   = "\x1b\x64";     // + n lines byte

    private int $width;
    private string $buf = '';

    public function build(array $ticketData, int $charWidth): string
    {
        $this->width = $charWidth;
        $this->buf   = '';

        $this->buf .= self::INIT;

        $this->printRestaurantHeader($ticketData['restaurant'] ?? null);
        $this->printTicketTitle($ticketData);
        $this->printHeaderInfo($ticketData);
        $this->printOrderLines($ticketData['order_lines'] ?? []);
        $this->printTaxBreakdown($ticketData['tax_breakdown'] ?? []);
        $this->printTotal($ticketData['total_consumed_cents'] ?? 0);
        $this->printPayments($ticketData['payments_snapshot'] ?? []);
        $this->printFooter();

        // Feed 4 lines before cut
        $this->buf .= self::FEED_LINES . "\x04";
        $this->buf .= self::FULL_CUT;

        return $this->buf;
    }

    /**
     * Builds a plain-text pre-ticket in ESC/POS format.
     * The text is expected to be pre-formatted with ASCII dividers.
     */
    public function buildPlainText(string $text, int $charWidth): string
    {
        $this->width = $charWidth;
        $this->buf   = '';

        $this->buf .= self::INIT;
        $this->buf .= self::ALIGN_LEFT;

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $this->write($this->sanitize($line));
        }

        $this->buf .= self::FEED_LINES . "\x04";
        $this->buf .= self::FULL_CUT;

        return $this->buf;
    }

    public function buildTest(string $printerName, int $charWidth): string
    {
        $this->width = $charWidth;
        $this->buf   = '';

        $this->buf .= self::INIT;
        $this->buf .= self::ALIGN_CENTER;
        $this->buf .= self::BOLD_ON;
        $this->write('*** TEST DE IMPRESORA ***');
        $this->buf .= self::BOLD_OFF;
        $this->write($this->divider());
        $this->write($this->sanitize($printerName));
        $this->write(date('d/m/Y H:i:s'));
        $this->write($this->divider());
        $this->write('Impresion correcta');
        $this->buf .= self::FEED_LINES . "\x04";
        $this->buf .= self::FULL_CUT;

        return $this->buf;
    }

    // ── Private helpers ───────────────────────────────────────

    private function printRestaurantHeader(?array $restaurant): void
    {
        if ($restaurant === null) {
            return;
        }

        $this->buf .= self::ALIGN_CENTER;
        $this->buf .= self::BOLD_ON;

        if (!empty($restaurant['name'])) {
            $this->write(strtoupper($this->sanitize($restaurant['name'])));
        }

        $this->buf .= self::BOLD_OFF;

        if (!empty($restaurant['legal_name']) && $restaurant['legal_name'] !== $restaurant['name']) {
            $this->write($this->sanitize($restaurant['legal_name']));
        }

        if (!empty($restaurant['tax_id'])) {
            $this->write('NIF: ' . $this->sanitize($restaurant['tax_id']));
        }

        $this->write($this->doubleDivider());
    }

    private function printTicketTitle(array $data): void
    {
        $this->buf .= self::ALIGN_CENTER;
        $this->buf .= self::BOLD_ON;
        $this->write('FACTURA SIMPLIFICADA');
        $this->buf .= self::BOLD_OFF;

        $ticketNumber = $data['ticket_number'] ?? null;
        if ($ticketNumber !== null && $ticketNumber !== '') {
            $this->write('N. ' . $ticketNumber);
        }

        $this->write($this->divider());
        $this->buf .= self::ALIGN_LEFT;
    }

    private function printHeaderInfo(array $data): void
    {
        $this->buf .= self::ALIGN_LEFT;

        $createdAt   = $data['created_at'] ?? null;
        $createdTime = $data['created_time'] ?? null;

        if ($createdAt !== null) {
            $date = substr($createdAt, 0, 10);
            [$y, $m, $d] = explode('-', $date);
            $dateStr = "{$d}/{$m}/{$y}";
            $timeStr = $createdTime ?? '';

            if ($timeStr !== '') {
                $this->write($this->twoCol("Fecha: {$dateStr}", "Hora: {$timeStr}"));
            } else {
                $this->write("Fecha: {$dateStr}");
            }
        }

        $table    = $data['table'] ?? null;
        $operator = $data['operator'] ?? null;
        $tableStr    = ($table !== null && !empty($table['name'])) ? 'Mesa: ' . $table['name'] : '';
        $operatorStr = !empty($operator) ? 'Cam.: ' . $this->truncate($this->sanitize($operator), 14) : '';

        if ($tableStr !== '' && $operatorStr !== '') {
            $this->write($this->twoCol($tableStr, $operatorStr));
        } elseif ($tableStr !== '') {
            $this->write($tableStr);
        } elseif ($operatorStr !== '') {
            $this->write($operatorStr);
        }

        $this->write($this->divider());
    }

    private function printOrderLines(array $lines): void
    {
        if ($lines === []) {
            return;
        }

        $descW = $this->width - 13;
        $this->buf .= self::BOLD_ON;
        $this->write($this->padR('DESCRIPCION', $descW) . $this->padL('UDS', 4) . $this->padL('IMPORTE', 9));
        $this->buf .= self::BOLD_OFF;
        $this->write($this->divider());

        foreach ($lines as $line) {
            $name  = $this->sanitize((string) ($line['name'] ?? 'Producto'));
            $qty   = (int) ($line['quantity'] ?? 1);
            $total = (int) ($line['total_cents'] ?? 0);

            $this->write(
                $this->padR($name, $descW) .
                $this->padL((string) $qty, 4) .
                $this->padL($this->formatCents($total), 9)
            );

            // Variant
            $variant = isset($line['variant_name']) ? trim((string) $line['variant_name']) : '';
            if ($variant !== '') {
                $this->write('   ' . $this->truncate('· ' . $this->sanitize($variant), $this->width - 3));
            }

            // Modifiers
            foreach ($line['modifiers'] ?? [] as $mod) {
                $modName = isset($mod['name']) ? trim($this->sanitize((string) $mod['name'])) : '';
                if ($modName !== '') {
                    $this->write('   ' . $this->truncate('+ ' . $modName, $this->width - 3));
                }
            }
        }

        $this->write($this->divider());
    }

    private function printTaxBreakdown(array $breakdown): void
    {
        if ($breakdown === []) {
            return;
        }

        $totalBase = 0;
        foreach ($breakdown as $row) {
            $totalBase += (int) ($row['base_cents'] ?? 0);
        }

        $this->write($this->kv('Subtotal (s/IVA)', $this->formatCents($totalBase)));

        foreach ($breakdown as $row) {
            $rate = (int) ($row['rate'] ?? 0);
            $base = $this->formatCents((int) ($row['base_cents'] ?? 0));
            $tax  = $this->formatCents((int) ($row['tax_cents'] ?? 0));
            $this->write($this->twoCol("Base {$rate}%: {$base}", "IVA: {$tax}"));
        }
    }

    private function printTotal(int $totalCents): void
    {
        $this->write($this->doubleDivider());

        $this->buf .= self::ALIGN_CENTER;
        $this->buf .= self::DOUBLE_SIZE;
        $this->buf .= self::BOLD_ON;
        $this->write('TOTAL  ' . $this->formatAmount($totalCents));
        $this->buf .= self::BOLD_OFF;
        $this->buf .= self::NORMAL_SIZE;
        $this->buf .= self::ALIGN_LEFT;

        $this->write($this->doubleDivider());
    }

    private function printPayments(array $payments): void
    {
        if ($payments === []) {
            return;
        }

        $this->buf .= self::ALIGN_CENTER;
        $this->write('FORMA DE PAGO');
        $this->buf .= self::ALIGN_LEFT;
        $this->write($this->divider());

        foreach ($payments as $payment) {
            $method = $this->translateMethod((string) ($payment['method'] ?? ''));
            $amount = $this->formatAmount((int) ($payment['amount_cents'] ?? 0));
            $this->write($this->kv($method, $amount));
        }
    }

    private function printFooter(): void
    {
        $this->write($this->doubleDivider());
        $this->buf .= self::ALIGN_CENTER;
        $this->write('GRACIAS POR SU VISITA');
        $this->buf .= self::ALIGN_LEFT;
        $this->write($this->doubleDivider());
    }

    // ── Formatting utilities ──────────────────────────────────

    private function write(string $line): void
    {
        $this->buf .= $line . self::LF;
    }

    private function divider(): string
    {
        return str_repeat('-', $this->width);
    }

    private function doubleDivider(): string
    {
        return str_repeat('=', $this->width);
    }

    private function kv(string $label, string $value): string
    {
        $valueLen = strlen($value);
        $maxLabel = max(0, $this->width - $valueLen - 1);
        if (strlen($label) > $maxLabel) {
            $label = substr($label, 0, $maxLabel);
        }
        $spaces = max(1, $this->width - strlen($label) - $valueLen);

        return $label . str_repeat(' ', $spaces) . $value;
    }

    private function twoCol(string $left, string $right): string
    {
        $leftLen  = strlen($left);
        $rightLen = strlen($right);
        if ($leftLen + $rightLen + 1 > $this->width) {
            $maxLeft = max(0, $this->width - $rightLen - 1);
            $left    = substr($left, 0, $maxLeft);
            $leftLen = strlen($left);
        }
        $spaces = max(1, $this->width - $leftLen - $rightLen);

        return $left . str_repeat(' ', $spaces) . $right;
    }

    private function padR(string $text, int $len): string
    {
        $text = $this->truncate($text, $len);
        return $text . str_repeat(' ', max(0, $len - strlen($text)));
    }

    private function padL(string $text, int $len): string
    {
        $text = $this->truncate($text, $len);
        return str_repeat(' ', max(0, $len - strlen($text))) . $text;
    }

    private function truncate(string $text, int $max): string
    {
        return strlen($text) <= $max ? $text : substr($text, 0, $max);
    }

    private function formatAmount(int $cents): string
    {
        $sign  = $cents < 0 ? '-' : '';
        $abs   = abs($cents);
        $euros = (int) floor($abs / 100);
        $dec   = $abs % 100;

        return sprintf('%s%d,%02d EUR', $sign, $euros, $dec);
    }

    private function formatCents(int $cents): string
    {
        $sign  = $cents < 0 ? '-' : '';
        $abs   = abs($cents);
        $euros = (int) floor($abs / 100);
        $dec   = $abs % 100;

        return sprintf('%s%d,%02d', $sign, $euros, $dec);
    }

    private function translateMethod(string $method): string
    {
        return match (strtolower($method)) {
            'cash'       => 'EFECTIVO',
            'card'       => 'TARJETA',
            'bizum'      => 'BIZUM',
            'voucher'    => 'VALE',
            'invitation' => 'INVITACION',
            'mixed'      => 'MIXTO',
            'other'      => 'OTRO',
            default      => strtoupper($method),
        };
    }

    /**
     * Replaces non-ASCII characters with CP437-safe equivalents for Spanish text.
     */
    private function sanitize(string $text): string
    {
        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'ã' => 'a', 'õ' => 'o', 'Ã' => 'A', 'Õ' => 'O',
            'ñ' => 'n', 'Ñ' => 'N',
            'ç' => 'c', 'Ç' => 'C',
            'ü' => 'u', 'Ü' => 'U',
            '€' => 'EUR', '·' => '-', '…' => '...', '—' => '-', '–' => '-',
            "\u{00A0}" => ' ',
        ]);
    }
}
