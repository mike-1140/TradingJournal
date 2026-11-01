<?php

namespace Tests\Concerns;

use App\Models\PsychologyEntry;
use Illuminate\Support\Facades\DB;

/**
 * Legt die Tabellen des Psychologie-Moduls für den Test-Prefix phpunit_ an bzw. entfernt sie.
 * Gleiche Struktur wie sql/psychology_*.sql im Weaver; reset_IBKR_DB.py legt sie nicht an.
 */
trait CreatesPsychologyTables
{
    protected function createPsychologyTables(): void
    {
        $db = DB::connection('trading_test');
        $db->statement("CREATE TABLE IF NOT EXISTS `phpunit_psychology_rulesets` (
            id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, environment VARCHAR(20) NOT NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL)");
        $db->statement("CREATE TABLE IF NOT EXISTS `phpunit_psychology_rules` (
            id INT AUTO_INCREMENT PRIMARY KEY, ruleset_id INT NOT NULL, label VARCHAR(200) NOT NULL, description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL)");
        $db->statement("CREATE TABLE IF NOT EXISTS `phpunit_psychology_entries` (
            id INT AUTO_INCREMENT PRIMARY KEY, trade_id VARCHAR(70) NULL, opt_strategyID VARCHAR(50) NULL,
            trade_type ENUM('open','closed','open_spread','spread') NULL, campaign_id INT NULL, opened_at DATETIME NULL,
            ruleset_id INT NULL, symbol VARCHAR(100) NOT NULL, trade_date DATE NOT NULL, pnl DECIMAL(10,2) NULL,
            emotion_pre TINYINT UNSIGNED NULL, emotion_post TINYINT UNSIGNED NULL, motivation VARCHAR(20) NULL,
            market_context VARCHAR(20) NULL, lesson TEXT NULL, environment VARCHAR(20) NOT NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB");
        $db->statement("CREATE TABLE IF NOT EXISTS `phpunit_psychology_compliance_results` (
            id INT AUTO_INCREMENT PRIMARY KEY, entry_id INT NOT NULL, rule_id INT NOT NULL,
            complied TINYINT(1) NOT NULL DEFAULT 0, tempted TINYINT(1) NOT NULL DEFAULT 0) ENGINE=InnoDB");
        PsychologyEntry::forgetTableCache();
    }

    protected function dropPsychologyTables(): void
    {
        $db = DB::connection('trading_test');
        foreach (['compliance_results', 'entries', 'rules', 'rulesets'] as $table) {
            $db->statement("DROP TABLE IF EXISTS `phpunit_psychology_{$table}`");
        }
        PsychologyEntry::forgetTableCache();
    }
}
