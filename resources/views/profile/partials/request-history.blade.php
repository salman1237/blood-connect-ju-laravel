<section>
    <x-section-title title="My requests" subtitle="Every blood request you've posted, any status." />

    @if ($myRequests->isEmpty())
        <p class="text-sm text-muted-foreground">You haven't posted a request yet.</p>
    @else
        <ul class="space-y-3">
            @foreach ($myRequests as $request)
                <li>
                    @include('partials.request-card', ['request' => $request])
                </li>
            @endforeach
        </ul>
    @endif
</section>
