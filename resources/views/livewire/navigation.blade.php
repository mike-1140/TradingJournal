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
                <!-- Open Trades -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.open.stocks') ? 'active' : '' }}" href="{{ route('trades.open.stocks') }}">
                        <i class="bi bi-database"></i> Open Stocks
                    </a>
                </li>

                <!-- Closed Trades -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.closed.stocks') ? 'active' : '' }}" href="{{ route('trades.closed.stocks') }}">
                            <i class="bi bi-list"></i> Closed Stocks
                        </a>
                </li>
{{--
                <!-- FX Trades
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.fx') ? 'active' : '' }}" href="{{ route('trades.fx') }}">
                        <i class="bi bi-currency-exchange"></i> FX Trades
                    </a>
                </li>  -->
--}}
                <!-- Campains -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('campaigns.campaigns') ? 'active' : '' }}" href="{{ route('campaigns.campaigns') }}">
                        <i class="bi bi-currency-exchange"></i> Campaigns
                    </a>
                </li>

                <!-- Spreads Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('spreads.*') ? 'active' : '' }}"
                       href="#"
                       id="spreadsDropdown"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <i class="bi bi-bezier2"></i> Spreads
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="spreadsDropdown">
                        <li><a class="dropdown-item {{ request()->routeIs('spreads.list') ? 'active' : '' }}" href="{{ route('spreads.list') }}">
                                <i class="bi bi-list"></i> Alle Spreads
                            </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('spreads.list', ['filterStrategy' => 'Vertical Spread']) }}">
                                <i class="bi bi-bar-chart-line"></i> Vertical Spreads
                            </a></li>
                        <li><a class="dropdown-item" href="{{ route('spreads.list', ['filterStrategy' => 'Iron Condor']) }}">
                                <i class="bi bi-diagram-3"></i> Iron Condors
                            </a></li>
                    </ul>
                </li>

                <!-- Open Options -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.open.options') ? 'active' : '' }}" href="{{ route('trades.open.options') }}">
                        <i class="bi bi-database"></i> Open Options
                    </a>
                </li>
                <!-- Closed Options -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.closed.options') ? 'active' : '' }}" href="{{ route('trades.closed.options') }}">
                        <i class="bi bi-database"></i> Closed Options
                    </a>
                </li>

                <!-- Raw Trades -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.raw*') ? 'active' : '' }}" href="{{ route('trades.raw') }}">
                        <i class="bi bi-database"></i> Raw Data
                    </a>
                </li>

                <!-- Symbols -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('symbols') ? 'active' : '' }}" href="{{ route('symbols') }}">
                        <i class="bi bi-tag"></i> Symbole
                    </a>
                </li>
                <!-- Tags -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('tags.manage') }}">
                        <i class="bi bi-tags"></i> Tags
                    </a>
                </li>
                <!-- Stats -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('stats.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Stats
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Zeitraum">
                        @foreach(['13w' => '13W', '6m' => '6M', 'ytd' => 'YTD', 'all' => 'ALL'] as $key => $label)
                            <button type="button"
                                    wire:click="$set('timeRange', '{{ $key }}')"
                                    class="btn btn-outline-light {{ $timeRange === $key ? 'active' : '' }}"
                                    style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </li>

                <li class="nav-item">
        <span class="navbar-text">
            <span id="env-badge" class="badge bg-{{ $selectedEnvironment === 'MONEY' ? 'success' : ($selectedEnvironment === 'PAPER' ? 'warning' : 'secondary') }} fs-6">
                <i class="bi bi-database"></i> <span id="env-text">{{ $selectedEnvironment }}</span>
            </span>
        </span>
                </li>
            </ul>
        </div>
    </div>
</nav>
