@if(session('success'))
    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach
        </ul>
    </div>
@endif
