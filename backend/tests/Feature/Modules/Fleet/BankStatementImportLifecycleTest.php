<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Fleet\Models\BankStatementImportDuplicateCandidate;
use App\Modules\Fleet\Models\BankStatementImportRow;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Services\BankStatementImportService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BankStatementImportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_configurable_csv_import_is_idempotent_duplicate_aware_and_non_matching(): void
    {
        $organization = Organization::query()->create([
            'name' => 'S068 master',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $registrar->forgetCachedPermissions();

        $firstFile = $this->csv("Booked;Value;Amount;Currency;Account;Counterparty;CounterpartyAccount;VS;Message;Reference;Statement\n2026-09-07;2026-09-08;1250,50;CZK;CZ-MASTER;Customer One;CZ-COUNTERPARTY;68001;Invoice 68001;BANK-68001;STATEMENT-09\n");
        $exactFile = $this->csv("Booked;Value;Amount;Currency;Account;Counterparty;CounterpartyAccount;VS;Message;Reference;Statement;Ignored\n2026-09-07;2026-09-08;1250,50;CZK;CZ-MASTER;Customer One;CZ-COUNTERPARTY;68001;Invoice 68001;BANK-68001;STATEMENT-09;different-file\n");
        $probableFile = $this->csv("Booked;Value;Amount;Currency;Account;Counterparty;CounterpartyAccount;VS;Message;Reference;Statement\n2026-09-07;2026-09-08;1250,50;CZK;CZ-MASTER;Customer One;CZ-COUNTERPARTY;68001;Changed message;BANK-68001-CORRECTED;STATEMENT-09\n");

        try {
            $service = app(BankStatementImportService::class);
            $input = $this->input((string) Str::uuid());
            $first = $service->import($input, (int) $organization->id, $actor, 'statement.csv', $firstFile);
            $again = $service->import($input, (int) $organization->id, $actor, 'statement.csv', $firstFile);

            self::assertSame($first->public_id, $again->public_id);
            self::assertSame('completed', $first->status);
            self::assertSame(1, $first->source_row_count);
            self::assertSame(1, $first->accepted_row_count);
            self::assertSame(0, $first->duplicate_candidate_row_count);
            self::assertSame(0, $first->rejected_row_count);

            try {
                $service->import($this->input((string) Str::uuid()), (int) $organization->id, $actor, 'same-file.csv', $firstFile);
                self::fail('A repeated file hash must be rejected.');
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('file', $exception->errors());
            }

            $exact = $service->import($this->input((string) Str::uuid()), (int) $organization->id, $actor, 'exact-candidate.csv', $exactFile);
            $probable = $service->import($this->input((string) Str::uuid()), (int) $organization->id, $actor, 'probable-candidate.csv', $probableFile);

            self::assertSame('completed_with_review', $exact->status);
            self::assertSame(1, $exact->duplicate_candidate_row_count);
            self::assertSame('completed_with_review', $probable->status);
            self::assertSame(1, $probable->duplicate_candidate_row_count);
        } finally {
            @unlink($firstFile);
            @unlink($exactFile);
            @unlink($probableFile);
        }

        self::assertDatabaseCount('bank_statement_import_batches', 3);
        self::assertDatabaseCount('bank_statement_import_rows', 3);
        self::assertDatabaseCount('bank_transaction_evidence', 1);
        self::assertDatabaseCount('bank_transaction_evidence_events', 1);
        self::assertDatabaseCount('bank_statement_import_duplicate_candidates', 2);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);

        $accepted = BankStatementImportRow::query()->where('status', 'accepted')->sole();
        self::assertSame('1250.50', $accepted->normalized_payload['amount']);
        self::assertSame('2026-09-07', $accepted->normalized_payload['booked_at']);
        self::assertSame('1250,50', $accepted->raw_payload['Amount']);
        self::assertNotNull($accepted->bank_transaction_evidence_id);

        $evidence = BankTransactionEvidence::query()->sole();
        self::assertSame('bank_import', $evidence->source_type);
        self::assertSame('credit', $evidence->direction);
        self::assertSame('1250.50', $evidence->amount);
        self::assertSame('CZK', $evidence->currency);

        $methods = BankStatementImportDuplicateCandidate::query()->orderBy('id')->pluck('comparison_method')->all();
        self::assertSame(['exact_fingerprint', 'probable_core_fields'], $methods);
        self::assertSame(3, BankStatementImportBatch::query()->where('organization_context_id', $organization->id)->count());
    }

    private function input(string $idempotencyKey): array
    {
        return [
            'idempotency_key' => $idempotencyKey,
            'delimiter' => ';',
            'encoding' => 'UTF-8',
            'mapping_version' => 's068-test-v1',
            'date_format' => 'Y-m-d',
            'decimal_separator' => ',',
            'default_account_identifier' => null,
            'default_currency' => null,
            'mapping' => [
                'booked_at' => 'Booked',
                'value_date' => 'Value',
                'amount' => 'Amount',
                'currency' => 'Currency',
                'direction' => null,
                'source_reference' => 'Reference',
                'bank_statement_reference' => 'Statement',
                'account_identifier' => 'Account',
                'counterparty_name' => 'Counterparty',
                'counterparty_account_identifier' => 'CounterpartyAccount',
                'variable_symbol' => 'VS',
                'message' => 'Message',
            ],
        ];
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 's068-bank-');
        self::assertIsString($path);
        self::assertNotFalse(file_put_contents($path, $contents));

        return $path;
    }
}
