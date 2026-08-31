<x-app-layout title="Bulk import donors" subtitle="Upload an Excel file to register many donors at once">
    <div class="mx-auto max-w-lg space-y-5">
        @if (session('status') === 'donors-imported')
            <div class="surface-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Import finished</h3>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ session('importedCount', 0) }} donor(s) imported.
                    @if (count(session('failures', [])) > 0)
                        {{ count(session('failures')) }} row(s) skipped.
                    @endif
                </p>
                @if (count(session('failures', [])) > 0)
                    <ul class="mt-3 space-y-1 text-xs text-muted-foreground">
                        @foreach (session('failures') as $failure)
                            <li>{{ $failure }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">1. Download the template</h3>
            <p class="mt-1.5 text-sm text-muted-foreground">
                Fill in one row per donor, keeping the existing column headings.
            </p>
            <a href="{{ route('admin.donors.import.template') }}" class="mt-3 inline-block">
                <x-button type="button" variant="outline">Download template</x-button>
            </a>
        </div>

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">2. Upload the completed file</h3>
            <form method="POST" action="{{ route('admin.donors.import.store') }}" enctype="multipart/form-data" class="mt-3 space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <x-input-label for="file" value="Excel file (.xlsx, .xls, .csv)" />
                    <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium">
                    <x-input-error :messages="$errors->get('file')" />
                </div>

                <x-button type="submit">Upload and import</x-button>
            </form>
        </div>
    </div>
</x-app-layout>
