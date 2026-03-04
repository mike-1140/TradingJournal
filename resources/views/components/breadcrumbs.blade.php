<nav {{ $attributes->merge(['class' => 'breadcrumbs mb-3']) }} aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        @if(!empty($items))
            @foreach($items as $item)
                @if($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">
                        @if(isset($item['icon']))
                            <i class="bi bi-{{ $item['icon']}} me-1"></i>
                        @endif
                        {{ $item['label'] }}
                    </li>
                @else
                    <li class="breadcrumb-item">
                        @if(isset($item['route']))
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}">
                                @if(isset($item['icon']))
                                    <i class="bi bi-{{ $item['icon']}} me-1"></i>
                                @endif
                                {{ $item['label'] }}
                            </a>
                        @elseif(isset($item['url']))
                            <a href="{{ $item['url'] }}">
                                @if(isset($item['icon']))
                                    <i class="bi bi-{{ $item['icon']}} me-1"></i>
                                @endif
                                {{ $item['label'] }}
                            </a>
                        @else
                            @if(isset($item['icon']))
                                <i class="bi bi-{{ $item['icon']}} me-1"></i>
                            @endif
                            {{ $item['label'] }}
                        @endif
                    </li>
                @endif
            @endforeach
        @else
            {{ $slot }}
        @endif
    </ol>
</nav>

@once
    @push('styles')
        <style>
            .breadcrumb {
                background-color: transparent;
                padding: 0;
            }
            .breadcrumb-item + .breadcrumb-item::before {
                content: '\F285'; /* Bootstrap Icon chevron-right */
                font-family: 'bootstrap-icons';
                color: #6c757d;
            }
            .breadcrumb-item a {
                text-decoration: none;
                color: #0d6efd;
            }
            .breadcrumb-item a:hover {
                text-decoration: underline;
            }
            .breadcrumb-item.active {
                color: #6c757d;
            }
        </style>
    @endpush
@endonce
