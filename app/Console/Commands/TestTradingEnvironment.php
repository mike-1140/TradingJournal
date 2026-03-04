
// app/Console/Commands/TestTradingEnvironment.php
public function handle()
{
    $this->info('=== Trading Environment Test ===');
    $this->newLine();

    // 1. Config Test
    $this->testConfig();

    // 2. Session Test
    $this->testSession();

    // 3. Helper Test
    $this->testHelper();

    // 4. User Test
    $this->testUsers();
}

protected function testSession()
{
    $this->info('Session Test:');

    // Session löschen
    session()->forget('trading_environment');
    $this->line('  Session cleared');

    // Helper aufrufen
    $connection = \App\Helpers\TradingHelper::getConnection(null);
    $this->line("  getConnection(null): {$connection}");

    $sessionValue = session('trading_environment');
    $this->line("  Session value: " . ($sessionValue ?? '(empty)'));

    $this->newLine();
}

protected function testUsers()
{
    $this->info('User Test:');

    $users = \App\Models\User::all();
    foreach ($users as $user) {
        $prefix = $user->table_prefix ?? '(none)';
        $hasTables = $user->hasSetupTables() ? '✓' : '✗';
        $this->line("  {$user->name}: {$prefix} [Tables: {$hasTables}]");
    }

    $this->newLine();
}
