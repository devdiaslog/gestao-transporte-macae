<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->upSqlite();
        } else {
            $this->upMysql();
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->downSqlite();
        } else {
            $this->downMysql();
        }
    }

    // ── MySQL ────────────────────────────────────────────────────────────────

    private function upMysql(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('demandas', function (Blueprint $table) {
            $table->dropForeign(['local_origem_id']);
            $table->dropForeign(['local_destino_id']);
            $table->dropColumn([
                'local_origem_id',
                'local_destino_id',
                'prazo_atendimento_demanda',
                'data_hora_agendamento',
                'data_hora_inicio_carregamento',
                'data_hora_fim_carregamento',
                'data_hora_saida_origem',
                'data_hora_chegada_destino',
                'data_hora_inicio_descarregamento',
                'data_hora_fim_descarregamento',
                'observacao_adicional',
            ]);
        });

        Schema::table('demandas', function (Blueprint $table) {
            $table->string('documento_demanda')->nullable()->after('equipamento_id');
            $table->string('origem', 500)->nullable()->after('documento_demanda');
            $table->string('destino', 500)->nullable()->after('origem');
            $table->dateTime('prazo_referencia')->nullable()->after('destino');
            $table->dateTime('data_hora_inicio_demanda')->nullable()->after('prazo_referencia');
            $table->dateTime('data_hora_fim_demanda')->nullable()->after('data_hora_inicio_demanda');
            $table->text('observacao')->nullable()->after('data_hora_fim_demanda');
            $table->boolean('status_auditoria')->default(false)->after('status_demanda');
        });

        Schema::enableForeignKeyConstraints();
    }

    private function downMysql(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn([
                'documento_demanda',
                'origem',
                'destino',
                'prazo_referencia',
                'data_hora_inicio_demanda',
                'data_hora_fim_demanda',
                'observacao',
                'status_auditoria',
            ]);
        });

        Schema::table('demandas', function (Blueprint $table) {
            $table->foreignId('local_origem_id')->nullable()->constrained('locais')->nullOnDelete();
            $table->foreignId('local_destino_id')->nullable()->constrained('locais')->nullOnDelete();
            $table->dateTime('prazo_atendimento_demanda')->nullable();
            $table->dateTime('data_hora_agendamento')->nullable();
            $table->dateTime('data_hora_inicio_carregamento')->nullable();
            $table->dateTime('data_hora_fim_carregamento')->nullable();
            $table->dateTime('data_hora_saida_origem')->nullable();
            $table->dateTime('data_hora_chegada_destino')->nullable();
            $table->dateTime('data_hora_inicio_descarregamento')->nullable();
            $table->dateTime('data_hora_fim_descarregamento')->nullable();
            $table->text('observacao_adicional')->nullable();
        });

        Schema::enableForeignKeyConstraints();
    }

    // ── SQLite (desenvolvimento local) ───────────────────────────────────────

    private function upSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement("CREATE TABLE demandas_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            numero_demanda INTEGER NOT NULL,
            tipo_cadastro VARCHAR NOT NULL DEFAULT 'manual',
            tipo_demanda VARCHAR NULL,
            equipamento_id INTEGER NULL REFERENCES equipamentos(id) ON DELETE SET NULL,
            documento_demanda VARCHAR NULL,
            origem VARCHAR(500) NULL,
            destino VARCHAR(500) NULL,
            prazo_referencia DATETIME NULL,
            data_hora_inicio_demanda DATETIME NULL,
            data_hora_fim_demanda DATETIME NULL,
            status_demanda VARCHAR NOT NULL DEFAULT 'pendente',
            status_auditoria TINYINT(1) NOT NULL DEFAULT 0,
            observacao TEXT NULL,
            criado_por INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )");

        DB::statement('INSERT INTO demandas_new
            (id, numero_demanda, tipo_cadastro, tipo_demanda, equipamento_id,
             status_demanda, criado_por, created_at, updated_at, deleted_at)
            SELECT id, numero_demanda, tipo_cadastro, tipo_demanda, equipamento_id,
                   status_demanda, criado_por, created_at, updated_at, deleted_at
            FROM demandas');

        DB::statement('CREATE UNIQUE INDEX demandas_new_numero_demanda_unique ON demandas_new (numero_demanda)');
        DB::statement('DROP TABLE demandas');
        DB::statement('ALTER TABLE demandas_new RENAME TO demandas');

        Schema::enableForeignKeyConstraints();
    }

    private function downSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement("CREATE TABLE demandas_old (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            numero_demanda INTEGER NOT NULL,
            tipo_cadastro VARCHAR NOT NULL DEFAULT 'manual',
            tipo_demanda VARCHAR NULL,
            equipamento_id INTEGER NULL REFERENCES equipamentos(id) ON DELETE SET NULL,
            local_origem_id INTEGER NULL REFERENCES locais(id) ON DELETE SET NULL,
            local_destino_id INTEGER NULL REFERENCES locais(id) ON DELETE SET NULL,
            prazo_atendimento_demanda DATETIME NULL,
            data_hora_agendamento DATETIME NULL,
            data_hora_inicio_carregamento DATETIME NULL,
            data_hora_fim_carregamento DATETIME NULL,
            data_hora_saida_origem DATETIME NULL,
            data_hora_chegada_destino DATETIME NULL,
            data_hora_inicio_descarregamento DATETIME NULL,
            data_hora_fim_descarregamento DATETIME NULL,
            status_demanda VARCHAR NOT NULL DEFAULT 'pendente',
            observacao_adicional TEXT NULL,
            criado_por INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )");

        DB::statement('INSERT INTO demandas_old
            (id, numero_demanda, tipo_cadastro, tipo_demanda, equipamento_id,
             status_demanda, criado_por, created_at, updated_at, deleted_at)
            SELECT id, numero_demanda, tipo_cadastro, tipo_demanda, equipamento_id,
                   status_demanda, criado_por, created_at, updated_at, deleted_at
            FROM demandas');

        DB::statement('CREATE UNIQUE INDEX demandas_old_numero_demanda_unique ON demandas_old (numero_demanda)');
        DB::statement('DROP TABLE demandas');
        DB::statement('ALTER TABLE demandas_old RENAME TO demandas');

        Schema::enableForeignKeyConstraints();
    }
};
