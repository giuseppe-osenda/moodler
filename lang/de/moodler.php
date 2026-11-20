<?php

return [
    'tagline' => 'Musik für den Moment, den du gerade erlebst',
    'language' => 'Sprache',

    'steps' => [
        'label' => 'Schritte',
        'moment' => 'Moment',
        'atmosphere' => 'Stimmung',
        'tuning' => 'Feinschliff',
    ],

    'phrase' => [
        'title' => 'Welchen Moment erlebst du gerade?',
        'subtitle' => 'Eine Situation, ein Gefühl, eine Erinnerung. Moodler macht Musik daraus.',
        'placeholder' => 'z. B. Sonnenuntergang im Auto mit meiner Freundin nach einer langen Woche',
        'submit' => 'Lies meine Stimmung',
        'submit_again' => 'Stimmung neu lesen',
        'submitting' => 'Deine Stimmung wird gelesen…',
        'replace_note' => 'Beim erneuten Lesen werden die gewählte Stimmung und die Regler ersetzt.',
    ],

    'atmosphere' => [
        'title' => 'Das haben wir herausgehört',
        'subtitle' => 'Lass es so oder tippe auf eine Option, um die Stimmung zu ändern. Gefühle und Genres lassen sich kombinieren.',
        'next' => 'Zum Feinschliff',
        'pick_up_to' => 'bis zu :max',
        'groups' => [
            'moods' => 'Gefühle',
            'genres' => 'Genres',
            'dayTime' => 'Tageszeit',
            'relationship' => 'Zusammen mit',
        ],
    ],

    'tuning' => [
        'title' => 'Feinschliff für den Mix',
        'subtitle' => 'Wir haben sie auf deinen Moment eingestellt. Verschieb sie nach Belieben und lass moodler die Songs auswählen.',
        'knobs' => [
            'energy' => ['label' => 'Energie', 'low' => 'Ruhig', 'high' => 'Intensiv'],
            'popularity' => ['label' => 'Bekanntheit', 'low' => 'Geheimtipps', 'high' => 'Große Hits'],
            'instrumentalness' => ['label' => 'Gesang', 'low' => 'Gesungen', 'high' => 'Instrumental'],
        ],
    ],

    'actions' => [
        'pending' => 'Neue Einstellungen: remixe, um sie zu hören.',
        'create' => 'Meine Liste erstellen',
        'remix' => 'Remixen',
        'mixing' => 'Deine Songs werden gemischt…',
        'restart' => 'Neu anfangen',
        'restart_confirm' => 'Ganz von vorn beginnen? Deine aktuelle Liste wird gelöscht.',
    ],

    'player' => [
        'your_list' => 'Deine Liste',
        'copy' => 'Liste kopieren',
        'copied' => 'Kopiert!',
        'open' => 'In Spotify öffnen',
        'full_tracks' => 'Melde dich in diesem Browser bei Spotify an, um ganze Songs statt Vorschauen zu hören.',
        'mixing' => 'Eine neue Liste wird gemischt…',
    ],

    'errors' => [
        'interpret' => 'Wir können deine Stimmung gerade nicht lesen. Bitte versuche es gleich noch einmal.',
        'compose' => 'Bei der Songauswahl ist etwas schiefgelaufen. Bitte versuche es noch einmal.',
        'no_tracks' => 'Kein Song passt zu diesem Mix. Ändere ein paar Einstellungen und remixe.',
    ],

    'validation' => [
        'phrase_required' => 'Erzähl uns zuerst von deinem Moment.',
        'phrase_min' => 'Schreib mindestens :min Zeichen.',
        'phrase_max' => 'Bleib unter :max Zeichen.',
    ],

    'options' => [
        'Dawn' => 'Morgengrauen',
        'Morning' => 'Morgen',
        'Afternoon' => 'Nachmittag',
        'Dusk' => 'Abenddämmerung',
        'Evening' => 'Abend',
        'Night' => 'Nacht',
        'Amused' => 'Spaß',
        'Angry' => 'Wut',
        'Bored' => 'Langeweile',
        'Confident' => 'Selbstvertrauen',
        'Cozy' => 'Geborgenheit',
        'Excited' => 'Begeisterung',
        'Grateful' => 'Dankbarkeit',
        'Hopeful' => 'Hoffnung',
        'Inspirational' => 'Inspiration',
        'Joy' => 'Freude',
        'Loving' => 'Liebe',
        'Melancholic' => 'Melancholie',
        'Nostalgic' => 'Nostalgie',
        'Relaxed' => 'Entspannung',
        'Sad' => 'Traurigkeit',
        'Tired' => 'Müdigkeit',
        'Alone' => 'Allein',
        'Colleagues' => 'Kollegen',
        'Family' => 'Familie',
        'Friends' => 'Freunde',
        'Lovers' => 'Zu zweit',
        'Classical' => 'Klassik',
    ],
];
