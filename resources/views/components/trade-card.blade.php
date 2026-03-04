<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || isset($header))
        <div class="card-header {{ $headerClass }}">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    @if($icon)
                        <i class="bi bi-{{ $icon }}"></i>
                    @endif
                    {{ $title }}
                    {{ $header ?? '' }}
                </h5>

                @if($collapsible)
                    <button class="btn btn-sm btn-link text-decoration-none"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $cardId }}">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                @endif

                @isset($actions)
                    <div class="card-actions">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        </div>
    @endif

    <div class="card-body {{ $bodyClass }} {{ $collapsible ? 'collapse' : '' }} {{ $collapsed ? '' : 'show' }}"
         id="{{ $cardId }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endisset
</div>
