<div {{ $attributes->merge(['class' => 'page-header mb-4']) }}>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                @if($icon)
                    <i class="bi bi-{{ $icon }}"></i>
                @endif
                {{ $title }}
            </h1>
            @if($subtitle)
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="d-flex gap-2">
            @if($backUrl || $backRoute)
                <a href="{{ $backUrl ?: route($backRoute) }}"
                   onclick="if (window.history.length > 1) { history.back(); return false; }"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> {{ $backText }}
                </a>
            @endif

            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .page-header h1 {
                font-size: 2rem;
                font-weight: 600;
            }
        </style>
    @endpush
@endonce
