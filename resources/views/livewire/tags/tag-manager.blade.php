<div>
    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-tags"></i> Tag Management</h1>
        <button wire:click="openModal" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Tag
        </button>
    </div>

    <!-- Filter -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <button wire:click="$set('filterCategory', 'all')"
                    class="btn btn-{{ $filterCategory === 'all' ? 'primary' : 'outline-primary' }} btn-sm">
                All
            </button>
            @foreach($categories as $key => $cat)
                <button wire:click="$set('filterCategory', '{{ $key }}')"
                        class="btn btn-{{ $filterCategory === $key ? 'primary' : 'outline-primary' }} btn-sm">
                    <i class="bi bi-{{ $cat['icon'] }}"></i> {{ $cat['name'] }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Tags Grid -->
    @foreach($tags as $categoryKey => $categoryTags)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-{{ $categories[$categoryKey]['icon'] ?? 'tag' }}"></i>
                    {{ $categories[$categoryKey]['name'] ?? ucfirst($categoryKey) }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($categoryTags as $tag)
                        <div class="col-md-4">
                            <div class="card {{ $tag->is_active ? '' : 'opacity-50' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge" style="background-color: {{ $tag->color }}; color: {{ $tag->getContrastColor() }};">
                                            {{ $tag->name }}
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="editTag({{ $tag->id }})"
                                                    class="btn btn-outline-primary btn-sm"
                                                    title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button wire:click="toggleActive({{ $tag->id }})"
                                                    class="btn btn-outline-{{ $tag->is_active ? 'warning' : 'success' }} btn-sm"
                                                    title="{{ $tag->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi bi-{{ $tag->is_active ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                            <button wire:click="deleteTag({{ $tag->id }})"
                                                    onclick="return confirm('Delete this tag? This cannot be undone.')"
                                                    class="btn btn-outline-danger btn-sm"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @if($tag->description)
                                        <small class="text-muted">{{ $tag->description }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    @if($tags->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No tags found. Click "New Tag" to create your first tag!
        </div>
    @endif

    <!-- Modal for Add/Edit Tag -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $isEditing ? 'Edit Tag' : 'New Tag' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="saveTag">
                            <!-- Name -->
                            <div class="mb-3">
                                <label class="form-label">Tag Name *</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       wire:model="name"
                                       placeholder="e.g., Revenge Trade">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Color -->
                            <div class="mb-3">
                                <label class="form-label">Color *</label>
                                <div class="input-group">
                                    <input type="color"
                                           class="form-control form-control-color @error('color') is-invalid @enderror"
                                           wire:model.live="color">
                                    <input type="text"
                                           class="form-control @error('color') is-invalid @enderror"
                                           wire:model="color"
                                           placeholder="#6c757d">
                                </div>
                                @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Preview:
                                    <span class="badge ms-2" style="background-color: {{ $color }};">{{ $name ?: 'Tag' }}</span>
                                </small>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select class="form-select @error('category') is-invalid @enderror"
                                        wire:model="category">
                                    <option value="psychology">🧠 Psychology</option>
                                    <option value="strategy">📊 Strategy</option>
                                    <option value="market">📈 Market Conditions</option>
                                    <option value="execution">⚡ Execution</option>
                                    <option value="mistake">⚠️ Mistakes</option>
                                    <option value="success">🏆 Success Factors</option>
                                </select>
                                @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          wire:model="description"
                                          rows="3"
                                          placeholder="What does this tag represent?"></textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="saveTag">
                            <i class="bi bi-save"></i> Save Tag
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
