<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use InvalidArgumentException;

/**
 * Calculates an informational route amount from the driver's current record.
 * Amounts use integer cents; no financial calculation or settlement is written.
 */
final class ProvisionalRouteRemunerationCalculator
{
    /**
     * @param  array{delivered:int,redirected:int,km:string,surcharge:string}  $route
     * @param  array{delivered:string,redirected:string,km:string,quality:string}  $rates
     * @param  array{loaded:int,delivered:int,redirected:int,rejected:int}  $month
     * @return array{delivered_minor:int,redirected_minor:int,km_minor:int,quality_minor:int,surcharge_minor:int,total_minor:int,quality_eligible:bool}
     */
    public function calculate(array $route, array $rates, array $month): array
    {
        foreach (['delivered', 'redirected'] as $key) {
            if ($route[$key] < 0) {
                throw new InvalidArgumentException('Parcel counts cannot be negative.');
            }
        }

        foreach ($month as $quantity) {
            if ($quantity < 0) {
                throw new InvalidArgumentException('Monthly counts cannot be negative.');
            }
        }

        $eligible = $this->cents($rates['quality']) > 0
            && $month['loaded'] > 0
            && ($month['delivered'] + $month['redirected'] + $month['rejected']) * 100
                >= $month['loaded'] * 20;

        $delivered = $this->multiplyUnits($route['delivered'], $rates['delivered']);
        $redirected = $this->multiplyUnits($route['redirected'], $rates['redirected']);
        $km = $this->multiplyDecimals($route['km'], $rates['km']);
        $quality = $eligible
            ? $this->multiplyUnits($route['delivered'] + $route['redirected'], $rates['quality'])
            : 0;
        $surcharge = $this->cents($route['surcharge']);

        return [
            'delivered_minor' => $delivered,
            'redirected_minor' => $redirected,
            'km_minor' => $km,
            'quality_minor' => $quality,
            'surcharge_minor' => $surcharge,
            'total_minor' => $delivered + $redirected + $km + $quality + $surcharge,
            'quality_eligible' => $eligible,
        ];
    }

    private function cents(string $amount): int
    {
        if (preg_match('/^\d+(?:\.\d{1,4})?$/D', $amount) !== 1) {
            throw new InvalidArgumentException('Invalid non-negative decimal amount.');
        }

        [$units, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $four = (int) str_pad($fraction, 4, '0');

        return (int) $units * 100 + intdiv($four + 50, 100);
    }

    private function multiplyUnits(int $quantity, string $rate): int
    {
        if (preg_match('/^\d+(?:\.\d{1,4})?$/D', $rate) !== 1) {
            throw new InvalidArgumentException('Invalid non-negative rate.');
        }

        [$units, $fraction] = array_pad(explode('.', $rate, 2), 2, '');
        $tenThousandths = (int) $units * 10000 + (int) str_pad($fraction, 4, '0');

        return intdiv($quantity * $tenThousandths + 50, 100);
    }

    private function multiplyDecimals(string $quantity, string $rate): int
    {
        if (preg_match('/^\d+(?:\.\d{1,2})?$/D', $quantity) !== 1
            || preg_match('/^\d+(?:\.\d{1,4})?$/D', $rate) !== 1) {
            throw new InvalidArgumentException('Invalid kilometre quantity or rate.');
        }

        [$km, $kmFraction] = array_pad(explode('.', $quantity, 2), 2, '');
        [$units, $rateFraction] = array_pad(explode('.', $rate, 2), 2, '');
        $hundredths = (int) $km * 100 + (int) str_pad($kmFraction, 2, '0');
        $tenThousandths = (int) $units * 10000 + (int) str_pad($rateFraction, 4, '0');

        return intdiv($hundredths * $tenThousandths + 5000, 10000);
    }
}
