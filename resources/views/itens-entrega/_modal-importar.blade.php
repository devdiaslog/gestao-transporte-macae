<div id="modal-importar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <form method="POST" action="{{ route('itens-entrega.importar') }}" enctype="multipart/form-data"
          id="form-importar" class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
        @csrf
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Importar itens do SAP</h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Reimportar atualiza os itens existentes. O que a equipe já editou é preservado.
        </p>

        <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Planilha (.xlsx)</label>
        <input type="file" name="arquivo" accept=".xlsx,.xls" required
               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-zinc-900
                      file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:file:bg-zinc-700 dark:file:text-zinc-200">

        <label class="mt-4 flex items-start gap-2 rounded-lg border border-slate-200 p-3 dark:border-zinc-700">
            <input type="checkbox" name="marcar_ausentes" value="1"
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-zinc-900 dark:border-zinc-600">
            <span class="text-xs text-zinc-600 dark:text-zinc-400">
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">Esta planilha traz todos os itens em cobrança</span><br>
                Os que não constarem nela serão marcados para conferência. Deixe desmarcado ao importar um recorte.
            </span>
        </label>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="fecharModal('modal-importar')" class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Cancelar</button>
            <button type="submit" id="btn-importar" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Importar</button>
        </div>
    </form>
</div>
