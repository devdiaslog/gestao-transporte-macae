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
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->string('status_operacional')->nullable()->after('status');
            $table->string('documento_demanda')->nullable()->after('status_operacional');
            $table->text('observacao_operacional')->nullable()->after('documento_demanda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropColumn(['status_operacional', 'documento_demanda', 'observacao_operacional']);
        });
    }
};
