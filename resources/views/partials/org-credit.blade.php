@if ($setting->funded_by || $setting->maintained_by)
    <div class="space-y-2 text-center">
        @if ($setting->funded_by)
            <div class="flex flex-col items-center gap-1">
                @if ($setting->funded_by_logo_url)
                    <img src="{{ $setting->funded_by_logo_url }}" alt="" class="h-8 w-auto object-contain">
                @endif
                <p class="text-xs text-muted-foreground">Implemented &amp; funded by {{ $setting->funded_by }}</p>
            </div>
        @endif
        @if ($setting->maintained_by)
            <div class="flex flex-col items-center gap-1">
                @if ($setting->maintained_by_logo_url)
                    <img src="{{ $setting->maintained_by_logo_url }}" alt="" class="h-8 w-auto object-contain">
                @endif
                <p class="text-xs text-muted-foreground">Maintained by {{ $setting->maintained_by }}</p>
            </div>
        @endif
    </div>
@endif
