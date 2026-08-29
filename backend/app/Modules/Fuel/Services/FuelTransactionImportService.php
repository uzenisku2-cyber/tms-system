<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\DailyReports\Services\DepotWorkbookReader;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelImportRow;
use App\Modules\Fuel\Models\FuelTransaction;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class FuelTransactionImportService
{
    public function __construct(private readonly DepotWorkbookReader $workbooks) {}

    public function visibleBatches(int $organizationId): Collection
    {
        return FuelImportBatch::query()->where('owner_organization_id', $organizationId)->latest()->get();
    }

    public function visibleBatch(FuelImportBatch $batch, int $organizationId): FuelImportBatch
    {
        if ((int) $batch->owner_organization_id !== $organizationId) {
            abort(404);
        }

        return $batch->load(['rows' => static fn ($query) => $query->orderBy('source_row')]);
    }

    public function import(int $organizationId, User $actor, string $provider, string $filename, string $path): FuelImportBatch
    {
        $preview = $this->preview($provider, $path);
        $duplicate = FuelImportBatch::query()->where('owner_organization_id', $organizationId)->where('provider', $provider)->where('file_sha256', $preview['file_sha256'])->first();
        if ($duplicate instanceof FuelImportBatch) {
            throw ValidationException::withMessages(['file' => ["Tento soubor již byl importován v dávce {$duplicate->public_id}."]]);
        }

        return DB::transaction(function () use ($organizationId, $actor, $provider, $filename, $preview): FuelImportBatch {
            $batch = FuelImportBatch::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'provider' => $provider, 'status' => 'processing', 'original_filename' => mb_substr(basename($filename), 0, 255), 'file_sha256' => $preview['file_sha256'], 'schema_fingerprint' => $preview['schema_fingerprint'], 'source_row_count' => count($preview['rows']), 'imported_by_user_id' => (int) $actor->getAuthIdentifier()]);
            $counts = ['accepted' => 0, 'duplicate' => 0, 'review' => 0, 'rejected' => 0];
            $dates = [];

            foreach ($preview['rows'] as $row) {
                $normalized = $row['normalized'];
                $dates[] = substr((string) $normalized['occurred_at'], 0, 10);
                $existing = FuelTransaction::query()->where('owner_organization_id', $organizationId)->where('provider', $provider)->where('transaction_fingerprint', $row['fingerprint'])->first();
                $status = 'accepted';
                $messages = $row['messages'];
                $transaction = null;

                if ($existing instanceof FuelTransaction) {
                    $status = 'duplicate';
                } elseif ($messages !== []) {
                    $status = 'rejected';
                } else {
                    $match = $this->match($organizationId, $provider, $normalized['provider_card_identifier'], $normalized['occurred_at']);
                    $status = $match['status'] === 'matched' ? 'accepted' : 'review';
                    $transaction = FuelTransaction::query()->create([...$normalized, ...$match, 'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'provider' => $provider, 'transaction_fingerprint' => $row['fingerprint'], 'fuel_import_batch_id' => $batch->getKey(), 'source_row' => $row['source_row']]);
                }

                FuelImportRow::query()->create(['fuel_import_batch_id' => $batch->getKey(), 'source_row' => $row['source_row'], 'status' => $status, 'row_fingerprint' => $row['fingerprint'], 'provider_transaction_identifier' => $normalized['provider_transaction_identifier'], 'raw_payload' => $row['raw'], 'normalized_payload' => $normalized, 'validation_messages' => $messages === [] ? null : $messages, 'fuel_transaction_id' => $transaction?->getKey(), 'duplicate_fuel_transaction_id' => $existing?->getKey()]);
                $counts[$status]++;
            }

            $batch->forceFill(['status' => $counts['review'] > 0 || $counts['rejected'] > 0 ? 'completed_with_review' : 'completed', 'period_start' => $dates === [] ? null : min($dates), 'period_end' => $dates === [] ? null : max($dates), 'accepted_row_count' => $counts['accepted'], 'duplicate_row_count' => $counts['duplicate'], 'review_row_count' => $counts['review'], 'rejected_row_count' => $counts['rejected'], 'completed_at' => now()])->save();

            return $batch->refresh()->load('rows');
        });
    }

    /** @return array{file_sha256:string,schema_fingerprint:string,rows:list<array{source_row:int,raw:array<string,mixed>,normalized:array<string,mixed>,messages:list<string>,fingerprint:string}>} */
    public function preview(string $provider, string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['file' => ['Zdrojový soubor nelze bezpečně přečíst.']]);
        }
        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw new RuntimeException('Fuel import file hash could not be calculated.');
        }

        $parsed = match ($provider) {
            'ORLEN' => $this->orlen($path),
            'MOL' => $this->mol($path),
            default => throw ValidationException::withMessages(['provider' => ['Nepodporovaný poskytovatel palivových dat.']]),
        };

        return ['file_sha256' => strtoupper($hash), 'schema_fingerprint' => strtoupper(hash('sha256', json_encode($parsed['headers'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))), 'rows' => $parsed['rows']];
    }

    /** @return array{headers:list<string>,rows:list<array{source_row:int,raw:array<string,mixed>,normalized:array<string,mixed>,messages:list<string>,fingerprint:string}>} */
    private function orlen(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => ['Soubor ORLEN nelze otevřít.']]);
        }
        try {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $headers = fgetcsv($handle, 0, ';', '"', '\\');
            if (! is_array($headers)) {
                throw ValidationException::withMessages(['file' => ['Soubor ORLEN neobsahuje hlavičku.']]);
            }
            $headers = array_map(fn ($value): string => $this->clean((string) $value), $headers);
            $headers[0] = preg_replace('/^\x{FEFF}/u', '', $headers[0]) ?? $headers[0];
            $required = ['Číslo účtenky', 'Datum a čas prodeje', 'Číslo karty', 'Množství', 'Jednotková cena po slevě', 'Celková cena po slevě', 'DPH', 'Celková cena (bez DPH)', 'Měna', 'Čerpací stanice', 'Produkt'];
            $this->headers($headers, $required, 'ORLEN');
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
                $line++;
                if (count($values) !== count($headers)) {
                    continue;
                }
                $raw = array_combine($headers, array_map(fn ($value): string => $this->clean((string) $value), $values));
                $grossAmount = $this->decimal(
                    $raw['Celková cena po slevě'],
                    true,
                );
                $taxRate = $this->decimal($raw['Sazba DPH']);
                [$netAmount, $taxAmount] = $this->splitGrossAmount(
                    $grossAmount,
                    $taxRate,
                );
                $normalized = ['provider_transaction_identifier' => $raw['Číslo účtenky'], 'occurred_at' => $this->date($raw['Datum a čas prodeje'], 'j.n.Y H:i:s'), 'posting_date' => null, 'provider_card_identifier' => $this->identifier($raw['Číslo karty']), 'station_identifier' => trim(explode(' - ', $raw['Čerpací stanice'], 2)[0]), 'station_name' => $raw['Čerpací stanice'], 'station_address' => $raw['Adresa čerpací stanice'] ?: null, 'product_code' => trim(explode(' ', $raw['Produkt'], 2)[0]), 'product_name' => $raw['Produkt'], 'quantity' => $this->decimal($raw['Množství'], true), 'unit_of_measure' => 'L', 'unit_price' => $this->decimal($raw['Jednotková cena po slevě']), 'net_amount' => $netAmount, 'tax_amount' => $taxAmount, 'gross_amount' => $grossAmount, 'discount_amount' => $this->decimal($raw['Sleva']), 'tax_rate' => $taxRate, 'currency' => strtoupper($raw['Měna']), 'vehicle_registration' => $raw['RZ'] ?: null, 'odometer' => $this->optionalDecimal($raw['Stav tachometru']), 'invoice_reference' => $raw['Faktura číslo'] ?: null, 'source_description' => $raw['Typ transakce'] ?: null];
                $rows[] = $this->row($line, $raw, $normalized);
            }

            return ['headers' => array_values($headers), 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    /** @return array{headers:list<string>,rows:list<array{source_row:int,raw:array<string,mixed>,normalized:array<string,mixed>,messages:list<string>,fingerprint:string}>} */
    private function mol(string $path): array
    {
        $workbook = $this->workbooks->read($path);
        $sheet = null;
        foreach ($workbook['sheets'] as $candidate) {
            if (mb_strtolower(trim($candidate['name']), 'UTF-8') === 'podrobný report o transakcích') {
                $sheet = $candidate;
                break;
            }
        }
        if (! is_array($sheet) || $sheet['rows'] === []) {
            throw ValidationException::withMessages(['file' => ['Datový list MOL nebyl nalezen.']]);
        }
        $headerRow = $sheet['rows'][0];
        $headers = [];
        foreach ($headerRow['cells'] as $column => $cell) {
            $headers[$column] = $this->clean((string) ($cell['value'] ?? ''));
        }
        $headers[21] = ($headers[21] ?? '') === '' ? 'Jednotka množství' : $headers[21];
        $required = ['Datum transakce', 'Číslo karty', 'Kód produktu', 'Produkt', 'Čerpací stanice', 'Množství', 'Jednotka množství', 'Měna', 'Částka', 'Fakturovaná částka', 'Číslo stvrzenky'];
        $this->headers(array_values($headers), $required, 'MOL');
        $rows = [];
        foreach (array_slice($sheet['rows'], 1) as $source) {
            $raw = [];
            foreach ($headers as $column => $header) {
                $raw[$header] = $this->clean((string) ($source['cells'][$column]['value'] ?? ''));
            }
            $normalized = ['provider_transaction_identifier' => $raw['Číslo stvrzenky'] ?: null, 'occurred_at' => $this->excelDate($raw['Datum transakce']), 'posting_date' => null, 'provider_card_identifier' => $this->identifier($raw['Číslo karty']), 'station_identifier' => $raw['Čerpací stanice'] ?: null, 'station_name' => $raw['Název čerpací stanice'] ?: null, 'station_address' => null, 'product_code' => $raw['Kód produktu'] ?: null, 'product_name' => $raw['Produkt'], 'quantity' => $this->decimal($raw['Množství'], true), 'unit_of_measure' => strtoupper($raw['Jednotka množství']), 'unit_price' => $this->optionalDecimal($raw['Použitá jednotková cena']), 'net_amount' => null, 'tax_amount' => null, 'gross_amount' => $this->decimal($raw['Fakturovaná částka'], true), 'discount_amount' => $this->optionalDecimal($raw['Sleva/Jednotka']), 'tax_rate' => $this->optionalDecimal($raw['DPH %']), 'currency' => strtoupper($raw['Měna']), 'vehicle_registration' => $raw['Registrační značka'] ?: null, 'odometer' => $this->optionalDecimal($raw['Stav KM']), 'invoice_reference' => ltrim($raw['Č. faktury']) ?: null, 'source_description' => $raw['Cenotvorba'] ?: null];
            $rows[] = $this->row((int) $source['row'], $raw, $normalized);
        }

        return ['headers' => array_values($headers), 'rows' => $rows];
    }

    /** @param array<string,mixed> $raw @param array<string,mixed> $normalized */
    private function row(int $sourceRow, array $raw, array $normalized): array
    {
        $messages = [];
        foreach (['occurred_at', 'provider_card_identifier', 'product_name', 'quantity', 'gross_amount', 'currency'] as $field) {
            if (($normalized[$field] ?? null) === null || $normalized[$field] === '') {
                $messages[] = "Chybí povinná normalizovaná hodnota {$field}.";
            }
        }
        if (($normalized['unit_of_measure'] ?? '') !== 'L') {
            $messages[] = 'Podporovaná jednotka kapalného paliva je L.';
        }
        $fingerprint = strtoupper(hash('sha256', json_encode(['provider_transaction_identifier' => $normalized['provider_transaction_identifier'], 'occurred_at' => $normalized['occurred_at'], 'provider_card_identifier' => $normalized['provider_card_identifier'], 'station_identifier' => $normalized['station_identifier'], 'product_code' => $normalized['product_code'], 'quantity' => $normalized['quantity'], 'gross_amount' => $normalized['gross_amount'], 'currency' => $normalized['currency']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));

        return ['source_row' => $sourceRow, 'raw' => $raw, 'normalized' => $normalized, 'messages' => $messages, 'fingerprint' => $fingerprint];
    }

    /** @return array<string,mixed> */
    private function match(int $organizationId, string $provider, string $cardIdentifier, string $occurredAt): array
    {
        $card = FuelCard::query()->where('owner_organization_id', $organizationId)->where('provider', $provider)->where('provider_card_identifier', $cardIdentifier)->first();
        if (! $card instanceof FuelCard) {
            return ['fuel_card_id' => null, 'fuel_card_assignment_id' => null, 'responsible_organization_id' => null, 'driver_id' => null, 'vehicle_id' => null, 'match_status' => 'review', 'match_method' => 'unknown_card'];
        }
        $assignments = FuelCardAssignment::query()->where('fuel_card_id', $card->getKey())->where('valid_from', '<=', $occurredAt)->where(static function ($query) use ($occurredAt): void {
            $query->whereNull('valid_until')->orWhere('valid_until', '>=', $occurredAt);
        })->get();
        if ($assignments->count() !== 1) {
            return ['fuel_card_id' => $card->getKey(), 'fuel_card_assignment_id' => null, 'responsible_organization_id' => null, 'driver_id' => null, 'vehicle_id' => null, 'match_status' => 'review', 'match_method' => $assignments->isEmpty() ? 'no_valid_assignment' : 'conflicting_assignments'];
        }
        $assignment = $assignments->first();
        if (! $assignment instanceof FuelCardAssignment) {
            throw new RuntimeException('The effective fuel-card assignment could not be resolved.');
        }

        return ['fuel_card_id' => $card->getKey(), 'fuel_card_assignment_id' => $assignment->getKey(), 'responsible_organization_id' => $assignment->responsible_organization_id, 'driver_id' => $assignment->driver_id, 'vehicle_id' => $assignment->vehicle_id, 'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period'];
    }

    /** @param list<string> $headers @param list<string> $required */
    private function headers(array $headers, array $required, string $provider): void
    {
        $missing = array_values(array_diff($required, $headers));
        if ($missing !== []) {
            throw ValidationException::withMessages(['file' => ["Soubor {$provider} nemá povinné sloupce: ".implode(', ', $missing).'.']]);
        }
    }

    private function clean(string $value): string
    {
        return trim(str_replace("\u{00A0}", ' ', $value));
    }

    private function identifier(string $value): string
    {
        return preg_replace('/[\s\x{00A0}]+/u', '', $value) ?? '';
    }

    private function decimal(string $value, bool $positive = false): ?string
    {
        $value = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $value);
        if ($value === '' || ! is_numeric($value) || ($positive && (float) $value <= 0)) {
            return null;
        }

        return $value;
    }

    private function optionalDecimal(string $value): ?string
    {
        return $this->decimal($value);
    }

    /** @return array{0:?string,1:?string} */
    private function splitGrossAmount(
        ?string $grossAmount,
        ?string $taxRate,
    ): array {
        if ($grossAmount === null || $taxRate === null) {
            return [null, null];
        }

        $divisor = bcadd(
            '1',
            bcdiv($taxRate, '100', 12),
            12,
        );

        if (bccomp($divisor, '0', 12) <= 0) {
            return [null, null];
        }

        $netAmount = bcdiv($grossAmount, $divisor, 6);
        $taxAmount = bcsub($grossAmount, $netAmount, 6);

        return [$netAmount, $taxAmount];
    }

    private function date(string $value, string $format): ?string
    {
        $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

        return $date === false ? null : $date->format('Y-m-d H:i:s');
    }

    private function excelDate(string $value): ?string
    {
        if (! is_numeric($value)) {
            return $this->date($value, 'Y-m-d H:i:s');
        }
        $serial = (float) $value;
        $days = (int) floor($serial);
        $seconds = (int) round(($serial - $days) * 86400);

        return (new DateTimeImmutable('1899-12-30 00:00:00'))->modify("+{$days} days")->modify("+{$seconds} seconds")->format('Y-m-d H:i:s');
    }
}
