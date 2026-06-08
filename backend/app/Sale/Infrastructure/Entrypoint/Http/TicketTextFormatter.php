<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

final class TicketTextFormatter
{
    private const WIDTH_58 = 32;

    private const WIDTH_80 = 48;

    public function formatPayment(array $data, int $width): string
    {
        $lines = [];

        $lines[] = $this->doubleDivider($width);
        $lines = array_merge($lines, $this->restaurantBlock($data['restaurant'] ?? null, $width));
        $lines[] = $this->doubleDivider($width);

        $lines[] = $this->center('RECIBO DE PAGO', $width);
        $lines[] = $this->center('(NO CIERRA MESA)', $width);
        $lines[] = $this->divider($width);

        $lines = array_merge($lines, $this->headerInfo($data, $width, 'issued_at', 'issued_time'));
        $lines[] = $this->divider($width);

        $lines[] = $this->center('FORMA DE PAGO', $width);
        $lines[] = $this->divider($width);
        $payments = $data['payments'] ?? [];
        foreach ($payments as $payment) {
            $label = $this->translatePaymentMethod((string) ($payment['method'] ?? 'pago'));
            $amount = $this->formatAmount((int) ($payment['amount_cents'] ?? 0));
            $lines[] = $this->kv($label, $amount, $width);

            if (! empty($payment['diner_number'])) {
                $lines[] = $this->kv('  Comensal', (string) $payment['diner_number'], $width);
            }
        }

        $lines[] = $this->doubleDivider($width);

        if (isset($data['total_consumed_cents'])) {
            $lines[] = $this->kv('Total consumido', $this->formatAmount((int) $data['total_consumed_cents']), $width);
        }
        if (isset($data['total_paid_cents'])) {
            $lines[] = $this->kv('Total pagado', $this->formatAmount((int) $data['total_paid_cents']), $width);
        }
        if (isset($data['remaining_cents']) && (int) $data['remaining_cents'] > 0) {
            $lines[] = $this->kv('DEUDA RESTANTE', $this->formatAmount((int) $data['remaining_cents']), $width);
        }

        $taxBreakdown = $data['tax_breakdown'] ?? [];
        if (count($taxBreakdown) > 0) {
            $lines[] = $this->divider($width);
            $lines = array_merge($lines, $this->taxBlock($taxBreakdown, $width));
        }

        $lines[] = $this->doubleDivider($width);
        $lines[] = $this->center('GRACIAS POR SU VISITA', $width);
        $lines[] = $this->doubleDivider($width);

        return implode("\n", $lines)."\n";
    }

    public function formatFinal(array $data, int $width): string
    {
        $lines = [];

        $lines[] = $this->doubleDivider($width);
        $lines = array_merge($lines, $this->restaurantBlock($data['restaurant'] ?? null, $width));
        $lines[] = $this->doubleDivider($width);

        $ticketNumber = ! empty($data['ticket_number']) ? (string) $data['ticket_number'] : '';
        $lines[] = $this->center('FACTURA SIMPLIFICADA', $width);
        if ($ticketNumber !== '') {
            $lines[] = $this->center('Nº '.$ticketNumber, $width);
        }
        $lines[] = $this->divider($width);

        $lines = array_merge($lines, $this->headerInfo($data, $width, 'created_at', 'created_time', false));
        $lines[] = $this->divider($width);

        $orderLines = $data['order_lines'] ?? [];
        if (count($orderLines) > 0) {
            $lines[] = $this->lineHeader($width);
            $lines[] = $this->divider($width);
            foreach ($orderLines as $line) {
                $name = (string) ($line['name'] ?? 'Producto');
                $qty = (int) ($line['quantity'] ?? 0);
                $total = (int) ($line['total_cents'] ?? 0);
                $lines[] = $this->lineItem($name, $qty, $total, $width);
                $lines = array_merge($lines, $this->lineDetails($line, $width));
            }
            $lines[] = $this->divider($width);
        }

        $totalConsumed = (int) ($data['total_consumed_cents'] ?? 0);

        $taxBreakdown = $data['tax_breakdown'] ?? [];
        if (count($taxBreakdown) > 0) {
            $totalBase = 0;
            foreach ($taxBreakdown as $row) {
                $totalBase += (int) ($row['base_cents'] ?? 0);
            }
            $lines[] = $this->kv('Subtotal (s/IVA)', $this->formatAmount($totalBase), $width);
            $lines = array_merge($lines, $this->taxBlock($taxBreakdown, $width));
        }

        $lines[] = $this->doubleDivider($width);
        $lines[] = $this->kv('TOTAL', $this->formatAmount($totalConsumed), $width);
        $lines[] = $this->doubleDivider($width);

        $payments = $data['payments_snapshot'] ?? [];
        if (count($payments) > 0) {
            $lines[] = $this->center('FORMA DE PAGO', $width);
            $lines[] = $this->divider($width);
            foreach ($payments as $payment) {
                $label = $this->translatePaymentMethod((string) ($payment['method'] ?? 'pago'));
                $amount = $this->formatAmount((int) ($payment['amount_cents'] ?? 0));
                $lines[] = $this->kv($label, $amount, $width);
            }
            $lines[] = $this->divider($width);
        }

        $lines[] = $this->center('MESA CERRADA', $width);
        $lines[] = '';
        $lines[] = $this->center('GRACIAS POR SU VISITA', $width);
        $lines[] = $this->doubleDivider($width);

        return implode("\n", $lines)."\n";
    }

    public function resolveWidth(?string $width): int
    {
        if ($width === '80') {
            return self::WIDTH_80;
        }

        return self::WIDTH_58;
    }

    private function restaurantBlock(?array $restaurant, int $width): array
    {
        if ($restaurant === null) {
            return [];
        }

        $lines = [];
        if (! empty($restaurant['name'])) {
            $lines[] = $this->center(strtoupper((string) $restaurant['name']), $width);
        }
        if (! empty($restaurant['legal_name'])) {
            $lines[] = $this->center((string) $restaurant['legal_name'], $width);
        }
        if (! empty($restaurant['tax_id'])) {
            $lines[] = $this->center('NIF: '.$restaurant['tax_id'], $width);
        }

        return $lines;
    }

    private function headerInfo(array $data, int $width, string $dateKey, string $timeKey, bool $isPayment = true): array
    {
        $lines = [];

        $dateValue = $data[$dateKey] ?? null;
        $timeValue = $data[$timeKey] ?? null;
        if ($dateValue !== null) {
            $dateStr = $this->formatDate((string) $dateValue);
            $timeStr = $timeValue !== null ? (string) $timeValue : '';
            if ($timeStr !== '') {
                $lines[] = $this->twoColumn('Fecha: '.$dateStr, 'Hora: '.$timeStr, $width);
            } else {
                $lines[] = $this->kv('Fecha', $dateStr, $width);
            }
        }

        $table = $data['table'] ?? null;
        $operator = $data['operator'] ?? null;
        $tableStr = ($table !== null && ! empty($table['name'])) ? 'Mesa: '.$table['name'] : '';
        $operatorStr = ! empty($operator) ? 'Cam.: '.$this->truncate((string) $operator, 14) : '';
        if ($tableStr !== '' && $operatorStr !== '') {
            $lines[] = $this->twoColumn($tableStr, $operatorStr, $width);
        } elseif ($tableStr !== '') {
            $lines[] = $tableStr;
        } elseif ($operatorStr !== '') {
            $lines[] = $operatorStr;
        }

        $ticketStr = '';
        if ($isPayment && ! empty($data['ticket_number'])) {
            $ticketStr = 'Ticket: '.$data['ticket_number'];
        }
        $zStr = ! empty($data['z_report_number']) ? 'Z-Rep: '.$data['z_report_number'] : '';
        if ($ticketStr !== '' && $zStr !== '') {
            $lines[] = $this->twoColumn($ticketStr, $zStr, $width);
        } elseif ($ticketStr !== '') {
            $lines[] = $ticketStr;
        } elseif ($zStr !== '') {
            $lines[] = $zStr;
        }

        return $lines;
    }

    private function lineHeader(int $width): string
    {

        $descWidth = $width - 13;

        return $this->padRight('DESCRIPCION', $descWidth).$this->padLeft('UDS', 4).$this->padLeft('IMPORTE', 9);
    }

    private function lineItem(string $name, int $qty, int $totalCents, int $width): string
    {

        $descWidth = $width - 13;
        $name = $this->truncate($name, $descWidth);
        $total = $this->formatCents($totalCents);

        return $this->padRight($name, $descWidth).$this->padLeft((string) $qty, 4).$this->padLeft($total, 9);
    }

    private function lineDetails(array $line, int $width): array
    {
        $details = [];
        $indent = '   ';
        $maxText = max(0, $width - mb_strlen($indent, 'UTF-8'));

        $variantName = isset($line['variant_name']) ? trim((string) $line['variant_name']) : '';
        if ($variantName !== '') {
            $details[] = $indent.$this->truncate('· '.$variantName, $maxText);
        }

        foreach ($line['modifiers'] ?? [] as $modifier) {
            $modName = isset($modifier['name']) ? trim((string) $modifier['name']) : '';
            if ($modName !== '') {
                $details[] = $indent.$this->truncate('+ '.$modName, $maxText);
            }
        }

        return $details;
    }

    private function taxBlock(array $taxBreakdown, int $width): array
    {
        $lines = [];
        foreach ($taxBreakdown as $row) {
            $rate = (int) ($row['rate'] ?? 0);
            $base = $this->formatCents((int) ($row['base_cents'] ?? 0));
            $tax = $this->formatCents((int) ($row['tax_cents'] ?? 0));
            $left = sprintf('Base %d%%: %s', $rate, $base);
            $right = sprintf('IVA: %s', $tax);
            $lines[] = $this->twoColumn($left, $right, $width);
        }

        return $lines;
    }

    private function translatePaymentMethod(string $method): string
    {
        return match (strtolower($method)) {
            'cash' => 'EFECTIVO',
            'card' => 'TARJETA',
            'bizum' => 'BIZUM',
            'voucher' => 'VALE',
            'invitation' => 'INVITACION',
            'mixed' => 'MIXTO',
            'other' => 'OTRO',
            default => strtoupper($method),
        };
    }

    private function formatDate(string $isoDate): string
    {

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $isoDate, $m) === 1) {
            return $m[3].'/'.$m[2].'/'.$m[1];
        }

        return substr($isoDate, 0, 10);
    }

    private function doubleDivider(int $width): string
    {
        return str_repeat('=', $width);
    }

    private function divider(int $width): string
    {
        return str_repeat('-', $width);
    }

    private function center(string $text, int $width): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = $this->truncate($text, $width);
        $len = mb_strlen($text, 'UTF-8');
        $pad = max(0, $width - $len);
        $left = (int) floor($pad / 2);
        $right = $pad - $left;

        return str_repeat(' ', $left).$text.str_repeat(' ', $right);
    }

    private function kv(string $label, string $value, int $width): string
    {
        $label = trim($label);
        $value = trim($value);
        $valueLen = mb_strlen($value, 'UTF-8');
        $maxLabel = max(0, $width - $valueLen - 1);

        if (mb_strlen($label, 'UTF-8') > $maxLabel) {
            $label = mb_substr($label, 0, $maxLabel, 'UTF-8');
        }

        $spaces = max(1, $width - mb_strlen($label, 'UTF-8') - $valueLen);

        return $label.str_repeat(' ', $spaces).$value;
    }

    private function twoColumn(string $left, string $right, int $width): string
    {
        $left = trim($left);
        $right = trim($right);
        $leftLen = mb_strlen($left, 'UTF-8');
        $rightLen = mb_strlen($right, 'UTF-8');
        if ($leftLen + $rightLen + 1 > $width) {
            $maxLeft = max(0, $width - $rightLen - 1);
            $left = mb_substr($left, 0, $maxLeft, 'UTF-8');
            $leftLen = mb_strlen($left, 'UTF-8');
        }
        $spaces = max(1, $width - $leftLen - $rightLen);

        return $left.str_repeat(' ', $spaces).$right;
    }

    private function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }

    private function padRight(string $text, int $length): string
    {
        $text = $this->truncate($text, $length);
        $pad = max(0, $length - mb_strlen($text, 'UTF-8'));

        return $text.str_repeat(' ', $pad);
    }

    private function padLeft(string $text, int $length): string
    {
        $text = $this->truncate($text, $length);
        $pad = max(0, $length - mb_strlen($text, 'UTF-8'));

        return str_repeat(' ', $pad).$text;
    }

    private function formatAmount(int $cents): string
    {

        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);
        $euros = (int) floor($abs / 100);
        $dec = $abs % 100;

        return sprintf('%s%d,%02d EUR', $sign, $euros, $dec);
    }

    private function formatCents(int $cents): string
    {

        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);
        $euros = (int) floor($abs / 100);
        $dec = $abs % 100;

        return sprintf('%s%d,%02d', $sign, $euros, $dec);
    }
}
