// The notification bell (resources/views/partials/notification-bell.blade.php, Flow 10).
// One shared Alpine store holds the feed and polls it, so the desktop and mobile bells
// never double the requests. The layout prints the first feed into #notification-feed.
document.addEventListener('alpine:init', () => {
    const source = document.getElementById('notification-feed');
    const POLL_EVERY_MS = 15000;

    if (!source) return;

    Alpine.store('notifications', {
        unread: 0,
        groups: [],
        loading: false,
        stopped: false,
        timer: null,

        init() {
            Object.assign(this, JSON.parse(source.textContent));
            this.schedule();

            // No polling in a background tab; catch up as soon as it's looked at again.
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.refresh();
            });
        },

        get badge() {
            return this.unread > 9 ? '9+' : String(this.unread);
        },

        schedule() {
            clearTimeout(this.timer);
            if (!this.stopped) this.timer = setTimeout(() => this.refresh(), POLL_EVERY_MS);
        },

        async refresh() {
            clearTimeout(this.timer);
            if (this.stopped || this.loading || document.hidden) return;

            this.loading = true;

            try {
                const response = await fetch(source.dataset.url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                // Signed out (session expired or logged out in another tab): stop asking.
                if (response.status === 401 || response.status === 419 || response.redirected) {
                    this.stopped = true;
                    return;
                }

                if (response.ok) {
                    const feed = await response.json();
                    this.unread = feed.unread;
                    this.groups = feed.groups;
                }
            } catch (error) {
                // Offline or a network blip: just try again on the next round.
            } finally {
                this.loading = false;
                this.schedule();
            }
        },
    });
});
