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
        'tradePrice',
        'strike',
        'expiry',
        'multiplier',
        'putCall',
        'buySell',
        'netcash',
        'proceeds',
        'commission',
        'realized_pnl',
        'inventory_delta',
        'action',
        'dateTime',
        'created_at',
        'environment',
        'fxRateToBase',
        'netcash_eur',
        'proceeds_eur',
        'commission_eur',
    ];
    protected $casts = [
        'tradePrice'     => 'decimal:4',
        'strike'         => 'decimal:4',
        'expiry'         => 'date',
        'multiplier'     => 'decimal:4',
        'created_at'     => 'date',
        'fxRateToBase'   => 'decimal:6',
        'netcash'        => 'decimal:4',
        'proceeds'       => 'decimal:4',
        'commission'     => 'decimal:4',
        'realized_pnl'   => 'decimal:4',
        'netcash_eur'    => 'decimal:4',
        'proceeds_eur'   => 'decimal:4',
        'commission_eur' => 'decimal:4',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Tranche in trades_open, aus der die Buchung stammt (source_table = 'TRADES_OPEN').
     * Liefert u. a. die Spread-Kennung (opt_strategyID) fuer die Kampagnen-Art.
     */
    public function openTrade()
    {
        return $this->belongsTo(TradeOpen::class, 'source_id');
    }
}
