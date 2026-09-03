<x-guest-layout>
    <x-slot name="title">Account Deletion — Blood Connect JU</x-slot>

    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-20 border-b border-border bg-background/85 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-2 px-4 py-3">
                <x-logo compact />
                <x-button :href="route('landing')" variant="ghost" size="sm">Back home</x-button>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed text-foreground">
            <h1 class="text-2xl font-semibold">Account Deletion</h1>
            <p class="mt-1 text-xs text-muted-foreground">Last updated: September 3, 2026</p>

            <p class="mt-6">
                You can permanently delete your Blood Connect JU account at any time, directly
                from the app — no need to contact us first, and no waiting period.
            </p>

            <h2 class="mt-8 text-lg font-semibold">How to delete your account</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>On the web: sign in at <a href="{{ route('landing') }}" class="text-primary underline">bloodconnectju.org</a> → Profile → scroll to "Delete account"</li>
                <li>On Android: open the app → Profile → scroll to "Danger zone" → Delete account</li>
            </ul>
            <p class="mt-2">You'll be asked to confirm your password before the deletion goes through.</p>

            <h2 class="mt-8 text-lg font-semibold">What gets deleted</h2>
            <p class="mt-2">Everything tied to your account, immediately and permanently:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Your profile (name, email, phone, blood group, hall/department, photo, and every other field you filled in)</li>
                <li>Your donor profile and donation history</li>
                <li>Every blood request you posted</li>
                <li>Every response you made to someone else's request</li>
                <li>Badges you earned</li>
                <li>Your push-notification device token</li>
            </ul>

            <h2 class="mt-8 text-lg font-semibold">What this means</h2>
            <p class="mt-2">
                This is a hard delete, not a deactivation — your account row and everything linked
                to it are removed from our database outright, not just hidden. It can't be undone,
                and we can't recover it for you afterward, so make sure it's what you want before
                confirming. If you signed in with Google, deleting your Blood Connect JU account
                does not touch your Google account itself — only what we stored.
            </p>

            <h2 class="mt-8 text-lg font-semibold">Prefer we do it for you?</h2>
            <p class="mt-2">
                Email <a href="mailto:salmanahmed382.jubair@gmail.com" class="text-primary underline">salmanahmed382.jubair@gmail.com</a>
                from the address on your account and we'll delete it manually — useful if you've
                lost access to your account and can't sign in to do it yourself.
            </p>

            <p class="mt-8 text-xs text-muted-foreground">
                See also our <a href="{{ route('privacy') }}" class="text-primary underline">Privacy Policy</a>
                for what we collect and how it's used before deletion.
            </p>
        </main>
    </div>
</x-guest-layout>
