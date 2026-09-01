@if ($setting->logo_url || $setting->funded_by || $setting->maintained_by)
    <div class="flex flex-col items-center gap-2 text-center">
        @if ($setting->logo_url)
            <img src="{{ $setting->logo_url }}" alt="" class="h-10 w-auto object-contain">
        @endif
        <div class="space-y-0.5 text-xs text-muted-foreground">
            @if ($setting->funded_by)
                <p>Implemented &amp; funded by {{ $setting->funded_by }}</p>
            @endif
            @if ($setting->maintained_by)
                <p>Maintained by {{ $setting->maintained_by }}</p>
            @endif
        </div>
    </div>
@endif
