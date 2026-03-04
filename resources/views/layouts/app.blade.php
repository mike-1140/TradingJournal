<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TradingJournal' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<!-- Livewire Navigation Component -->
@livewire('navigation')

<!-- Content -->
<main class="container-fluid mt-4">
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>
