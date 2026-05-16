<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Lista todos los productos con sus imágenes.
     * Endpoint público para la landing page.
     */
    public function index()
    {
        return response()->json(Product::with('images')->get());
    }

    /**
     * Muestra un producto específico con sus imágenes.
     * Endpoint público para la landing page.
     */
    public function show(string $id)
    {
        $product = Product::with('images')->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Crea un nuevo producto con sus imágenes (archivos subidos al servidor).
     * Requiere autenticación.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'images' => 'sometimes|array',
            'images.*.file' => 'required|file|image|mimes:jpeg,png,jpg,webp,svg|max:10240',
            'images.*.alt' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'productId' => (string) Str::uuid(),
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

            if ($request->file('images')) {
                foreach ($request->file('images') as $index => $imageGroup) {
                    $file = is_array($imageGroup) ? ($imageGroup['file'] ?? null) : $imageGroup;

                    if ($file && $file->isValid()) {
                        $path = $file->store('products', 'public');

                        $product->images()->create([
                            'imageId' => (string) Str::uuid(),
                            'imageurl' => '/storage/' . $path,
                            'alt' => $validated['images'][$index]['alt'] ?? $product->name,
                        ]);
                    }
                }
            }

            return response()->json($product->load('images'), 201);
        });
    }

    /**
     * Actualiza los datos de un producto.
     * Las imágenes se gestionan por separado con endpoints dedicados.
     * Requiere autenticación.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
        ]);

        $product->update($validated);

        return response()->json($product->load('images'));
    }

    /**
     * Elimina un producto y todas sus imágenes del servidor.
     * Requiere autenticación.
     */
    public function destroy(string $id)
    {
        $product = Product::with('images')->findOrFail($id);

        DB::transaction(function () use ($product) {
            // Eliminar archivos físicos del servidor
            foreach ($product->images as $image) {
                $relativePath = str_replace('/storage/', '', $image->imageurl);
                Storage::disk('public')->delete($relativePath);
            }

            $product->delete(); // cascade elimina las imágenes de la BD
        });

        return response()->json(['message' => 'Producto y sus imágenes eliminados']);
    }

    /**
     * Agrega una o más imágenes a un producto existente.
     * Requiere autenticación.
     */
    public function addImages(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'images' => 'required|array|min:1',
            'images.*.file' => 'required|file|image|mimes:jpeg,png,jpg,webp,svg|max:10240',
            'images.*.alt' => 'nullable|string|max:255',
        ]);

        $createdImages = [];

        DB::transaction(function () use ($request, $product, &$createdImages) {
            foreach ($request->file('images') as $index => $imageGroup) {
                $file = is_array($imageGroup) ? ($imageGroup['file'] ?? null) : $imageGroup;

                if ($file && $file->isValid()) {
                    $path = $file->store('products', 'public');

                    $createdImages[] = $product->images()->create([
                        'imageId' => (string) Str::uuid(),
                        'imageurl' => '/storage/' . $path,
                        'alt' => $request->input("images.{$index}.alt", $product->name),
                    ]);
                }
            }
        });

        return response()->json([
            'message' => count($createdImages) . ' imagen(es) agregada(s)',
            'images' => $createdImages,
        ], 201);
    }

    /**
     * Elimina una imagen específica de un producto.
     * Requiere autenticación.
     */
    public function removeImage(string $productId, string $imageId)
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->where('imageId', $imageId)->firstOrFail();

        // Eliminar archivo físico
        $relativePath = str_replace('/storage/', '', $image->imageurl);
        Storage::disk('public')->delete($relativePath);

        $image->delete();

        return response()->json(['message' => 'Imagen eliminada']);
    }
}
