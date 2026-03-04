<div {{ $attributes->merge(['class' => "alert alert-{$type} d-flex align-items-center" . ($dismissible ? ' alert-dismissible fade show' : '')]) }}
     role="alert">
    <i class="bi bi-{{ $icon }} me-2"></i>
    <div class="flex-grow-1">
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
