<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LogWhatsappRequest;
use App\Http\Requests\UpdateWhatsappNumberRequest;
use App\Models\Ticket;
use App\Services\TicketActivityLoggerService;
use App\Support\Whatsapp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TicketWhatsappController extends Controller
{
    public function __construct(private TicketActivityLoggerService $logger) {}

    /** Save/relink the customer's WhatsApp number from the ticket panel. */
    public function updateNumber(UpdateWhatsappNumberRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket->loadMissing('customer');

        // A ticket can lose its customer if the customer record was soft-deleted.
        if (! $ticket->customer) {
            return response()->json([
                'message' => 'Este ticket no tiene un cliente asignado.',
                'errors' => ['phone' => ['Este ticket no tiene un cliente al que vincularle el número.']],
            ], 422);
        }

        $phone = $request->validated()['phone'];
        $normalized = Whatsapp::normalize($phone);

        if (! $normalized) {
            return response()->json([
                'message' => 'Número no válido.',
                'errors' => ['phone' => ['Número no válido. Incluí el código de país, por ejemplo +54 9 11 1234 5678.']],
            ], 422);
        }

        $ticket->customer->update(['phone' => $phone]);

        return response()->json([
            'phone' => $ticket->customer->phone,
            'phone_normalized' => $normalized,
            'wa_base' => 'https://wa.me/'.$normalized,
        ]);
    }

    /** Record that the agent contacted the customer by WhatsApp (best-effort, keeps the thread). */
    public function log(LogWhatsappRequest $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validated();

        // Atomic: the activity entry, the optional note and the activity bump
        // either all land or none do.
        DB::transaction(function () use ($request, $ticket, $data) {
            $this->logger->whatsappContacted($ticket, $request->user());

            if ($request->boolean('save_note')) {
                $ticket->notes()->create([
                    'user_id' => $request->user()->id,
                    'body' => $data['body'],
                    'channel' => 'whatsapp',
                ]);
            }

            $ticket->markActivity();
        });

        return response()->json(['ok' => true]);
    }
}
