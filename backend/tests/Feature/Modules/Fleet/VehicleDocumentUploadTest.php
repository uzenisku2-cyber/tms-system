<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Organization $otherOrganization;

    private User $admin;

    private string $vehicleId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->organization = $this->organization();
        $this->otherOrganization = $this->organization();
        $this->admin = User::factory()->create();
        $this->member($this->organization, $this->admin);
        $this->member($this->otherOrganization, $this->admin);
        $this->seed(RolePermissionSeeder::class);

        foreach ([$this->organization, $this->otherOrganization] as $organization) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $organization->id);
            $this->admin->assignRole('super-admin');
            $this->admin->unsetRelation('roles');
        }
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $this->admin->unsetRelation('roles');
        $this->admin->unsetRelation('permissions');
        Sanctum::actingAs($this->admin);

        $this->vehicleId = $this->withHeaders($this->headers())
            ->postJson('/api/v1/vehicle-registry-administration', [
                'registration_number' => 'UPLOAD-'.Str::random(8),
                'reason' => 'Upload test vehicle.',
            ])->assertCreated()->json('vehicle.public_id');
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_upload_is_private_revisioned_and_downloadable(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $this->payload())
            ->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)
            ->assertJsonPath('document.verification_status', 'unverified');

        $reference = $response->json('document.storage_reference');
        self::assertStringStartsWith('managed-vehicle-document:vehicle-documents/', $reference);
        $path = substr($reference, strlen('managed-vehicle-document:'));
        Storage::disk('local')->assertExists($path);
        self::assertSame($this->pdfContent(), Storage::disk('local')->get($path));

        $this->withHeaders($this->headers())
            ->get($this->downloadUrl($response->json('document.public_id')))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent($this->pdfContent());

        $this->assertDatabaseHas('vehicle_registry_events', [
            'event_type' => 'vehicle_document_evidence_registered',
            'vehicle_revision' => 2,
            'organization_context_id' => $this->organization->id,
        ]);
    }

    public function test_stale_revision_removes_uploaded_file_and_keeps_database_unchanged(): void
    {
        $payload = $this->payload();
        $payload['expected_revision'] = 99;
        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)->assertConflict();

        self::assertSame([], Storage::disk('local')->allFiles());
        self::assertSame(0, VehicleDocument::query()->count());
        self::assertSame(1, (int) Vehicle::query()
            ->where('public_id', $this->vehicleId)->value('current_revision'));
    }

    public function test_invalid_uploads_and_forged_storage_references_are_rejected(): void
    {
        $payload = $this->payload();
        $payload['file'] = UploadedFile::fake()->createWithContent('code.php', '<?php echo 1;');
        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)->assertUnprocessable();

        $payload = $this->payload();
        $payload['file'] = UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf');
        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)->assertUnprocessable();

        $payload = $this->payload();
        $payload['storage_reference'] = 'external-reference';
        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)->assertUnprocessable();

        $payload = $this->payload();
        unset($payload['file']);
        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)->assertUnprocessable();

        $payload['storage_reference'] = 'managed-vehicle-document:vehicle-documents/forged.pdf';
        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)->assertUnprocessable();

        self::assertSame([], Storage::disk('local')->allFiles());
        self::assertSame(0, VehicleDocument::query()->count());
    }

    public function test_document_cannot_be_uploaded_or_downloaded_in_another_organization(): void
    {
        $documentId = $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $this->payload())
            ->assertCreated()->json('document.public_id');

        $otherHeaders = ['X-Organization-ID' => (string) $this->otherOrganization->id];
        $this->withHeaders($otherHeaders)
            ->get($this->downloadUrl($documentId))->assertNotFound();

        $payload = $this->payload();
        $payload['expected_revision'] = 2;
        $this->withHeaders($otherHeaders)
            ->postJson($this->documentsUrl(), $payload)->assertNotFound();

        self::assertCount(1, Storage::disk('local')->allFiles());
        self::assertSame(1, VehicleDocument::query()->count());
    }

    public function test_document_cannot_be_downloaded_through_another_vehicle(): void
    {
        $documentId = $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $this->payload())
            ->assertCreated()->json('document.public_id');

        $otherVehicleId = $this->withHeaders($this->headers())
            ->postJson('/api/v1/vehicle-registry-administration', [
                'registration_number' => 'SECOND-'.Str::random(8),
                'reason' => 'Second upload test vehicle.',
            ])->assertCreated()->json('vehicle.public_id');

        $this->withHeaders($this->headers())
            ->get('/api/v1/vehicle-registry-administration/'.$otherVehicleId
                .'/documents/'.$documentId.'/download')
            ->assertNotFound();
    }

    public function test_viewer_can_download_operational_document_but_cannot_upload_or_read_private_document(): void
    {
        $documentId = $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $this->payload())
            ->assertCreated()->json('document.public_id');

        $viewer = User::factory()->create();
        $this->member($this->organization, $viewer);
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $this->organization->id);
        $viewer->givePermissionTo('vehicle.view');
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $viewer->unsetRelation('permissions');
        Sanctum::actingAs($viewer);

        $this->withHeaders($this->headers())
            ->get($this->downloadUrl($documentId))->assertOk();

        $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $this->payload())->assertForbidden();

        VehicleDocument::query()->where('public_id', $documentId)
            ->update(['access_classification' => 'private']);

        $this->withHeaders($this->headers())
            ->get($this->downloadUrl($documentId))->assertForbidden();

        self::assertCount(1, Storage::disk('local')->allFiles());
        self::assertSame(1, VehicleDocument::query()->count());
    }

    public function test_existing_external_reference_remains_supported_without_local_file_access(): void
    {
        $payload = $this->payload();
        unset($payload['file']);
        $payload['storage_reference'] = 'https://example.test/document.pdf';

        $documentId = $this->withHeaders($this->headers())
            ->postJson($this->documentsUrl(), $payload)
            ->assertCreated()->json('document.public_id');

        $this->withHeaders($this->headers())
            ->get($this->downloadUrl($documentId))->assertNotFound();

        self::assertSame([], Storage::disk('local')->allFiles());
    }

    private function organization(): Organization
    {
        return Organization::query()->create([
            'name' => 'Upload test '.Str::uuid(),
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function member(Organization $organization, User $user): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);
    }

    private function headers(): array
    {
        return ['X-Organization-ID' => (string) $this->organization->id];
    }

    private function documentsUrl(): string
    {
        return '/api/v1/vehicle-registry-administration/'.$this->vehicleId.'/documents';
    }

    private function downloadUrl(string $documentId): string
    {
        return $this->documentsUrl().'/'.$documentId.'/download';
    }

    private function pdfContent(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
    }

    private function payload(): array
    {
        return [
            'expected_revision' => 1,
            'document_type' => 'registration_certificate',
            'title' => 'Test registration document',
            'access_classification' => 'operational',
            'reason' => 'Document upload regression test.',
            'file' => UploadedFile::fake()->createWithContent('registration.pdf', $this->pdfContent()),
        ];
    }
}
