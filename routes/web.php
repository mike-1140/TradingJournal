<?php

use App\Livewire\Trades\OpenOptionsDetail;
use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
// Optionen
use App\Livewire\Trades\OpenOptions;
use App\Livewire\Trades\ClosedOptions;
// offene STK
use App\Livewire\Trades\OpenStocks;
use App\Livewire\Trades\OpenStocksDetail;
// geschlossene STK
use App\Livewire\Trades\ClosedStocks;
use App\Livewire\Trades\ClosedStocksDetail;
// Rohdaten
use App\Livewire\Campaigns\Campaigns;
use App\Livewire\Campaigns\CampaignDetail;
use App\Livewire\Trades\RawData;
use App\Livewire\Trades\OpenTradeDetail;
use App\Livewire\Trades\ClosedOptionsDetail;
use App\Livewire\Trades\RawTradeDetail;
use App\Livewire\Symbols\SymbolList;
use App\Livewire\Trades\FxTrades;
use App\Livewire\Spreads\SpreadsList;
use App\Livewire\Spreads\SpreadDetail;
use App\Livewire\Tags\TagManager;
use App\Livewire\Log\LogList;
use App\Livewire\Log\LogForm;

use App\Livewire\Stats\StatsDashboard;

Route::get('/', Dashboard::class)->name('dashboard');

// Open Trades
//Route::get('/trades/open', OpenTrades::class)->name('trades.open.all');
Route::get('/trades/open/stocks', OpenStocks::class)->name('trades.open.stocks');
Route::get('/trades/open/stocks/{id}', OpenStocksDetail::class)->name('trades.open.stocks.detail');
Route::get('/trades/open/options', OpenOptions::class)->name('trades.open.options');
Route::get('/trades/open/options/{id}', OpenOptionsDetail::class)->name('trades.open.options.detail');

// Closed Trades
//Route::get('/trades/closed', ClosedTrades::class)->name('trades.closed.all');
Route::get('/trades/closed/stocks', ClosedStocks::class)->name('trades.closed.stocks');
Route::get('/trades/closed/stocks/{id}', ClosedStocksDetail::class)->name('trades.closed.stocks.detail');
Route::get('/trades/closed/options', ClosedOptions::class)->name('trades.closed.options');
Route::get('/trades/closed/options/{id}', ClosedOptionsDetail::class)->name('trades.closed.options.detail');

// Raw Trades
Route::get('/trades/raw', RawData::class)->name('trades.raw');
Route::get('/trades/raw/{id}', RawTradeDetail::class)->name('trades.raw.detail');

// Symbols
Route::get('/symbols', SymbolList::class)->name('symbols');

// FX Trades
//Route::get('/trades/fx', xFxTrades::class)->name('trades.fx');

// Campains
Route::get('/campaigns/campaigns', Campaigns::class)->name('campaigns.campaigns');
Route::get('/campaigns/{campaignId}', CampaignDetail::class)->name('campaigns.campaign.detail');

// Spreads
Route::get('/spreads', SpreadsList::class)->name('spreads.list');
Route::get('/spreads/{opt_strategyID}', SpreadDetail::class)->name('spreads.detail');

//Tags
Route::get('/tags', TagManager::class)->name('tags.manage');

// Stats
Route::get('/stats', StatsDashboard::class)->name('stats.dashboard');

// Trade Log
Route::get('/log', LogList::class)->name('log.list');
Route::get('/log/neu', LogForm::class)->name('log.create');
Route::get('/log/schnell', LogForm::class)->name('log.quick');
Route::get('/log/{id}', LogForm::class)->name('log.edit');
