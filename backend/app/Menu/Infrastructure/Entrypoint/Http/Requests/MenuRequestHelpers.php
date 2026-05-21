<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\Shared\MenuSectionInput;

/**
 * Helpers compartidos por los FormRequests del módulo Menu.
 *
 * - Conversión de array de días ISO (1..7) a bitmask para el VO MenuAvailability.
 * - Construcción de la lista tipada de MenuSectionInput a partir del array crudo.
 */
final class MenuRequestHelpers
{
    /**
     * @param  array<int, mixed>  $weekdays  Lista de enteros 1..7 (ISO: 1=Lunes, 7=Domingo)
     */
    public static function weekdaysToBitmask(array $weekdays): int
    {
        $bitmask = 0;
        foreach ($weekdays as $day) {
            $d = (int) $day;
            if ($d < 1 || $d > 7) {
                continue;
            }
            $bitmask |= (1 << ($d - 1));
        }

        return $bitmask;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawSections
     * @return MenuSectionInput[]
     */
    public static function buildSections(array $rawSections): array
    {
        $sections = [];
        $i = 0;
        foreach ($rawSections as $rawSection) {
            $sections[] = MenuSectionInput::fromArray((array) $rawSection, $i);
            $i++;
        }

        return $sections;
    }
}
