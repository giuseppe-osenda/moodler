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
 * YouTube player states and errors we react to.
 */
const YOUTUBE_ENDED = 0;
const YOUTUBE_PLAYING = 1;

/**
 * YouTube player error codes. 2 and 5 can be glitches of a player switching videos quickly;
 * the others mean this video will never play here (removed, private, not embeddable, region-blocked).
 */
const YOUTUBE_GLITCH_ERRORS = [2, 5];

/**
 * When skipping through tracks, wait this long before looking one up, so only the track
 * the listener settles on spends a YouTube search.
 */
const YOUTUBE_LOOKUP_DELAY_MS = 350;

/**
 * Look up the next track only once the current one has been playing this long.
 */
const YOUTUBE_PREFETCH_AFTER_MS = 20000;

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
     * One track list played through YouTube. Click a row to play it; the next track starts
     * when the current one ends.
     *
     * Videos are looked up on the server only when a track is about to play, because YouTube
     * allows few searches a day. The YouTube player lives in closure variables, not in
     * Alpine's reactive state, so it is never wrapped in a proxy.
     */
    window.Alpine.data('trackPlayer', (initialTracks = []) => {
        let youtubePlayer = null;
        let youtubeReady = null;
        let loadedVideo = null;
        let prefetchTimer = null;
        const videoLookups = new Map();
        const unplayableVideos = new Map();

        return {
            tracks: initialTracks,
            current: 0,
            isPlaying: false,
            notice: null,
            missing: [],
            retryMinutes: 0,
            resolving: false,
            youtubeStarted: false,
            generation: 0,
            copied: false,

            destroy() {
                clearTimeout(prefetchTimer);
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
                this.resolving = false;
                this.isPlaying = false;
                videoLookups.clear();
                unplayableVideos.clear();
                loadedVideo = null;
                clearTimeout(prefetchTimer);
                youtubePlayer?.stopVideo();
                this.youtubeStarted = false;
            },

            play(index, autoplay = true) {
                if (!this.tracks[index]) {
                    return;
                }

                this.current = index;
                this.notice = null;

                return this.playOnYouTube(index, autoplay);
            },

            next() {
                if (this.current < this.tracks.length - 1) {
                    this.play(this.current + 1);
                } else {
                    this.isPlaying = false;
                }
            },

            isStale(generation, index) {
                return generation !== this.generation || index !== this.current;
            },

            /**
             * The current track on Spotify, for listening there when YouTube cannot play it.
             */
            currentSpotifyUrl() {
                return this.tracks[this.current]?.url ?? null;
            },

            ensureYouTube() {
                youtubeReady ??= loadYouTubeIframeApi().then((YT) => new Promise((resolve) => {
                    youtubePlayer = new YT.Player(this.$refs.youtubeEmbed, {
                        width: '100%',
                        height: '100%',
                        playerVars: { playsinline: 1, rel: 0 },
                        events: {
                            onReady: () => resolve(youtubePlayer),
                            onStateChange: (event) => this.onYouTubeState(event.data),
                            onError: (event) => this.onYouTubeError(event.data, event.target?.getVideoData?.()?.video_id),
                        },
                    });
                }));

                return youtubeReady;
            },

            lookupKey(index) {
                return `${this.generation}:${index}`;
            },

            /**
             * Ask the server for the track's video once per list, leaving out videos that failed to play.
             * A lookup that hit a limit or an outage may be retried later.
             */
            lookupVideo(index) {
                if (!this.tracks[index]) {
                    return Promise.resolve({ status: 'missing' });
                }

                const unplayable = unplayableVideos.get(this.lookupKey(index)) ?? [];
                const key = `${this.lookupKey(index)}:${unplayable.length}`;

                if (!videoLookups.has(key)) {
                    videoLookups.set(key, this.$wire.youtubeVideo(index, unplayable)
                        .catch(() => ({ status: 'unavailable' }))
                        .then((result) => {
                            if (!['found', 'missing'].includes(result?.status)) {
                                videoLookups.delete(key);
                            }

                            return result ?? { status: 'unavailable' };
                        }));
                }

                return videoLookups.get(key);
            },

            hasLookup(index) {
                const unplayable = unplayableVideos.get(this.lookupKey(index)) ?? [];

                return videoLookups.has(`${this.lookupKey(index)}:${unplayable.length}`);
            },

            async playOnYouTube(index, autoplay) {
                const generation = this.generation;

                clearTimeout(prefetchTimer);
                this.resolving = true;

                // Stop the previous track at once instead of letting it play while the next one loads.
                if (loadedVideo && loadedVideo.index !== index) {
                    youtubePlayer?.pauseVideo();
                }

                if (!this.hasLookup(index)) {
                    await new Promise((resolve) => setTimeout(resolve, YOUTUBE_LOOKUP_DELAY_MS));

                    if (this.isStale(generation, index)) {
                        return;
                    }
                }

                const result = await this.lookupVideo(index);

                if (this.isStale(generation, index)) {
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

                if (result.status === 'limited') {
                    this.retryMinutes = Math.max(1, Math.ceil((result.retryAfter ?? 60) / 60));
                    this.notice = 'youtube_limited';

                    return;
                }

                if (result.status !== 'found') {
                    this.notice = 'youtube_unavailable';

                    return;
                }

                const player = await this.ensureYouTube();

                if (this.isStale(generation, index)) {
                    return;
                }

                loadedVideo = { generation, index, videoId: result.videoId, retried: false };
                this.youtubeStarted = true;
                autoplay ? player.loadVideoById(result.videoId) : player.cueVideoById(result.videoId);
            },

            markMissing(index) {
                if (!this.missing.includes(index)) {
                    this.missing.push(index);
                }

                this.notice = 'youtube_missing';
            },

            onYouTubeState(state) {
                this.isPlaying = state === YOUTUBE_PLAYING;
                clearTimeout(prefetchTimer);

                // Warm up the next track once this one is really being listened to, not while skipping.
                if (state === YOUTUBE_PLAYING) {
                    const generation = this.generation;
                    const index = this.current;

                    prefetchTimer = setTimeout(() => {
                        if (!this.isStale(generation, index)) {
                            this.lookupVideo(index + 1);
                        }
                    }, YOUTUBE_PREFETCH_AFTER_MS);
                }

                if (state === YOUTUBE_ENDED) {
                    this.next();
                }
            },

            /**
             * A glitch is retried once; a video that cannot play here is swapped for the song's next candidate.
             * Errors from a video the listener already skipped are ignored.
             */
            onYouTubeError(code, erroredVideoId) {
                const video = loadedVideo;

                if (!video || video.generation !== this.generation || video.index !== this.current) {
                    return;
                }

                if (erroredVideoId && erroredVideoId !== video.videoId) {
                    return;
                }

                if (YOUTUBE_GLITCH_ERRORS.includes(code) && !video.retried) {
                    video.retried = true;
                    youtubePlayer?.loadVideoById(video.videoId);

                    return;
                }

                const key = this.lookupKey(video.index);

                unplayableVideos.set(key, [...(unplayableVideos.get(key) ?? []), video.videoId]);
                loadedVideo = null;
                this.play(video.index);
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
