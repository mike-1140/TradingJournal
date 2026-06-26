<div>
    <x-page-header title="Trade-Psychologie" icon="journal-check">
        <x-slot:actions>
            <a href="{{ route('psychology.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Neuer Eintrag
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info">
        <i class="bi bi-hourglass-split"></i>
        Liste wird in Phase 5 implementiert.
        <a href="{{ route('psychology.create') }}" class="alert-link">Neuen Eintrag erfassen</a>
    </div>
</div>
