<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HostCredentialRequest;
use App\Models\HostCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class HostCredentialController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isStaff(), 403);

        $search = trim((string) $request->get('search', ''));

        $hosts = HostCredential::query()
            ->with(['creator:id,name', 'sourceTicket:id,code'])
            ->visibleTo($request->user())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('website_url', 'like', "%{$search}%")
                        ->orWhere('server_url', 'like', "%{$search}%")
                        ->orWhere('hosting_provider', 'like', "%{$search}%")
                        ->orWhere('cpanel_user', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.host-credentials.index', [
            'hosts' => $hosts,
            'search' => $search,
            'canSeeAll' => $request->user()->hasAnyRole(['Super Admin', 'Admin']),
        ]);
    }

    public function store(HostCredentialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['cpanel_password'] ?? null)) {
            unset($data['cpanel_password']);
        }
        $data['created_by'] = $request->user()->id;
        $data['fingerprint'] = HostCredential::fingerprintFor($data);

        HostCredential::updateOrCreate(
            ['fingerprint' => $data['fingerprint']],
            $data,
        );

        return back()->with('success', 'Host registrado.');
    }

    public function update(HostCredentialRequest $request, HostCredential $hostCredential): RedirectResponse
    {
        $this->ensureCanManage($request, $hostCredential);

        $data = $request->validated();
        if (blank($data['cpanel_password'] ?? null)) {
            unset($data['cpanel_password']);
        }
        $data['fingerprint'] = HostCredential::fingerprintFor($data);

        if ($existing = HostCredential::where('fingerprint', $data['fingerprint'])->whereKeyNot($hostCredential->id)->first()) {
            $existing->update($data);
            $hostCredential->delete();

            return back()->with('success', 'Host actualizado y duplicado unificado.');
        }

        $hostCredential->update($data);

        return back()->with('success', 'Host actualizado.');
    }

    public function revealPassword(Request $request, HostCredential $hostCredential): JsonResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless(
            HostCredential::query()->visibleTo($request->user())->whereKey($hostCredential->id)->exists(),
            404,
        );

        Log::info('Host credential password revealed', [
            'host_credential_id' => $hostCredential->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'password' => $hostCredential->cpanel_password,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function destroy(Request $request, HostCredential $hostCredential): RedirectResponse
    {
        $this->ensureCanManage($request, $hostCredential);

        $hostCredential->delete();

        return back()->with('success', 'Host eliminado.');
    }

    private function ensureCanManage(Request $request, HostCredential $hostCredential): void
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyRole(['Super Admin', 'Admin']) || $hostCredential->created_by === $user->id,
            403,
        );
    }
}
