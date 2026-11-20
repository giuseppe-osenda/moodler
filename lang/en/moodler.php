<?php

return [
    'tagline' => "music for the moment you're in",
    'language' => 'Language',

    'steps' => [
        'moment' => 'Moment',
        'atmosphere' => 'Atmosphere',
        'tuning' => 'Fine-tune',
    ],

    'phrase' => [
        'title' => "What's the moment?",
        'subtitle' => 'A situation, a feeling, a memory. Moodler turns it into music.',
        'placeholder' => 'e.g. sunset drive with my girlfriend after a long week',
        'submit' => 'Read my mood',
        'submit_again' => 'Read my mood again',
        'submitting' => 'Reading your mood…',
        'replace_note' => 'Reading it again replaces the atmosphere and knobs you picked.',
    ],

    'atmosphere' => [
        'title' => "Here's what we heard",
        'subtitle' => 'Keep it, or tap anything to change the vibe. Moods and genres can be combined.',
        'next' => 'Fine-tune',
        'pick_up_to' => 'up to :max',
        'search' => [
            'moods' => ['placeholder' => 'Search moods…', 'more' => 'More moods found', 'empty' => 'No mood found for “:query”. Try another word.'],
            'genres' => ['placeholder' => 'Search genres…', 'more' => 'More genres found', 'empty' => 'No genre found for “:query”. Try another name.'],
            'failed' => 'Search is not available right now. Please try again shortly.',
            'searching' => 'Searching…',
            'clear' => 'Clear search',
        ],
        'groups' => [
            'moods' => 'Moods',
            'genres' => 'Genres',
            'dayTime' => 'Time of day',
            'relationship' => 'Listening with',
        ],
    ],

    'tuning' => [
        'title' => 'Fine-tune the mix',
        'subtitle' => 'We set them for your moment. Slide them as you like, then let moodler pick the songs.',
        'knobs' => [
            'energy' => ['label' => 'Energy', 'low' => 'Calm', 'high' => 'Intense'],
            'popularity' => ['label' => 'Popularity', 'low' => 'Hidden gems', 'high' => 'Big hits'],
            'instrumentalness' => ['label' => 'Vocals', 'low' => 'Sung', 'high' => 'Instrumental'],
        ],
    ],

    'actions' => [
        'pending' => 'New settings — remix to hear them.',
        'create' => 'Create my list',
        'remix' => 'Remix',
        'mixing' => 'Mixing your songs…',
        'restart' => 'Start over',
        'restart_confirm' => 'Start over from scratch? Your current list will be cleared.',
    ],

    'player' => [
        'your_list' => 'Your list',
        'copy' => 'Copy list',
        'copied' => 'Copied!',
        'open' => 'Open in Spotify',
        'full_tracks' => 'Log in to Spotify in this browser to hear full tracks instead of previews.',
        'mixing' => 'Mixing a new list…',
    ],

    'errors' => [
        'interpret' => 'We could not read your mood right now. Please try again in a moment.',
        'compose' => 'Something went wrong while picking your songs. Please try again.',
        'no_tracks' => 'No songs matched this mix. Try changing a few settings and remix.',
    ],

    'validation' => [
        'phrase_required' => 'Tell us about your moment first.',
        'phrase_min' => 'Write at least :min characters.',
        'phrase_max' => 'Keep it under :max characters.',
    ],

    /*
    | Display names for the built-in mood options. The English values are what
    | gets sent to the AI, so English needs no entries here.
    */
    'options' => [],

    'mood_descriptions' => [
        'Amused' => 'Light, playful music that brings out a smile.',
        'Angry' => 'Tense, powerful tracks to let the anger out.',
        'Bored' => 'Something fresh and unexpected to shake off the boredom.',
        'Confident' => 'Bold beats and swagger to feel unstoppable.',
        'Cozy' => 'Soft, warm sounds, like a blanket and a cup of tea.',
        'Determined' => 'Driving, resolute tracks for pushing on without giving up.',
        'Energetic' => 'Pounding rhythm and lots of energy to move and let off steam.',
        'Excited' => 'Sky-high energy, sing-along choruses and a rising beat.',
        'Focused' => 'Steady, unobtrusive music that helps you stay in the zone.',
        'Grateful' => 'Bright, peaceful songs for appreciating what you have.',
        'Hopeful' => 'Melodies that open up toward something better.',
        'Inspirational' => 'Epic, building tracks that push you to do great things.',
        'Joy' => 'Pure happiness: sunny melodies and an urge to dance.',
        'Loving' => 'Tender, romantic songs to dedicate to someone.',
        'Melancholic' => 'A sweet, thoughtful sadness that is lovely to listen to.',
        'Nostalgic' => 'Sounds that take you back to good memories.',
        'Relaxed' => 'Slow rhythms and soft textures for unwinding.',
        'Sad' => 'Intense, heartfelt songs that give voice to the hurt.',
        'Tired' => 'Quiet, enveloping music for when your energy has run out.',
    ],

    'genre_descriptions' => [
        'Alternative' => 'Left-of-center rock that grew out of 1980s college radio.',
        'Blues' => 'Delta-born guitars and voices: pain and release in twelve bars.',
        'Classical' => 'Orchestral and chamber music, from Bach to living composers.',
        'Country' => 'Stories of the road and rural America, with acoustic guitars and fiddles.',
        'Disco' => '1970s dance-floor groove: four-on-the-floor kicks, strings and bouncing bass.',
        'Dubstep' => 'Electronic music with deep, wobbling, half-time bass, born in early-2000s London.',
        'EDM' => 'Festival-sized electronic music: build-ups, drops and huge synths.',
        'Folk' => 'Simple acoustic songs that tell stories, rooted in tradition.',
        'Funk' => 'Tight grooves on the one, slap bass and horns that make you move.',
        'Gospel' => 'Choir singing born in Black American churches, full of joy and faith.',
        'Hip-Hop' => 'Rhymes over sampled beats, born in the Bronx in the 1970s.',
        'Indie' => 'Music from independent labels: guitars, personal ideas, off the mainstream.',
        'Jazz' => 'Improvisation, swing and rich harmony, from New Orleans to the world.',
        'Latin' => 'Warm Latin American rhythms: salsa, bossa nova, cumbia and more.',
        'Lo-Fi' => 'Soft, slightly dusty beats, made for studying or unwinding.',
        'Metal' => 'Distorted guitars, pounding drums and plenty of intensity.',
        'Pop' => 'Catchy melodies and choruses that stick in your head.',
        'Punk' => 'Short, fast, rebellious songs played with three chords and urgency.',
        'R&B' => 'Soulful vocals over smooth grooves, sensual and modern.',
        'Reggae' => 'Laid-back Jamaican offbeat rhythm, often with a message.',
        'Reggaeton' => 'Puerto Rican dembow rhythm, sensual and made for dancing.',
        'Rock' => 'Electric guitars, drums and energy: from rock ’n’ roll to stadiums.',
        'Soul' => 'Warm, intense vocals rooted in gospel, from Motown to Stax.',
        'Techno' => 'Hypnotic, repetitive electronic music born in Detroit, built for clubs.',
    ],
];
