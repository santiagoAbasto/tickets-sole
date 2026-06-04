@props(['status'])
@if ($status)
    <x-badge :color="$status['color']">{{ $status['name'] }}</x-badge>
@endif
