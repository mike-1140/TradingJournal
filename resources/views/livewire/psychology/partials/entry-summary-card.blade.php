{{--
  Psychologie-Karte für Detail-Views.
  Variablen: $psychEntry (PsychologyEntry|null), $psychCreateUrl (string)
--}}
@php
$motivationLabels = [
    'SYSTEM'  => ['System',     'success'],
    'FOMO'    => ['FOMO',       'warning'],
    'REVENGE' => ['Revenge',    'danger'],
    'BOREDOM' => ['Langeweile', 'secondary'],
    'IMPULSE' => ['Impuls',     'dark'],
];
$errorTypeLabels = [
    'greed'      => ['Gier',       'warning'],
    'fear'       => ['Angst',      'info'],
    'hope'       => ['Hoffnung',   'primary'],
    'impulse'    => ['Impuls',     'danger'],
    'discipline' => ['Disziplin',  'success'],
];
@endphp

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-journal-check"></i> Psychologie</h6>
        @if ($psychEntry)
        <a href="{{ route('psychology.edit', $psychEntry->id) }}"
           class="btn btn-sm btn-outline-secondary py-0">
            <i class="bi bi-pencil"></i> Bearbeiten
        </a>
        @endif
    </div>

    <div class="card-body">
        @if (!$psychEntry)
        {{-- Kein Eintrag --}}
        <div class="text-center py-3">
            <i class="bi bi-journal-plus fs-3 text-muted d-block mb-2"></i>
            <p class="text-muted small mb-3">Noch kein Psychologie-Eintrag für diesen Trade.</p>
            <a href="{{ $psychCreateUrl }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle"></i> Psychologie erfassen
            </a>
        </div>
        @else
        {{-- Eintrag vorhanden --}}
        <div class="d-flex flex-wrap gap-3 align-items-start">

            {{-- Motivation --}}
            @if ($psychEntry->motivation && isset($motivationLabels[$psychEntry->motivation]))
            @php [$motLabel, $motColor] = $motivationLabels[$psychEntry->motivation]; @endphp
            <div>
                <div class="text-muted small mb-1">Motivation</div>
                <span class="badge bg-{{ $motColor }} bg-opacity-10 border border-{{ $motColor }} text-{{ $motColor }} fw-normal">
                    {{ $motLabel }}
                </span>
            </div>
            @endif

            {{-- Emotion Pre/Post --}}
            @if ($psychEntry->emotion_pre || $psychEntry->emotion_post)
            <div>
                <div class="text-muted small mb-1">Gemütszustand</div>
                <div class="d-flex align-items-center gap-1 small">
                    @if ($psychEntry->emotion_pre)
                    <span class="badge bg-secondary bg-opacity-25 text-body">vor: {{ $psychEntry->emotion_pre }}/5</span>
                    @endif
                    @if ($psychEntry->emotion_post)
                    <span class="badge bg-secondary bg-opacity-25 text-body">nach: {{ $psychEntry->emotion_post }}/5</span>
                    @endif
                </div>
            </div>
            @endif

            {{-- Compliance --}}
            @if ($psychEntry->complianceResults->count() > 0)
            @php
                $complied = $psychEntry->complianceResults->where('complied', true)->count();
                $total    = $psychEntry->complianceResults->count();
                $tempted  = $psychEntry->complianceResults->where('tempted', true)->count();
            @endphp
            <div>
                <div class="text-muted small mb-1">Regelwerk</div>
                <div class="d-flex gap-1 align-items-center">
                    @foreach ($psychEntry->complianceResults as $cr)
                    <span class="rounded-circle d-inline-block"
                          style="width:10px;height:10px;background:{{ $cr->complied ? '#198754' : '#dc3545' }}"
                          title="{{ $cr->rule->label ?? '' }}: {{ $cr->complied ? 'eingehalten' : 'verletzt' }}">
                    </span>
                    @endforeach
                    <span class="text-muted small ms-1">{{ $complied }}/{{ $total }}</span>
                    @if ($tempted > 0)
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning fw-normal ms-1 small">
                        <i class="bi bi-lightning-charge-fill"></i> {{ $tempted }}
                    </span>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- Lektion --}}
        @if ($psychEntry->lesson)
        <div class="border-top mt-3 pt-3 text-muted small fst-italic">
            <i class="bi bi-quote text-primary"></i> {{ $psychEntry->lesson }}
        </div>
        @endif
        @endif
    </div>
</div>
