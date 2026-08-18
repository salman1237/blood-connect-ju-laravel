{{--
    Visual EN/বাংলা switch, decorative for now (mirrors the reference prototype,
    which also only toggles local state). Session-persisted real locale
    switching is wired up in the localization phase.
--}}
<div x-data="{ lang: '{{ str_starts_with(app()->getLocale(), 'bn') ? 'bn' : 'en' }}' }"
     class="inline-flex items-center rounded-full border border-border bg-card p-0.5 text-xs font-medium">
    <button type="button" @click="lang = 'en'"
            :class="lang === 'en' ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground'"
            class="rounded-full px-3 py-1 transition-colors">
        English
    </button>
    <button type="button" @click="lang = 'bn'"
            :class="lang === 'bn' ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground'"
            class="rounded-full px-3 py-1 transition-colors">
        বাংলা
    </button>
</div>
