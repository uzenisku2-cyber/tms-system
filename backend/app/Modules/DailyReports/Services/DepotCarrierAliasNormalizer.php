<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use Normalizer;

final class DepotCarrierAliasNormalizer
{
    public function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $decomposed = Normalizer::normalize(
            $value,
            Normalizer::FORM_D,
        );

        if (is_string($decomposed)) {
            $value = $decomposed;
        }

        $value = preg_replace('/\p{Mn}+/u', '', $value) ?? $value;
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';

        return $value;
    }
}
