# Design System — Gestão de Transporte Macaé

> Referência completa para construção de novas telas e componentes.
> Stack: **Laravel 12 · Blade · Tailwind CSS v4 · Vite**

---

## 1. Fundação

### Tecnologia
- **CSS engine:** Tailwind CSS v4 (`@import 'tailwindcss'`)
- **Dark mode:** class-based (`.dark` no `<html>`) com `@custom-variant dark (&:where(.dark, .dark *))`
- **Font:** Inter (Google Fonts — 400, 500, 600, 700)
- **Bundler:** Vite + `@tailwindcss/vite`

### Fontes (app.css)
```css
@import 'tailwindcss';
@custom-variant dark (&:where(.dark, .dark *));

@theme {
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
}
```

---

## 2. Paleta de Cores

### Neutros (base da interface)
| Token | Uso principal |
|-------|---------------|
| `slate-50` | Fundo claro (página) |
| `slate-100` | Fundo de inputs / hover sutil |
| `slate-200` | Bordas claras |
| `slate-300` | Bordas de inputs |
| `slate-400` | Texto placeholder / ícones desabilitados |
| `slate-500` | Texto secundário |
| `slate-600` | Texto de apoio |
| `slate-700` | Hover de botão primário |
| `slate-800` | Hover sutil dark |
| `slate-900` | Botão primário / texto strong |
| `slate-950` | Fundo dark (página) |
| `zinc-50`–`zinc-950` | Componentes (cards, sidebar, badges) |

### Cores Semânticas de Status
| Status operacional | Cor | Token |
|-------------------|-----|-------|
| Ag-Carregamento | Âmbar | `amber` |
| Ag-Descarregamento | Laranja | `orange` |
| Ag-Documentação | Amarelo | `yellow` |
| Ag-Motorista / Disponível | Lima | `lime` |
| Ag-Programação | Ciano | `cyan` |
| Carregado | Esmeralda | `emerald` |
| Carregando | Turquesa | `teal` |
| Em Trânsito | Azul | `blue` |
| Em Viagem | Índigo | `indigo` |
| Em Operação Interna | Violeta | `violet` |
| Descarregando / Descarregado | Roxo | `purple` |
| Manutenção / Recusa | Rosa | `rose` |
| Parado / Reserva | Zinco | `zinc` |

### Padrão de Badge de Status
```html
<!-- Estrutura de badge dinâmico -->
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium
             ring-1
             bg-{color}-500/10  text-{color}-600  ring-{color}-400/30
             dark:text-{color}-400  dark:ring-{color}-500/30">
    <span class="h-1.5 w-1.5 rounded-full bg-{color}-400"></span>
    Rótulo do Status
</span>
```

---

## 3. Tipografia

### Escala
| Uso | Classes |
|-----|---------|
| Título de página | `text-2xl font-bold tracking-tight` |
| Título de seção | `text-base font-bold` ou `text-lg font-semibold` |
| Subtítulo | `text-sm font-semibold text-zinc-900 dark:text-zinc-100` |
| Corpo padrão | `text-sm text-zinc-700 dark:text-zinc-300` |
| Texto secundário | `text-sm text-zinc-500 dark:text-zinc-400` |
| Label de campo | `text-sm font-medium text-zinc-700 dark:text-zinc-300` |
| Caption / badge | `text-xs font-medium` |
| Micro (detalhes) | `text-[11px] font-medium` |
| Número tabulado | `tabular-nums font-medium` |

### Tracking (espaçamento entre letras)
| Uso | Classe |
|-----|--------|
| Títulos | `tracking-tight` |
| Números grandes | `tracking-wide` |
| Rótulos uppercase | `tracking-widest uppercase text-[10px]` |

---

## 4. Espaçamento

### Padding padrão
| Contexto | Classes |
|---------|---------|
| Botão / Input | `px-3.5 py-2.5` |
| Botão pequeno | `px-3 py-1.5` |
| Card compacto | `p-4` |
| Card médio | `p-5` ou `p-6` |
| Card grande / form | `p-8` |
| Toolbar / barra de filtros | `px-4 py-3` |
| Conteúdo principal | `px-4 py-6` ou `px-6 py-8` |

### Gap
| Uso | Classe |
|-----|--------|
| Entre badges | `gap-1.5` |
| Entre ícone e texto | `gap-2` |
| Entre cards | `gap-3` |
| Entre seções | `gap-4` ou `gap-6` |

### Margem vertical
```
mt-1   mt-1.5   mt-2   mt-3   mt-4   mt-6   mt-8
space-y-1.5   space-y-5
```

---

## 5. Border Radius

| Escala | Uso |
|--------|-----|
| `rounded-md` | Chips de filtro |
| `rounded-lg` | Botões, inputs, dropdowns (padrão) |
| `rounded-xl` | Cards maiores, modais |
| `rounded-2xl` | Containers de login, ícone decorativo |
| `rounded-full` | Badges, avatares, dots |

---

## 6. Sombras

| Classe | Uso |
|--------|-----|
| `shadow-xs` | Inputs, botões, componentes pequenos |
| `shadow-sm` | Cards em repouso |
| `shadow-md` | Cards em hover |
| `shadow-lg` | Dropdowns, menus flutuantes |
| `shadow-xl` | Painéis de modal |

---

## 7. Componentes

### Botão Primário
```html
<button class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-medium
               bg-slate-900 text-white shadow-xs
               hover:bg-slate-700
               dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100
               active:scale-[0.98] transition-all duration-150
               disabled:cursor-not-allowed disabled:opacity-60">
    Confirmar
</button>
```

### Botão Secundário
```html
<button class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-medium
               border border-slate-200 bg-white text-slate-700 shadow-xs
               hover:bg-slate-50
               dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200 dark:hover:bg-slate-800
               active:scale-[0.98] transition-all duration-150">
    Cancelar
</button>
```

### Botão Destrutivo
```html
<button class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-medium
               bg-red-600 text-white shadow-xs
               hover:bg-red-500
               active:scale-[0.98] transition-all duration-150">
    Excluir
</button>
```

### Botão Ícone (ghost)
```html
<button class="rounded-lg p-1.5 text-zinc-500 transition-colors duration-150
               hover:bg-zinc-100 hover:text-zinc-700
               dark:hover:bg-zinc-800/70 dark:hover:text-zinc-300">
    <svg class="h-4 w-4">...</svg>
</button>
```

---

### Input de Texto
```html
<div class="space-y-1.5">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
        Campo
    </label>
    <input type="text"
           class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs
                  border-slate-300 bg-white text-slate-900 placeholder:text-slate-400
                  focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10
                  dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100
                  dark:placeholder:text-slate-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20
                  disabled:cursor-not-allowed disabled:opacity-60"
           placeholder="Placeholder..." />
    <!-- Erro -->
    <p class="text-xs text-red-600 dark:text-red-400">Mensagem de erro</p>
</div>
```

### Input de Busca (com botão)
```html
<div class="flex overflow-hidden rounded-lg border shadow-xs
            border-slate-300 bg-white
            focus-within:border-zinc-900 focus-within:ring-2 focus-within:ring-zinc-900/10
            dark:border-zinc-800 dark:bg-zinc-950
            dark:focus-within:border-blue-500 dark:focus-within:ring-blue-500/20">
    <span class="flex items-center pl-3 text-zinc-400">
        <svg class="h-4 w-4"><!-- search icon --></svg>
    </span>
    <input type="text"
           class="flex-1 bg-transparent px-2.5 py-2 text-sm text-zinc-900
                  placeholder:text-zinc-400 outline-none
                  dark:text-zinc-100 dark:placeholder:text-zinc-500"
           placeholder="Buscar..." />
    <button class="flex items-center border-l border-slate-300 px-4 py-2.5
                   text-sm font-medium text-zinc-600 transition-colors
                   hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
        Filtrar
    </button>
</div>
```

### Select
```html
<select class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs
               border-slate-300 bg-white text-slate-900
               focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10
               dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
    <option value="">Selecionar...</option>
</select>
```

---

### Card Padrão
```html
<div class="rounded-xl border bg-white p-5 shadow-sm transition-all duration-200
            border-slate-200
            hover:border-slate-300 hover:shadow-md
            dark:border-zinc-800 dark:bg-zinc-900/50
            dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
    <!-- Cabeçalho do card -->
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">Título</p>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Subtítulo</p>
        </div>
        <!-- badge de status -->
    </div>
    <!-- Conteúdo -->
    <div class="mt-4 space-y-2">
        ...
    </div>
</div>
```

---

### Badge / Pill
```html
<!-- Neutro -->
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
             bg-zinc-100 text-zinc-600 ring-1 ring-zinc-200
             dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700">
    Rótulo
</span>

<!-- Sucesso -->
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
             bg-emerald-500/10 text-emerald-600 ring-1 ring-emerald-400/30
             dark:text-emerald-400 dark:ring-emerald-500/30">
    Ativo
</span>

<!-- Aviso -->
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
             bg-amber-500/10 text-amber-600 ring-1 ring-amber-400/30
             dark:text-amber-400 dark:ring-amber-500/30">
    Pendente
</span>

<!-- Erro -->
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
             bg-red-500/10 text-red-600 ring-1 ring-red-400/30
             dark:text-red-400 dark:ring-red-500/30">
    Inativo
</span>
```

---

### Avatar
```html
<div class="flex h-8 w-8 items-center justify-center rounded-full
            bg-zinc-200 ring-2 ring-zinc-300/50
            dark:bg-zinc-800 dark:ring-zinc-700/50">
    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-200">AB</span>
</div>
```

---

### Chip de Filtro (ativo / inativo)
```html
<!-- Ativo -->
<a href="#" class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                   bg-zinc-900 text-white
                   dark:bg-white dark:text-zinc-900">
    Todos
</a>
<!-- Inativo -->
<a href="#" class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                   text-zinc-600 hover:text-zinc-900
                   dark:text-zinc-400 dark:hover:text-zinc-100">
    Filtro
</a>
```

---

### Modal
```html
<!-- Overlay -->
<div id="modal-overlay"
     class="fixed inset-0 z-40 flex items-center justify-center p-4
            bg-black/60 backdrop-blur-sm
            opacity-0 transition-opacity duration-200 pointer-events-none"
     aria-hidden="true">

    <!-- Painel -->
    <div class="relative w-full max-w-sm rounded-2xl border shadow-xl
                border-slate-200 bg-white p-6
                dark:border-zinc-800 dark:bg-zinc-900
                scale-95 opacity-0 transition-all duration-200">

        <!-- Cabeçalho -->
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Título do Modal</h2>
            <button class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300 transition-colors">
                <svg class="h-4 w-4"><!-- x icon --></svg>
            </button>
        </div>

        <!-- Corpo -->
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Conteúdo do modal.</p>

        <!-- Ações -->
        <div class="mt-6 flex items-center justify-end gap-3">
            <button class="rounded-lg px-3.5 py-2.5 text-sm font-medium border
                           border-slate-200 text-slate-700 hover:bg-slate-50
                           dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Cancelar
            </button>
            <button class="rounded-lg px-3.5 py-2.5 text-sm font-medium
                           bg-slate-900 text-white hover:bg-slate-700
                           dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                Confirmar
            </button>
        </div>
    </div>
</div>
```

**Abrir/fechar via JS:**
```javascript
function openModal(id) {
    const overlay = document.getElementById(id + '-overlay');
    const panel   = overlay.querySelector('[class*="scale-95"]');
    overlay.classList.remove('opacity-0', 'pointer-events-none');
    panel.classList.replace('opacity-0', 'opacity-100');
    panel.classList.replace('scale-95', 'scale-100');
}
```

---

### Dropdown Menu
```html
<div class="relative">
    <button id="menu-btn"
            class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm font-medium
                   border-slate-200 bg-white shadow-xs hover:bg-slate-50
                   dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800">
        Opções
        <svg class="h-4 w-4 text-zinc-400"><!-- chevron --></svg>
    </button>

    <div id="menu-panel"
         class="absolute right-0 top-full z-40 mt-1.5 w-48 rounded-xl border shadow-lg
                border-slate-200 bg-white
                dark:border-zinc-800 dark:bg-zinc-900
                hidden">
        <div class="p-1">
            <a href="#" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm
                               text-zinc-700 hover:bg-zinc-50
                               dark:text-zinc-200 dark:hover:bg-zinc-800">
                <svg class="h-4 w-4 text-zinc-400"><!-- icon --></svg>
                Item do menu
            </a>
            <hr class="my-1 border-slate-100 dark:border-zinc-800">
            <a href="#" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm
                               text-red-600 hover:bg-red-50
                               dark:text-red-400 dark:hover:bg-red-950/30">
                <svg class="h-4 w-4"><!-- icon --></svg>
                Excluir
            </a>
        </div>
    </div>
</div>
```

---

### Tabela
```html
<div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm dark:border-zinc-800">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-zinc-800">
        <thead class="bg-slate-50 dark:bg-zinc-900/60">
            <tr>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider
                           text-slate-500 dark:text-zinc-400">
                    Coluna
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white dark:divide-zinc-800/60 dark:bg-zinc-900/30">
            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                    Dado
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

---

### Linha de Informação (chave + valor)
```html
<div class="flex items-center gap-2">
    <span class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-600 shrink-0">
        Rótulo
    </span>
    <span class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-300">
        Valor
    </span>
</div>
```

---

### Empty State
```html
<div class="flex flex-col items-center justify-center rounded-xl border px-6 py-20 text-center
            border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl
                bg-zinc-100 dark:bg-zinc-800/60">
        <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-600" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
            <!-- ícone representativo -->
        </svg>
    </div>
    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">
        Sem dados
    </h3>
    <p class="mt-1.5 max-w-xs text-sm text-zinc-500 dark:text-zinc-400">
        Descrição do estado vazio com orientação de ação.
    </p>
    <a href="#" class="mt-6 inline-flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-medium
                       bg-slate-900 text-white hover:bg-slate-700
                       dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
        Ação principal
    </a>
</div>
```

---

### Flash / Alert
```html
<!-- Sucesso -->
<div class="flex items-start gap-3 rounded-xl border p-4
            border-emerald-200 bg-emerald-50 text-emerald-700
            dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-400">
    <svg class="h-5 w-5 mt-0.5 shrink-0"><!-- check icon --></svg>
    <p class="text-sm font-medium">Operação realizada com sucesso.</p>
</div>

<!-- Erro -->
<div class="flex items-start gap-3 rounded-xl border p-4
            border-red-200 bg-red-50 text-red-700
            dark:border-red-800 dark:bg-red-950 dark:text-red-400">
    <svg class="h-5 w-5 mt-0.5 shrink-0"><!-- x-circle icon --></svg>
    <p class="text-sm font-medium">Ocorreu um erro. Tente novamente.</p>
</div>
```

---

## 8. Layout

### Estrutura de Página (com Sidebar)
```html
<body class="min-h-screen bg-slate-50 dark:bg-slate-950">

    <!-- Sidebar (desktop fixo, mobile off-canvas) -->
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r
                  border-slate-200 bg-white
                  dark:border-slate-800 dark:bg-slate-950
                  -translate-x-full transition-transform duration-300
                  lg:translate-x-0">
        <!-- conteúdo da sidebar -->
    </aside>

    <!-- Overlay mobile -->
    <div id="sidebar-overlay"
         class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm hidden lg:hidden">
    </div>

    <!-- Conteúdo principal -->
    <main class="min-h-screen transition-all duration-300 lg:pl-64">

        <!-- Header / Toolbar -->
        <header class="sticky top-0 z-10 flex items-center gap-4 border-b px-4 py-3
                       border-slate-200 bg-white/80 backdrop-blur-md
                       dark:border-slate-800 dark:bg-slate-950/80">
            <!-- botão hamburger (mobile) + título + ações -->
        </header>

        <!-- Corpo da página -->
        <div class="px-4 py-6 lg:px-6">
            <!-- conteúdo -->
        </div>

    </main>

</body>
```

### Sidebar Colapsável (expandido ↔ ícones)

O sidebar possui **dois estados no desktop** e **off-canvas no mobile**:

| Estado | Largura | O que exibe |
|--------|---------|-------------|
| Expandido | `w-64` | Logo + texto, rótulo de nav com ícone, avatar + nome + e-mail |
| Recolhido | `w-16` | Ícone do logo, apenas ícone do nav (tooltip no hover), só avatar |
| Mobile | off-canvas | Igual ao expandido, desliza sobre a tela |

**Regra de informação:** rótulos de seção (ex.: "PRINCIPAL") devem ser **ocultados** — poluem o layout sem agregar valor quando há poucos itens de nav.

```html
<!-- ─── SCRIPT anti-flash (primeiro no <head>) ─────────────────── -->
<script>
    (function () {
        const collapsed = localStorage.getItem('sidebar') === 'collapsed';
        if (collapsed) document.documentElement.classList.add('sidebar-collapsed');
    })();
</script>

<!-- ─── ESTILOS de transição ──────────────────────────────────── -->
<style>
    /* largura da sidebar */
    #sidebar { width: 16rem; }                          /* 256px expandido */
    html.sidebar-collapsed #sidebar { width: 4rem; }   /* 64px recolhido  */

    /* padding do conteúdo principal no desktop */
    @media (min-width: 1024px) {
        #main { padding-left: 16rem; }
        html.sidebar-collapsed #main { padding-left: 4rem; }
    }

    /* transição suave */
    #sidebar, #main { transition: width .25s ease, padding-left .25s ease; }

    /* ocultar texto quando recolhido */
    html.sidebar-collapsed .sidebar-label { display: none; }
    html.sidebar-collapsed .sidebar-section { display: none; } /* rótulo "PRINCIPAL" */
</style>
```

```html
<!-- ─── SIDEBAR ─────────────────────────────────────────────────── -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 flex flex-col overflow-hidden
              border-r border-zinc-200 bg-white
              dark:border-zinc-800 dark:bg-zinc-900
              -translate-x-full transition-transform duration-200
              lg:translate-x-0">

    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-zinc-200
                px-4 dark:border-zinc-800">
        <!-- Ícone (sempre visível) -->
        <div class="flex h-8 w-8 shrink-0 items-center justify-center
                    rounded-lg bg-zinc-900 dark:bg-white">
            <svg class="h-4 w-4 text-white dark:text-zinc-900" ...></svg>
        </div>
        <!-- Texto (some quando recolhido) -->
        <span class="sidebar-label truncate text-sm font-semibold tracking-tight">
            Gestão de Transporte
        </span>
    </div>

    <!-- Navegação -->
    <nav class="flex flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden px-2 py-4">

        <!-- Rótulo de seção — OCULTO quando recolhido (e opcional mesmo expandido) -->
        {{-- <p class="sidebar-section px-2 pb-1 text-[10px] font-medium uppercase
                  tracking-widest text-zinc-400 dark:text-zinc-600">Principal</p> --}}

        <!-- Item de nav -->
        <a href="{{ route('control-tower.index') }}"
           title="Torre de Controle"   {{-- tooltip no hover quando recolhido --}}
           class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium
                  transition-colors duration-150
                  {{ active classes }}">
            <svg class="h-4 w-4 shrink-0"><!-- ícone --></svg>
            <span class="sidebar-label truncate">Torre de Controle</span>
        </a>

        <a href="{{ route('users.index') }}"
           title="Usuários"
           class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium
                  transition-colors duration-150
                  {{ active classes }}">
            <svg class="h-4 w-4 shrink-0"><!-- ícone --></svg>
            <span class="sidebar-label truncate">Usuários</span>
        </a>
    </nav>

    <!-- Rodapé do usuário -->
    <div class="shrink-0 border-t border-zinc-200 p-2 dark:border-zinc-800">
        <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2
                       text-sm transition-colors hover:bg-zinc-100
                       dark:hover:bg-zinc-800">
            <!-- Avatar (sempre visível) -->
            <div class="flex h-8 w-8 shrink-0 items-center justify-center
                        rounded-full bg-zinc-200 dark:bg-zinc-700">
                <span class="text-xs font-semibold">AD</span>
            </div>
            <!-- Nome + e-mail (somem quando recolhido) -->
            <div class="sidebar-label min-w-0 flex-1 text-left">
                <p class="truncate text-sm font-medium text-zinc-900
                           dark:text-zinc-100">Administrador</p>
                <p class="truncate text-xs text-zinc-500">admin@macae.gov.br</p>
            </div>
        </button>
    </div>
</aside>

<!-- Overlay mobile -->
<div id="sidebar-overlay"
     class="fixed inset-0 z-40 hidden bg-black/60 backdrop-blur-sm lg:hidden"></div>

<!-- Conteúdo principal -->
<main id="main" class="min-h-screen lg:pl-64">
    <!-- Header -->
    <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b px-4
                   border-zinc-200 bg-white/80 backdrop-blur-md
                   dark:border-zinc-800 dark:bg-zinc-900/80">

        <!-- Botão collapse desktop -->
        <button id="sidebar-collapse-btn"
                class="hidden lg:flex rounded-lg p-1.5 text-zinc-500
                       hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            <svg class="h-5 w-5"><!-- menu / menu-fold icon --></svg>
        </button>

        <!-- Botão hamburger mobile -->
        <button id="sidebar-toggle-btn"
                class="flex lg:hidden rounded-lg p-1.5 text-zinc-500
                       hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            <svg class="h-5 w-5"><!-- menu icon --></svg>
        </button>

        <h1 class="text-base font-semibold">{{ $title }}</h1>
    </header>

    <div class="px-4 py-6 lg:px-6">{{ $slot }}</div>
</main>
```

```javascript
// ── Toggle collapse desktop ───────────────────────────────────────
document.getElementById('sidebar-collapse-btn')?.addEventListener('click', () => {
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'expanded');
});

// ── Toggle mobile (off-canvas) ────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');

document.getElementById('sidebar-toggle-btn')?.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
});
overlay?.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
});
```

### Grid de Cards Responsivo
```html
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    <!-- cards -->
</div>
```

### Formulário Centrado
```html
<div class="mx-auto max-w-lg">
    <div class="rounded-2xl border bg-white p-8 shadow-sm
                border-slate-200 dark:border-zinc-800 dark:bg-zinc-900">
        <!-- conteúdo do form -->
    </div>
</div>
```

---

## 9. Dark Mode

### Implementação (no `<head>`)
```html
<script>
    (function () {
        const stored = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (stored === 'dark' || (!stored && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
```

### Toggle
```javascript
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}
```

### Mapeamento de cores
| Light | Dark |
|-------|------|
| `bg-white` | `dark:bg-zinc-900` |
| `bg-slate-50` | `dark:bg-slate-950` |
| `border-slate-200` | `dark:border-zinc-800` |
| `text-slate-900` | `dark:text-zinc-100` |
| `text-slate-500` | `dark:text-zinc-400` |
| `text-slate-400` | `dark:text-zinc-500` |

---

## 10. Animações e Transições

| Duração | Uso |
|---------|-----|
| `duration-150` | Hover de botão, focus de input |
| `duration-200` | Modal, dropdown, opacity |
| `duration-300` | Sidebar collapse |
| `duration-500` | Spinner / ícone de refresh |

### Padrões comuns
```css
/* Hover de cor */
transition-colors duration-150

/* Múltiplas propriedades */
transition-all duration-200

/* Pressionar botão */
active:scale-[0.98]

/* Fade de modal */
opacity-0 → opacity-100

/* Slide de painel */
scale-95 opacity-0 → scale-100 opacity-100

/* Sidebar */
-translate-x-full → translate-x-0
```

---

## 11. Breakpoints (Tailwind v4 padrão)

| Prefixo | Min-width | Uso típico |
|---------|-----------|------------|
| *(nenhum)* | 0px | Mobile first |
| `sm:` | 640px | Grid 2 colunas, elementos visíveis |
| `md:` | 768px | Layouts intermediários |
| `lg:` | 1024px | Sidebar fixa, grid 3 colunas |
| `xl:` | 1280px | Grid 4 colunas |
| `2xl:` | 1536px | Containers large |

---

## 12. Estados de UI

### Desabilitado
```html
class="disabled:cursor-not-allowed disabled:opacity-60"
```

### Foco (acessibilidade)
```html
class="focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
<!-- Dark -->
class="dark:focus:ring-blue-500/20 dark:focus:border-blue-500"
```

### Erro em campo
```html
class="border-red-400 bg-white focus:ring-red-500/20 dark:border-red-600 dark:bg-slate-800"
```

### Truncate
```html
class="truncate"          <!-- 1 linha -->
class="line-clamp-2"      <!-- 2 linhas -->
class="min-w-0 truncate"  <!-- dentro de flex -->
```

---

## 13. Ícones

A aplicação usa **SVG inline** com as convenções:
```html
<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
     stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="..." />
</svg>
```

| Tamanho | Classe |
|---------|--------|
| Micro (inline) | `h-3 w-3` |
| Pequeno (botão) | `h-4 w-4` |
| Médio (sidebar) | `h-5 w-5` |
| Grande (empty state) | `h-8 w-8` |

---

## 14. Checklist para Novas Telas

- [ ] Incluir script anti-flash de dark mode no `<head>`
- [ ] Usar `bg-slate-50 dark:bg-slate-950` no `<body>`
- [ ] Aplicar `font-sans` / `antialiased` no `<body>`
- [ ] Usar `Inter` via Google Fonts
- [ ] Todos os componentes com variante `dark:`
- [ ] Inputs com `focus:ring-2` para acessibilidade
- [ ] Botões com `active:scale-[0.98]`
- [ ] Textos com `truncate` / `min-w-0` em flex
- [ ] Estado vazio implementado em listas/grids
- [ ] Transições com `transition-colors duration-150` ou `transition-all duration-200`
