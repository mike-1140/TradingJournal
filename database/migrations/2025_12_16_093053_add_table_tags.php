<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Tags Tabelle (zentral, für alle Environments)
        Schema::connection('mysql')->create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('color', 7)->default('#6c757d'); // Hex color
            $table->enum('category', ['psychology', 'strategy', 'market', 'execution', 'mistake', 'success'])->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::connection('mysql')->dropIfExists('tags');
    }
};
