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
        Schema::table('reportes', function (Blueprint $table) {
            $table->string('nome')->nullable()->after('numero_reporte');
            $table->string('status')->default('publicado')->after('nome'); // rascunho | publicado
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn(['nome', 'status']);
        });
    }
};
