<?php

namespace App\Http\Controllers;

use App\Models\AnnualProcessedWaste;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnualProcessedWasteController extends Controller
{
    /**
     * Retorna todos los registros de residuos procesados, ordenados por año descendente.
     * Solo accesible por administrador autenticado.
     */
    public function index()
    {
        $records = AnnualProcessedWaste::orderBy('year', 'desc')->get();
        return response()->json($records);
    }

    /**
     * Retorna únicamente el registro del año más reciente.
     * Endpoint público para la landing page.
     */
    public function latest()
    {
        $latest = AnnualProcessedWaste::orderBy('year', 'desc')->first();

        if (!$latest) {
            return response()->json([
                'message' => 'No hay registros de residuos procesados disponibles'
            ], 404);
        }

        return response()->json($latest);
    }

    /**
     * Crea un nuevo registro anual de residuos procesados.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|date',
            'processedWaste' => 'required|numeric|min:0',
        ]);

        // Evitar duplicados: verificar que no exista ya un registro para el mismo año
        $yearValue = date('Y', strtotime($validated['year']));
        $exists = AnnualProcessedWaste::whereYear('year', $yearValue)->exists();

        if ($exists) {
            return response()->json([
                'message' => "Ya existe un registro para el año {$yearValue}. Utilice el endpoint de actualización."
            ], 409);
        }

        $record = AnnualProcessedWaste::create([
            'wasteId' => (string) Str::uuid(),
            'year' => $validated['year'],
            'processedWaste' => $validated['processedWaste'],
        ]);

        return response()->json($record, 201);
    }

    /**
     * Muestra un registro específico por su ID.
     */
    public function show(string $id)
    {
        $record = AnnualProcessedWaste::findOrFail($id);
        return response()->json($record);
    }

    /**
     * Actualiza un registro existente.
     */
    public function update(Request $request, string $id)
    {
        $record = AnnualProcessedWaste::findOrFail($id);

        $validated = $request->validate([
            'year' => 'sometimes|date',
            'processedWaste' => 'sometimes|numeric|min:0',
        ]);

        // Si se intenta cambiar el año, verificar que no haya duplicados
        if (isset($validated['year'])) {
            $yearValue = date('Y', strtotime($validated['year']));
            $exists = AnnualProcessedWaste::whereYear('year', $yearValue)
                ->where('wasteId', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => "Ya existe un registro para el año {$yearValue}."
                ], 409);
            }
        }

        $record->update($validated);

        return response()->json($record);
    }

    /**
     * Elimina un registro.
     */
    public function destroy(string $id)
    {
        $record = AnnualProcessedWaste::findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Registro de residuos eliminado']);
    }
}
