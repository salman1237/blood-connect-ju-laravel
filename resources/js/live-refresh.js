/**
 * Refetches the current page (same URL, same query filters) and swaps one
 * container's markup with the freshly-rendered version — used to react to
 * a WebSocket "something changed" signal without a manual reload. Reuses
 * the exact same Blade partials a normal page load already renders, so
 * there's no separate client-side template to keep in sync with the
 * server-rendered one. See resources/js/echo.js + .claude-progress.md.
 */
window.refreshFragment = async function refreshFragment(containerId) {
    const current = document.getElementById(containerId);
    if (!current) return;

    const response = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) return;

    const html = await response.text();
    const next = new DOMParser().parseFromString(html, 'text/html').getElementById(containerId);
    if (next) current.outerHTML = next.outerHTML;
};
