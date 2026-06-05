<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Default assignee: who every new ticket falls to. Admin / Super Admin only
 * (route gated by `permission:settings.manage`).
 */
class AssignmentSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.assignment-settings.edit', [
            'agents' => User::agents()->active()->orderBy('name')->get(['id', 'name', 'job_title']),
            'current' => SiteSetting::get('default_assignee_id'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $id = $data['default_assignee_id'] ?? null;

        // Only accept an active, assignable agent (empty = no default).
        if ($id && ! User::whereKey($id)->where('is_active', true)->where('is_agent', true)->exists()) {
            $id = null;
        }

        SiteSetting::setMany(['default_assignee_id' => $id ? (string) $id : null]);

        return back()->with('success', 'Asignación predeterminada actualizada.');
    }
}
