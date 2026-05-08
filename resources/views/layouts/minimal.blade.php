<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Log — Neuer Eintrag</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-light">

{{-- Kontext-Leiste: zeigt aktives Environment und Prefix --}}
@isset($quickEnv)
@php
    $envColor = match($quickEnv) {
        'MONEY'       => '#198754',
        'TEST'        => '#6c757d',
        'AUTOM_MONEY' => '#dc3545',
        'AUTOM_PAPER' => '#0dcaf0',
        default       => '#ffc107',  // PAPER = gelb
    };
@endphp
<div style="background: {{ $envColor }}; color: white; padding: 6px 16px; font-size: 13px; font-weight: 600; letter-spacing: 0.3px;">
    <span style="opacity: 0.85;">Trade Log Quick-Entry</span>
    &nbsp;·&nbsp;
    <span>{{ $quickEnv }}</span>
    @if(!empty($quickPrefix))
        &nbsp;·&nbsp;
        <span style="font-family: monospace;">{{ $quickPrefix }}</span>
    @endif
    <a href="/log" style="color: white; opacity: 0.7; font-size: 11px; font-weight: 400; float: right; margin-top: 2px;">
        → Zum Journal
    </a>
</div>
@endisset

<div class="container py-4" style="max-width: 960px;">
    {{ $slot }}
</div>

@livewireScripts
</body>
</html>
