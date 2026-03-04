<div {{ $attributes->merge(['class' => 'empty-state text-center py-5']) }}>
    <i class="bi bi-{{ $icon }} empty-state-icon text-muted"></i>
    <h4 class="mt-3 mb-2">{{ $title }}</h4>
    @if($message)
        <p class="text-muted">{{ $message }}</p>
    @endif

    @if($actionText && $actionRoute)
        <a href="{{ route($actionRoute) }}" class="btn btn-primary mt-3">
            {{ $actionText }}
        </a>
    @endif

    @isset($action)
        <div class="mt-3">
            {{ $action }}
        </div>
    @endisset
</div>

@once
    @push('styles')
        <style>
            .empty-state-icon {
                font-size: 4rem;
                opacity: 0.5;
            }
        </style>
    @endpush
@endonce
