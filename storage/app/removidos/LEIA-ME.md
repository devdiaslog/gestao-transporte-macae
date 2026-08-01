# Arquivamento — Fase 2 da limpeza de código obsoleto

Estes arquivos **não fazem parte da aplicação**. Estão fora do autoload PSR-4
(`App\` aponta para `app/`) e fora de `view.paths`, portanto o framework não os
enxerga.

Ficam aqui por um ciclo de uso em produção como rede de segurança. Se nenhuma
regressão aparecer, remover definitivamente (Etapa 2):

    git rm -r storage/app/removidos/

Para restaurar qualquer item:

    git checkout pre-limpeza-fase-2 -- <caminho original>

| Arquivo | Motivo do arquivamento |
|---|---|
| `app/Models/UserPermission.php` | RBAC legado, substituído por spatie/laravel-permission em `a28daaf`. Zero referências. |
| `app/Enums/UserPermission.php` | Referenciado apenas pelo model acima. |
| `app/Http/Requests/StoreReporteRequest.php` | Duplicata de `CreateReporteRequest`, que atende `store` e `update`. |
| `app/Http/Requests/UpdateStatusManualRequest.php` | O método `ControlTowerController::editarStatus` foi removido em `caaed`. |
| `resources/views/welcome.blade.php` | Scaffolding do Laravel, sem rota desde o primeiro commit. |
| `resources/views/layouts/app.blade.php` | Layout anterior à migração para componente Blade. Todo o sistema usa `<x-layouts.app>`, que resolve para `resources/views/components/layouts/app.blade.php`. |

A tabela `user_permissions` **não foi tocada** — ela guarda as permissões
anteriores ao spatie e é a única fonte de rollback do RBAC.
