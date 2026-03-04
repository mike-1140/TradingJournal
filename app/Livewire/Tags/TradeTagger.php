<?php

namespace App\Livewire\Tags;

use Livewire\Component;
use App\Models\Tag;
use App\Models\TradeTagPivot;
use App\Models\TradeClose;
use App\Traits\ManagesEnvironment;

class TradeTagger extends Component
{
    use ManagesEnvironment;

    public $tradeId;
    public $tradeType; // 'open', 'closed', 'spread', 'fx'
    public $searchQuery = '';
    public $showAllCategories = false;

    public function mount()
    {
        $this->mountManagesEnvironment();

        // ✅ Normalisiere tradeId auf tradeIDOpen
        if ($this->tradeType === 'closed') {
            // Wenn closed: Hole tradeIDOpen aus dem Trade
            $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);
            $trade = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->where('tradeIDOpen', $this->tradeId)
                ->first();

            if ($trade) {
                $this->tradeId = $trade->tradeIDOpen; // ✅ Verwende Open-ID!
            }
        }
        // Bei 'open' ist tradeId bereits tradeID (Open-ID)
    }

    /**
     * Lade die aktuell ausgewählten Tags für diesen Trade
     */

    public function getSelectedTagsProperty()
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeTagPivot::getConnectionForEnvironment($this->selectedEnvironment);

        // ✅ WICHTIG: tradeId ist jetzt immer tradeIDOpen!
        return TradeTagPivot::on($connection)
            ->where('trade_id', $this->tradeId)  // tradeIDOpen
            ->where('trade_type', $this->tradeType)
            ->where('environment', $this->selectedEnvironment)
            ->pluck('tag_id')
            ->toArray();
    }

    /**
     * Toggle Tag für diesen Trade
     */
    public function toggleTag($tagId)
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeTagPivot::getConnectionForEnvironment($this->selectedEnvironment);

        $exists = TradeTagPivot::on($connection)
            ->where('trade_id', $this->tradeId)  // tradeIDOpen
            ->where('trade_type', $this->tradeType)
            ->where('tag_id', $tagId)
            ->where('environment', $this->selectedEnvironment)
            ->exists();

        if ($exists) {
            // Remove tag
            TradeTagPivot::on($connection)
                ->where('trade_id', $this->tradeId)
                ->where('trade_type', $this->tradeType)
                ->where('tag_id', $tagId)
                ->where('environment', $this->selectedEnvironment)
                ->delete();
        } else {
            // Add tag
            TradeTagPivot::on($connection)->create([
                'tag_id' => $tagId,
                'trade_id' => $this->tradeId,  // tradeIDOpen
                'trade_type' => $this->tradeType,
                'environment' => $this->selectedEnvironment,
                'tagged_at' => now(),
            ]);
        }

        $this->dispatch('tagsUpdated');
    }

    public function render()
    {
        $query = Tag::active()->orderBy('category')->orderBy('name');

        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        $allTags = $query->get()->groupBy('category');

        $categories = [
            'psychology' => ['name' => 'Psychology', 'icon' => 'brain', 'color' => 'danger'],
            'strategy' => ['name' => 'Strategy', 'icon' => 'diagram-3', 'color' => 'primary'],
            'market' => ['name' => 'Market', 'icon' => 'graph-up', 'color' => 'info'],
            'execution' => ['name' => 'Execution', 'icon' => 'lightning', 'color' => 'warning'],
            'mistake' => ['name' => 'Mistakes', 'icon' => 'exclamation-triangle', 'color' => 'danger'],
            'success' => ['name' => 'Success', 'icon' => 'trophy', 'color' => 'success'],
        ];

        return view('livewire.tags.trade-tagger', [
            'allTags' => $allTags,
            'categories' => $categories,
        ]);
    }
}
