import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Deliberately NOT import.meta.env.VITE_* — those are baked in at `npm run
// build` time, but this app's Docker build runs before Dokploy's runtime
// env vars are ever set (createEnvFile: true injects real container env
// vars at container start, not a .env file the build step can read — see
// .claude-progress.md). window.__reverbConfig is rendered server-side by
// Blade on every request instead, so it always reflects the real running
// config regardless of when the JS bundle was built. Only the public app
// key/host/port are exposed here — REVERB_APP_SECRET never reaches the
// browser (it's what signs private-channel auth, server-side only).
const config = window.__reverbConfig;

window.Echo = config
    ? new Echo({
          broadcaster: 'reverb',
          key: config.key,
          wsHost: config.host,
          wsPort: config.port,
          wssPort: config.port,
          forceTLS: config.scheme === 'https',
          enabledTransports: ['ws', 'wss'],
      })
    : null;
