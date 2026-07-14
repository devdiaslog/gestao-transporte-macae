<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // SQLite não suporta DROP COLUMN em colunas com FK via ALTER TABLE.
        // Estratégia: criar nova tabela com o schema desejado, copiar dados, substituir.
        DB::statement('CREATE TABLE demandas_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            numero_demanda INTEGER NOT NULL,
            tipo_cadastro VARCHAR NOT NULL DEFAULT \'manual\',
            tipo_demanda VARCHAR NULL,
            equipamento_id INTEGER NULL REFERENCES equipamentos(id) ON DELETE SET NULL,
            documento_demanda VARCHAR NULL,
            origem VARCHAR(500) NULL,
            destino VARCHAR(500) NULL,
            prazo_referencia DATETIME NULL,
            data_hora_inicio_demanda DATETIME NULL,
            data_hora_fim_demanda DATETIME NULL,
            status_demanda VARCHAR NOT NULL DEFAULT \'pendente\',
            status_auditoria TINYINT(1) NOT NULL DEFAULT 0,
            observacao TEXT NULL,
            criado_por INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )');

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

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('CREATE TABLE demandas_old (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            numero_demanda INTEGER NOT NULL,
            tipo_cadastro VARCHAR NOT NULL DEFAULT \'manual\',
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
            status_demanda VARCHAR NOT NULL DEFAULT \'pendente\',
            observacao_adicional TEXT NULL,
            criado_por INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )');

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
