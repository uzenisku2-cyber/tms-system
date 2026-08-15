<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class AresEconomicSubjectService
{
    private const BASE_URL =
        'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty';

    /**
     * @return array{
     *     registration_number:string,
     *     name:string,
     *     vat_number:?string,
     *     vat_status:string,
     *     dph_registry_status:?string,
     *     street:?string,
     *     city:?string,
     *     postal_code:?string,
     *     country_code:?string,
     *     full_address:?string
     * }
     */
    public function lookup(string $registrationNumber): array
    {
        $registrationNumber = trim(
            $registrationNumber,
        );

        if (! preg_match('/^\d{8}$/', $registrationNumber)) {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'IČO musí obsahovat přesně 8 číslic.',
                ],
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get(
                    self::BASE_URL.'/'.$registrationNumber,
                );
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'ARES je momentálně nedostupný. IČO se nepodařilo ověřit.',
                ],
            ]);
        }

        if ($response->status() === 404) {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'IČO nebylo v ARES nalezeno.',
                ],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'ARES vrátil chybu při ověřování IČO.',
                ],
            ]);
        }

        $payload = $response->json();

        if (
            ! is_array($payload)
            || (string) ($payload['ico'] ?? '')
                !== $registrationNumber
        ) {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'ARES vrátil neočekávanou odpověď.',
                ],
            ]);
        }

        $name = trim(
            (string) ($payload['obchodniJmeno'] ?? ''),
        );

        if ($name === '') {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'ARES nevrátil obchodní jméno subjektu.',
                ],
            ]);
        }

        $vatNumber = strtoupper(
            trim(
                (string) ($payload['dic'] ?? ''),
            ),
        );

        if ($vatNumber === '') {
            $vatNumber = null;
        }

        $dphRegistryStatus = data_get(
            $payload,
            'seznamRegistraci.stavZdrojeDph',
        );

        if ($dphRegistryStatus === 'AKTIVNI') {
            $vatStatus =
                Organization::VAT_STATUS_PAYER;
        } elseif (
            $dphRegistryStatus === 'NEEXISTUJICI'
            || $dphRegistryStatus === null
            || trim((string) $dphRegistryStatus) === ''
        ) {
            $vatStatus =
                Organization::VAT_STATUS_NON_PAYER;
        } else {
            throw ValidationException::withMessages([
                'vat_status' => [
                    'ARES vrátil nejednoznačný stav registrace DPH: '
                    .(string) $dphRegistryStatus.'.',
                ],
            ]);
        }

        if (
            $vatStatus === Organization::VAT_STATUS_PAYER
            && $vatNumber === null
        ) {
            throw ValidationException::withMessages([
                'vat_number' => [
                    'ARES potvrdil plátce DPH, ale nevrátil DIČ.',
                ],
            ]);
        }

        $seat = is_array(
            $payload['sidlo'] ?? null,
        )
            ? $payload['sidlo']
            : [];

        return [
            'registration_number' => $registrationNumber,
            'name' => $name,
            'vat_number' => $vatNumber,
            'vat_status' => $vatStatus,
            'dph_registry_status' => is_string($dphRegistryStatus)
                    ? $dphRegistryStatus
                    : null,
            'street' => $this->street($seat),
            'city' => $this->nullableString(
                $seat['nazevObce'] ?? null,
            ),
            'postal_code' => $this->nullableString(
                $seat['psc'] ?? null,
            ),
            'country_code' => $this->nullableString(
                $seat['kodStatu'] ?? null,
            ),
            'full_address' => $this->nullableString(
                $seat['textovaAdresa'] ?? null,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $seat
     */
    private function street(array $seat): ?string
    {
        $street = trim(
            (string) ($seat['nazevUlice'] ?? ''),
        );

        $house = trim(
            (string) ($seat['cisloDomovni'] ?? ''),
        );

        $orientation = trim(
            (string) ($seat['cisloOrientacni'] ?? ''),
        );

        if ($house !== '') {
            $street .=
                ($street === '' ? '' : ' ')
                .$house;
        }

        if ($orientation !== '') {
            $street .= '/'.$orientation;
        }

        return $street === ''
            ? null
            : $street;
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        $value = trim(
            (string) ($value ?? ''),
        );

        return $value === ''
            ? null
            : $value;
    }
}
