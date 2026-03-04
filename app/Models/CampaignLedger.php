<?php

namespace App\Models;

class CampaignLedger extends BaseTradingModel
{
    protected $table = 'campaign_ledger';

    protected $fillable = [
        'id',
        'campaign_id',
        'conid',
        'assetCategory',
        'symbol',
        'currency',
        'source_table', // 'trades_open', 'trades_closed', 'system_migration'
        'source_id',
        'linkID',
        'netcash',
        'proceeds',
        'commission',
        'realized_pnl',
        'inventory_delta',
        'action', // 'OPEN', 'CLOSE', 'PCU', 'REV', 'CARRY_OVER'
        'dateTime',
        'created_at',
        'environment',
        'fxRateToBase',
        'netcash_eur',
        'proceeds_eur',
        'commission_eur',
    ];
    protected $casts = [
        'created_at' => 'date',
        'fxRateToBase' => 'decimal:6',
        'netcash_eur' => 'decimal:4',
        'proceeds_eur' => 'decimal:4',
        'commission_eur' => 'decimal:4',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }
}
