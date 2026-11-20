<?php

return [
    'tagline' => "music for the moment you're in",
    'language' => 'Language',

    'steps' => [
        'label' => 'Steps',
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
        'subtitle' => 'Keep it, or tap anything to change the vibe.',
        'next' => 'Fine-tune',
        'groups' => [
            'mood' => 'Mood',
            'genre' => 'Genre',
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
];
