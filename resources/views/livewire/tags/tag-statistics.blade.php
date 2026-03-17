<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Tag Performance Analysis</h5>
            <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />
        </div>
        <div class="card-body">
            @if($tagStats->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                        <tr>
                            <th>Tag</th>
                            <th class="text-center">Trades</th>
                            <th class="text-center">Win Rate</th>
                            <th class="text-end">Total P/L</th>
                            <th class="text-end">Avg P/L</th>
                            <th class="text-center">Avg Days</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tagStats as $stat)
                            <tr>
                                <td>
                                <span class="badge" style="background-color: {{ $stat->tag->color }}; color: {{ $stat->tag->contrast_color }};">
                                    {{ $stat->tag->name }}
                                </span>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $stat->trade_count }}</strong>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $stat->win_rate >= 50 ? 'success' : 'danger' }}"
                                             style="width: {{ $stat->win_rate }}%">
                                            {{ $stat->win_rate }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong class="text-{{ $stat->total_profit >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($stat->total_profit, 2) }} $
                                    </strong>
                                </td>
                                <td class="text-end">
                                <span class="text-{{ $stat->avg_profit >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($stat->avg_profit, 2) }} $
                                </span>
                                </td>
                                <td class="text-center">
                                    {{ $stat->avg_days_held }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No tags used yet. Start tagging your trades!
                </div>
            @endif
        </div>
    </div>
</div>
