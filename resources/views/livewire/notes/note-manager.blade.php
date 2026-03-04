<div>
    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header with Add Button -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-journal-text"></i> Trade Notes
            @if($this->notes->count() > 0)
                <span class="badge bg-primary">{{ $this->notes->count() }}</span>
            @endif
        </h5>
        <button wire:click="openModal" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Add Note
        </button>
    </div>

    <!-- Notes List -->
    @if($this->notes->count() > 0)
        <div class="row g-3">
            @foreach($this->notes as $note)
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <!-- Note Type Badge -->
                                    <span class="badge {{ $note->getNoteTypeBadgeClass() }} mb-2">
                                    {{ $note->getNoteTypeLabel() }}
                                </span>

                                    <!-- Title -->
                                    @if($note->title)
                                        <h6 class="mb-2">{{ $note->title }}</h6>
                                    @endif

                                    <!-- Content -->
                                    <p class="mb-2" style="white-space: pre-wrap;">{{ $note->content }}</p>

                                    <!-- Timestamp -->
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        {{ $note->created_at->format('d.m.Y H:i') }}
                                        @if($note->created_at->ne($note->updated_at))
                                            <span class="ms-2">(edited {{ $note->updated_at->diffForHumans() }})</span>
                                        @endif
                                    </small>
                                </div>

                                <!-- Actions -->
                                <div class="ms-3">
                                    <button wire:click="editNote({{ $note->id }})"
                                            class="btn btn-sm btn-outline-primary me-1"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button wire:click="deleteNote({{ $note->id }})"
                                            onclick="return confirm('Are you sure you want to delete this note?')"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No notes yet. Click "Add Note" to create your first note.
        </div>
    @endif

    <!-- Modal bleibt gleich -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $isEditing ? 'Edit Note' : 'Add New Note' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="saveNote">
                            <!-- Note Type -->
                            <div class="mb-3">
                                <label class="form-label">Note Type</label>
                                <select class="form-select @error('noteType') is-invalid @enderror"
                                        wire:model="noteType">
                                    <option value="general">📝 General Note</option>
                                    <option value="entry_plan">📋 Entry Plan</option>
                                    <option value="exit_reason">🚪 Exit Reason</option>
                                    <option value="lesson_learned">🎓 Lesson Learned</option>
                                </select>
                                @error('noteType')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Title (Optional) -->
                            <div class="mb-3">
                                <label class="form-label">Title (Optional)</label>
                                <input type="text"
                                       class="form-control @error('title') is-invalid @enderror"
                                       wire:model="title"
                                       placeholder="e.g., Why I entered this trade">
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Content -->
                            <div class="mb-3">
                                <label class="form-label">Content *</label>
                                <textarea class="form-control @error('content') is-invalid @enderror"
                                          wire:model="content"
                                          rows="8"
                                          placeholder="Write your note here..."></textarea>
                                @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    Tip: Include your reasoning, emotions, market conditions, and what you learned.
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveNote">
                            <i class="bi bi-save"></i> Save Note
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
