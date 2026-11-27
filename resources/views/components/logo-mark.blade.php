{{--
    The moodler mascot: a little robot whose head is the pastel tile, with three equalizer bars and a smile on its visor.
    It waves, its equalizer bars bounce like a level meter and its antenna glows (animations live in app.css as .robot-*).
    Depth comes from gradients, highlights and a darker extrusion under the head and body, not from shadows.
--}}
<svg {{ $attributes->class(['robot']) }} viewBox="0 0 72 68" aria-hidden="true">
    <defs>
        <linearGradient id="robot-face" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#b4f5e1" />
            <stop offset=".5" stop-color="#a9c7ff" />
            <stop offset="1" stop-color="#c3a2ff" />
        </linearGradient>
        <linearGradient id="robot-side" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#5fc7a9" />
            <stop offset=".5" stop-color="#7393ea" />
            <stop offset="1" stop-color="#8a62e0" />
        </linearGradient>
        <linearGradient id="robot-limb" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#a9c7ff" />
            <stop offset="1" stop-color="#b893ff" />
        </linearGradient>
        <radialGradient id="robot-light" cx=".35" cy=".35" r=".75">
            <stop offset="0" stop-color="#fff4ea" />
            <stop offset=".45" stop-color="#ffc9a8" />
            <stop offset="1" stop-color="#ff8fb0" />
        </radialGradient>
        <linearGradient id="robot-visor" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#2a2340" />
            <stop offset="1" stop-color="#16121f" />
        </linearGradient>
    </defs>

    <g class="robot-bob">
        {{-- Antenna --}}
        <rect x="26.75" y="7" width="2.5" height="4.5" rx="1.25" fill="#7a5ad8" />
        <g class="robot-antenna">
            <circle cx="28" cy="4.5" r="3.8" fill="url(#robot-light)" />
            <circle cx="26.9" cy="3.4" r="1.1" fill="#fff" fill-opacity=".85" />
        </g>

        {{-- Ears --}}
        <rect x="1.5" y="21" width="6.5" height="13" rx="3.25" fill="url(#robot-side)" />
        <rect x="48" y="21" width="6.5" height="13" rx="3.25" fill="url(#robot-side)" />

        {{-- Resting arm --}}
        <path d="M17 51 11.5 60" stroke="url(#robot-limb)" stroke-width="5.5" stroke-linecap="round" />
        <circle cx="11" cy="61" r="3.6" fill="url(#robot-face)" />

        {{-- Body, with its extrusion underneath --}}
        <rect x="15" y="47.5" width="26" height="17" rx="8" fill="url(#robot-side)" />
        <rect x="15" y="45" width="26" height="17" rx="8" fill="url(#robot-face)" />
        <ellipse cx="21" cy="48.5" rx="4" ry="1.6" fill="#fff" fill-opacity=".5" />
        <circle class="robot-chest" cx="28" cy="54" r="2.6" fill="#16121f" />
        <circle class="robot-chest-light" cx="28" cy="54" r="1.5" fill="#9bf0d6" />

        {{-- Head, with its extrusion underneath --}}
        <rect x="6" y="13.5" width="44" height="34" rx="13" fill="url(#robot-side)" />
        <rect x="6" y="10" width="44" height="34" rx="13" fill="url(#robot-face)" />
        <ellipse cx="17" cy="14.6" rx="7.5" ry="2.6" fill="#fff" fill-opacity=".6" transform="rotate(-12 17 14.6)" />

        {{-- Visor with a glossy reflection --}}
        <rect x="11" y="16.5" width="34" height="22.5" rx="8" fill="url(#robot-visor)" />
        <path d="M14 19.5c2-1.6 4-2 7-2h6l-11 18c-1.4-.8-2-2.4-2-4Z" fill="#fff" fill-opacity=".08" />

        {{-- Equalizer bars and a smile --}}
        <rect class="robot-eq robot-eq-1" x="18.5" y="21.5" width="4" height="7.5" rx="2" fill="#9bf0d6" />
        <rect class="robot-eq robot-eq-2" x="26" y="19.5" width="4" height="9.5" rx="2" fill="#c9a8ff" />
        <rect class="robot-eq robot-eq-3" x="33.5" y="22.5" width="4" height="6.5" rx="2" fill="#9bf0d6" />
        <path class="robot-smile" d="M20 32.2c4.6 4.4 11.4 4.4 16 0" fill="none" stroke="#9bf0d6" stroke-width="2.6" stroke-linecap="round" />
        <circle cx="15.5" cy="33" r="1.6" fill="#ffc2dc" fill-opacity=".75" />
        <circle cx="40.5" cy="33" r="1.6" fill="#ffc2dc" fill-opacity=".75" />

        {{-- Waving arm --}}
        <g class="robot-wave">
            <path d="M41 52 57.5 40.5" stroke="url(#robot-limb)" stroke-width="5.5" stroke-linecap="round" />
            <circle cx="60" cy="38.5" r="4.6" fill="url(#robot-face)" />
            <ellipse cx="58.6" cy="36.9" rx="1.8" ry="1.1" fill="#fff" fill-opacity=".7" />
        </g>
    </g>
</svg>
