<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\Shared\MenuSectionInput;

final class MenuRequestHelpers
{

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
