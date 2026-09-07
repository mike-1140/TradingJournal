<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Campaign;
use App\Models\TradeClose;
use App\Models\TradePlan;
use App\Helpers\TradingHelper;
use App\Traits\FilterableByTime;

class Navigation extends Component
{
    use FilterableByTime;

    public $selectedEnvironment;
    public bool $showDatePicker = false;
    public string $customFrom = '';
    public string $customTo = '';

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount()
    {
        $this->selectedEnvironment = session('trading_environment', 'PAPER');
    }

    #[Computed]
    public function currentLabel(): string
    {
        $user   = auth()->user();
        $preset = $user?->filter_preset;

        return match($preset) {
            'all'  => 'Alle Daten',
            '13w'  => '13 Wochen',
            '6m'   => '6 Monate',
            'ytd'  => 'YTD ' . now()->year,
            default => $this->buildCustomLabel($user),
        };
    }

    /**
     * Liest die vom Update-Skript geschriebene storage/app/version.json aus (Tag + Kurz-Hash
     * des letzten `git pull`) - kein Git-Aufruf zur Laufzeit, funktioniert daher auch ohne
     * installiertes Git auf dem Server.
     */
    #[Computed]
    public function buildVersion(): ?array
    {
        $path = storage_path('app/version.json');
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data) || empty($data['hash'])) {
            return null;
        }

        return $data;
    }

    #[Computed]
    public function availableYears(): array
    {
        try {
            $connection = TradingHelper::getConnection($this->selectedEnvironment);
            $minYear = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->selectRaw('YEAR(MIN(dateTimeClose)) as min_year')
                ->value('min_year');

            if (!$minYear) {
                return [now()->year];
            }

            return range((int) $minYear, now()->year);
        } catch (\Exception $e) {
            return [now()->year];
        }
    }

    /**
     * Anzahl Kampagnen ohne Log-Eintrag (offene + geschlossene der letzten 30 Tage) —
     * sanfte Erinnerung als Sidebar-Badge, siehe LogList::render() für dieselbe Logik.
     */
    #[Computed]
    public function unloggedCount(): int
    {
        try {
            $connection = TradingHelper::getConnection($this->selectedEnvironment);

            $linkedCampaignIds = TradePlan::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('campaign_id')
                ->pluck('campaign_id');

            $cutoff = Carbon::now()->subDays(30);

            return Campaign::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotIn('id', $linkedCampaignIds)
                ->where(function ($q) use ($cutoff) {
                    $q->where('status', '!=', 'CLOSED')
                        ->orWhere('start_date', '>=', $cutoff);
                })
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function setPreset(string $preset): void
    {
        auth()->user()?->update([
            'filter_preset' => $preset,
            'filter_from'   => null,
            'filter_to'     => null,
        ]);

        $this->showDatePicker = false;
        $this->unsetComputedProperties();
        $this->dispatch('time-range-changed');
    }

    public function setYear(int $year): void
    {
        auth()->user()?->update([
            'filter_preset' => null,
            'filter_from'   => Carbon::create($year, 1, 1)->toDateString(),
            'filter_to'     => Carbon::create($year, 12, 31)->toDateString(),
        ]);

        $this->showDatePicker = false;
        $this->unsetComputedProperties();
        $this->dispatch('time-range-changed');
    }

    public function applyCustomRange(): void
    {
        if (!$this->customFrom || !$this->customTo) {
            return;
        }

        auth()->user()?->update([
            'filter_preset' => null,
            'filter_from'   => $this->customFrom,
            'filter_to'     => $this->customTo,
        ]);

        $this->showDatePicker = false;
        $this->unsetComputedProperties();
        $this->dispatch('time-range-changed');
    }

    public function render()
    {
        $this->selectedEnvironment = session('trading_environment', 'PAPER');
        return view('livewire.navigation');
    }

    private function buildCustomLabel($user): string
    {
        if (!$user?->filter_from) {
            return 'YTD ' . now()->year;
        }

        $from = Carbon::parse($user->filter_from);
        $to   = $user->filter_to ? Carbon::parse($user->filter_to) : null;

        if ($to && $from->day === 1 && $from->month === 1
            && $to->day === 31 && $to->month === 12
            && $from->year === $to->year) {
            return (string) $from->year;
        }

        $label = $from->format('d.m.y');
        if ($to) {
            $label .= '–' . $to->format('d.m.y');
        }

        return $label;
    }

    private function unsetComputedProperties(): void
    {
        unset($this->currentLabel);
        unset($this->startDate);
        unset($this->endDate);
    }
}
