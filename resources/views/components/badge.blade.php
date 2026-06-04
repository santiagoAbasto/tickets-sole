@props(['color' => '#64748b', 'dot' => true, 'square' => false])
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap']) }}
      style="color: {{ $color }}; background-color: {{ $color }}14; box-shadow: inset 0 0 0 1px {{ $color }}33;">
    @if ($dot)
        <span class="{{ $square ? 'h-2 w-2 rounded-[3px]' : 'h-1.5 w-1.5 rounded-full' }}" style="background-color: {{ $color }};"></span>
    @endif
    {{ $slot }}
</span>
