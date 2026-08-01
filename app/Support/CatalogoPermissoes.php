<?php

namespace App\Support;

/**
 * Catálogo central de permissões do sistema.
 *
 * Para adicionar um módulo novo, basta incluí-lo aqui e rodar
 * `php artisan permissoes:sincronizar` — as permissões são criadas no banco e
 * passam a aparecer na matriz de perfis e de usuários automaticamente.
 */
class CatalogoPermissoes
{
    /** Ações CRUD usadas pela maioria dos módulos. */
    public const ACOES_CRUD = ['ver', 'criar', 'editar', 'excluir'];

    /**
     * Módulos agrupados por área, com as ações que cada um oferece.
     *
     * @return array<string, array<string, array{label: string, acoes: array<int, string>}>>
     */
    public static function grupos(): array
    {
        return [
            'Acesso e Segurança' => [
                'usuarios' => ['label' => 'Usuários', 'acoes' => ['ver', 'criar', 'editar', 'excluir', 'resetar-senha']],
                'perfis' => ['label' => 'Perfis de Acesso', 'acoes' => self::ACOES_CRUD],
            ],

            'Operação' => [
                'dashboard' => ['label' => 'Dashboard', 'acoes' => ['ver', 'atualizar']],
                'demandas' => ['label' => 'Demandas', 'acoes' => ['ver', 'criar', 'editar', 'excluir', 'importar', 'auditar']],
                'mapa-geral' => ['label' => 'Mapa Geral', 'acoes' => ['ver']],
                'alertas' => ['label' => 'Alertas', 'acoes' => self::ACOES_CRUD],
                'ocorrencias' => ['label' => 'Ocorrências', 'acoes' => ['ver', 'criar', 'editar', 'excluir', 'auditar']],
                'reportes' => ['label' => 'Reportes', 'acoes' => self::ACOES_CRUD],
                'metricas' => ['label' => 'Métricas', 'acoes' => ['ver']],
            ],

            'Cadastros' => [
                'equipamentos' => ['label' => 'Equipamentos', 'acoes' => self::ACOES_CRUD],
                'motoristas' => ['label' => 'Motoristas', 'acoes' => self::ACOES_CRUD],
                'divisoes' => ['label' => 'Divisões', 'acoes' => self::ACOES_CRUD],
                'subdivisoes' => ['label' => 'Subdivisões', 'acoes' => self::ACOES_CRUD],
                'tipos-equipamentos' => ['label' => 'Tipos de Equipamento', 'acoes' => self::ACOES_CRUD],
                'modelos-equipamentos' => ['label' => 'Modelos de Equipamento', 'acoes' => self::ACOES_CRUD],
                'cercas' => ['label' => 'Cercas', 'acoes' => self::ACOES_CRUD],
                'medicoes' => ['label' => 'Medições', 'acoes' => self::ACOES_CRUD],
            ],

            'Tabelas de Apoio' => [
                'responsaveis' => ['label' => 'Responsáveis', 'acoes' => self::ACOES_CRUD],
                'tipos-ocorrencia' => ['label' => 'Tipos de Ocorrência', 'acoes' => self::ACOES_CRUD],
                'justificativas' => ['label' => 'Justificativas', 'acoes' => self::ACOES_CRUD],
            ],
        ];
    }

    /**
     * Todas as permissões no formato "modulo.acao".
     *
     * @return array<int, string>
     */
    public static function todas(): array
    {
        $permissoes = [];

        foreach (self::grupos() as $modulos) {
            foreach ($modulos as $modulo => $config) {
                foreach ($config['acoes'] as $acao) {
                    $permissoes[] = "{$modulo}.{$acao}";
                }
            }
        }

        return $permissoes;
    }

    /**
     * Rótulo amigável de uma ação.
     */
    public static function rotuloAcao(string $acao): string
    {
        return match ($acao) {
            'ver' => 'Ver',
            'criar' => 'Criar',
            'editar' => 'Editar',
            'excluir' => 'Excluir',
            'importar' => 'Importar',
            'auditar' => 'Auditar',
            'atualizar' => 'Atualizar',
            'resetar-senha' => 'Resetar senha',
            default => ucfirst(str_replace('-', ' ', $acao)),
        };
    }

    /**
     * Papéis entregues com o sistema e as permissões de cada um.
     * 'todas' => true concede tudo (inclusive módulos criados depois).
     *
     * @return array<string, array{label: string, descricao: string, todas?: bool, modulos?: array<string, array<int, string>>}>
     */
    public static function papeisPadrao(): array
    {
        $crud = self::ACOES_CRUD;

        return [
            'Administrador' => [
                'label' => 'Administrador',
                'descricao' => 'Acesso total ao sistema, incluindo usuários e perfis.',
                'todas' => true,
            ],

            'Supervisor' => [
                'label' => 'Supervisor',
                'descricao' => 'Gerencia a operação e os cadastros, sem acesso a usuários e perfis.',
                'modulos' => [
                    'dashboard' => ['ver'],
                    'demandas' => ['ver', 'criar', 'editar', 'importar', 'auditar'],
                    'mapa-geral' => ['ver'],
                    'alertas' => $crud,
                    'ocorrencias' => ['ver', 'criar', 'editar', 'auditar'],
                    'reportes' => $crud,
                    'metricas' => ['ver'],
                    'equipamentos' => $crud,
                    'motoristas' => $crud,
                    'divisoes' => $crud,
                    'subdivisoes' => $crud,
                    'tipos-equipamentos' => $crud,
                    'modelos-equipamentos' => $crud,
                    'cercas' => $crud,
                    'medicoes' => $crud,
                    'responsaveis' => $crud,
                    'tipos-ocorrencia' => $crud,
                    'justificativas' => $crud,
                ],
            ],

            'Operador' => [
                'label' => 'Operador',
                'descricao' => 'Opera demandas, alertas, ocorrências e reportes do dia a dia.',
                'modulos' => [
                    'demandas' => ['ver', 'criar', 'editar', 'importar'],
                    'mapa-geral' => ['ver'],
                    'alertas' => ['ver', 'criar', 'editar'],
                    'ocorrencias' => ['ver', 'criar', 'editar'],
                    'reportes' => ['ver', 'criar', 'editar'],
                    'metricas' => ['ver'],
                ],
            ],

            'Visualizador' => [
                'label' => 'Visualizador',
                'descricao' => 'Somente consulta do Mapa Geral.',
                'modulos' => [
                    'mapa-geral' => ['ver'],
                ],
            ],
        ];
    }
}
