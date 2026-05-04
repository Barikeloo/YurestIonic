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
        $lines[] = $this->center('TICKET DE PAGO', $width);
        $lines[] = $this->divider($width);

        $lines = array_merge($lines, $this->restaurantBlock($data['restaurant'] ?? null, $width));
        $lines = array_merge($lines, $this->tableBlock($data['table'] ?? null, $width));

        $issuedAt = $data['issued_at'] ?? null;
        if ($issuedAt !== null) {
            $lines[] = $this->kv('Fecha', $issuedAt, $width);
        }

        if (! empty($data['ticket_number'])) {
            $lines[] = $this->kv('Ticket', (string) $data['ticket_number'], $width);
        }

        $lines[] = $this->divider($width);

        $payments = $data['payments'] ?? [];
        foreach ($payments as $payment) {
            $label = strtoupper((string) ($payment['method'] ?? 'pago'));
            $amount = $this->formatCents((int) ($payment['amount_cents'] ?? 0));
            $lines[] = $this->kv($label, $amount, $width);

            if (! empty($payment['diner_number'])) {
                $lines[] = $this->kv('Comensal', (string) $payment['diner_number'], $width);
            }
        }

        $lines[] = $this->divider($width);

        if (isset($data['total_consumed_cents'])) {
            $lines[] = $this->kv('Total consumido', $this->formatCents((int) $data['total_consumed_cents']), $width);
        }

        if (isset($data['total_paid_cents'])) {
            $lines[] = $this->kv('Total pagado', $this->formatCents((int) $data['total_paid_cents']), $width);
        }

        if (isset($data['remaining_cents'])) {
            $lines[] = $this->kv('Deuda restante', $this->formatCents((int) $data['remaining_cents']), $width);
        }

        $lines[] = $this->divider($width);
        $lines[] = $this->center('RECIBO DE PAGO', $width);
        $lines[] = $this->center('NO CIERRA MESA', $width);
        $lines[] = $this->center('GRACIAS', $width);

        return implode("\n", $lines)."\n";
    }

    public function formatFinal(array $data, int $width): string
    {
        $lines = [];
        $lines[] = $this->center('TICKET FINAL', $width);
        $lines[] = $this->divider($width);

        $lines = array_merge($lines, $this->restaurantBlock($data['restaurant'] ?? null, $width));
        $lines = array_merge($lines, $this->tableBlock($data['table'] ?? null, $width));

        if (! empty($data['created_at'])) {
            $lines[] = $this->kv('Fecha', (string) $data['created_at'], $width);
        }

        if (! empty($data['ticket_number'])) {
            $lines[] = $this->kv('Ticket', (string) $data['ticket_number'], $width);
        }

        $lines[] = $this->divider($width);

        $lines[] = $this->kv('Total consumido', $this->formatCents((int) $data['total_consumed_cents']), $width);
        $lines[] = $this->kv('Total pagado', $this->formatCents((int) $data['total_paid_cents']), $width);

        $lines[] = $this->divider($width);

        $lines[] = $this->center('PAGOS', $width);
        $payments = $data['payments_snapshot'] ?? [];
        foreach ($payments as $payment) {
            $label = strtoupper((string) ($payment['method'] ?? 'pago'));
            $amount = $this->formatCents((int) ($payment['amount_cents'] ?? 0));
            $lines[] = $this->kv($label, $amount, $width);
            if (! empty($payment['paid_at'])) {
                $lines[] = $this->kv('Hora', (string) $payment['paid_at'], $width);
            }
        }

        $lines[] = $this->divider($width);

        $taxBreakdown = $data['tax_breakdown'] ?? [];
        if (count($taxBreakdown) > 0) {
            $lines[] = $this->center('IVA', $width);
            foreach ($taxBreakdown as $row) {
                $rate = (int) ($row['rate'] ?? 0);
                $base = $this->formatCents((int) ($row['base_cents'] ?? 0));
                $tax = $this->formatCents((int) ($row['tax_cents'] ?? 0));
                $lines[] = $this->kv('IVA '.$rate.'%', $tax, $width);
                $lines[] = $this->kv('Base '.$rate.'%', $base, $width);
            }
            $lines[] = $this->divider($width);
        }

        $lines[] = $this->center('MESA CERRADA', $width);
        $lines[] = $this->center('GRACIAS', $width);

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
            $lines[] = $this->center((string) $restaurant['name'], $width);
        }
        if (! empty($restaurant['legal_name'])) {
            $lines[] = $this->center((string) $restaurant['legal_name'], $width);
        }
        if (! empty($restaurant['tax_id'])) {
            $lines[] = $this->center('NIF '.$restaurant['tax_id'], $width);
        }

        return $lines;
    }

    private function tableBlock(?array $table, int $width): array
    {
        if ($table === null || empty($table['name'])) {
            return [];
        }

        return [$this->kv('Mesa', (string) $table['name'], $width)];
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

        $pad = max(0, $width - strlen($text));
        $left = (int) floor($pad / 2);
        $right = $pad - $left;

        return str_repeat(' ', $left).$text.str_repeat(' ', $right);
    }

    private function kv(string $label, string $value, int $width): string
    {
        $label = trim($label);
        $value = trim($value);
        $maxLabel = max(0, $width - strlen($value) - 1);

        if (strlen($label) > $maxLabel) {
            $label = substr($label, 0, $maxLabel);
        }

        $spaces = max(1, $width - strlen($label) - strlen($value));

        return $label.str_repeat(' ', $spaces).$value;
    }

    private function formatCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);
        $euros = (int) floor($abs / 100);
        $dec = $abs % 100;

        return sprintf('%s%d,%02d EUR', $sign, $euros, $dec);
    }
}
