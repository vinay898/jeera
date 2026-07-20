@props(['lov'])

@php
$colorMap = [
    'danger' => '#dc2626',
    'success' => '#16a34a',
    'info' => '#2563eb',
    'warning' => '#d97706',
    'purple' => '#9333ea',
    'pink' => '#db2777',
    'gray' => '#6b7280',
    'primary' => '#2563eb',
];
$color = $colorMap[$lov->color ?? 'gray'] ?? '#6b7280';
@endphp

<span style="display: inline-flex; align-items: center; gap: 8px;">
    @if($lov->icon)
        <x-filament::icon :icon="$lov->icon" style="width: 16px; height: 16px; color: {{ $color }}; flex-shrink: 0;" />
    @endif
    {{ $lov->name }}
</span>
