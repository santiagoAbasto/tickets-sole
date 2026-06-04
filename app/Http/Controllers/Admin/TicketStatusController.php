<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketStatusRequest;
use App\Models\TicketStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketStatusController extends Controller
{
    public function index(): View
    {
        return view('admin.ticket-settings.statuses', [
            'statuses' => TicketStatus::ordered()->withCount('tickets')->get(),
        ]);
    }

    public function store(TicketStatusRequest $request): RedirectResponse
    {
        $this->persist($request->validated());

        return back()->with('success', 'Estado creado.');
    }

    public function update(TicketStatusRequest $request, TicketStatus $status): RedirectResponse
    {
        $this->persist($request->validated(), $status);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(TicketStatus $status): RedirectResponse
    {
        if ($status->tickets()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene tickets asociados.');
        }

        $status->delete();

        return back()->with('success', 'Estado eliminado.');
    }

    private function persist(array $data, ?TicketStatus $status = null): void
    {
        $data['slug'] ??= Str::slug($data['name']);

        // Keep a single default status.
        if (! empty($data['is_default'])) {
            TicketStatus::when($status, fn ($q) => $q->whereKeyNot($status->id))
                ->update(['is_default' => false]);
        }

        $status ? $status->update($data) : TicketStatus::create($data);
    }
}
