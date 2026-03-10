@props(['startDate' => null, 'endDate' => null])

<div class="text-muted small mt-1">
    <i class="bi bi-calendar3"></i>
    @if($startDate && $endDate)
        Zeitraum: {{ $startDate->format('d.m.Y') }} – {{ $endDate->format('d.m.Y') }}
    @elseif($startDate)
        Daten seit dem {{ $startDate->format('d.m.Y') }}
    @else
        Alle historischen Daten
    @endif
</div>
