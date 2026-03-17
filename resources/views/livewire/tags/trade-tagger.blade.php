<div>
    <!-- Selected Tags anzeigen -->
    @if(count($this->selectedTags) > 0)
        <div class="mb-3">
            <div class="d-flex flex-wrap gap-2">
                @foreach($this->selectedTagModels as $tag)
                    <span class="badge d-inline-flex align-items-center"
                          style="background-color: {{ $tag->color }}; color: {{ $tag->contrast_color }}; cursor: pointer; font-size: 0.9rem; padding: 0.5rem 0.75rem;"
                          wire:click="toggleTag({{ $tag->id }})"
                          title="Click to remove">
                        <i class="bi bi-{{ $categories[$tag->category]['icon'] ?? 'tag' }} me-2"></i>
                        {{ $tag->name }}
                        <i class="bi bi-x ms-2"></i>
                    </span>
                @endforeach
            </div>
        </div>
    @else
        <div class="alert alert-info alert-sm mb-3">
            <i class="bi bi-info-circle"></i> No tags assigned. Click tags below to add them.
        </div>
    @endif

    <!-- Schnellzugriff auf häufige Tags -->
    <div class="mb-3">
        <small class="text-muted text-uppercase fw-bold d-block mb-2">Quick Tags</small>
        <div class="d-flex flex-wrap gap-2">
            @foreach(['psychology', 'strategy'] as $cat)
                @if(isset($allTags[$cat]))
                    @foreach($allTags[$cat]->take(4) as $tag)
                        <button
                            wire:click="toggleTag({{ $tag->id }})"
                            class="btn btn-sm"
                            style="
                                {{ in_array($tag->id, $this->selectedTags) ? 'background-color: ' . $tag->color . '; color: ' . $tag->contrast_color . '; border-color: ' . $tag->color : 'background-color: transparent; color: ' . $tag->color . '; border: 1px solid ' . $tag->color }}
                            ">
                            {{ $tag->name }}
                        </button>
                    @endforeach
                @endif
            @endforeach
        </div>
    </div>

    <!-- Alle Kategorien (ausklappbar) -->
    <div>
        <button wire:click="$toggle('showAllCategories')" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-{{ $showAllCategories ? 'chevron-up' : 'chevron-down' }}"></i>
            {{ $showAllCategories ? 'Hide' : 'Show' }} All Categories
        </button>

        @if($showAllCategories)
            @foreach($allTags as $categoryKey => $categoryTags)
                <div class="mb-3 border-start border-3 border-{{ $categories[$categoryKey]['color'] ?? 'secondary' }} ps-3">
                    <small class="text-muted text-uppercase fw-bold d-block mb-2">
                        <i class="bi bi-{{ $categories[$categoryKey]['icon'] ?? 'tag' }}"></i>
                        {{ $categories[$categoryKey]['name'] ?? ucfirst($categoryKey) }}
                    </small>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($categoryTags as $tag)
                            <button
                                wire:click="toggleTag({{ $tag->id }})"
                                class="btn btn-sm"
                                style="
                                    {{ in_array($tag->id, $this->selectedTags) ? 'background-color: ' . $tag->color . '; color: ' . $tag->contrast_color . '; border-color: ' . $tag->color : 'background-color: transparent; color: ' . $tag->color . '; border: 1px solid ' . $tag->color }}
                                ">
                                {{ $tag->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @if($allTags->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No tags available.
            <a href="{{ route('tags.manage') }}" class="alert-link">Create tags first</a>
        </div>
    @endif
</div>
