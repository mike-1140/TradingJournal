<div>

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <aside id="sidebar" class="sidebar">

        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <i class="bi bi-graph-up-arrow"></i>
            <span class="sidebar-text">TradingJournal</span>
        </a>

        <button class="sidebar-collapse-btn" onclick="toggleSidebar()" title="Sidebar ein-/ausklappen">
            <i class="bi bi-chevron-double-left"></i>
        </button>

        <nav class="sidebar-nav">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>

            {{-- Aktien --}}
            @php $aktienOpen = request()->routeIs('trades.*.stocks*'); @endphp
            <a class="sidebar-link sidebar-toggle-btn {{ $aktienOpen ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#nav-aktien"
               aria-expanded="{{ $aktienOpen ? 'true' : 'false' }}">
                <i class="bi bi-bar-chart-line"></i>
                <span class="sidebar-text">Aktien</span>
                <i class="bi bi-chevron-down sidebar-arrow"></i>
            </a>
            <div id="nav-aktien" class="collapse {{ $aktienOpen ? 'show' : '' }}">
                <div class="sidebar-subnav">
                    <a href="{{ route('trades.open.stocks') }}"
                       class="sidebar-link {{ request()->routeIs('trades.open.stocks*') ? 'active' : '' }}">
                        <i class="bi bi-unlock"></i>
                        <span class="sidebar-text">Offene Positionen</span>
                    </a>
                    <a href="{{ route('trades.closed.stocks') }}"
                       class="sidebar-link {{ request()->routeIs('trades.closed.stocks*') ? 'active' : '' }}">
                        <i class="bi bi-check-circle"></i>
                        <span class="sidebar-text">Geschlossene Positionen</span>
                    </a>
                </div>
            </div>

            {{-- Optionen --}}
            @php $optionenOpen = request()->routeIs('trades.*.options*'); @endphp
            <a class="sidebar-link sidebar-toggle-btn {{ $optionenOpen ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#nav-optionen"
               aria-expanded="{{ $optionenOpen ? 'true' : 'false' }}">
                <i class="bi bi-layers"></i>
                <span class="sidebar-text">Optionen</span>
                <i class="bi bi-chevron-down sidebar-arrow"></i>
            </a>
            <div id="nav-optionen" class="collapse {{ $optionenOpen ? 'show' : '' }}">
                <div class="sidebar-subnav">
                    <a href="{{ route('trades.open.options') }}"
                       class="sidebar-link {{ request()->routeIs('trades.open.options*') ? 'active' : '' }}">
                        <i class="bi bi-unlock"></i>
                        <span class="sidebar-text">Offene Positionen</span>
                    </a>
                    <a href="{{ route('trades.closed.options') }}"
                       class="sidebar-link {{ request()->routeIs('trades.closed.options*') ? 'active' : '' }}">
                        <i class="bi bi-check-circle"></i>
                        <span class="sidebar-text">Geschlossene Positionen</span>
                    </a>
                </div>
            </div>

            {{-- Kampagnen --}}
            <a href="{{ route('campaigns.campaigns') }}"
               class="sidebar-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                <i class="bi bi-currency-exchange"></i>
                <span class="sidebar-text">Kampagnen</span>
            </a>

            {{-- Spreads --}}
            @php
                $spreadsOpen      = request()->routeIs('spreads.*');
                $qStatus          = request()->query('filterStatus');
                $qGroup           = request()->query('filterStrategyGroup');
                $qStrategy        = request()->query('filterStrategy');
                $isOpenSpreads    = request()->routeIs('spreads.list') && $qStatus === 'open'    && !$qGroup && !$qStrategy;
                $isClosedSpreads  = request()->routeIs('spreads.list') && !$qStatus              && !$qGroup && !$qStrategy;
                $isVerticals      = request()->routeIs('spreads.list') && $qGroup === 'verticals';
                $isIronCondors    = request()->routeIs('spreads.list') && $qStrategy === 'Iron Condor';
            @endphp
            <a class="sidebar-link sidebar-toggle-btn {{ $spreadsOpen ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#nav-spreads"
               aria-expanded="{{ $spreadsOpen ? 'true' : 'false' }}">
                <i class="bi bi-bezier2"></i>
                <span class="sidebar-text">Spreads</span>
                <i class="bi bi-chevron-down sidebar-arrow"></i>
            </a>
            <div id="nav-spreads" class="collapse {{ $spreadsOpen ? 'show' : '' }}">
                <div class="sidebar-subnav">
                    <a href="{{ route('spreads.list', ['filterStatus' => 'open']) }}"
                       class="sidebar-link {{ $isOpenSpreads ? 'active' : '' }}">
                        <i class="bi bi-unlock"></i>
                        <span class="sidebar-text">Offene Spreads</span>
                    </a>
                    <a href="{{ route('spreads.list') }}"
                       class="sidebar-link {{ $isClosedSpreads ? 'active' : '' }}">
                        <i class="bi bi-check-circle"></i>
                        <span class="sidebar-text">Geschlossene Spreads</span>
                    </a>
                    <a href="{{ route('spreads.list', ['filterStrategyGroup' => 'verticals']) }}"
                       class="sidebar-link {{ $isVerticals ? 'active' : '' }}">
                        <i class="bi bi-arrows-collapse"></i>
                        <span class="sidebar-text">Vertical Spreads</span>
                    </a>
                    <a href="{{ route('spreads.list', ['filterStrategy' => 'Iron Condor']) }}"
                       class="sidebar-link {{ $isIronCondors ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>
                        <span class="sidebar-text">Iron Condors</span>
                    </a>
                </div>
            </div>

            {{-- Trade Log --}}
            <a href="{{ route('log.list') }}"
               class="sidebar-link {{ request()->routeIs('log.*') ? 'active' : '' }}">
                <i class="bi bi-journal-plus"></i>
                <span class="sidebar-text">Log</span>
            </a>

            <div class="sidebar-divider"></div>

            {{-- Analyse --}}
            @php $analyseOpen = request()->routeIs('symbols') || request()->routeIs('tags.*') || request()->routeIs('stats.*') || request()->routeIs('trades.raw*'); @endphp
            <a class="sidebar-link sidebar-toggle-btn {{ $analyseOpen ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#nav-analyse"
               aria-expanded="{{ $analyseOpen ? 'true' : 'false' }}">
                <i class="bi bi-speedometer2"></i>
                <span class="sidebar-text">Analyse</span>
                <i class="bi bi-chevron-down sidebar-arrow"></i>
            </a>
            <div id="nav-analyse" class="collapse {{ $analyseOpen ? 'show' : '' }}">
                <div class="sidebar-subnav">
                    <a href="{{ route('symbols') }}"
                       class="sidebar-link {{ request()->routeIs('symbols') ? 'active' : '' }}">
                        <i class="bi bi-tag"></i>
                        <span class="sidebar-text">Symbole</span>
                    </a>
                    <a href="{{ route('tags.manage') }}"
                       class="sidebar-link {{ request()->routeIs('tags.*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>
                        <span class="sidebar-text">Tags</span>
                    </a>
                    <a href="{{ route('stats.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('stats.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span class="sidebar-text">Stats</span>
                    </a>
                    <a href="{{ route('trades.raw') }}"
                       class="sidebar-link {{ request()->routeIs('trades.raw*') ? 'active' : '' }}">
                        <i class="bi bi-database"></i>
                        <span class="sidebar-text">Raw Data</span>
                    </a>
                </div>
            </div>

        </nav>
    </aside>

    {{-- ── Topbar ───────────────────────────────────────────────────────────── --}}
    <header id="topbar" class="topbar">
        <div class="ms-auto d-flex align-items-center gap-2">
            {{-- Quick-Entry (immer sichtbar) --}}
            <a href="{{ route('log.quick', ['env' => $selectedEnvironment]) }}"
               class="btn btn-sm btn-warning"
               title="Neuen Log-Eintrag anlegen (Quick-Entry)">
                <i class="bi bi-journal-plus"></i>
                <span class="d-none d-md-inline"> Neuer Eintrag</span>
            </a>

            {{-- Zeitraum-Button --}}
            <button wire:click="$toggle('showDatePicker')"
                    class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-calendar3"></i>
                <span class="d-none d-md-inline">{{ $this->currentLabel }}</span>
            </button>

            {{-- Environment Badge --}}
            @php
                $envColor = match($selectedEnvironment) {
                    'MONEY'       => 'success',
                    'PAPER'       => 'warning',
                    'AUTOM_MONEY' => 'danger',
                    'AUTOM_PAPER' => 'info',
                    default       => 'secondary',
                };
            @endphp
            <span class="badge bg-{{ $envColor }} fs-6 px-3 py-2">
                <i class="bi bi-database"></i> {{ $selectedEnvironment }}
            </span>
        </div>
    </header>

    {{-- ── Datepicker Modal ─────────────────────────────────────────────────── --}}
    @if($showDatePicker)
    <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);"
         wire:click.self="$set('showDatePicker', false)">
        <div class="modal-dialog modal-sm" style="margin-top: 60px; margin-left: auto; margin-right: 1rem;">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0"><i class="bi bi-calendar3"></i> Zeitraum wählen</h6>
                    <button type="button" class="btn-close" wire:click="$set('showDatePicker', false)"></button>
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
