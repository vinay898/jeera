@php
    $user = auth()->user();
    $settings = $user?->setting;

    $compactMode = $settings?->compact_mode ?? false;
    $fontSize = $settings?->font_size ?? 'medium';
    $theme = $settings?->theme ?? 'system';

    $fontSizeMap = [
        'small' => '0.8125rem',  // 13px
        'medium' => '0.875rem',  // 14px (default)
        'large' => '1rem',       // 16px
    ];

    $baseFontSize = $fontSizeMap[$fontSize] ?? '0.875rem';
@endphp

<style>
    @if($fontSize !== 'medium')
    /* Font size - target body and key Filament elements */
    body.fi-body {
        font-size: {{ $baseFontSize }} !important;
    }

    /* Form elements */
    .fi-body .fi-input,
    .fi-body .fi-select-input,
    .fi-body .fi-fo-field-wrp,
    .fi-body input,
    .fi-body select,
    .fi-body textarea,
    .fi-body button,
    .fi-body label {
        font-size: {{ $baseFontSize }} !important;
    }

    /* Table cells */
    .fi-body .fi-ta-cell,
    .fi-body .fi-ta-text,
    .fi-body td,
    .fi-body th {
        font-size: {{ $baseFontSize }} !important;
    }

    /* Navigation and UI */
    .fi-body .fi-sidebar-item-label,
    .fi-body .fi-btn,
    .fi-body .fi-topbar,
    .fi-body .fi-section-content,
    .fi-body .fi-tabs-tab-label {
        font-size: {{ $baseFontSize }} !important;
    }

    /* Headings - scale proportionally */
    .fi-body h1,
    .fi-body .fi-header-heading {
        font-size: calc({{ $baseFontSize }} * 1.75) !important;
    }
    .fi-body h2 {
        font-size: calc({{ $baseFontSize }} * 1.5) !important;
    }
    .fi-body h3,
    .fi-body .fi-section-header-heading {
        font-size: calc({{ $baseFontSize }} * 1.25) !important;
    }
    @endif

    @if(!$compactMode)
    /* Spacious mode (when compact is OFF) - more padding and gaps */

    /* Form inputs - more vertical padding */
    .fi-input-wrp input,
    .fi-select-input,
    [class*="fi-fo-"] input,
    [class*="fi-fo-"] select {
        padding-top: 0.625rem !important;
        padding-bottom: 0.625rem !important;
    }

    /* Section padding - more space */
    .fi-section-content-ctn {
        padding: 1.25rem !important;
    }

    /* Grid gaps - more space between fields */
    .fi-fo-component-ctn {
        gap: 1.25rem !important;
    }

    /* Table rows - more breathing room */
    .fi-ta-row > td {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
    }
    @endif
</style>

@if($theme === 'dark')
<script>
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
</script>
@elseif($theme === 'light')
<script>
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
</script>
@endif
