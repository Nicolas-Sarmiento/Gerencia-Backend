<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuoteTest extends TestCase
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

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'productId' => Str::uuid()->toString(),
            'name' => 'Producto Test',
            'description' => 'Descripción',
        ], $overrides));
    }

    private function createQuoteWithClient(): Quote
    {
        $client = Client::create([
            'clientId' => Str::uuid()->toString(),
            'name' => 'Cliente Test',
            'phone' => '3001234567',
            'mail' => 'cliente@test.com',
        ]);

        return Quote::create([
            'quoteId' => Str::uuid()->toString(),
            'clientId' => $client->clientId,
            'requestDate' => now(),
            'status' => 'PENDIENTE',
            'description' => 'Cotización de prueba',
        ]);
    }

    // ─── POST /api/quotes (público) ───

    public function test_store_creates_quote(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson('/api/quotes', [
            'client_name' => 'María García',
            'client_email' => 'maria@test.com',
            'client_phone' => '3001234567',
            'description' => 'Necesito contenedores',
            'items' => [
                ['productId' => $product->productId, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'quoteId']);

        $this->assertDatabaseHas('clients', ['mail' => 'maria@test.com']);
        $this->assertDatabaseHas('quotes', ['status' => 'PENDIENTE']);
    }

    public function test_store_reuses_existing_client_by_email(): void
    {
        $product = $this->createProduct();

        Client::create([
            'clientId' => Str::uuid()->toString(),
            'name' => 'María Vieja',
            'phone' => '0000000',
            'mail' => 'maria@test.com',
        ]);

        $response = $this->postJson('/api/quotes', [
            'client_name' => 'María Actualizada',
            'client_email' => 'maria@test.com',
            'client_phone' => '3009999999',
            'items' => [
                ['productId' => $product->productId],
            ],
        ]);

        $response->assertStatus(201);

        // Solo debe haber 1 cliente, con datos actualizados
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseHas('clients', ['name' => 'María Actualizada', 'phone' => '3009999999']);
    }

    public function test_store_creates_multiple_items(): void
    {
        $productA = $this->createProduct(['name' => 'Producto A']);
        $productB = $this->createProduct(['name' => 'Producto B']);

        $response = $this->postJson('/api/quotes', [
            'client_name' => 'Juan',
            'client_email' => 'juan@test.com',
            'client_phone' => '3001234567',
            'items' => [
                ['productId' => $productA->productId, 'quantity' => 2],
                ['productId' => $productB->productId, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('quoteItems', 2);
    }

    public function test_store_fails_with_nonexistent_product(): void
    {
        $response = $this->postJson('/api/quotes', [
            'client_name' => 'Juan',
            'client_email' => 'juan@test.com',
            'client_phone' => '3001234567',
            'items' => [
                ['productId' => 'no-existe-este-id', 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.productId']);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/quotes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_name', 'client_email', 'client_phone', 'items']);
    }

    // ─── GET /api/quotes (protegido) ───

    public function test_index_returns_all_quotes(): void
    {
        $user = $this->createAuthUser();
        $this->createQuoteWithClient();
        $this->createQuoteWithClient();

        $response = $this->actingAs($user)->getJson('/api/quotes');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_index_filters_by_status(): void
    {
        $user = $this->createAuthUser();
        $quote = $this->createQuoteWithClient();
        $this->createQuoteWithClient();

        $quote->update(['status' => 'RESPONDIDA']);

        $response = $this->actingAs($user)->getJson('/api/quotes?status=RESPONDIDA');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_index_fails_without_auth(): void
    {
        $response = $this->getJson('/api/quotes');

        $response->assertStatus(401);
    }

    // ─── GET /api/quotes/{id} (protegido) ───

    public function test_show_returns_quote_with_relations(): void
    {
        $user = $this->createAuthUser();
        $quote = $this->createQuoteWithClient();

        $response = $this->actingAs($user)->getJson("/api/quotes/{$quote->quoteId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'PENDIENTE'])
            ->assertJsonStructure(['client', 'quoted__items']);
    }

    public function test_show_fails_without_auth(): void
    {
        $quote = $this->createQuoteWithClient();

        $response = $this->getJson("/api/quotes/{$quote->quoteId}");

        $response->assertStatus(401);
    }

    // ─── PATCH /api/quotes/{id}/status (protegido) ───

    public function test_update_status_changes_quote_status(): void
    {
        $user = $this->createAuthUser();
        $quote = $this->createQuoteWithClient();

        $response = $this->actingAs($user)->patchJson("/api/quotes/{$quote->quoteId}/status", [
            'status' => 'EN_REVISION',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'EN_REVISION']);
    }

    public function test_update_status_rejects_invalid_status(): void
    {
        $user = $this->createAuthUser();
        $quote = $this->createQuoteWithClient();

        $response = $this->actingAs($user)->patchJson("/api/quotes/{$quote->quoteId}/status", [
            'status' => 'INVALIDO',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_status_fails_without_auth(): void
    {
        $quote = $this->createQuoteWithClient();

        $response = $this->patchJson("/api/quotes/{$quote->quoteId}/status", [
            'status' => 'RESPONDIDA',
        ]);

        $response->assertStatus(401);
    }

    // ─── DELETE /api/quotes/{id} (protegido) ───

    public function test_destroy_deletes_quote(): void
    {
        $user = $this->createAuthUser();
        $quote = $this->createQuoteWithClient();

        $response = $this->actingAs($user)->deleteJson("/api/quotes/{$quote->quoteId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Cotización eliminada']);

        $this->assertDatabaseMissing('quotes', ['quoteId' => $quote->quoteId]);
    }

    public function test_destroy_fails_without_auth(): void
    {
        $quote = $this->createQuoteWithClient();

        $response = $this->deleteJson("/api/quotes/{$quote->quoteId}");

        $response->assertStatus(401);
    }
}
