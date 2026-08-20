<x-app-layout title="All requests" subtitle="Every request posted, any status">
    @if ($requests->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="inbox" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No requests yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Once someone posts a request, it'll show up here.</p>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($requests as $request)
                <li>
                    @include('partials.request-card', ['request' => $request])
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    @endif
</x-app-layout>
