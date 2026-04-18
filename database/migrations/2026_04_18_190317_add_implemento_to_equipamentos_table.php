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
            $table->foreignId('implemento_id')->nullable()->after('sub_divisao_id')
                ->constrained('equipamentos')->nullOnDelete();
            $table->string('implemento_nome_override')->nullable()->after('implemento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropForeign(['implemento_id']);
            $table->dropColumn(['implemento_id', 'implemento_nome_override']);
        });
    }
};
