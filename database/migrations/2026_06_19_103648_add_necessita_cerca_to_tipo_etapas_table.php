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
        Schema::table('tipo_etapas', function (Blueprint $table) {
            $table->boolean('necessita_cerca')->default(false)->after('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_etapas', function (Blueprint $table) {
            $table->dropColumn('necessita_cerca');
        });
    }
};
