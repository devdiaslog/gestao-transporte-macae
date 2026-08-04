<?php

namespace App\Console\Commands;

use App\Support\CatalogoPermissoes;
use App\Support\SincronizadorPermissoes;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SincronizarPermissoes extends Command
{
    /**
     * Cria no banco as permissões do catálogo e (re)aplica os papéis padrão.
     * Idempotente: pode rodar a cada deploy para publicar módulos novos.
     */
    protected $signature = 'permissoes:sincronizar {--papeis : Também redefine as permissões dos papéis padrão}';

    protected $description = 'Sincroniza as permissões do catálogo (e opcionalmente os papéis padrão) com o banco';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $criadas = SincronizadorPermissoes::garantir();

        $this->info('Permissões: '.count($criadas).' criada(s), '.count(CatalogoPermissoes::todas()).' no catálogo.');

        foreach ($criadas as $nome) {
            $this->line("  + {$nome}");
        }

        if ($this->option('papeis')) {
            foreach (CatalogoPermissoes::papeisPadrao() as $nome => $config) {
                $papel = Role::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);

                $permissoes = ($config['todas'] ?? false)
                    ? CatalogoPermissoes::todas()
                    : $this->permissoesDoPapel($config['modulos'] ?? []);

                $papel->syncPermissions($permissoes);

                $this->info("• {$nome}: ".count($permissoes).' permissão(ões).');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array<int, string>>  $modulos
     * @return array<int, string>
     */
    private function permissoesDoPapel(array $modulos): array
    {
        $lista = [];
        foreach ($modulos as $modulo => $acoes) {
            foreach ($acoes as $acao) {
                $lista[] = "{$modulo}.{$acao}";
            }
        }

        return array_values(array_intersect($lista, CatalogoPermissoes::todas()));
    }
}
