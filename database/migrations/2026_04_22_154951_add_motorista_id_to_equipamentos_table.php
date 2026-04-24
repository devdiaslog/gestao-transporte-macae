<?php

use App\Models\Motorista;
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
            $table->foreignId('motorista_id')
                ->nullable()
                ->after('implemento_nome_override')
                ->constrained('motoristas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropForeignIdFor(Motorista::class);
            $table->dropColumn('motorista_id');
        });
    }
};
