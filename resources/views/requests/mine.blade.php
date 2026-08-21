<x-app-layout title="My requests" subtitle="Every blood request you've posted, any status">
    @if ($myRequests->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="inbox" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">You haven't posted a request yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Requests you post will show up here so you can track their status.</p>
            <x-button :href="route('requests.create')" variant="outline" class="mt-4">Post a request</x-button>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($myRequests as $request)
                <li>@include('partials.request-card', ['request' => $request])</li>
            @endforeach
        </ul>
    @endif
</x-app-layout>
