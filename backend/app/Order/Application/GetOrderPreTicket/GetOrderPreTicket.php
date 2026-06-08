<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderPreTicket;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class GetOrderPreTicket
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly TableRepositoryInterface $tableRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(GetOrderPreTicketCommand $command): GetOrderPreTicketResponse
    {
        $orderUuid = Uuid::create($command->orderId);
        $order = $this->orderRepository->findByUuid($orderUuid);

        if ($order === null) {
            throw new \DomainException('Order not found.');
        }

        $table = $this->tableRepository->findById($order->tableId()->value());
        $restaurant = $this->restaurantRepository->findByUuid($order->restaurantId());

        $lines = $this->orderLineRepository->findByOrderId($orderUuid);
        $width = (int) $command->width;

        $text = $this->buildText($order, $table, $restaurant, $lines, $width);

        return new GetOrderPreTicketResponse($text);
    }

    private function buildText(
        Order $order,
        ?Table $table,
        ?Restaurant $restaurant,
        array $lines,
        int $width,
    ): string {
        $fill = str_repeat('=', $width);
        $sep = str_repeat('-', $width);
        $out = [];

        $out[] = $this->center('PRE-CUENTA', $width);
        $out[] = '';

        if ($restaurant !== null) {
            $out[] = $this->center($restaurant->name()->value(), $width);
            if ($restaurant->legalName() !== null) {
                $out[] = $this->center($restaurant->legalName()->value(), $width);
            }
            $out[] = '';
        }

        $out[] = $this->pair('Mesa', $table !== null ? $table->name()->value() : $order->tableId()->value(), $width);
        $out[] = $this->pair('Fecha', (new \DateTimeImmutable)->format('d/m/Y H:i'), $width);
        $out[] = $this->pair('Comensales', (string) $order->diners()->value(), $width);
        $out[] = $fill;
        $out[] = '';

        $total = 0;
        foreach ($lines as $line) {
            $qty = $line->quantity()->value();
            $price = $line->price()->value();
            $lineTotal = $qty * $price;
            $total += $lineTotal;

            if ($line->isMenuLine()) {
                $name = $line->menuName() ?? 'Menú';
                $out[] = $this->itemLine($qty, $name, $price, $lineTotal, $width);
                $selections = $line->menuSelections() ?? [];
                foreach ($selections as $sel) {
                    $selName = '  '.($sel['product_name'] ?? 'Producto');
                    $selPrice = $sel['extra_price'] ?? 0;
                    if ($selPrice > 0) {
                        $selName .= ' (+'.$this->moneyLabel($selPrice).')';
                    }
                    $mods = $sel['modifiers'] ?? [];
                    foreach ($mods as $mod) {
                        if ($mod['price'] > 0) {
                            $selName .= ' + '.$mod['name'];
                        }
                    }
                    $out[] = $this->wrapLine($selName, $width);
                }
            } else {
                $name = 'Producto';
                if ($line->productId() !== null) {

                    $name = 'Producto';
                }
                $out[] = $this->itemLine($qty, $name, $price, $lineTotal, $width);
                if ($line->variantName() !== null) {
                    $out[] = $this->wrapLine('  '.$line->variantName(), $width);
                }
                $mods = $line->modifiers() ?? [];
                foreach ($mods as $mod) {
                    if ($mod['price'] > 0) {
                        $out[] = $this->wrapLine('  + '.$mod['name'].'  '.$this->moneyLabel($mod['price']), $width);
                    }
                }
            }
            $out[] = '';
        }

        $out[] = $fill;
        $out[] = $this->pair('TOTAL', $this->moneyLabel($total).' EUR', $width);
        $out[] = $fill;
        $out[] = '';
        $out[] = $this->center('PENDIENTE DE COBRO', $width);
        $out[] = $this->center('Gracias por su visita', $width);
        $out[] = '';

        return implode("\n", $out);
    }

    private function center(string $text, int $width): string
    {
        $len = mb_strlen($text);
        if ($len >= $width) {
            return $text;
        }
        $pad = (int) floor(($width - $len) / 2);

        return str_repeat(' ', $pad).$text;
    }

    private function pair(string $left, string $right, int $width): string
    {
        $gap = $width - mb_strlen($left) - mb_strlen($right);
        if ($gap <= 0) {
            return mb_substr($left, 0, $width);
        }

        return $left.str_repeat(' ', $gap).$right;
    }

    private function itemLine(int $qty, string $name, int $price, int $total, int $width): string
    {
        $prefix = $qty.'x ';
        $priceStr = $this->moneyLabel($price).'  '.$this->moneyLabel($total);
        $available = $width - mb_strlen($prefix) - mb_strlen($priceStr);
        if ($available <= 0) {
            return mb_substr($prefix.$name, 0, $width);
        }
        $namePart = mb_strlen($name) > $available ? mb_substr($name, 0, $available - 1).'…' : $name;

        return $prefix.$namePart.str_repeat(' ', $available - mb_strlen($namePart)).$priceStr;
    }

    private function wrapLine(string $text, int $width): string
    {
        if (mb_strlen($text) <= $width) {
            return $text;
        }

        return mb_substr($text, 0, $width - 1).'…';
    }

    private function moneyLabel(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').'€';
    }
}
