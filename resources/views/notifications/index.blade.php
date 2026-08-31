<x-app-layout title="Notifications" subtitle="Everything that's happened, in one place">
    {{-- id="notifications-list" is the live-refresh target — a new
         notification for this user (private-App.Models.User.{id}, same
         channel the layout's own unread-badge counter listens on) triggers
         refreshFragment('notifications-list') so a new item shows up here
         without a manual reload while this page is open. --}}
    <div id="notifications-list" x-data x-init="window.Echo && window.__currentUserId && window.Echo.private('App.Models.User.' + window.__currentUserId).notification(() => refreshFragment('notifications-list'))">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted-foreground">{{ $notifications->total() }} total</p>
        @if ($notifications->contains(fn ($notification) => $notification->read_at === null))
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <x-button type="submit" variant="ghost" size="sm">Mark all as read</x-button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="bell" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No notifications yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">You'll see updates about your requests and donations here.</p>
        </div>
    @else
        <ul class="space-y-2">
            @foreach ($notifications as $notification)
                <li class="surface-panel flex items-start gap-3 p-4 {{ $notification->read_at ? '' : 'bg-accent/40' }}">
                    <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $notification->read_at ? '' : 'bg-primary' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            @if (isset($notification->data['request_id']) && Route::has('requests.show'))
                                <a href="{{ route('requests.show', $notification->data['request_id']) }}" class="hover:underline">{{ $notification->data['message'] }}</a>
                            @else
                                {{ $notification->data['message'] }}
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @unless ($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="shrink-0">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" variant="ghost" size="sm">Mark read</x-button>
                        </form>
                    @endunless
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
    </div>
</x-app-layout>
