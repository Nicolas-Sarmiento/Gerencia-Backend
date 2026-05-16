<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index()
    {
        return response()->json(Article::with('user', 'multimedia')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'multimedia' => 'sometimes|array',
            'multimedia.*.content' => 'nullable|string',
            'multimedia.*.resourceUrl' => 'nullable|string',
            'multimedia.*.type' => 'required|in:TEXT,IMAGE,VIDEO',
            'multimedia.*.file' => 'nullable|file|image|max:10240', // up to 10MB
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $article = Article::create([
                'articleId' => (string) Str::uuid(),
                'title' => $validated['title'],
                'userId' => $request->user()->userId,
            ]);

            if (!empty($validated['multimedia'])) {
                foreach ($validated['multimedia'] as $index => $mediaData) {
                    $resourceUrl = $mediaData['resourceUrl'] ?? null;

                    if (isset($mediaData['type']) && $mediaData['type'] === 'IMAGE') {
                        if (isset($mediaData['file']) && $mediaData['file'] instanceof \Illuminate\Http\UploadedFile && $mediaData['file']->isValid()) {
                            $path = $mediaData['file']->store('articles', 'public');
                            $resourceUrl = '/storage/' . $path;
                        } elseif ($request->hasFile("multimedia.{$index}.file")) {
                            $path = $request->file("multimedia.{$index}.file")->store('articles', 'public');
                            $resourceUrl = '/storage/' . $path;
                        } else {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                "multimedia.{$index}.file" => "La imagen o el archivo para el índice {$index} no se recibió correctamente."
                            ]);
                        }
                    }

                    $article->multimedia()->create([
                        'multimediaId' => (string) Str::uuid(),
                        'content' => $mediaData['content'] ?? null,
                        'resourceUrl' => $resourceUrl,
                        'type' => $mediaData['type'],
                    ]);
                }
            }

            return response()->json($article->load('multimedia', 'user'), 201);
        });
    }

    public function show(string $id)
    {
        $article = Article::with('user', 'multimedia')->findOrFail($id);
        return response()->json($article);
    }

    public function update(Request $request, string $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'multimedia' => 'sometimes|array',
            'multimedia.*.multimediaId' => 'nullable|string',
            'multimedia.*.content' => 'nullable|string',
            'multimedia.*.resourceUrl' => 'nullable|string',
            'multimedia.*.type' => 'required_with:multimedia|in:TEXT,IMAGE,VIDEO',
            'multimedia.*.file' => 'nullable|file|image|max:10240',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, $article) {
            if (isset($validated['title'])) {
                $article->update(['title' => $validated['title']]);
            }

            if (isset($validated['multimedia'])) {
                $existingIds = $article->multimedia()->pluck('multimediaId')->toArray();
                $incomingIds = collect($validated['multimedia'])->pluck('multimediaId')->filter()->toArray();
                
                $toDelete = array_diff($existingIds, $incomingIds);
                if (!empty($toDelete)) {
                    // Opcional: Eliminar archivos físicos aquí si se desea
                    $article->multimedia()->whereIn('multimediaId', $toDelete)->delete();
                }

                foreach ($validated['multimedia'] as $index => $mediaData) {
                    $resourceUrl = $mediaData['resourceUrl'] ?? null;

                    if (isset($mediaData['type']) && $mediaData['type'] === 'IMAGE' && $request->hasFile("multimedia.{$index}.file")) {
                        $path = $request->file("multimedia.{$index}.file")->store('articles', 'public');
                        $resourceUrl = '/storage/' . $path;
                    }

                    if (!empty($mediaData['multimediaId']) && in_array($mediaData['multimediaId'], $existingIds)) {
                        // Solo actualizar recurso si se subió uno nuevo o se envió explícitamente
                        $updateData = [
                            'content' => $mediaData['content'] ?? null,
                            'type' => $mediaData['type'],
                        ];
                        if ($resourceUrl !== null) {
                            $updateData['resourceUrl'] = $resourceUrl;
                        }
                        
                        $article->multimedia()->where('multimediaId', $mediaData['multimediaId'])->update($updateData);
                    } else {
                        $article->multimedia()->create([
                            'multimediaId' => (string) Str::uuid(),
                            'content' => $mediaData['content'] ?? null,
                            'resourceUrl' => $resourceUrl,
                            'type' => $mediaData['type'],
                        ]);
                    }
                }
            }

            return response()->json($article->load('multimedia', 'user'));
        });
    }

    public function destroy(string $id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Artículo eliminado']);
    }
}
