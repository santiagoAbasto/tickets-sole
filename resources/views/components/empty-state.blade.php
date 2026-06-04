@props(['icon' => 'inbox', 'title', 'description' => null])
<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <i data-lucide="{{ $icon }}" class="h-6 w-6"></i>
    </span>
    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>@endif
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
