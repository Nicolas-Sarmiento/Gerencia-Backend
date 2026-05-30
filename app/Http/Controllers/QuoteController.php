<?php

namespace App\Http\Controllers;

use App\Mail\NewQuoteNotification;
use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    /**
     * Endpoint público: Crea una cotización desde el formulario del landing page.
     * - Crea o reutiliza el cliente por email.
     * - Registra la cotización con los productos seleccionados.
     * - Envía un correo de notificación al equipo administrativo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Datos del cliente
            'client_name' => 'required|string|max:50',
            'client_email' => 'required|email|max:80',
            'client_phone' => 'required|string|max:50',

            // Datos de la cotización
            'description' => 'nullable|string|max:2000',

            // Productos seleccionados (opcional)
            'items' => 'nullable|array',
            'items.*.productId' => 'required|string|exists:products,productId',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $quote = DB::transaction(function () use ($validated) {
            // Buscar o crear el cliente por correo electrónico
            $client = Client::firstOrCreate(
                ['mail' => $validated['client_email']],
                [
                    'clientId' => (string) Str::uuid(),
                    'name' => $validated['client_name'],
                    'phone' => $validated['client_phone'],
                ]
            );

            // Si el cliente ya existía, actualizar sus datos de contacto
            if (!$client->wasRecentlyCreated) {
                $client->update([
                    'name' => $validated['client_name'],
                    'phone' => $validated['client_phone'],
                ]);
            }

            // Crear la cotización
            $quote = Quote::create([
                'quoteId' => (string) Str::uuid(),
                'clientId' => $client->clientId,
                'requestDate' => now(),
                'status' => 'PENDIENTE',
                'description' => $validated['description'] ?? '',
            ]);

            // Crear los items de la cotización (solo si se enviaron productos)
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $quote->quoted_Items()->create([
                        'itemquoteId' => (string) Str::uuid(),
                        'productId' => $item['productId'],
                        'quantity' => $item['quantity'] ?? 1,
                        'requestDate' => now(),
                        'status' => 'PENDIENTE',
                        'description' => '',
                    ]);
                }
            }

            return $quote;
        });

        // Enviar correo de notificación al equipo administrativo
        $adminEmail = config('mail.admin_address', env('MAIL_ADMIN_ADDRESS'));
        if ($adminEmail) {
            Mail::to($adminEmail)->send(new NewQuoteNotification($quote));
        }

        return response()->json([
            'message' => 'Cotización enviada exitosamente. Nos pondremos en contacto pronto.',
            'quoteId' => $quote->quoteId,
        ], 201);
    }

    /**
     * Lista todas las cotizaciones con sus clientes y productos.
     * Solo para administradores autenticados.
     */
    public function index(Request $request)
    {
        $query = Quote::with('client', 'quoted_Items.product');

        // Filtro opcional por estado
        if ($request->has('status')) {
            $request->validate(['status' => 'string|in:PENDIENTE,EN_REVISION,RESPONDIDA,RECHAZADA']);
            $query->where('status', $request->status);
        }

        $quotes = $query->orderBy('requestDate', 'desc')->get();
        return response()->json($quotes);
    }

    /**
     * Muestra una cotización específica.
     * Solo para administradores autenticados.
     */
    public function show(string $id)
    {
        $quote = Quote::with('client', 'quoted_Items.product')->findOrFail($id);
        return response()->json($quote);
    }

    /**
     * Actualiza el estado de una cotización (para gestión administrativa).
     * Solo para administradores autenticados.
     */
    public function updateStatus(Request $request, string $id)
    {
        $quote = Quote::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:PENDIENTE,EN_REVISION,RESPONDIDA,RECHAZADA',
        ]);

        $quote->update(['status' => $validated['status']]);

        return response()->json($quote->load('client', 'quoted_Items.product'));
    }

    /**
     * Elimina una cotización.
     * Solo para administradores autenticados.
     */
    public function destroy(string $id)
    {
        $quote = Quote::findOrFail($id);
        $quote->delete(); // cascade elimina los items

        return response()->json(['message' => 'Cotización eliminada']);
    }
}
