<?php

namespace App\Livewire\Tags;

use Livewire\Component;
use App\Models\Tag;
use App\Models\TradeTagPivot;
use Illuminate\Support\Str;

class TagManager extends Component
{
    // Form fields
    public $tagId = null;
    public $name = '';
    public $color = '#6c757d';
    public $category = 'psychology';
    public $description = '';

    // UI state
    public $showModal = false;
    public $isEditing = false;
    public $filterCategory = 'all';

    protected $rules = [
        'name' => 'required|min:2|max:100',
        'color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
        'category' => 'required|in:psychology,strategy,market,execution,mistake,success',
        'description' => 'nullable|max:500',
    ];

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editTag($tagId)
    {
        $tag = Tag::findOrFail($tagId);

        $this->tagId = $tag->id;
        $this->name = $tag->name;
        $this->color = $tag->color;
        $this->category = $tag->category;
        $this->description = $tag->description;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function saveTag()
    {
        $this->validate();

        if ($this->isEditing) {
            $tag = Tag::findOrFail($this->tagId);
            $tag->update([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'color' => $this->color,
                'category' => $this->category,
                'description' => $this->description,
            ]);

            session()->flash('message', 'Tag updated successfully!');
        } else {
            Tag::create([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'color' => $this->color,
                'category' => $this->category,
                'description' => $this->description,
                'is_active' => true,
            ]);

            session()->flash('message', 'Tag created successfully!');
        }

        $this->closeModal();
    }

    public function toggleActive($tagId)
    {
        $tag = Tag::findOrFail($tagId);
        $tag->update(['is_active' => !$tag->is_active]);

        session()->flash('message', 'Tag status updated!');
    }

    public function deleteTag($tagId)
    {
        $tag = Tag::findOrFail($tagId);

        // Prüfe ob Tag noch in irgendeiner Environment verwendet wird
        $environments = ['MONEY', 'PAPER', 'TEST', 'AUTOM_MONEY', 'AUTOM_PAPER'];
        $totalUsage = 0;

        foreach ($environments as $env) {
            $connection = TradeTagPivot::getConnectionForEnvironment($env);
            $totalUsage += TradeTagPivot::on($connection)
                ->where('tag_id', $tagId)
                ->count();
        }

        if ($totalUsage > 0) {
            session()->flash('error', "Tag \"{$tag->name}\" wird noch in {$totalUsage} Trade(s) verwendet und kann nicht gelöscht werden.");
            return;
        }

        $tag->delete();
        session()->flash('message', 'Tag gelöscht.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->tagId = null;
        $this->name = '';
        $this->color = '#6c757d';
        $this->category = 'psychology';
        $this->description = '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function render()
    {
        $query = Tag::query()->orderBy('category')->orderBy('name');

        if ($this->filterCategory !== 'all') {
            $query->where('category', $this->filterCategory);
        }

        $tags = $query->get()->groupBy('category');

        $categories = [
            'psychology' => ['name' => 'Psychology', 'icon' => 'brain'],
            'strategy' => ['name' => 'Strategy', 'icon' => 'diagram-3'],
            'market' => ['name' => 'Market Conditions', 'icon' => 'graph-up'],
            'execution' => ['name' => 'Execution', 'icon' => 'lightning'],
            'mistake' => ['name' => 'Mistakes', 'icon' => 'exclamation-triangle'],
            'success' => ['name' => 'Success Factors', 'icon' => 'trophy'],
        ];

        return view('livewire.tags.tag-manager', [
            'tags' => $tags,
            'categories' => $categories,
        ]);
    }
}
