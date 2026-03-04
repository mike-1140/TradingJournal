<span class="badge badge-tag badge-tag-{{ $size }} {{ $clickable ? 'badge-clickable' : '' }}"
      style="background-color: {{ $tag->color }}; color: {{ $tag->contrast_color }};"
      title="{{ $tag->description ?? $tag->name }}"
      {{ $attributes }}>
    @if($showIcon && isset($tag->category))
        <i class="bi bi-{{ config('tags.category_icons.' . $tag->category, 'tag') }} me-1"></i>
    @endif
    {{ $tag->name }}
</span>

@once
    @push('styles')
        <style>
            .badge-tag {
                display: inline-flex;
                align-items: center;
                border-radius: 0.375rem;
                font-weight: 500;
                white-space: nowrap;
            }
            .badge-tag-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            .badge-tag-md {
                font-size: 0.875rem;
                padding: 0.375rem 0.625rem;
            }
            .badge-tag-lg {
                font-size: 1rem;
                padding: 0.5rem 0.875rem;
            }
            .badge-clickable {
                cursor: pointer;
                transition: opacity 0.2s, transform 0.1s;
            }
            .badge-clickable:hover {
                opacity: 0.85;
                transform: translateY(-1px);
            }
            .badge-clickable:active {
                transform: translateY(0);
            }
        </style>
    @endpush
@endonce
