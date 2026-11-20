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

window.addEventListener('locale-changed', (event) => {
    document.documentElement.lang = event.detail.locale;
});

document.addEventListener('alpine:init', () => {
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
