<div>
    <x-page-header
        title="Raw Trade: {{ $trade->symbol }}"
        :subtitle="$trade->description"
        icon="database"
        :backUrl="$backUrl">
        <x-slot:actions>
            @php
                $envColor = match($trade->environment) {
                    'MONEY'       => 'success',
                    'PAPER'       => 'warning',
                    'AUTOM_MONEY' => 'danger',
                    'AUTOM_PAPER' => 'info',
                    default       => 'secondary',
                };
            @endphp
            <span class="badge bg-{{ $trade->processed ? 'success' : 'warning' }} fs-6 me-1">
                <i class="bi bi-{{ $trade->processed ? 'check-circle' : 'clock' }}"></i>
                {{ $trade->processed ? 'Verarbeitet' : 'Unverarbeitet' }}
            </span>
            <span class="badge bg-{{ $envColor }} fs-6">
                <i class="bi bi-database"></i> {{ $trade->environment }}
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">

        {{-- Hauptbereich --}}
        <div class="col-md-8">

            {{-- Instrument --}}
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Instrument</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Symbol</dt>
                        <dd class="col-sm-8"><strong class="fs-5">{{ $trade->symbol }}</strong></dd>

                        <dt class="col-sm-4 text-muted">Beschreibung</dt>
                        <dd class="col-sm-8">{{ $trade->description ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Asset Category</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">{{ $trade->assetCategory }}</span>
                            @if($trade->subCategory)
                                <span class="badge bg-light text-dark ms-1">{{ $trade->subCategory }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Buy/Sell</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $trade->buySell === 'BUY' ? 'success' : 'danger' }}">
                                {{ $trade->buySell }}
                            </span>
                        </dd>

                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8">{{ $trade->status ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Currency</dt>
                        <dd class="col-sm-8">{{ $trade->currency ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Conid</dt>
                        <dd class="col-sm-8"><code>{{ $trade->conid ?: '—' }}</code></dd>
                    </dl>
                </div>
            </div>

            {{-- Finanzdaten --}}
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-cash-stack"></i> Finanzdaten</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Datum / Zeit</dt>
                                <dd class="col-sm-7">{{ $trade->dateTime?->format('d.m.Y H:i:s') ?: '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Quantity</dt>
                                <dd class="col-sm-7"><strong>{{ number_format($trade->quantity, 2, ',', '') }}</strong></dd>

                                <dt class="col-sm-5 text-muted">Trade Preis</dt>
                                <dd class="col-sm-7"><strong>{{ number_format($trade->tradePrice, 2, ',', '') }}</strong></dd>

                                <dt class="col-sm-5 text-muted">Proceeds</dt>
                                <dd class="col-sm-7">{{ number_format($trade->proceeds ?? 0, 2, ',', '') }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Net Cash</dt>
                                <dd class="col-sm-7">{{ number_format($trade->netCash, 2, ',', '') }}</dd>

                                <dt class="col-sm-5 text-muted">IB Commission</dt>
                                <dd class="col-sm-7">{{ number_format($trade->ibCommission, 2, ',', '') }}</dd>

                                <dt class="col-sm-5 text-muted">FX Rate Base</dt>
                                <dd class="col-sm-7">{{ $trade->fxRateToBase ? number_format($trade->fxRateToBase, 6, ',', '') : '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Closed By</dt>
                                <dd class="col-sm-7">{{ $trade->closedBy ?: '—' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Options-Details --}}
            @if(in_array($trade->assetCategory, ['OPT', 'FOP']))
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-layers"></i> Options-Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-6 text-muted">Underlying</dt>
                                    <dd class="col-sm-6"><strong>{{ $trade->underlyingSymbol ?: '—' }}</strong></dd>

                                    <dt class="col-sm-6 text-muted">Underlying Conid</dt>
                                    <dd class="col-sm-6"><code>{{ $trade->underlyingConid ?: '—' }}</code></dd>

                                    <dt class="col-sm-6 text-muted">Put/Call</dt>
                                    <dd class="col-sm-6">
                                        <span class="badge bg-{{ $trade->putCall === 'C' ? 'success' : 'danger' }}">
                                            {{ $trade->putCall }}
                                        </span>
                                    </dd>

                                    <dt class="col-sm-6 text-muted">Opt. Strategy</dt>
                                    <dd class="col-sm-6">{{ $trade->opt_strategy ?: '—' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted">Strike</dt>
                                    <dd class="col-sm-7"><strong>{{ number_format($trade->strike, 2, ',', '') }}</strong></dd>

                                    <dt class="col-sm-5 text-muted">Expiry</dt>
                                    <dd class="col-sm-7">{{ $trade->expiry?->format('d.m.Y') ?: '—' }}</dd>

                                    <dt class="col-sm-5 text-muted">Multiplier</dt>
                                    <dd class="col-sm-7">{{ $trade->multiplier }}</dd>

                                    <dt class="col-sm-5 text-muted">Strategy Name</dt>
                                    <dd class="col-sm-7">{{ $trade->opt_strategyName ?: '—' }}</dd>

                                    <dt class="col-sm-5 text-muted">Strategy ID</dt>
                                    <dd class="col-sm-7"><code>{{ $trade->opt_strategyID ?: '—' }}</code></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Strategie --}}
            @if($trade->strategy || $trade->is_strategy)
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Strategie</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Strategie</dt>
                            <dd class="col-sm-8">{{ $trade->strategy ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Is Strategy</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $trade->is_strategy ? 'success' : 'secondary' }}">
                                    {{ $trade->is_strategy ? 'Ja' : 'Nein' }}
                                </span>
                            </dd>

                            @if($trade->opt_strategyName)
                                <dt class="col-sm-4 text-muted">Strategy Name</dt>
                                <dd class="col-sm-8">{{ $trade->opt_strategyName }}</dd>
                            @endif

                            @if($trade->opt_strategyID)
                                <dt class="col-sm-4 text-muted">Strategy ID</dt>
                                <dd class="col-sm-8"><code>{{ $trade->opt_strategyID }}</code></dd>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Verbundene Raw Trades --}}
            @if($relatedRawTrades->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-link"></i> Verbundene Raw Trades
                            <span class="badge bg-secondary ms-1">{{ $relatedRawTrades->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Datum</th>
                                    <th>Buy/Sell</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Preis</th>
                                    <th class="text-end">Net Cash</th>
                                    <th>Trade ID</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($relatedRawTrades as $rel)
                                    <tr>
                                        <td>{{ $rel->dateTime?->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $rel->buySell === 'BUY' ? 'success' : 'danger' }}">
                                                {{ $rel->buySell }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($rel->quantity, 2, ',', '') }}</td>
                                        <td class="text-end">{{ number_format($rel->tradePrice, 2, ',', '') }}</td>
                                        <td class="text-end">{{ number_format($rel->netCash, 2, ',', '') }}</td>
                                        <td><code class="small">{{ $rel->tradeID }}</code></td>
                                        <td>
                                            <a href="{{ route('trades.raw.detail', $rel->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">

            {{-- IDs & Referenzen --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-hash"></i> IDs & Referenzen</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5 text-muted">DB ID</dt>
                        <dd class="col-sm-7"><code>{{ $trade->id }}</code></dd>

                        <dt class="col-sm-5 text-muted">Sym ID</dt>
                        <dd class="col-sm-7"><code>{{ $trade->symID }}</code></dd>

                        <dt class="col-sm-5 text-muted">Trade ID</dt>
                        <dd class="col-sm-7"><code class="text-break">{{ $trade->tradeID ?: '—' }}</code></dd>

                        @if($trade->ibOrderID)
                            <dt class="col-sm-5 text-muted">IB Order ID</dt>
                            <dd class="col-sm-7"><code class="text-break">{{ $trade->ibOrderID }}</code></dd>
                        @endif

                        @if($trade->linkID)
                            <dt class="col-sm-5 text-muted">Link ID</dt>
                            <dd class="col-sm-7"><code class="text-break">{{ $trade->linkID }}</code></dd>
                        @endif

                        @if($trade->actionID)
                            <dt class="col-sm-5 text-muted">Action ID</dt>
                            <dd class="col-sm-7"><code class="text-break">{{ $trade->actionID }}</code></dd>
                        @endif

                        @if($trade->checksum)
                            <dt class="col-sm-5 text-muted">Checksum</dt>
                            <dd class="col-sm-7"><code class="text-break">{{ $trade->checksum }}</code></dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Verarbeitung --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> Verarbeitung</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6 text-muted">Processed</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-{{ $trade->processed ? 'success' : 'warning' }}">
                                {{ $trade->processed ? 'Ja' : 'Nein' }}
                            </span>
                        </dd>

                        <dt class="col-sm-6 text-muted">Source</dt>
                        <dd class="col-sm-6">{{ $trade->source ?: '—' }}</dd>

                        <dt class="col-sm-6 text-muted">Is Split</dt>
                        <dd class="col-sm-6">
                            @if($trade->is_split)
                                <span class="badge bg-info">Ja</span>
                            @else
                                <span class="text-muted">Nein</span>
                            @endif
                        </dd>

                        @if($trade->split_type)
                            <dt class="col-sm-6 text-muted">Split Type</dt>
                            <dd class="col-sm-6">{{ $trade->split_type }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

        </div>
    </div>
</div>
