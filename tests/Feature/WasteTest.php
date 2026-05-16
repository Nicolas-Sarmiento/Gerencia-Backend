<?php

namespace Tests\Feature;

use App\Models\AnnualProcessedWaste;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WasteTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthUser(): User
    {
        return User::create([
            'userId' => Str::uuid()->toString(),
            'firstName' => 'Admin',
            'lastName' => 'Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);
    }

    private function createWasteRecord(array $overrides = []): AnnualProcessedWaste
    {
        return AnnualProcessedWaste::create(array_merge([
            'wasteId' => Str::uuid()->toString(),
            'year' => '2025-01-01',
            'processedWaste' => 15000.5,
        ], $overrides));
    }

    // ─── GET /api/waste/latest (público) ───

    public function test_latest_returns_most_recent_record(): void
    {
        $this->createWasteRecord(['year' => '2024-01-01', 'processedWaste' => 10000]);
        $this->createWasteRecord(['year' => '2025-01-01', 'processedWaste' => 15000]);

        $response = $this->getJson('/api/waste/latest');

        $response->assertStatus(200)
            ->assertJsonFragment(['processedWaste' => 15000]);
    }

    public function test_latest_returns_404_when_no_records(): void
    {
        $response = $this->getJson('/api/waste/latest');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'No hay registros de residuos procesados disponibles']);
    }

    // ─── GET /api/waste (protegido) ───

    public function test_index_returns_all_records(): void
    {
        $user = $this->createAuthUser();
        $this->createWasteRecord(['year' => '2024-01-01']);
        $this->createWasteRecord(['year' => '2025-01-01']);

        $response = $this->actingAs($user)->getJson('/api/waste');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_index_fails_without_auth(): void
    {
        $response = $this->getJson('/api/waste');

        $response->assertStatus(401);
    }

    // ─── POST /api/waste (protegido) ───

    public function test_store_creates_record(): void
    {
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/waste', [
            'year' => '2026-01-01',
            'processedWaste' => 20000,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['processedWaste' => 20000]);

        $this->assertDatabaseHas('annual_processed_wastes', ['processedWaste' => 20000]);
    }

    public function test_store_fails_with_duplicate_year(): void
    {
        $user = $this->createAuthUser();
        $this->createWasteRecord(['year' => '2025-01-01']);

        $response = $this->actingAs($user)->postJson('/api/waste', [
            'year' => '2025-06-15',
            'processedWaste' => 99999,
        ]);

        $response->assertStatus(409);
    }

    public function test_store_fails_without_auth(): void
    {
        $response = $this->postJson('/api/waste', [
            'year' => '2025-01-01',
            'processedWaste' => 10000,
        ]);

        $response->assertStatus(401);
    }

    public function test_store_fails_with_invalid_data(): void
    {
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/waste', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year', 'processedWaste']);
    }

    // ─── GET /api/waste/{id} (protegido) ───

    public function test_show_returns_record(): void
    {
        $user = $this->createAuthUser();
        $record = $this->createWasteRecord();

        $response = $this->actingAs($user)->getJson("/api/waste/{$record->wasteId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['processedWaste' => 15000.5]);
    }

    // ─── PUT /api/waste/{id} (protegido) ───

    public function test_update_modifies_record(): void
    {
        $user = $this->createAuthUser();
        $record = $this->createWasteRecord();

        $response = $this->actingAs($user)->putJson("/api/waste/{$record->wasteId}", [
            'processedWaste' => 99999,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['processedWaste' => 99999]);
    }

    public function test_update_fails_with_duplicate_year(): void
    {
        $user = $this->createAuthUser();
        $this->createWasteRecord(['year' => '2024-01-01']);
        $record = $this->createWasteRecord(['year' => '2025-01-01']);

        $response = $this->actingAs($user)->putJson("/api/waste/{$record->wasteId}", [
            'year' => '2024-06-15',
        ]);

        $response->assertStatus(409);
    }

    public function test_update_fails_without_auth(): void
    {
        $record = $this->createWasteRecord();

        $response = $this->putJson("/api/waste/{$record->wasteId}", [
            'processedWaste' => 99999,
        ]);

        $response->assertStatus(401);
    }

    // ─── DELETE /api/waste/{id} (protegido) ───

    public function test_destroy_deletes_record(): void
    {
        $user = $this->createAuthUser();
        $record = $this->createWasteRecord();

        $response = $this->actingAs($user)->deleteJson("/api/waste/{$record->wasteId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Registro de residuos eliminado']);

        $this->assertDatabaseMissing('annual_processed_wastes', ['wasteId' => $record->wasteId]);
    }

    public function test_destroy_fails_without_auth(): void
    {
        $record = $this->createWasteRecord();

        $response = $this->deleteJson("/api/waste/{$record->wasteId}");

        $response->assertStatus(401);
    }
}
