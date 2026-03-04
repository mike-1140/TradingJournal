<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('trades.raw') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Zurück zu Raw Data
            </a>
            <h1>
                <i class="bi bi-database"></i> Raw Trade Details: {{ $trade->symbol }}
            </h1>
        </div>
        <span class="badge bg-{{ $trade->processed ? 'success' : 'warning' }} fs-5">
            {{ $trade->processed ? 'Verarbeitet' : 'Unverarbeitet' }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <!-- Basis-Informationen -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Basis-Informationen</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Symbol:</th>
                                    <td><strong class="fs-4">{{ $trade->symbol }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Beschreibung:</th>
                                    <td>{{ $trade->description }}</td>
                                </tr>
                                <tr>
                                    <th>Asset Category:</th>
                                    <td><span class="badge bg-secondary">{{ $trade->assetCategory }}</span></td>
                                </tr>
                                @if($trade->subCategory)
                                    <tr>
                                        <th>Sub Category:</th>
                                        <td>{{ $trade->subCategory }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Buy/Sell:</th>
                                    <td>
                                        <span class="badge bg-{{ $trade->buySell === 'BUY' ? 'success' : 'danger' }}">
                                            {{ $trade->buySell }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Trade Datum:</th>
                                    <td>{{ $trade->dateTime?->format('d.m.Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Trade Zeit:</th>
                                    <td>{{ $trade->tradeTime ? substr($trade->tradeTime, 0, 8) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Quantity:</th>
                                    <td><strong>{{ number_format($trade->quantity, 2, ',', '') }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Trade Preis:</th>
                                    <td><strong>{{ number_format($trade->tradePrice, 2, ',', '') }} $</strong></td>
                                </tr>
                                <tr>
                                    <th>Net Cash:</th>
                                    <td>{{ number_format($trade->netCash, 2, ',', '') }} $</td>
                                </tr>
                                <tr>
                                    <th>IB Commission:</th>
                                    <td>{{ number_format($trade->ibCommission, 2, ',', '') }} $</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options Details -->
            @if($trade->assetCategory === 'OPT' || $trade->assetCategory === 'FOP')
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Options Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Underlying Symbol:</th>
                                        <td><strong>{{ $trade->underlyingSymbol }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Underlying Conid:</th>
                                        <td>{{ $trade->underlyingConid }}</td>
                                    </tr>
                                    <tr>
                                        <th>Put/Call:</th>
                                        <td>
                                        <span class="badge bg-{{ $trade->putCall === 'CALL' ? 'success' : 'danger' }}">
                                            {{ $trade->putCall }}
                                        </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Strike:</th>
                                        <td><strong>{{ number_format($trade->strike, 2, ',', '') }} $</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Expiry:</th>
                                        <td>{{ $trade->expiry?->format('d.m.Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Multiplier:</th>
                                        <td>{{ $trade->multiplier }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Processing Details -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Verarbeitungs-Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">Source:</th>
                            <td>{{ $trade->source }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>{{ $trade->status }}</td>
                        </tr>
                        <tr>
                            <th>Processed:</th>
                            <td>
                                @if($trade->processed)
                                    <span class="badge bg-success">Ja</span>
                                @else
                                    <span class="badge bg-warning">Nein</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Is Split:</th>
                            <td>{{ $trade->is_split ? 'Ja' : 'Nein' }}</td>
                        </tr>
                        @if($trade->split_type)
                            <tr>
                                <th>Split Type:</th>
                                <td>{{ $trade->split_type }}</td>
                            </tr>
                        @endif
                        @if($trade->actionID)
                            <tr>
                                <th>Action ID:</th>
                                <td><code>{{ $trade->actionID }}</code></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Strategie -->
            @if($trade->strategy || $trade->is_strategy)
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Strategie</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th width="30%">Strategie:</th>
                                <td>{{ $trade->strategy ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Is Strategy:</th>
                                <td>{{ $trade->is_strategy ? 'Ja' : 'Nein' }}</td>
                            </tr>
                            @if($trade->opt_strategyName)
                                <tr>
                                    <th>Strategy Name:</th>
                                    <td>{{ $trade->opt_strategyName }}</td>
                                </tr>
                            @endif
                            @if($trade->strategyID)
                                <tr>
                                    <th>Strategy ID:</th>
                                    <td><code>{{ $trade->strategyID }}</code></td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            @endif

            <!-- Related Raw Trades -->
            @if($relatedRawTrades->count() > 0)
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-link"></i> Verbundene Raw Trades</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Buy/Sell</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Preis</th>
                                    <th>Trade ID</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($relatedRawTrades as $rel)
                                    <tr>
                                        <td>{{ $rel->tradeDate?->format('d.m.Y H:i') }}</td>
                                        <td>
                                        <span class="badge bg-{{ $rel->buySell === 'BUY' ? 'success' : 'danger' }}">
                                            {{ $rel->buySell }}
                                        </span>
                                        </td>
                                        <td class="text-end">{{ number_format($rel->quantity, 2, ',', '') }}</td>
                                        <td class="text-end">{{ number_format($rel->tradePrice, 2, ',', '') }}</td>
                                        <td><small>{{ $rel->tradeID }}</small></td>
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

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- IDs -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-hash"></i> IDs & Referenzen</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm small">
                        <tr>
                            <th>Database ID:</th>
                            <td><code>{{ $trade->id }}</code></td>
                        </tr>
                        <tr>
                            <th>Symbol ID:</th>
                            <td><code>{{ $trade->symID }}</code></td>
                        </tr>
                        <tr>
                            <th>Trade ID:</th>
                            <td><code class="small">{{ $trade->tradeID }}</code></td>
                        </tr>
                        @if($trade->ibOrderID)
                            <tr>
                                <th>IB Order ID:</th>
                                <td><code class="small">{{ $trade->ibOrderID }}</code></td>
                            </tr>
                        @endif
                        @if($trade->linkID)
                            <tr>
                                <th>Link ID:</th>
                                <td><code class="small">{{ $trade->linkID }}</code></td>
                            </tr>
                        @endif
                        @if($trade->checksum)
                            <tr>
                                <th>Checksum:</th>
                                <td><code class="small">{{ $trade->checksum }}</code></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Environment -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-flag"></i> Environment</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Environment:</th>
                            <td>
                                <span class="badge bg-{{ $trade->environment === 'MONEY' ? 'success' : ($trade->environment === 'PAPER' ? 'warning' : 'secondary') }}">
                                    {{ $trade->environment }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
