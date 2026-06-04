@props(['name' => '', 'src' => null, 'size' => 'md'])
@php
    $sizes = [
        'xs' => 'h-6 w-6 text-[10px]',
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
    $tints = [
        'bg-brand-100 text-brand-700', 'bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-700', 'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700',
    ];
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $initials = strtoupper(implode('', array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2)))) ?: 'U';
    $tint = $tints[array_sum(array_map('ord', str_split($name ?: 'U'))) % count($tints)];
    $cls = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(["rounded-full object-cover ring-1 ring-slate-200 $cls"]) }}>
@else
    <span {{ $attributes->class(["inline-flex items-center justify-center rounded-full font-semibold ring-1 ring-inset ring-black/5 $cls $tint"]) }} aria-hidden="true">{{ $initials }}</span>
@endif
