<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('department:id,name,color')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin', 'Agente', 'Diseñadora industrial']))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'department' => $user->department?->only(['name', 'color']),
                'role' => $user->getRoleNames()->first(),
                'is_active' => $user->is_active,
                'avatar_url' => $user->avatarUrl(),
                'can_manage' => $this->canManageTarget(request()->user(), $user),
                'can_remove_access' => $this->canRemoveAccess(request()->user(), $user),
            ]);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'is_agent' => in_array($data['role'], ['Super Admin', 'Admin', 'Agente'], true),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        }

        $user->assignRole($data['role']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario {$user->name} creado.");
    }

    public function edit(User $user): View
    {
        abort_unless($this->canManageTarget(request()->user(), $user), 403);

        return view('admin.users.edit', [
            'managedUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'department_id' => $user->department_id,
                'role' => $user->getRoleNames()->first(),
                'is_active' => $user->is_active,
                'avatar_url' => $user->avatarUrl(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($this->canManageTarget($request->user(), $user), 403);

        $data = $request->validated();

        if ($this->wouldRemoveLastSuperAdmin($user, $data['role'], $request->boolean('is_active'))) {
            return back()->with('error', 'Debe quedar al menos un Super Admin activo.');
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'is_agent' => in_array($data['role'], ['Super Admin', 'Admin', 'Agente'], true),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($this->canRemoveAccess(request()->user(), $user), 403);

        if ($this->wouldRemoveLastSuperAdmin($user, $user->getRoleNames()->first(), false)) {
            return back()->with('error', 'Debe quedar al menos un Super Admin activo.');
        }

        $user->update(['is_active' => false]);

        return back()->with('success', "Acceso eliminado para {$user->name}.");
    }

    private function options(): array
    {
        return [
            'departments' => Department::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'roles' => request()->user()->hasRole('Super Admin')
                ? ['Agente', 'Diseñadora industrial', 'Admin', 'Super Admin']
                : ['Agente', 'Diseñadora industrial'],
        ];
    }

    private function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        return $target->hasAnyRole(['Agente', 'Diseñadora industrial']);
    }

    private function canRemoveAccess(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        return $this->canManageTarget($actor, $target);
    }

    private function wouldRemoveLastSuperAdmin(User $target, ?string $nextRole, bool $nextActive): bool
    {
        if (! $target->hasRole('Super Admin')) {
            return false;
        }

        $activeSuperAdmins = User::role('Super Admin')->where('is_active', true)->count();
        $keepsSuperAdmin = $nextRole === 'Super Admin' && $nextActive;

        return $activeSuperAdmins <= 1 && ! $keepsSuperAdmin;
    }
}
