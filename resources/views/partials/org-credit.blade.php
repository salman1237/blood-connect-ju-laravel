@if ($setting->funded_by || $setting->maintained_by)
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center sm:divide-x sm:divide-border">
        @if ($setting->funded_by)
            <div class="flex items-center gap-3 sm:px-5">
                @if ($setting->funded_by_logo_url)
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card p-1 shadow-sm">
                        <img src="{{ $setting->funded_by_logo_url }}" alt="" class="size-full object-contain">
                    </span>
                @endif
                <div class="text-left">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Implemented &amp; funded by</p>
                    <p class="text-sm font-medium text-foreground">{{ $setting->funded_by }}</p>
                </div>
            </div>
        @endif
        @if ($setting->maintained_by)
            <div class="flex items-center gap-3 sm:px-5">
                @if ($setting->maintained_by_logo_url)
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card p-1 shadow-sm">
                        <img src="{{ $setting->maintained_by_logo_url }}" alt="" class="size-full object-contain">
                    </span>
                @endif
                <div class="text-left">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Maintained by</p>
                    <p class="text-sm font-medium text-foreground">{{ $setting->maintained_by }}</p>
                </div>
            </div>
        @endif
    </div>
@endif
