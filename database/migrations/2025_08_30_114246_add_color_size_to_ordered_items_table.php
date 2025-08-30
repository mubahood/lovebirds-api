<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $table->text('color')->nullable();
            $table->text('size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $table->dropColumn(['color', 'size']);
        });
    }
};
