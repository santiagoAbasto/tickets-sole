<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketPriorityRequest;
use App\Models\TicketPriority;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketPriorityController extends Controller
{
    public function index(): View
    {
        return view('admin.ticket-settings.priorities', [
            'priorities' => TicketPriority::ordered()->withCount('tickets')->get(),
        ]);
    }

    public function store(TicketPriorityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        TicketPriority::create($data);

        return back()->with('success', 'Prioridad creada.');
    }

    public function update(TicketPriorityRequest $request, TicketPriority $priority): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        $priority->update($data);

        return back()->with('success', 'Prioridad actualizada.');
    }

    public function destroy(TicketPriority $priority): RedirectResponse
    {
        if ($priority->tickets()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene tickets asociados.');
        }

        $priority->delete();

        return back()->with('success', 'Prioridad eliminada.');
    }
}
