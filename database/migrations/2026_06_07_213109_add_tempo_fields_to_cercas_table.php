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
        Schema::table('cercas', function (Blueprint $table) {
            $table->unsignedSmallInteger('tempo_minimo')->default(15)->after('status');
            $table->unsignedSmallInteger('tempo_maximo')->default(120)->after('tempo_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('cercas', function (Blueprint $table) {
            $table->dropColumn(['tempo_minimo', 'tempo_maximo']);
        });
    }
};
