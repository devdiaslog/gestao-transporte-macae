<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->string('status_auditoria')->default('pendente')->after('created_by');
            $table->unsignedBigInteger('auditado_por')->nullable()->after('status_auditoria');
            $table->foreign('auditado_por')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('auditado_em')->nullable()->after('auditado_por');
            $table->text('observacao_auditoria')->nullable()->after('auditado_em');
        });
    }

    public function down(): void
    {
        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->dropForeign(['auditado_por']);
            $table->dropColumn(['status_auditoria', 'auditado_por', 'auditado_em', 'observacao_auditoria']);
        });
    }
};
