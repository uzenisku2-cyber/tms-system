<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Fleet\Models\BankStatementImportDuplicateCandidate;
use App\Modules\Fleet\Models\BankStatementImportRow;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

final class BankStatementImportService
{
    private const PARSER_VERSION = 'configurable-csv-v1';

    public function __construct(private readonly BankTransactionEvidenceService $evidence) {}

    public function visibleBatches(int $organizationId, User $actor): Collection
    {
        $this->authorize($actor);

        return BankStatementImportBatch::query()->where('organization_context_id', $organizationId)->latest()->get();
    }

    public function visibleBatch(BankStatementImportBatch $batch, int $organizationId, User $actor): BankStatementImportBatch
    {
        $this->authorize($actor);
        if ((int) $batch->organization_context_id !== $organizationId) {
            abort(404);
        }

        return $batch->load(['rows' => static fn ($query) => $query->orderBy('source_row'), 'rows.duplicateCandidates']);
    }

    public function import(array $data, int $organizationId, User $actor, string $originalFilename, string $path): BankStatementImportBatch
    {
        $this->authorize($actor);
        $fileSha256 = hash_file('sha256', $path);
        if (! is_string($fileSha256)) {
            throw new RuntimeException('Bank statement file hashing failed.');
        }

        $byKey = BankStatementImportBatch::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
        if ($byKey instanceof BankStatementImportBatch) {
            return $this->visibleBatch($byKey, $organizationId, $actor);
        }
        if (BankStatementImportBatch::query()->where('organization_context_id', $organizationId)->where('file_sha256', $fileSha256)->exists()) {
            throw ValidationException::withMessages(['file' => ['This bank statement file was already imported in the organization context.']]);
        }

        $records = $this->records($path, (string) $data['encoding'], (string) $data['delimiter']);

        return DB::transaction(function () use ($data, $organizationId, $actor, $originalFilename, $fileSha256, $records): BankStatementImportBatch {
            $batch = BankStatementImportBatch::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_context_id' => $organizationId,
                'idempotency_key' => $data['idempotency_key'],
                'status' => 'processing',
                'original_filename' => $originalFilename,
                'file_sha256' => $fileSha256,
                'source_type' => 'configurable_csv',
                'parser_version' => self::PARSER_VERSION,
                'mapping_version' => $data['mapping_version'],
                'delimiter' => $data['delimiter'],
                'encoding' => $data['encoding'],
                'mapping' => $data['mapping'],
                'imported_by_user_id' => $actor->id,
            ]);

            $counts = ['accepted' => 0, 'duplicate_candidate' => 0, 'rejected' => 0];
            foreach ($records as $index => $raw) {
                [$normalized, $messages] = $this->normalize($raw, $data);
                $rowFingerprint = hash('sha256', $this->json($raw));
                if ($messages !== []) {
                    BankStatementImportRow::query()->create([
                        'bank_statement_import_batch_id' => $batch->id, 'source_row' => $index + 2, 'status' => 'rejected',
                        'row_fingerprint' => $rowFingerprint, 'raw_payload' => $raw, 'validation_messages' => $messages,
                    ]);
                    $counts['rejected']++;

                    continue;
                }

                $transactionFingerprint = hash('sha256', $this->json($normalized));
                $sourceReference = 'bank-import:'.$transactionFingerprint;
                $candidate = BankTransactionEvidence::query()
                    ->where('organization_context_id', $organizationId)
                    ->where('source_type', 'bank_import')
                    ->where('source_reference', $sourceReference)
                    ->first();

                if (! $candidate instanceof BankTransactionEvidence) {
                    $candidate = BankTransactionEvidence::query()
                        ->where('organization_context_id', $organizationId)
                        ->where('source_type', 'bank_import')
                        ->get()
                        ->first(static fn (BankTransactionEvidence $existing): bool => substr((string) $existing->getRawOriginal('booked_at'), 0, 10) === $normalized['booked_at']
                            && (string) $existing->direction === $normalized['direction']
                            && (string) $existing->amount === $normalized['amount']
                            && (string) $existing->currency === $normalized['currency']
                            && (string) ($existing->counterparty_account_identifier ?? '') === (string) ($normalized['counterparty_account_identifier'] ?? ''));
                }

                if ($candidate instanceof BankTransactionEvidence) {
                    $method = $candidate->source_reference === $sourceReference ? 'exact_fingerprint' : 'probable_core_fields';
                    $row = BankStatementImportRow::query()->create([
                        'bank_statement_import_batch_id' => $batch->id, 'source_row' => $index + 2, 'status' => 'duplicate_candidate',
                        'row_fingerprint' => $rowFingerprint, 'transaction_fingerprint' => $transactionFingerprint,
                        'raw_payload' => $raw, 'normalized_payload' => $normalized, 'validation_messages' => [],
                    ]);
                    BankStatementImportDuplicateCandidate::query()->create([
                        'public_id' => (string) Str::uuid(), 'bank_statement_import_row_id' => $row->id,
                        'candidate_bank_transaction_evidence_id' => $candidate->id, 'comparison_method' => $method,
                        'matching_fields' => $method === 'exact_fingerprint' ? ['transaction_fingerprint'] : ['booked_at', 'direction', 'amount', 'currency', 'counterparty_account_identifier'],
                        'confidence' => $method === 'exact_fingerprint' ? 1 : 0.8, 'decision' => 'unresolved', 'detected_at' => now(),
                    ]);
                    $counts['duplicate_candidate']++;

                    continue;
                }

                $payload = $normalized + [
                    'idempotency_key' => (string) Str::uuid(), 'source_type' => 'bank_import', 'source_reference' => $sourceReference,
                    'evidence_note' => 'Normalized from bank statement import batch '.$batch->public_id.' source row '.($index + 2).'.',
                ];
                $result = $this->evidence->record($payload, $organizationId, $actor);
                $evidence = BankTransactionEvidence::query()->where('public_id', $result['bank_transaction_evidence_public_id'])->firstOrFail();
                BankStatementImportRow::query()->create([
                    'bank_statement_import_batch_id' => $batch->id, 'source_row' => $index + 2, 'status' => 'accepted',
                    'row_fingerprint' => $rowFingerprint, 'transaction_fingerprint' => $transactionFingerprint,
                    'raw_payload' => $raw, 'normalized_payload' => $normalized, 'validation_messages' => [], 'bank_transaction_evidence_id' => $evidence->id,
                ]);
                $counts['accepted']++;
            }

            $batch->forceFill([
                'status' => $counts['rejected'] > 0 || $counts['duplicate_candidate'] > 0 ? 'completed_with_review' : 'completed',
                'source_row_count' => count($records), 'accepted_row_count' => $counts['accepted'],
                'duplicate_candidate_row_count' => $counts['duplicate_candidate'], 'rejected_row_count' => $counts['rejected'], 'completed_at' => now(),
            ])->save();

            return $this->visibleBatch($batch->refresh(), $organizationId, $actor);
        });
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->can('compensation.manage'), 403);
    }

    private function records(string $path, string $encoding, string $delimiter): array
    {
        $bytes = file_get_contents($path);
        if (! is_string($bytes)) {
            throw new RuntimeException('Bank statement file reading failed.');
        }
        $utf8 = $encoding === 'UTF-8' ? $bytes : mb_convert_encoding($bytes, 'UTF-8', $encoding);
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Temporary CSV stream creation failed.');
        }
        fwrite($stream, $utf8);
        rewind($stream);
        $header = fgetcsv($stream, null, $delimiter);
        if ($header === false) {
            throw ValidationException::withMessages(['file' => ['The CSV header is missing.']]);
        }
        $header = array_map(static fn ($value): string => trim((string) $value), $header);
        if (count($header) !== count(array_unique($header))) {
            throw ValidationException::withMessages(['file' => ['CSV headers must be unique.']]);
        }
        $records = [];
        while (($values = fgetcsv($stream, null, $delimiter)) !== false) {
            if ($values === [null]) {
                continue;
            }
            if (count($values) !== count($header)) {
                throw ValidationException::withMessages(['file' => ['A CSV row has a different field count than the header.']]);
            }
            $record = array_combine($header, array_map(static fn ($value): string => trim((string) $value), $values));

            $records[] = $record;
        }
        fclose($stream);

        return $records;
    }

    private function normalize(array $raw, array $data): array
    {
        $mapping = $data['mapping'];
        $value = static fn (string $field): ?string => isset($mapping[$field], $raw[$mapping[$field]]) && $raw[$mapping[$field]] !== '' ? trim((string) $raw[$mapping[$field]]) : null;
        $messages = [];
        $booked = $this->date($value('booked_at'), (string) $data['date_format']);
        if ($booked === null) {
            $messages[] = 'booked_at is missing or invalid.';
        }
        $valueDateRaw = $value('value_date');
        $valueDate = $valueDateRaw === null ? null : $this->date($valueDateRaw, (string) $data['date_format']);
        if ($valueDateRaw !== null && $valueDate === null) {
            $messages[] = 'value_date is invalid.';
        }
        $amountRaw = $value('amount');
        $numeric = $amountRaw === null ? null : str_replace(["\u{00A0}", ' '], '', $amountRaw);
        if ($numeric !== null && $data['decimal_separator'] === ',') {
            $numeric = str_replace('.', '', $numeric);
            $numeric = str_replace(',', '.', $numeric);
        }
        if ($numeric === null || ! is_numeric($numeric) || (float) $numeric === 0.0) {
            $messages[] = 'amount is missing, invalid or zero.';
        }
        $directionRaw = strtolower((string) ($value('direction') ?? ''));
        $direction = in_array($directionRaw, ['credit', 'debit'], true) ? $directionRaw : ((float) ($numeric ?? 0) < 0 ? 'debit' : 'credit');
        $currency = strtoupper((string) ($value('currency') ?? ($data['default_currency'] ?? '')));
        if (strlen($currency) !== 3) {
            $messages[] = 'currency must contain three letters.';
        }

        return [[
            'bank_statement_reference' => $value('bank_statement_reference'), 'direction' => $direction,
            'booked_at' => $booked, 'value_date' => $valueDate, 'amount' => $numeric === null ? null : number_format(abs((float) $numeric), 2, '.', ''),
            'currency' => $currency, 'account_identifier' => $value('account_identifier') ?? ($data['default_account_identifier'] ?? null),
            'counterparty_name' => $value('counterparty_name'), 'counterparty_account_identifier' => $value('counterparty_account_identifier'),
            'variable_symbol' => $value('variable_symbol'), 'message' => $value('message'),
            'original_source_reference' => $value('source_reference'),
        ], $messages];
    }

    private function date(?string $value, string $format): ?string
    {
        if ($value === null) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

        return $date instanceof DateTimeImmutable && $date->format($format) === $value ? $date->format('Y-m-d') : null;
    }

    /** @throws JsonException */
    private function json(array $value): string
    {
        ksort($value);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
