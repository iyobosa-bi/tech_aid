function ticketList(config) {
    return {
        search: config.search ?? '',
        status: config.status ?? '',
        sort: config.sort,
        direction: config.direction,
        loading: false,
        request: null,

        init() {
            // "/" focuses search from anywhere on the page, unless the user is already typing.
            window.addEventListener('keydown', (event) => {
                const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName) || event.target.isContentEditable;

                if (event.key === '/' && !typing && !event.metaKey && !event.ctrlKey) {
                    event.preventDefault();
                    this.$refs.search.focus();
                }
            });
        },

        buildUrl() {
            const url = new URL(config.url, window.location.origin);
            const term = this.search.trim();

            if (term) url.searchParams.set('search', term);
            if (this.status) url.searchParams.set('status', this.status);
            if (this.sort !== 'created_at' || this.direction !== 'desc') {
                url.searchParams.set('sort', this.sort);
                url.searchParams.set('direction', this.direction);
            }

            return url;
        },

        // Search/filter changes always start again from page 1.
        refresh() {
            this.load(this.buildUrl());
        },

        clearSearch() {
            if (!this.search) return;
            this.search = '';
            this.refresh();
            this.$refs.search.focus();
        },

        clearFilters() {
            this.search = '';
            this.status = '';
            this.refresh();
        },

        // Sort headers and pagination are real links (they work without JS); intercept them here.
        navigate(event) {
            const link = event.target.closest('a[data-ajax]');

            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) return;

            event.preventDefault();

            const url = new URL(link.href);
            this.sort = url.searchParams.get('sort') ?? 'created_at';
            this.direction = url.searchParams.get('direction') ?? 'desc';
            this.load(url);
        },

        async load(url) {
            // A newer keystroke supersedes any request still in flight.
            this.request?.abort();
            const request = (this.request = new AbortController());
            this.loading = true;

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: request.signal,
                });

                // Expired session (redirected to login) or a server error: fall back to a normal page load.
                if (response.redirected || !response.ok) {
                    window.location.href = response.redirected ? response.url : url;
                    return;
                }

                this.$refs.results.innerHTML = await response.text();
                window.history.replaceState(null, '', url);
                lucide.createIcons();
            } catch (error) {
                if (error.name !== 'AbortError') window.location.href = url;
            } finally {
                if (this.request === request) this.loading = false;
            }
        },
    };
}
