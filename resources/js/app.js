/**
 * Loads Spotify's iFrame API once and resolves with its IFrameAPI object.
 * @see https://developer.spotify.com/documentation/embeds/references/iframe-api
 */
let spotifyApiPromise = null;

function loadSpotifyIframeApi() {
    if (!spotifyApiPromise) {
        spotifyApiPromise = new Promise((resolve) => {
            window.onSpotifyIframeApiReady = (IFrameAPI) => resolve(IFrameAPI);

            const script = document.createElement('script');
            script.src = 'https://open.spotify.com/embed/iframe-api/v1';
            script.async = true;
            document.body.appendChild(script);
        });
    }

    return spotifyApiPromise;
}

/**
 * Lowercase and strip accents, so "electronica" finds "Electrónica".
 */
function normalizeForSearch(text) {
    return (text ?? '').normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim();
}

/**
 * Bring a newly revealed section into view, unless it is already on screen
 * (on wide screens the player sits beside the board and is always visible).
 */
window.addEventListener('section-revealed', (event) => {
    requestAnimationFrame(() => {
        const section = document.getElementById(`section-${event.detail.section}`);

        if (!section) {
            return;
        }

        const { top, bottom } = section.getBoundingClientRect();
        const isOnScreen = top >= 0 && top < window.innerHeight * 0.6 && bottom > 0;

        if (!isOnScreen) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

window.addEventListener('locale-changed', (event) => {
    document.documentElement.lang = event.detail.locale;
});

document.addEventListener('alpine:init', () => {
    /**
     * Search box for a group of mood or genre chips. Typing filters the chips at once;
     * after a pause the server also asks the AI for matches beyond the built-in options.
     * Chips the AI recognizes (say, "Sad" for "giù di morale") stay visible too.
     */
    window.Alpine.data('optionSearch', (field) => ({
        query: '',
        searching: false,

        get term() {
            return normalizeForSearch(this.query);
        },

        get catalog() {
            return this.$wire.catalogSearches[field];
        },

        get isCurrent() {
            return this.catalog.query === this.query.trim();
        },

        get foundValues() {
            return this.isCurrent ? this.catalog.results.map((result) => normalizeForSearch(result.value)) : [];
        },

        matches(chip) {
            return normalizeForSearch(chip.dataset.search).includes(this.term)
                || this.foundValues.includes(normalizeForSearch(chip.dataset.value));
        },

        isVisible(chip) {
            return this.term === '' || chip.dataset.selected === 'true' || this.matches(chip);
        },

        get hasMatchingChips() {
            return [...this.$refs.options.querySelectorAll('[data-value]')].some((chip) => this.matches(chip));
        },

        get isSettled() {
            return this.term.length >= 2 && this.isCurrent && !this.searching;
        },

        get showEmpty() {
            return this.isSettled && !this.catalog.failed && this.catalog.results.length === 0 && !this.hasMatchingChips;
        },

        get showFailed() {
            return this.isSettled && this.catalog.failed && !this.hasMatchingChips;
        },

        async lookup() {
            const query = this.query.trim();

            if (query.length < 2) {
                this.searching = false;

                return;
            }

            this.searching = true;

            try {
                await this.$wire.searchCatalog(field, query);
            } finally {
                if (this.query.trim() === query) {
                    this.searching = false;
                }
            }
        },

        clear() {
            this.query = '';
            this.searching = false;
        },
    }));

    /**
     * One tooltip for every chip with a data-tooltip, positioned next to the chip
     * and kept inside the viewport.
     */
    window.Alpine.data('chipTooltip', () => ({
        text: '',
        visible: false,
        x: 0,
        y: 0,
        anchor: null,

        show(event) {
            const chip = event.target.closest?.('[data-tooltip]');

            if (!chip || chip === this.anchor) {
                return;
            }

            this.anchor = chip;
            this.text = chip.dataset.tooltip;
            this.visible = true;
            this.$nextTick(() => this.position());
        },

        hide(event) {
            if (!this.anchor || (event.relatedTarget && this.anchor.contains(event.relatedTarget))) {
                return;
            }

            this.close();
        },

        close() {
            this.visible = false;
            this.anchor = null;
        },

        position() {
            if (!this.anchor) {
                return;
            }

            const margin = 8;
            const chip = this.anchor.getBoundingClientRect();
            const tooltip = this.$refs.tooltip;
            const above = chip.top - tooltip.offsetHeight - margin;

            this.x = Math.min(
                Math.max(chip.left + chip.width / 2 - tooltip.offsetWidth / 2, margin),
                window.innerWidth - tooltip.offsetWidth - margin,
            );
            this.y = above >= margin ? above : chip.bottom + margin;
        },
    }));

    /**
     * One Spotify embed driven by our own track list: click a row to play it,
     * and the next track starts when the current one ends.
     */
    window.Alpine.data('spotifyPlayer', (initialUris = []) => ({
        uris: initialUris,
        current: 0,
        controller: null,
        playWhenReady: false,
        lastPosition: 0,
        lastDuration: 0,
        advancing: false,
        copied: false,

        init() {
            loadSpotifyIframeApi().then((IFrameAPI) => {
                IFrameAPI.createController(
                    this.$refs.embed,
                    { uri: this.uris[0], width: '100%', height: 152 },
                    (controller) => {
                        this.controller = controller;
                        controller.addListener('ready', () => {
                            if (this.playWhenReady) {
                                this.playWhenReady = false;
                                controller.play();
                            }
                        });
                        controller.addListener('playback_update', (event) => this.onPlaybackUpdate(event.data));
                    },
                );
            });
        },

        destroy() {
            this.controller?.destroy();
        },

        load(uris) {
            this.uris = uris;
            this.current = 0;
            this.advancing = false;
            this.controller?.loadUri(uris[0]);
        },

        play(index) {
            if (!this.uris[index]) {
                return;
            }

            this.current = index;
            this.lastPosition = 0;
            this.lastDuration = 0;

            if (!this.controller) {
                return;
            }

            this.playWhenReady = true;
            this.controller.loadUri(this.uris[index]);
        },

        next() {
            if (this.current < this.uris.length - 1) {
                this.play(this.current + 1);
            }
        },

        onPlaybackUpdate({ isPaused, position, duration }) {
            const nearEnd = (pos, dur) => dur > 0 && pos >= dur - 1500;

            // A new track is playing normally: allow the next auto-advance.
            if (!isPaused && !nearEnd(position, duration)) {
                this.advancing = false;
            }

            const ended = (isPaused && nearEnd(position, duration))
                || (isPaused && nearEnd(this.lastPosition, this.lastDuration) && position < this.lastPosition)
                || (!isPaused && duration > 0 && position >= duration);

            this.lastPosition = position;
            this.lastDuration = duration;

            if (ended && !this.advancing) {
                this.advancing = true;
                this.next();
            }
        },

        async copy(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => (this.copied = false), 1800);
            } catch {
                this.copied = false;
            }
        },
    }));
});
