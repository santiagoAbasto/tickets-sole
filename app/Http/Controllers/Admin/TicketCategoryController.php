<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketCategoryRequest;
use App\Models\TicketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.ticket-settings.categories', [
            'categories' => TicketCategory::ordered()->withCount('tickets')->get(),
        ]);
    }

    public function store(TicketCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        TicketCategory::create($data);

        return back()->with('success', 'Categoría creada.');
    }

    public function update(TicketCategoryRequest $request, TicketCategory $category): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        $category->update($data);

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(TicketCategory $category): RedirectResponse
    {
        if ($category->tickets()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene tickets asociados.');
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada.');
    }
}
