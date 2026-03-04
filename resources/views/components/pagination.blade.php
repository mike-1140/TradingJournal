@php
    // Standardwerte setzen, falls die Variablen nicht vom Controller kommen
    $showInfo = $showInfo ?? true;
    $showPerPage = $showPerPage ?? false;
    $perPageOptions = $perPageOptions ?? [10, 25, 50];
@endphp

@if($paginator->hasPages())
    <div class="pagination-wrapper d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
        {{-- Info Text --}}
        @if($showInfo)
            <div class="pagination-info text-muted">
                Showing
                <strong>{{ $paginator->firstItem() }}</strong> to
                <strong>{{ $paginator->lastItem() }}</strong> of
                <strong>{{ $paginator->total() }}</strong> results
            </div>
        @endif

        <div class="d-flex gap-3 align-items-center">
            {{-- Per Page Selector --}}
            @if($showPerPage)
                <div class="pagination-per-page d-flex align-items-center gap-2">
                    <label class="text-muted mb-0 small">Per page:</label>
                    <select class="form-select form-select-sm" style="width: auto;" wire:model.live="perPage">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            {{-- Pagination Links --}}
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    {{-- First Page Button --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link"><i class="bi bi-chevron-double-left"></i></span>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button" class="page-link"
                                    wire:click="gotoPage(1, '{{ $paginator->getPageName() }}')"
                                    title="Erste Seite">
                                <i class="bi bi-chevron-double-left"></i>
                            </button>
                        </li>
                    @endif

                    {{-- Previous Button --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button" class="page-link"
                                    wire:click="gotoPage({{ $paginator->currentPage() - 1 }}, '{{ $paginator->getPageName() }}')"
                                    rel="prev">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                        </li>
                    @endif

                    {{-- Page Numbers Bereich (mit is_string Check) --}}
                    @foreach($elements as $element)
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach($element as $page => $url)
                                <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}" wire:key="page-{{ $page }}">
                                    <button type="button" class="page-link" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')">
                                        {{ $page }}
                                    </button>
                                </li>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Button --}}
                    @if($paginator->hasMorePages())
                        <li class="page-item">
                            <button type="button" class="page-link" wire:click="nextPage('{{ $paginator->getPageName() }}')">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </li>
                    @else
                        <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>
                    @endif

                    {{-- Last Page Button --}}
                    @if($paginator->hasMorePages())
                        <li class="page-item">
                            <button type="button" class="page-link"
                                    wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $paginator->getPageName() }}')"
                                    title="Letzte Seite">
                                <i class="bi bi-chevron-double-right"></i>
                            </button>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link"><i class="bi bi-chevron-double-right"></i></span>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
@endif

@once
    @push('styles')
        <style>
            .pagination-wrapper {
                padding: 1rem 0;
            }
            .pagination-sm .page-link {
                padding: 0.375rem 0.75rem;
            }
        </style>
    @endpush
@endonce
