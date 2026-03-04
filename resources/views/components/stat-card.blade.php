<div {{ $attributes->merge(['class' => 'card stat-card']) }}>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="text-muted text-uppercase small mb-1">{{ $title }}</div>
                <div class="stat-value text-{{ $color }} mb-0">
                    {{ $value }}
                </div>
                @if($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>

            @if($icon)
                <div class="stat-icon bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
                    <i class="bi bi-{{ $icon }}"></i>
                </div>
            @endif
        </div>

        @if($trend)
            <div class="stat-trend mt-2">
                @if($trend === 'up')
                    <span class="text-success">
                    <i class="bi bi-arrow-up"></i>
                    {{ $trendValue }}
                </span>
                @elseif($trend === 'down')
                    <span class="text-danger">
                    <i class="bi bi-arrow-down"></i>
                    {{ $trendValue }}
                </span>
                @else
                    <span class="text-muted">
                    <i class="bi bi-dash"></i>
                    {{ $trendValue }}
                </span>
                @endif
            </div>
        @endif
    </div>
</div>

@once
    @push('styles')
        <style>
            .stat-card {
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .stat-value {
                font-size: 1.75rem;
                font-weight: 700;
                line-height: 1.2;
            }
            .stat-icon {
                width: 3rem;
                height: 3rem;
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
            }
            .stat-trend {
                font-size: 0.875rem;
                font-weight: 500;
            }
        </style>
    @endpush
@endonce
