@php
    /** @var array $grupos, array $selecionadas, bool $somenteLeitura */
    $somenteLeitura = $somenteLeitura ?? false;
    $sel = collect($selecionadas);
@endphp

<div class="space-y-5">
    @foreach($grupos as $grupo => $modulos)
        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-zinc-800">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">{{ $grupo }}</p>
                @unless($somenteLeitura)
                    <button type="button" data-marcar-grupo="{{ Str::slug($grupo) }}"
                            class="text-[11px] font-medium text-blue-600 hover:underline dark:text-blue-400">
                        Marcar/desmarcar tudo
                    </button>
                @endunless
            </div>

            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                    @foreach($modulos as $modulo => $config)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/20">
                            <td class="w-56 px-4 py-2.5 font-medium text-zinc-700 dark:text-zinc-300">{{ $config['label'] }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-x-5 gap-y-2">
                                    @foreach($config['acoes'] as $acao)
                                        @php $nome = $modulo.'.'.$acao; @endphp
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                                            <input type="checkbox" name="permissions[]" value="{{ $nome }}"
                                                   data-grupo="{{ Str::slug($grupo) }}"
                                                   @checked($sel->contains($nome)) @disabled($somenteLeitura)
                                                   class="h-3.5 w-3.5 rounded border-slate-300 text-zinc-900 focus:ring-zinc-900/20 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800">
                                            {{ App\Support\CatalogoPermissoes::rotuloAcao($acao) }}
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>

@unless($somenteLeitura)
    @push('scripts')
    <script>
        document.querySelectorAll('[data-marcar-grupo]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var grupo = btn.dataset.marcarGrupo;
                var itens = document.querySelectorAll('input[data-grupo="' + grupo + '"]');
                var marcarTodos = Array.from(itens).some(function (i) { return ! i.checked; });
                itens.forEach(function (i) { i.checked = marcarTodos; });
            });
        });
    </script>
    @endpush
@endunless
