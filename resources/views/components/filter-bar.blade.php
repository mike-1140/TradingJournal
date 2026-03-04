<div {{ $attributes->merge(['class' => 'card filter-bar mb-4']) }}
     x-data="{
        isOpen: @js(!$collapsed),
        toggle() {
            this.isOpen = !this.isOpen;
        }
     }">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> {{ $title }}
            </h6>

            <div class="d-flex gap-2">
                @if($showReset)
                    @isset($reset)
                        {{ $reset }}
                    @else
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </button>
                    @endisset
                @endif

                @if($collapsible)
                    <button
                        class="btn btn-sm btn-link text-decoration-none p-0"
                        type="button"
                        @click="toggle()"
                        :aria-expanded="isOpen">
                        <i class="bi" :class="isOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ✅ Alpine.js Transition für smooth open/close --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="card-body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer bg-light" x-show="isOpen">
            {{ $footer }}
        </div>
    @endisset
</div>

@once
    @push('styles')
        <style>
            .filter-bar .card-header {
                border-bottom: 2px solid #e9ecef;
            }
            .filter-bar .form-label {
                font-weight: 600;
                font-size: 0.875rem;
                margin-bottom: 0.375rem;
            }
        </style>
    @endpush
@endonce
