@props(['name'])
{{-- Lucide icon; hydrated client-side by renderIcons(). --}}
<i data-lucide="{{ $name }}" {{ $attributes->class(['h-5 w-5']) }}></i>
