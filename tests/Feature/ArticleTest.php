<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Multimedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArticleTest extends TestCase
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

    private function createArticle(User $user, array $overrides = []): Article
    {
        return Article::create(array_merge([
            'articleId' => Str::uuid()->toString(),
            'title' => 'Artículo Test',
            'userId' => $user->userId,
        ], $overrides));
    }

    // ─── GET /api/articles (público) ───

    public function test_index_returns_all_articles(): void
    {
        $user = $this->createAuthUser();
        $this->createArticle($user, ['title' => 'Artículo A']);
        $this->createArticle($user, ['title' => 'Artículo B']);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_index_includes_user_and_multimedia(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);
        Multimedia::create([
            'multimediaId' => Str::uuid()->toString(),
            'content' => 'Texto de prueba',
            'resourceUrl' => null,
            'type' => 'TEXT',
            'articleId' => $article->articleId,
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonFragment(['content' => 'Texto de prueba'])
            ->assertJsonFragment(['firstName' => 'Admin']);
    }

    // ─── GET /api/articles/{id} (público) ───

    public function test_show_returns_article(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);

        $response = $this->getJson("/api/articles/{$article->articleId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Artículo Test']);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/articles/nonexistent-id');

        $response->assertStatus(404);
    }

    // ─── POST /api/articles (protegido) ───

    public function test_store_creates_article_with_text_multimedia(): void
    {
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/articles', [
            'title' => 'Nuevo Artículo',
            'multimedia' => [
                ['type' => 'TEXT', 'content' => 'Contenido de texto'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Nuevo Artículo'])
            ->assertJsonFragment(['type' => 'TEXT']);

        $this->assertDatabaseHas('articles', ['title' => 'Nuevo Artículo']);
    }

    public function test_store_creates_article_with_image_upload(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->post('/api/articles', [
            'title' => 'Artículo con Imagen',
            'multimedia' => [
                ['type' => 'IMAGE', 'file' => UploadedFile::fake()->image('foto.jpg')],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'IMAGE']);
    }

    public function test_store_creates_article_with_video(): void
    {
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/articles', [
            'title' => 'Artículo con Video',
            'multimedia' => [
                ['type' => 'VIDEO', 'resourceUrl' => 'https://youtube.com/watch?v=abc123', 'content' => 'Descripción del video'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'VIDEO']);
    }

    public function test_store_fails_without_auth(): void
    {
        $response = $this->postJson('/api/articles', [
            'title' => 'Artículo',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_fails_without_title(): void
    {
        $user = $this->createAuthUser();

        $response = $this->actingAs($user)->postJson('/api/articles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // ─── PUT /api/articles/{id} (protegido) ───

    public function test_update_modifies_title(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);

        $response = $this->actingAs($user)->putJson("/api/articles/{$article->articleId}", [
            'title' => 'Título Actualizado',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Título Actualizado']);
    }

    public function test_update_removes_omitted_multimedia(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);

        // Crear un bloque multimedia existente
        Multimedia::create([
            'multimediaId' => Str::uuid()->toString(),
            'content' => 'Texto viejo',
            'type' => 'TEXT',
            'articleId' => $article->articleId,
        ]);

        // Enviar multimedia vacío → debería eliminar el bloque
        $response = $this->actingAs($user)->putJson("/api/articles/{$article->articleId}", [
            'multimedia' => [],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('multimedia', ['content' => 'Texto viejo']);
    }

    public function test_update_fails_without_auth(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);

        $response = $this->putJson("/api/articles/{$article->articleId}", [
            'title' => 'Nuevo',
        ]);

        $response->assertStatus(401);
    }

    // ─── DELETE /api/articles/{id} (protegido) ───

    public function test_destroy_deletes_article(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);

        $response = $this->actingAs($user)->deleteJson("/api/articles/{$article->articleId}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Artículo eliminado']);

        $this->assertDatabaseMissing('articles', ['articleId' => $article->articleId]);
    }

    public function test_destroy_fails_without_auth(): void
    {
        $user = $this->createAuthUser();
        $article = $this->createArticle($user);

        $response = $this->deleteJson("/api/articles/{$article->articleId}");

        $response->assertStatus(401);
    }
}
