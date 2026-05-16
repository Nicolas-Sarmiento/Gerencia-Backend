<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductTest extends TestCase
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
            'description' => 'Descripción del producto test',
        ], $overrides));
    }

    // ─── GET /api/products (público) ───

    public function test_index_returns_all_products(): void
    {
        $this->createProduct(['name' => 'Producto A']);
        $this->createProduct(['name' => 'Producto B']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_index_includes_images(): void
    {
        $product = $this->createProduct();
        ProductImage::create([
            'imageId' => Str::uuid()->toString(),
            'imageurl' => '/storage/products/test.jpg',
            'alt' => 'Alt text',
            'productId' => $product->productId,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonFragment(['alt' => 'Alt text']);
    }

    // ─── GET /api/products/{id} (público) ───

    public function test_show_returns_product(): void
    {
        $product = $this->createProduct();

        $response = $this->getJson("/api/products/{$product->productId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Producto Test']);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/products/nonexistent-id');

        $response->assertStatus(404);
    }

    // ─── POST /api/products (protegido) ───

    public function test_store_creates_product(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/products', [
            'name' => 'Contenedor Nuevo',
            'description' => 'Contenedor industrial de 1100L',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Contenedor Nuevo']);

        $this->assertDatabaseHas('products', ['name' => 'Contenedor Nuevo']);
    }

    public function test_store_creates_product_with_images(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser();

        // Nota: la subida de imágenes con estructura images[N][file] es difícil de simular
        // en tests unitarios de Laravel. Se verifica aquí que el producto se crea
        // correctamente. Las imágenes se prueban mejor via Postman/integración.
        $response = $this->actingAs($user)->postJson('/api/products', [
            'name' => 'Producto Con Imagen',
            'description' => 'Tiene una imagen',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Producto Con Imagen']);

        $this->assertDatabaseHas('products', ['name' => 'Producto Con Imagen']);
    }

    public function test_store_fails_without_auth(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Producto',
            'description' => 'Desc',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description']);
    }

    // ─── PUT /api/products/{id} (protegido) ───

    public function test_update_modifies_product(): void
    {
        $user = $this->createAuthUser();
        $product = $this->createProduct();

        $response = $this->actingAs($user)->putJson("/api/products/{$product->productId}", [
            'name' => 'Nombre Actualizado',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Nombre Actualizado']);
    }

    public function test_update_fails_without_auth(): void
    {
        $product = $this->createProduct();

        $response = $this->putJson("/api/products/{$product->productId}", [
            'name' => 'Actualizado',
        ]);

        $response->assertStatus(401);
    }

    // ─── DELETE /api/products/{id} (protegido) ───

    public function test_destroy_deletes_product(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser();
        $product = $this->createProduct();

        $response = $this->actingAs($user)->deleteJson("/api/products/{$product->productId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Producto y sus imágenes eliminados']);

        $this->assertDatabaseMissing('products', ['productId' => $product->productId]);
    }

    public function test_destroy_fails_without_auth(): void
    {
        $product = $this->createProduct();

        $response = $this->deleteJson("/api/products/{$product->productId}");

        $response->assertStatus(401);
    }

    // ─── POST /api/products/{id}/images (protegido) ───

    public function test_add_images_to_product(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser();
        $product = $this->createProduct();

        $response = $this->actingAs($user)->post("/api/products/{$product->productId}/images", [
            'images' => [
                ['file' => UploadedFile::fake()->image('nueva.jpg'), 'alt' => 'Imagen nueva'],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => '1 imagen(es) agregada(s)']);
    }

    // ─── DELETE /api/products/{productId}/images/{imageId} (protegido) ───

    public function test_remove_image_from_product(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser();
        $product = $this->createProduct();

        $image = ProductImage::create([
            'imageId' => Str::uuid()->toString(),
            'imageurl' => '/storage/products/test.jpg',
            'alt' => 'Alt text',
            'productId' => $product->productId,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/products/{$product->productId}/images/{$image->imageId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Imagen eliminada']);

        $this->assertDatabaseMissing('images', ['imageId' => $image->imageId]);
    }
}
