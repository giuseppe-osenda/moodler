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
 * Loads YouTube's IFrame Player API once and resolves with the YT namespace.
 * @see https://developers.google.com/youtube/iframe_api_reference
 */
let youTubeApiPromise = null;

function loadYouTubeIframeApi() {
    if (!youTubeApiPromise) {
        youTubeApiPromise = new Promise((resolve) => {
            const previousCallback = window.onYouTubeIframeAPIReady;

            window.onYouTubeIframeAPIReady = () => {
                previousCallback?.();
                resolve(window.YT);
            };

            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            script.async = true;
            document.body.appendChild(script);
        });
    }

    return youTubeApiPromise;
}

/**
 * The player each visitor picked last time. Storage can be unavailable (private mode), so failures are ignored.
 */
const PLAYBACK_SOURCE_KEY = 'moodler.playback-source';

function storedPlaybackSource() {
    try {
        return window.localStorage.getItem(PLAYBACK_SOURCE_KEY);
    } catch {
        return null;
    }
}

function storePlaybackSource(source) {
    try {
        window.localStorage.setItem(PLAYBACK_SOURCE_KEY, source);
    } catch {
        // Not remembered: the default applies next time.
    }
}

/**
 * YouTube player states and errors we react to.
 */
const YOUTUBE_ENDED = 0;
const YOUTUBE_PLAYING = 1;
const SPOTIFY_PREVIEW_MAX_MS = 31000;

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

/**
 * Open Paddle's overlay checkout with the options the server prepared.
 * Paddle sends the player back to the success URL once the payment goes through.
 */
window.addEventListener('paddle-checkout', (event) => {
    if (!window.Paddle) {
        console.error('Paddle.js is not loaded: is PADDLE_CLIENT_SIDE_TOKEN set?');

        return;
    }

    window.Paddle.Checkout.open(event.detail.options);
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
     * One track list played through YouTube (full tracks for everyone) or the Spotify embed
     * (full tracks only when signed in to Spotify in this browser). Click a row to play it;
     * the next track starts when the current one ends.
     *
     * YouTube videos are looked up on the server only when a track is about to play,
     * because YouTube allows few searches a day. The third-party players live in closure
     * variables, not in Alpine's reactive state, so they are never wrapped in proxies.
     */
    window.Alpine.data('trackPlayer', (initialTracks = [], youtubeEnabled = false) => {
        let spotifyController = null;
        let spotifyReady = null;
        let youtubePlayer = null;
        let youtubeReady = null;
        const videoLookups = new Map();

        return {
            tracks: initialTracks,
            current: 0,
            youtubeEnabled,
            source: youtubeEnabled && storedPlaybackSource() !== 'spotify' ? 'youtube' : 'spotify',
            isPlaying: false,
            notice: null,
            missing: [],
            resolving: false,
            youtubeStarted: false,
            generation: 0,
            playWhenReady: false,
            lastPosition: 0,
            lastDuration: 0,
            advancing: false,
            copied: false,

            init() {
                if (this.source === 'spotify') {
                    this.ensureSpotify();
                }
            },

            destroy() {
                spotifyController?.destroy();
                youtubePlayer?.destroy();
            },

            /**
             * A new list arrived (after a remix): start again from its first track.
             */
            load(tracks) {
                this.tracks = tracks;
                this.current = 0;
                this.generation++;
                this.missing = [];
                this.notice = null;
                this.advancing = false;
                this.resolving = false;
                this.isPlaying = false;
                videoLookups.clear();

                if (this.source === 'spotify') {
                    spotifyController?.loadUri(tracks[0]?.uri);
                } else {
                    youtubePlayer?.stopVideo();
                    this.youtubeStarted = false;
                }
            },

            play(index, autoplay = true) {
                if (!this.tracks[index]) {
                    return;
                }

                this.current = index;
                this.notice = null;

                return this.source === 'youtube' ? this.playOnYouTube(index, autoplay) : this.playOnSpotify(index, autoplay);
            },

            next() {
                if (this.current < this.tracks.length - 1) {
                    this.play(this.current + 1);
                } else {
                    this.isPlaying = false;
                }
            },

            async setSource(source) {
                if (source === this.source || (source === 'youtube' && !this.youtubeEnabled)) {
                    return;
                }

                const wasPlaying = this.isPlaying;

                spotifyController?.pause();
                youtubePlayer?.pauseVideo();
                this.source = source;
                this.notice = null;
                this.resolving = false;
                this.isPlaying = false;
                storePlaybackSource(source);

                if (source === 'spotify') {
                    await this.playOnSpotify(this.current, wasPlaying);
                } else if (wasPlaying || this.youtubeStarted) {
                    await this.playOnYouTube(this.current, wasPlaying);
                }
            },

            isStale(generation, index, source) {
                return generation !== this.generation || index !== this.current || source !== this.source;
            },

            // --- YouTube -------------------------------------------------------

            ensureYouTube() {
                youtubeReady ??= loadYouTubeIframeApi().then((YT) => new Promise((resolve) => {
                    youtubePlayer = new YT.Player(this.$refs.youtubeEmbed, {
                        width: '100%',
                        height: '100%',
                        playerVars: { playsinline: 1, rel: 0 },
                        events: {
                            onReady: () => resolve(youtubePlayer),
                            onStateChange: (event) => this.onYouTubeState(event.data),
                            onError: () => this.onYouTubeError(),
                        },
                    });
                }));

                return youtubeReady;
            },

            /**
             * Ask the server for the track's video once per list; a failed lookup may be retried later.
             */
            lookupVideo(index) {
                if (!this.tracks[index]) {
                    return Promise.resolve({ status: 'missing' });
                }

                const key = `${this.generation}:${index}`;

                if (!videoLookups.has(key)) {
                    videoLookups.set(key, this.$wire.youtubeVideo(index)
                        .catch(() => ({ status: 'unavailable' }))
                        .then((result) => {
                            if (result?.status === 'unavailable') {
                                videoLookups.delete(key);
                            }

                            return result ?? { status: 'unavailable' };
                        }));
                }

                return videoLookups.get(key);
            },

            async playOnYouTube(index, autoplay) {
                const generation = this.generation;

                this.resolving = true;
                const result = await this.lookupVideo(index);

                if (this.isStale(generation, index, 'youtube')) {
                    return;
                }

                this.resolving = false;

                if (result.status === 'missing') {
                    this.markMissing(index);

                    if (autoplay) {
                        this.next();
                    }

                    return;
                }

                if (result.status !== 'found') {
                    this.notice = 'youtube_unavailable';

                    return;
                }

                const player = await this.ensureYouTube();

                if (this.isStale(generation, index, 'youtube')) {
                    return;
                }

                this.youtubeStarted = true;
                autoplay ? player.loadVideoById(result.videoId) : player.cueVideoById(result.videoId);

                // Warm up the next track so it starts without waiting.
                this.lookupVideo(index + 1);
            },

            markMissing(index) {
                if (!this.missing.includes(index)) {
                    this.missing.push(index);
                }

                this.notice = 'youtube_missing';
            },

            onYouTubeState(state) {
                if (this.source !== 'youtube') {
                    return;
                }

                this.isPlaying = state === YOUTUBE_PLAYING;

                if (state === YOUTUBE_ENDED) {
                    this.next();
                }
            },

            /**
             * The video was removed, made private or cannot be embedded: skip it.
             */
            onYouTubeError() {
                if (this.source !== 'youtube') {
                    return;
                }

                this.markMissing(this.current);
                this.next();
            },

            // --- Spotify -------------------------------------------------------

            ensureSpotify() {
                spotifyReady ??= loadSpotifyIframeApi().then((IFrameAPI) => new Promise((resolve) => {
                    IFrameAPI.createController(
                        this.$refs.spotifyEmbed,
                        { uri: this.tracks[this.current]?.uri, width: '100%', height: 152 },
                        (controller) => {
                            spotifyController = controller;
                            controller.addListener('ready', () => {
                                if (this.playWhenReady) {
                                    this.playWhenReady = false;
                                    controller.play();
                                }
                            });
                            controller.addListener('playback_update', (event) => this.onSpotifyUpdate(event.data));
                            resolve(controller);
                        },
                    );
                }));

                return spotifyReady;
            },

            async playOnSpotify(index, autoplay) {
                this.lastPosition = 0;
                this.lastDuration = 0;

                const controller = await this.ensureSpotify();

                if (this.source !== 'spotify' || index !== this.current) {
                    return;
                }

                this.playWhenReady = autoplay;
                controller.loadUri(this.tracks[index].uri);
            },

            onSpotifyUpdate({ isPaused, position, duration }) {
                if (this.source !== 'spotify') {
                    return;
                }

                const nearEnd = (pos, dur) => dur > 0 && pos >= dur - 1500;

                this.isPlaying = !isPaused;

                // A new track is playing normally: allow the next auto-advance.
                if (!isPaused && !nearEnd(position, duration)) {
                    this.advancing = false;
                }

                // Signed-out listeners only get a 30-second preview of each track.
                if (duration > 0 && duration <= SPOTIFY_PREVIEW_MAX_MS && (this.tracks[this.current]?.durationMs ?? 0) > SPOTIFY_PREVIEW_MAX_MS + 10000) {
                    this.notice = 'preview';
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
        };
    });
});
