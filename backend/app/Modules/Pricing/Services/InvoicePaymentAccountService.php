<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\OrganizationInvoicePaymentAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InvoicePaymentAccountService
{
    public function __construct(private readonly OrganizationContext $context) {}

    /** @return array<string, mixed>|null */
    public function current(): ?array
    {
        $organizationId = $this->context->requireId();
        $organization = Organization::query()->findOrFail($organizationId);
        abort_unless($organization->type === Organization::TYPE_MASTER, 403);
        $account = OrganizationInvoicePaymentAccount::query()
            ->where('organization_id', $organizationId)->orderByDesc('revision')->first();

        return $account instanceof OrganizationInvoicePaymentAccount ? $this->present($account) : null;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function store(User $actor, array $data): array
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $data['iban']) ?? '');
        if (! $this->validIban($iban)) {
            throw ValidationException::withMessages(['iban' => ['IBAN has invalid syntax or check digits.']]);
        }
        $holder = trim((string) $data['account_holder']);
        $reference = trim((string) $data['source_reference']);
        $reason = trim((string) $data['reason']);
        if ($holder === '' || $reference === '' || $reason === '') {
            throw ValidationException::withMessages(['account_holder' => ['Payment account details must not be blank.']]);
        }
        $organizationId = $this->context->requireId();

        return DB::transaction(function () use ($actor, $iban, $holder, $reference, $reason, $organizationId): array {
            $organization = Organization::query()->whereKey($organizationId)->lockForUpdate()->firstOrFail();
            abort_unless($organization->type === Organization::TYPE_MASTER, 403);
            $latest = OrganizationInvoicePaymentAccount::query()->where('organization_id', $organizationId)
                ->orderByDesc('revision')->first();
            $account = OrganizationInvoicePaymentAccount::query()->create([
                'organization_id' => $organizationId, 'revision' => ($latest->revision ?? 0) + 1,
                'iban' => $iban, 'account_holder' => $holder, 'source_reference' => $reference,
                'reason' => $reason, 'confirmed_at' => now(), 'confirmed_by_user_id' => $actor->id,
            ]);

            return $this->present($account);
        });
    }

    /** @return array<string, mixed> */
    private function present(OrganizationInvoicePaymentAccount $account): array
    {
        return [
            'iban' => $account->iban, 'account_holder' => $account->account_holder,
            'source_reference' => $account->source_reference, 'revision' => $account->revision,
            'confirmed_at' => (string) $account->getRawOriginal('confirmed_at'),
        ];
    }

    private function validIban(string $iban): bool
    {
        if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) !== 1
            || strlen($iban) > 34) {
            return false;
        }
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $remainder = 0;
        foreach (str_split($rearranged) as $character) {
            $digits = ctype_alpha($character) ? (string) (ord($character) - 55) : $character;
            foreach (str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder === 1;
    }
}
