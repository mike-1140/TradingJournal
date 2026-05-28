<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TradingJournal' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>
<body>

@livewire('navigation')

<div id="content-wrapper" class="content-wrapper">

    {{-- DB-Fehler aus Session (nach Redirect oder Reload sichtbar) --}}
    @if(session('db_error'))
        <div class="px-4 pt-3">
            <x-alert-message type="danger" icon="exclamation-triangle-fill" :dismissible="true">
                <strong>Datenbankfehler:</strong> {!! session('db_error') !!}
            </x-alert-message>
        </div>
    @endif

    <main class="container-fluid p-4">
        {{ $slot }}
    </main>
</div>

{{-- Toast-Container für Livewire-Laufzeitfehler --}}
<div id="toast-container"
     class="toast-container position-fixed bottom-0 end-0 p-3"
     style="z-index: 9999;">
</div>

@livewireScripts

<script>
    (function () {
        function applySidebarState() {
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            document.getElementById('sidebar')?.classList.toggle('collapsed', collapsed);
            document.getElementById('topbar')?.classList.toggle('sidebar-collapsed', collapsed);
            document.getElementById('content-wrapper')?.classList.toggle('sidebar-collapsed', collapsed);
        }

        window.toggleSidebar = function () {
            const collapsed = document.getElementById('sidebar')?.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', String(!collapsed));
            applySidebarState();
        };

        applySidebarState();
        document.addEventListener('livewire:navigated', applySidebarState);
    })();
</script>

</body>
</html>
