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
        Schema::table('etapas', function (Blueprint $table) {
            $table->dropForeign(['local_etapa_id']);
            $table->dropColumn('local_etapa_id');
            $table->foreignId('cerca_id')->nullable()->after('tipo_etapa_id')->constrained('cercas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etapas', function (Blueprint $table) {
            $table->dropForeign(['cerca_id']);
            $table->dropColumn('cerca_id');
            $table->foreignId('local_etapa_id')->nullable()->after('tipo_etapa_id')->constrained('local_etapas')->nullOnDelete();
        });
    }
};
