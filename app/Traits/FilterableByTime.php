<?php
namespace App\Traits;

use Carbon\Carbon;
use Livewire\Attributes\Computed;

trait FilterableByTime
{
    public function mountFilterableByTime()
    {
        // Kein lokaler State nötig — Werte kommen direkt aus auth()->user()
    }

    #[Computed]
    public function startDate(): ?Carbon
    {
        $user = auth()->user();
        $preset = $user?->filter_preset;

        return match($preset) {
            'all'  => null,
            '13w'  => now()->subWeeks(13)->startOfDay(),
            '6m'   => now()->subMonths(6)->startOfDay(),
            'ytd'  => now()->startOfYear(),
            default => $user?->filter_from
                ? Carbon::parse($user->filter_from)->startOfDay()
                : null,
        };
    }

    #[Computed]
    public function endDate(): ?Carbon
    {
        $user = auth()->user();
        $preset = $user?->filter_preset;

        if ($preset) {
            return null; // Presets haben kein Enddatum
        }

        return $user?->filter_to
            ? Carbon::parse($user->filter_to)->endOfDay()
            : null;
    }

    public function applyTimeFilter($query, $dateColumn = 'dateTimeClose')
    {
        $start = $this->startDate;
        $end   = $this->endDate;

        if ($start && $end) {
            return $query->whereBetween($dateColumn, [$start, $end]);
        }

        if ($start) {
            return $query->where($dateColumn, '>=', $start);
        }

        return $query;
    }
}
