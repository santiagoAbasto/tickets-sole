<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('admin.ticket-settings.departments', [
            'departments' => Department::query()
                ->withCount(['agents', 'tickets'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Department::create($data);

        return back()->with('success', 'Departamento creado.');
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $department->update($data);

        return back()->with('success', 'Departamento actualizado.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->tickets()->exists() || $department->agents()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene usuarios o tickets asociados.');
        }

        $department->delete();

        return back()->with('success', 'Departamento eliminado.');
    }
}
