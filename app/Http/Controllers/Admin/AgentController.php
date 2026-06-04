<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AgentPerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function __construct(private AgentPerformanceService $performance) {}

    public function index(): View
    {
        return view('admin.agents.index', [
            'agents' => $this->performance->leaderboard(50, request()->user()->hasRole('Super Admin')),
        ]);
    }

    public function create(): View
    {
        return view('admin.agents.create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'is_agent' => true,
            'is_active' => $request->boolean('is_active'),
            'email_verified_at' => now(),
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        }

        $user->assignRole($data['role']);

        return redirect()
            ->route('admin.agents.index')
            ->with('success', "Agente {$user->name} creado.");
    }

    public function show(User $agent): View
    {
        abort_unless($this->canViewTarget(request()->user(), $agent), 403);

        $recentTickets = Ticket::with(['customer:id,name', 'priority', 'status'])
            ->assignedTo($agent->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Ticket $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'subject' => $t->subject,
                'customer' => $t->customer?->name,
                'priority' => $t->priority?->only(['name', 'slug', 'color']),
                'status' => $t->status?->only(['name', 'slug', 'color']),
                'is_overdue' => $t->is_overdue,
            ]);

        return view('admin.agents.show', [
            'agent' => array_merge($this->performance->forAgent($agent), [
                'email' => $agent->email,
                'phone' => $agent->phone,
                'department' => $agent->department?->name,
                'roles' => $agent->getRoleNames(),
                'is_active' => $agent->is_active,
            ]),
            'recentTickets' => $recentTickets,
        ]);
    }

    public function edit(User $agent): View
    {
        abort_unless($this->canManageTarget(request()->user(), $agent), 403);

        return view('admin.agents.edit', [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'job_title' => $agent->job_title,
                'phone' => $agent->phone,
                'department_id' => $agent->department_id,
                'is_active' => $agent->is_active,
                'role' => $agent->getRoleNames()->first(),
                'avatar_url' => $agent->avatarUrl(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateAgentRequest $request, User $agent): RedirectResponse
    {
        abort_unless($this->canManageTarget($request->user(), $agent), 403);

        $data = $request->validated();

        if ($this->wouldRemoveLastSuperAdmin($agent, $data['role'], $request->boolean('is_active'))) {
            return back()->with('error', 'Debe quedar al menos un Super Admin activo.');
        }

        $agent->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->boolean('remove_avatar') && $agent->avatar_path) {
            Storage::disk('public')->delete($agent->avatar_path);
            $agent->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            if ($agent->avatar_path) {
                Storage::disk('public')->delete($agent->avatar_path);
            }

            $agent->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        if (! empty($data['password'])) {
            $agent->password = Hash::make($data['password']);
        }

        $agent->save();
        $agent->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.agents.show', $agent)
            ->with('success', 'Agente actualizado.');
    }

    public function destroy(User $agent): RedirectResponse
    {
        abort_unless($this->canRemoveAccess(request()->user(), $agent), 403);

        if ($this->wouldRemoveLastSuperAdmin($agent, $agent->getRoleNames()->first(), false)) {
            return back()->with('error', 'Debe quedar al menos un Super Admin activo.');
        }

        // Deactivate rather than delete to preserve ticket history/FKs.
        $agent->update(['is_active' => false]);

        return redirect()
            ->route('admin.agents.index')
            ->with('success', "Agente {$agent->name} desactivado.");
    }

    private function options(): array
    {
        return [
            'departments' => Department::orderBy('sort_order')->get(['id', 'name']),
            'roles' => $this->assignableRoles(),
        ];
    }

    private function assignableRoles(): array
    {
        return request()->user()->hasRole('Super Admin')
            ? ['Agente', 'Admin', 'Super Admin']
            : ['Agente'];
    }

    private function canViewTarget(User $actor, User $target): bool
    {
        return $actor->hasRole('Super Admin') || ! $target->hasRole('Super Admin');
    }

    private function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        return $target->hasRole('Agente');
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
