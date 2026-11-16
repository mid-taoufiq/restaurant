<?php

namespace App\Service;

class UnitConverter
{
    private const GROUPS = [
        'kg' => ['group' => 'mass', 'to_base' => 1000.0],
        'g' => ['group' => 'mass', 'to_base' => 1.0],
        'L' => ['group' => 'volume', 'to_base' => 1000.0],
        'mL' => ['group' => 'volume', 'to_base' => 1.0],
        'piece' => ['group' => 'count', 'to_base' => 1.0],
    ];

    public function convert(float $amount, string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return $amount;
        }

        if (!isset(self::GROUPS[$fromUnit]) || !isset(self::GROUPS[$toUnit])) {
            throw new \InvalidArgumentException(sprintf('Unsupported unit conversion: %s to %s', $fromUnit, $toUnit));
        }

        $from = self::GROUPS[$fromUnit];
        $to = self::GROUPS[$toUnit];

        if ($from['group'] !== $to['group']) {
            throw new \InvalidArgumentException(sprintf('Incompatible units: cannot convert %s (%s) to %s (%s)', $fromUnit, $from['group'], $toUnit, $to['group']));
        }

        $inBase = $amount * $from['to_base'];
        return $inBase / $to['to_base'];
    }
}
