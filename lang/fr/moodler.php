<?php

return [
    'tagline' => 'la musique du moment que vous vivez',
    'language' => 'Langue',

    'steps' => [
        'label' => 'Étapes',
        'moment' => 'Moment',
        'atmosphere' => 'Ambiance',
        'tuning' => 'Nuances',
    ],

    'phrase' => [
        'title' => 'Quel moment vivez-vous ?',
        'subtitle' => 'Une situation, une émotion, un souvenir. Moodler le transforme en musique.',
        'placeholder' => 'ex. coucher de soleil en voiture avec ma copine après une longue semaine',
        'submit' => 'Lire mon humeur',
        'submit_again' => 'Relire mon humeur',
        'submitting' => 'Lecture de votre humeur…',
        'replace_note' => 'Relire remplacera l’ambiance et les réglages que vous avez choisis.',
    ],

    'atmosphere' => [
        'title' => 'Voici ce que nous avons compris',
        'subtitle' => 'Gardez-la ou touchez une option pour changer d’ambiance.',
        'next' => 'Ajuster les nuances',
        'groups' => [
            'mood' => 'Humeur',
            'genre' => 'Genre',
            'dayTime' => 'Moment de la journée',
            'relationship' => 'En compagnie de',
        ],
    ],

    'tuning' => [
        'title' => 'Affinez le mix',
        'subtitle' => 'Nous les avons réglés sur votre moment : ajustez-les à votre guise, puis laissez moodler choisir les morceaux.',
        'knobs' => [
            'energy' => ['label' => 'Énergie', 'low' => 'Calme', 'high' => 'Intense'],
            'popularity' => ['label' => 'Popularité', 'low' => 'Pépites cachées', 'high' => 'Grands tubes'],
            'instrumentalness' => ['label' => 'Voix', 'low' => 'Chanté', 'high' => 'Instrumental'],
        ],
    ],

    'actions' => [
        'pending' => 'Nouveaux réglages : remixez pour les écouter.',
        'create' => 'Créer ma liste',
        'remix' => 'Remixer',
        'mixing' => 'Mixage de vos morceaux…',
        'restart' => 'Recommencer',
        'restart_confirm' => 'Tout recommencer ? Votre liste actuelle sera effacée.',
    ],

    'player' => [
        'your_list' => 'Votre liste',
        'copy' => 'Copier la liste',
        'copied' => 'Copiée !',
        'open' => 'Ouvrir dans Spotify',
        'full_tracks' => 'Connectez-vous à Spotify dans ce navigateur pour écouter les morceaux en entier plutôt que des extraits.',
        'mixing' => 'Préparation d’une nouvelle liste…',
    ],

    'errors' => [
        'interpret' => 'Impossible de lire votre humeur pour le moment. Réessayez dans un instant.',
        'compose' => 'Un problème est survenu lors du choix des morceaux. Réessayez.',
        'no_tracks' => 'Aucun morceau ne correspond à ce mix. Modifiez quelques réglages et remixez.',
    ],

    'validation' => [
        'phrase_required' => 'Décrivez d’abord votre moment.',
        'phrase_min' => 'Écrivez au moins :min caractères.',
        'phrase_max' => 'Restez sous les :max caractères.',
    ],

    'options' => [
        'Dawn' => 'Aube',
        'Morning' => 'Matin',
        'Afternoon' => 'Après-midi',
        'Dusk' => 'Crépuscule',
        'Evening' => 'Soirée',
        'Night' => 'Nuit',
        'Amused' => 'Amusement',
        'Angry' => 'Colère',
        'Bored' => 'Ennui',
        'Confident' => 'Assurance',
        'Cozy' => 'Cocooning',
        'Excited' => 'Enthousiasme',
        'Grateful' => 'Gratitude',
        'Hopeful' => 'Espoir',
        'Inspirational' => 'Inspiration',
        'Joy' => 'Joie',
        'Loving' => 'Amour',
        'Melancholic' => 'Mélancolie',
        'Nostalgic' => 'Nostalgie',
        'Relaxed' => 'Détente',
        'Sad' => 'Tristesse',
        'Tired' => 'Fatigue',
        'Alone' => 'En solo',
        'Colleagues' => 'Collègues',
        'Family' => 'Famille',
        'Friends' => 'Amis',
        'Lovers' => 'En couple',
        'Classical' => 'Classique',
    ],
];
