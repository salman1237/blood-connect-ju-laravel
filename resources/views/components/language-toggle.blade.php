{{-- EN/বাংলা switch — a real POST to /locale/{locale}, persisted to the
     session (guests) and the user's own locale column (logged in), rather
     than local-only Alpine state. --}}
<div class="inline-flex items-center rounded-full border border-border bg-card p-0.5 text-xs font-medium">
    <form method="POST" action="{{ route('locale.update', 'en') }}">
        @csrf
        <button type="submit"
                class="rounded-full px-3 py-1 transition-colors {{ app()->getLocale() === 'en' ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground' }}">
            English
        </button>
    </form>
    <form method="POST" action="{{ route('locale.update', 'bn') }}">
        @csrf
        <button type="submit"
                class="rounded-full px-3 py-1 transition-colors {{ app()->getLocale() === 'bn' ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground' }}">
            বাংলা
        </button>
    </form>
</div>
