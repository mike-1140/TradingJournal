<div {{ $attributes->merge(['class' => 'row mb-2']) }}>
    <div class="col-md-4">
        <strong>
            @if($icon)
                <i class="bi bi-{{ $icon }} me-1"></i>
            @endif
            {{ $label }}:
        </strong>
    </div>
    <div class="col-md-8">
        @if($badge)
            <span class="badge bg-{{ $badgeColor }}">{{ $value }}</span>
        @else
            {{ $value ?? $slot }}
        @endif
    </div>
</div>
