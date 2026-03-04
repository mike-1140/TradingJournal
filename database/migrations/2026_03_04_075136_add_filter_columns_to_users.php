<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->table('users', function (Blueprint $table) {
            $table->string('filter_preset', 10)->nullable()->after('table_prefix');
            $table->date('filter_from')->nullable()->after('filter_preset');
            $table->date('filter_to')->nullable()->after('filter_from');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('users', function (Blueprint $table) {
            $table->dropColumn(['filter_preset', 'filter_from', 'filter_to']);
        });
    }
};
