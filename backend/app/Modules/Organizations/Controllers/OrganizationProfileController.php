<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class OrganizationProfileController
{
    public function show(
        OrganizationContext $context,
    ): JsonResponse {
        $organization = Organization::query()
            ->whereKey($context->requireId())
            ->firstOrFail();

        return response()->json([
            'data' => $this->resource($organization),
        ]);
    }

    public function update(
        Request $request,
        OrganizationContext $context,
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'registration_number' => [
                'nullable',
                'string',
                'max:32',
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:32',
            ],
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:32',
            ],
            'country_code' => [
                'nullable',
                'string',
                'size:2',
                'alpha',
            ],
            'contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'contact_phone' => [
                'nullable',
                'string',
                'max:64',
            ],
        ]);

        $organization = Organization::query()
            ->whereKey($context->requireId())
            ->firstOrFail();

        if ($organization->getAttribute('type') !== Organization::TYPE_MASTER) {
            abort(403, 'This pilot profile is available only for the master organization.');
        }

        $organization->fill([
            'name' => trim((string) $validated['name']),
            'registration_number' => $this->nullableTrimmed(
                $validated['registration_number'] ?? null,
            ),
            'vat_number' => $this->nullableTrimmed(
                $validated['vat_number'] ?? null,
            ),
            'street' => $this->nullableTrimmed(
                $validated['street'] ?? null,
            ),
            'city' => $this->nullableTrimmed(
                $validated['city'] ?? null,
            ),
            'postal_code' => $this->nullableTrimmed(
                $validated['postal_code'] ?? null,
            ),
            'country_code' => $this->countryCode(
                $validated['country_code'] ?? null,
            ),
            'contact_email' => $this->nullableTrimmed(
                $validated['contact_email'] ?? null,
            ),
            'contact_phone' => $this->nullableTrimmed(
                $validated['contact_phone'] ?? null,
            ),
        ]);

        $organization->save();

        return response()->json([
            'message' => 'Údaje firmy byly uloženy.',
            'data' => $this->resource($organization->refresh()),
        ]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function resource(
        Organization $organization,
    ): array {
        return [
            'id' => (int) $organization->getKey(),
            'name' => (string) $organization->getAttribute('name'),
            'type' => (string) $organization->getAttribute('type'),
            'status' => (string) $organization->getAttribute('status'),
            'registration_number' => $organization->getAttribute('registration_number'),
            'vat_number' => $organization->getAttribute('vat_number'),
            'street' => $organization->getAttribute('street'),
            'city' => $organization->getAttribute('city'),
            'postal_code' => $organization->getAttribute('postal_code'),
            'country_code' => $organization->getAttribute('country_code'),
            'contact_email' => $organization->getAttribute('contact_email'),
            'contact_phone' => $organization->getAttribute('contact_phone'),
        ];
    }

    private function nullableTrimmed(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function countryCode(
        mixed $value,
    ): ?string {
        $value = $this->nullableTrimmed($value);

        return $value === null
            ? null
            : Str::upper($value);
    }
}
