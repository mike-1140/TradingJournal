use App\Http\Controllers\YahooFinanceController;

Route::get('/stocks/{symbol}', [YahooFinanceController::class, 'quote']);
Route::get('/stocks/{symbol}/history', [YahooFinanceController::class, 'history']);
