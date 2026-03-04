<?php

namespace App\Livewire\Notes;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use App\Models\TradeNote;
use App\Traits\ManagesEnvironment;

class NoteManager extends Component
{
    use ManagesEnvironment;

    #[Locked]
    public $tradeId = null;

    #[Locked]
    public $opt_strategyID = null;

    #[Locked]
    public $tradeType;

    // Form fields
    public $noteId = null;
    public $noteType = 'general';
    public $title = '';
    public $content = '';

    // UI state
    public $showModal = false;
    public $isEditing = false;

    // ❌ ENTFERNE DIESE PROPERTY:
    // public $notes;

    protected $rules = [
        'noteType' => 'required|in:entry_plan,exit_reason,lesson_learned,general',
        'title' => 'nullable|max:200',
        'content' => 'required|min:3',
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
        // Kein loadNotes() mehr nötig!
    }

    /**
     * ✅ Computed Property - wird bei jedem Render neu geladen
     * Keine Hydration-Probleme, weil es nicht gespeichert wird!
     */
    #[Computed]
    public function notes()
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeNote::getConnectionForEnvironment($this->selectedEnvironment);

        $query = TradeNote::on($connection)
            ->where('environment', $this->selectedEnvironment);

        if ($this->tradeType === 'spread') {
            $query->where('opt_strategyID', $this->opt_strategyID)
                ->where('trade_type', 'spread');
        } else {
            $query->where('trade_id', $this->tradeId)
                ->where('trade_type', $this->tradeType);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editNote($noteId)
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeNote::getConnectionForEnvironment($this->selectedEnvironment);

        $note = TradeNote::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->findOrFail($noteId);

        $this->noteId = $note->id;
        $this->noteType = $note->note_type;
        $this->title = $note->title;
        $this->content = $note->content;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function saveNote()
    {
        $this->validate();

        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeNote::getConnectionForEnvironment($this->selectedEnvironment);

        if ($this->isEditing) {
            $note = TradeNote::on($connection)->findOrFail($this->noteId);
            $note->update([
                'note_type' => $this->noteType,
                'title' => $this->title,
                'content' => $this->content,
            ]);

            session()->flash('message', 'Note updated successfully!');
        } else {
            TradeNote::on($connection)->create([
                'trade_id' => $this->tradeId,
                'opt_strategyID' => $this->opt_strategyID,
                'trade_type' => $this->tradeType,
                'note_type' => $this->noteType,
                'title' => $this->title,
                'content' => $this->content,
                'environment' => $this->selectedEnvironment,
            ]);

            session()->flash('message', 'Note created successfully!');
        }

        // Kein loadNotes() mehr nötig - computed property lädt automatisch!
        $this->closeModal();
    }

    public function deleteNote($noteId)
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeNote::getConnectionForEnvironment($this->selectedEnvironment);

        $note = TradeNote::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->findOrFail($noteId);

        $note->delete();

        session()->flash('message', 'Note deleted successfully!');
        // Kein loadNotes() mehr nötig!
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->noteId = null;
        $this->noteType = 'general';
        $this->title = '';
        $this->content = '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.notes.note-manager');
    }
}
