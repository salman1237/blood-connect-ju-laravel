{{-- EN/বাংলা switch — a real POST to /locale/{locale}, persisted to the
     session (guests) and the user's own locale column (logged in), rather
     than local-only Alpine state. --}}
<div class="inline-flex shrink-0 items-center rounded-full border border-border bg-card p-0.5 text-[11px] font-medium sm:text-xs">
    <form method="POST" action="{{ route('locale.update', 'en') }}">
        @csrf
        <button type="submit"
                class="rounded-full px-2 py-1 transition-colors sm:px-3 {{ app()->getLocale() === 'en' ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground' }}">
            <span class="sm:hidden">EN</span>
            <span class="hidden sm:inline">English</span>
        </button>
    </form>
    <form method="POST" action="{{ route('locale.update', 'bn') }}">
        @csrf
        <button type="submit"
                class="rounded-full px-2 py-1 transition-colors sm:px-3 {{ app()->getLocale() === 'bn' ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground' }}">
            <span class="sm:hidden">বাং</span>
            <span class="hidden sm:inline">বাংলা</span>
        </button>
    </form>
</div>
