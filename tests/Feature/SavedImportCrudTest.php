<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SavedImport;
use App\Models\User;
use Tests\TestCase;

class SavedImportCrudTest extends TestCase
{
    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_index_lists_saved_imports_newest_first(): void
    {
        $this->actingUser();

        $older = SavedImport::create(['company' => 'cfocn', 'match_count' => 1, 'not_match_count' => 1, 'bill_count' => 1]);
        $newer = SavedImport::create(['company' => 'cfocn', 'match_count' => 2, 'not_match_count' => 2, 'bill_count' => 2]);

        $response = $this->getJson('/api/saved-imports');

        $response->assertOk();
        $this->assertSame($newer->id, $response->json('data.0.id'));
        $this->assertSame($older->id, $response->json('data.1.id'));
    }

    public function test_show_returns_counts(): void
    {
        $this->actingUser();

        $import = SavedImport::create([
            'company' => 'cfocn',
            'status' => 'active',
            'match_count' => 2947,
            'not_match_count' => 924,
            'bill_count' => 1303,
        ]);

        $response = $this->getJson("/api/saved-imports/{$import->id}");

        $response->assertOk()->assertJson([
            'id' => $import->id,
            'counts' => ['match' => 2947, 'not_match' => 924, 'bill' => 1303],
        ]);
    }

    public function test_show_missing_import_returns_404(): void
    {
        $this->actingUser();

        $this->getJson('/api/saved-imports/999999')->assertNotFound();
    }

    public function test_update_changes_label_and_writes_audit_log(): void
    {
        $user = $this->actingUser();
        $import = SavedImport::create(['company' => 'cfocn', 'match_count' => 1, 'not_match_count' => 1, 'bill_count' => 1]);

        $response = $this->patchJson("/api/saved-imports/{$import->id}", ['label' => 'October run']);

        $response->assertOk()->assertJsonPath('label', 'October run');
        $this->assertSame('October run', $import->refresh()->label);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'model' => 'SavedImport',
            'model_id' => $import->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_update_rejects_label_over_255_characters(): void
    {
        $this->actingUser();
        $import = SavedImport::create(['company' => 'cfocn', 'match_count' => 1, 'not_match_count' => 1, 'bill_count' => 1]);

        $response = $this->patchJson("/api/saved-imports/{$import->id}", ['label' => str_repeat('x', 300)]);

        $response->assertStatus(422);
    }

    public function test_destroy_soft_deletes_and_writes_audit_log(): void
    {
        $user = $this->actingUser();
        $import = SavedImport::create(['company' => 'cfocn', 'match_count' => 1, 'not_match_count' => 1, 'bill_count' => 1]);

        $response = $this->deleteJson("/api/saved-imports/{$import->id}");

        $response->assertOk();
        $this->assertSoftDeleted('saved_imports', ['id' => $import->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'model' => 'SavedImport',
            'model_id' => $import->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_deleted_import_is_not_reachable(): void
    {
        $this->actingUser();
        $import = SavedImport::create(['company' => 'cfocn', 'match_count' => 1, 'not_match_count' => 1, 'bill_count' => 1]);
        $import->delete();

        $this->getJson("/api/saved-imports/{$import->id}")->assertNotFound();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $import = SavedImport::create(['company' => 'cfocn', 'match_count' => 1, 'not_match_count' => 1, 'bill_count' => 1]);

        $this->getJson('/api/saved-imports')->assertUnauthorized();
        $this->getJson("/api/saved-imports/{$import->id}")->assertUnauthorized();
        $this->patchJson("/api/saved-imports/{$import->id}", ['label' => 'x'])->assertUnauthorized();
        $this->deleteJson("/api/saved-imports/{$import->id}")->assertUnauthorized();
    }
}
