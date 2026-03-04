<?php
// database/migrations/xxxx_add_table_prefix_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('mysql')->table('users', function (Blueprint $table) {
            $table->string('table_prefix', 50)->after('email')->unique();
        });
    }

    public function down()
    {
        Schema::connection('mysql')->table('users', function (Blueprint $table) {
            $table->dropColumn('table_prefix');
        });
    }
};
