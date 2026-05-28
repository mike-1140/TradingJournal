<?php

namespace App\Models;

class AccountSnapshot extends BaseTradingModel
{
    protected $table = 'account_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'snapshot_date', 'environment',
        'nav_prior', 'nav_current', 'nav_change', 'twrr',
        'starting_value', 'mtm_pnl', 'commissions_eur', 'ending_value',
        'cash_eur', 'cash_usd', 'forex_translation_pnl',
        'realized_pnl_eur', 'unrealized_pnl_eur',
        'mtd_pnl', 'ytd_pnl', 'imported_at',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];
}
