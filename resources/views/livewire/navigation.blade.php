<div>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" id="main-navigation">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="bi bi-graph-up"></i> TradingJournal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">

                {{-- Aktien Dropdown --}}
                @php
                    $aktienActive = request()->routeIs('trades.open.stocks*') || request()->routeIs('trades.closed.stocks*');
                @endphp
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ $aktienActive ? 'active' : '' }}"
                       href="#" id="aktienDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bar-chart-line"></i> Aktien
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="aktienDropdown">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('trades.open.stocks*') ? 'active' : '' }}"
                               href="{{ route('trades.open.stocks') }}">
                                <i class="bi bi-unlock"></i> Offene Positionen
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('trades.closed.stocks*') ? 'active' : '' }}"
                               href="{{ route('trades.closed.stocks') }}">
                                <i class="bi bi-check-circle"></i> Geschlossene Positionen
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Optionen Dropdown --}}
                @php
                    $optionenActive = request()->routeIs('trades.open.options*') || request()->routeIs('trades.closed.options*');
                @endphp
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ $optionenActive ? 'active' : '' }}"
                       href="#" id="optionenDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-layers"></i> Optionen
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="optionenDropdown">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('trades.open.options*') ? 'active' : '' }}"
                               href="{{ route('trades.open.options') }}">
                                <i class="bi bi-unlock"></i> Offene Positionen
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('trades.closed.options*') ? 'active' : '' }}"
                               href="{{ route('trades.closed.options') }}">
                                <i class="bi bi-check-circle"></i> Geschlossene Positionen
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Kampagnen --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}"
                       href="{{ route('campaigns.campaigns') }}">
                        <i class="bi bi-currency-exchange"></i> Kampagnen
                    </a>
                </li>

                {{-- Spreads Dropdown --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('spreads.*') ? 'active' : '' }}"
                       href="#" id="spreadsDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bezier2"></i> Spreads
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="spreadsDropdown">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('spreads.list') ? 'active' : '' }}"
                               href="{{ route('spreads.list') }}">
                                <i class="bi bi-list"></i> Alle Spreads
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item"
                               href="{{ route('spreads.list', ['filterStrategy' => 'Vertical Spread']) }}">
                                <i class="bi bi-bar-chart-line"></i> Vertical Spreads
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="{{ route('spreads.list', ['filterStrategy' => 'Iron Condor']) }}">
                                <i class="bi bi-diagram-3"></i> Iron Condors
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Analyse Dropdown --}}
                @php
                    $analyseActive = request()->routeIs('symbols')
                        || request()->routeIs('tags.*')
                        || request()->routeIs('stats.*')
                        || request()->routeIs('trades.raw*');
                @endphp
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ $analyseActive ? 'active' : '' }}"
                       href="#" id="analyseDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-speedometer2"></i> Analyse
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="analyseDropdown">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('symbols') ? 'active' : '' }}"
                               href="{{ route('symbols') }}">
                                <i class="bi bi-tag"></i> Symbole
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('tags.*') ? 'active' : '' }}"
                               href="{{ route('tags.manage') }}">
                                <i class="bi bi-tags"></i> Tags
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('stats.*') ? 'active' : '' }}"
                               href="{{ route('stats.dashboard') }}">
                                <i class="bi bi-graph-up"></i> Stats
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('trades.raw*') ? 'active' : '' }}"
                               href="{{ route('trades.raw') }}">
                                <i class="bi bi-database"></i> Raw Data
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                {{-- Zeitraum-Button --}}
                <li class="nav-item me-3">
                    <button wire:click="$toggle('showDatePicker')"
                            class="btn btn-outline-light btn-sm"
                            style="font-size: 0.75rem;">
                        <i class="bi bi-calendar3"></i> {{ $this->currentLabel }}
                    </button>
                </li>

                {{-- Environment Badge --}}
                <li class="nav-item">
                    <span class="navbar-text">
                        @php
                            $envColor = match($selectedEnvironment) {
                                'MONEY'       => 'success',
                                'PAPER'       => 'warning',
                                'AUTOM_MONEY' => 'danger',
                                'AUTOM_PAPER' => 'info',
                                default       => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $envColor }} fs-6">
                            <i class="bi bi-database"></i> {{ $selectedEnvironment }}
                        </span>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- Datepicker Modal --}}
@if($showDatePicker)
<div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showDatePicker', false)">
    <div class="modal-dialog modal-sm" style="margin-top: 60px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0"><i class="bi bi-calendar3"></i> Zeitraum wählen</h6>
                <button type="button" class="btn-close btn-close-white" wire:click="$set('showDatePicker', false)"></button>
            </div>
            <div class="modal-body p-3">

                {{-- Schnellauswahl --}}
                <div class="mb-3">
                    <div class="text-muted small mb-1">Schnellauswahl</div>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        @foreach(['13w' => '13W', '6m' => '6M', 'ytd' => 'YTD', 'all' => 'Alle'] as $key => $label)
                            <button type="button"
                                    wire:click="setPreset('{{ $key }}')"
                                    class="btn {{ (auth()->user()?->filter_preset === $key) ? 'btn-primary' : 'btn-outline-secondary' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <hr class="my-2">

                {{-- Jahresauswahl --}}
                <div class="mb-3">
                    <div class="text-muted small mb-1">Jahr</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($this->availableYears as $year)
                            @php
                                $user = auth()->user();
                                $isActive = !$user?->filter_preset
                                    && $user?->filter_from
                                    && \Carbon\Carbon::parse($user->filter_from)->year === $year
                                    && $user?->filter_to
                                    && \Carbon\Carbon::parse($user->filter_to)->year === $year;
                            @endphp
                            <button type="button"
                                    wire:click="setYear({{ $year }})"
                                    class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    style="min-width: 52px;">
                                {{ $year }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <hr class="my-2">

                {{-- Benutzerdefiniert --}}
                <div>
                    <div class="text-muted small mb-1">Benutzerdefiniert</div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Von</label>
                        <input type="date" wire:model="customFrom" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Bis</label>
                        <input type="date" wire:model="customTo" class="form-control form-control-sm">
                    </div>
                    <button type="button"
                            wire:click="applyCustomRange"
                            class="btn btn-primary btn-sm w-100">
                        Anwenden
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
@endif
</div>
