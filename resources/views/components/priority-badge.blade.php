@props(['priority'])
@if ($priority)
    <x-badge :color="$priority['color']" :square="true">{{ $priority['name'] }}</x-badge>
@endif
