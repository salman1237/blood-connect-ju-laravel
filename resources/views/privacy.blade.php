<x-guest-layout>
    <x-slot name="title">Privacy Policy — Blood Connect JU</x-slot>

    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-20 border-b border-border bg-background/85 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-2 px-4 py-3">
                <x-logo compact />
                <x-button :href="route('landing')" variant="ghost" size="sm">Back home</x-button>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed text-foreground">
            <h1 class="text-2xl font-semibold">Privacy Policy</h1>
            <p class="mt-1 text-xs text-muted-foreground">Last updated: September 2, 2026</p>

            <p class="mt-6">
                Blood Connect JU ("the app", "we", "us") is a campus blood-donation coordination
                platform for Jahangirnagar University, implemented and funded by the Jahangirnagar
                University Central Students' Union (JUCSU) and maintained by Badhan, Jahangirnagar
                University. This policy explains what information the app collects, how it's used,
                and the choices you have — on both the web app (bloodconnectju.org) and the Android
                app.
            </p>

            <h2 class="mt-8 text-lg font-semibold">Information we collect</h2>
            <p class="mt-2">When you create an account and complete your donor profile, we collect:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Name and email address</li>
                <li>Password (stored as an irreversible hash — we never store or can see your actual password)</li>
                <li>Role (student, staff, or faculty), gender, and date of birth</li>
                <li>Hall and/or department, and batch (for students)</li>
                <li>Blood group and donation history you record in the app</li>
                <li>Phone number and, optionally, a separate WhatsApp number — you choose whether this is visible to other users or only to admins</li>
                <li>A profile photo, if you choose to upload one</li>
                <li>If you sign in with Google, the name, email, and profile photo Google shares with us for that purpose</li>
                <li>A push-notification device token, so the app can alert you about matching blood requests</li>
            </ul>

            <h2 class="mt-8 text-lg font-semibold">How we use it</h2>
            <p class="mt-2">Your information is used only to run the platform:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Matching you with compatible blood requests, and requesters with compatible donors</li>
                <li>Letting other signed-in users find and contact you as a donor — your phone/WhatsApp number is only shown if you've chosen to make it public (see the "hide my number" option in your profile), otherwise it's visible to admins only</li>
                <li>Sending you notifications (in-app and, if enabled, email and push) about matching requests, responses, and your own request/donation activity</li>
                <li>Basic moderation and safety (e.g. reviewing reported requests)</li>
            </ul>
            <p class="mt-2">
                We do not sell your information, and we do not share it with advertisers — this app
                has no ads and no third-party analytics or advertising SDKs.
            </p>

            <h2 class="mt-8 text-lg font-semibold">Data security</h2>
            <p class="mt-2">
                All traffic between the app and our servers is encrypted in transit (HTTPS/TLS).
                Passwords are hashed, never stored in plain text. Access to the underlying database
                is restricted to the platform's administrators.
            </p>

            <h2 class="mt-8 text-lg font-semibold">Your choices</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>You can edit or remove most of your profile information at any time from your account settings</li>
                <li>You can turn your phone number's visibility to "admins only" at any time</li>
                <li>You can turn off email notifications while keeping in-app/push notifications</li>
                <li>You can permanently delete your account at any time — this removes your profile, donation history, and posted requests. This can't be undone</li>
            </ul>

            <h2 class="mt-8 text-lg font-semibold">Contact</h2>
            <p class="mt-2">
                Questions about this policy or your data can be sent to
                <a href="mailto:support@bloodconnectju.org" class="text-primary underline">support@bloodconnectju.org</a>.
            </p>
        </main>
    </div>
</x-guest-layout>
