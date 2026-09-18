<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final class FinancialSettlementBankPaymentReconciliationCsvExportService
{
    private const HEADERS = [
        'Vyúčtování', 'Příjemce', 'Období od', 'Období do', 'Směr', 'Čisté saldo', 'Měna',
        'Platba', 'Přiřazená částka', 'Stav platby', 'Bankovní reference', 'Datum zaúčtování',
        'Stav rekonciliace', 'Revize rekonciliace', 'Důvod', 'Potvrzeno', 'Znovu otevřeno',
    ];

    public function filename(): string
    {
        return 'reconcilace-vyuctovani-'.now()->format('Ymd-His').'.csv';
    }

    /** @param array<string, mixed> $filters @param resource $output */
    public function write(int $organizationId, User $actor, array $filters, mixed $output): int
    {
        if (! is_resource($output)) {
            throw new RuntimeException('The CSV output must be a writable resource.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        $this->put($output, self::HEADERS);
        $rowCount = 0;

        $query = FinancialSettlementBankPaymentReconciliation::query()
            ->with(['statement', 'payment', 'bankTransactionEvidence'])
            ->where('owner_organization_id', $organizationId)
            ->whereHas('statement', function (Builder $statement) use ($filters): void {
                if (isset($filters['status'])) {
                    $statement->where('status', $filters['status']);
                }
                if (isset($filters['party_type'])) {
                    $statement->where('recipient_type', $filters['party_type']);
                }
                if (isset($filters['direction'])) {
                    $statement->where('output_direction', $filters['direction']);
                }
                if (isset($filters['period_from'])) {
                    $statement->where('period_until', '>=', $filters['period_from']);
                }
                if (isset($filters['period_until'])) {
                    $statement->where('period_from', '<=', $filters['period_until']);
                }
            })
            ->orderBy('id');

        foreach ($query->lazyById(200) as $reconciliation) {
            /** @var FinancialSettlementStatement $statement */
            $statement = $reconciliation->statement;
            /** @var FinancialSettlementBankPayment $payment */
            $payment = $reconciliation->payment;
            /** @var BankTransactionEvidence $evidence */
            $evidence = $reconciliation->bankTransactionEvidence;
            $recipient = $statement->recipient_type === 'driver'
                ? 'Řidič #'.(string) $statement->recipient_driver_id
                : 'Externí dopravce #'.(string) $statement->recipient_organization_id;
            $this->put($output, [
                (string) $statement->public_id,
                $recipient,
                $this->date((string) $statement->getRawOriginal('period_from')),
                $this->date((string) $statement->getRawOriginal('period_until')),
                $this->direction((string) $statement->output_direction),
                $this->money((int) $statement->net_balance_minor),
                (string) $statement->currency,
                (string) $payment->public_id,
                $this->money((int) $payment->allocated_amount_minor),
                $payment->status === 'active' ? 'Aktivní' : 'Stornovaná',
                (string) $evidence->source_reference,
                $this->dateTime((string) $evidence->getRawOriginal('booked_at')),
                $reconciliation->status === FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED ? 'Potvrzeno' : 'Znovu otevřeno',
                (string) $reconciliation->revision,
                (string) $reconciliation->reason,
                $this->dateTime($reconciliation->confirmed_at === null ? '' : (string) $reconciliation->getRawOriginal('confirmed_at')),
                $this->dateTime($reconciliation->reopened_at === null ? '' : (string) $reconciliation->getRawOriginal('reopened_at')),
            ]);
            $rowCount++;
        }

        return $rowCount;
    }

    /** @param resource $output @param list<string> $row */
    private function put(mixed $output, array $row): void
    {
        if (fputcsv($output, $row, ';', '"', '') === false) {
            throw new RuntimeException('Unable to write a CSV row.');
        }
    }

    private function money(int $minor): string
    {
        return number_format($minor / 100, 2, ',', '');
    }

    private function date(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        }

        return $date === false ? $value : $date->format('d.m.Y');
    }

    private function dateTime(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $date === false ? $value : $date->format('d.m.Y H:i:s');
    }

    private function direction(string $value): string
    {
        return $value === 'receivable' ? 'Pohledávka' : ($value === 'payable' ? 'Závazek' : $value);
    }
}
