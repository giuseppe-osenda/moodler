<?php

return [
    'tagline' => 'la musica per il momento che stai vivendo',
    'language' => 'Lingua',

    'steps' => [
        'label' => 'Passaggi',
        'moment' => 'Momento',
        'atmosphere' => 'Atmosfera',
        'tuning' => 'Sfumature',
    ],

    'phrase' => [
        'title' => 'Che momento stai vivendo?',
        'subtitle' => 'Una situazione, un’emozione, un ricordo. Moodler lo trasforma in musica.',
        'placeholder' => 'es. tramonto in macchina con la mia ragazza dopo una lunga settimana',
        'submit' => 'Leggi il mio mood',
        'submit_again' => 'Rileggi il mio mood',
        'submitting' => 'Sto leggendo il tuo mood…',
        'replace_note' => 'Rileggendolo, l’atmosfera e le sfumature che hai scelto verranno sostituite.',
    ],

    'atmosphere' => [
        'title' => 'Ecco cosa abbiamo capito',
        'subtitle' => 'Tienilo così, oppure tocca qualsiasi voce per cambiare atmosfera. Mood e generi si possono combinare.',
        'next' => 'Regola le sfumature',
        'pick_up_to' => 'fino a :max',
        'groups' => [
            'moods' => 'Mood',
            'genres' => 'Generi',
            'dayTime' => 'Momento della giornata',
            'relationship' => 'In compagnia di',
        ],
    ],

    'tuning' => [
        'title' => 'Regola il mix',
        'subtitle' => 'Li abbiamo regolati sul tuo momento: spostali come vuoi, poi lascia che moodler scelga i brani.',
        'knobs' => [
            'energy' => ['label' => 'Energia', 'low' => 'Calma', 'high' => 'Intensa'],
            'popularity' => ['label' => 'Popolarità', 'low' => 'Chicche nascoste', 'high' => 'Grandi successi'],
            'instrumentalness' => ['label' => 'Voce', 'low' => 'Cantato', 'high' => 'Strumentale'],
        ],
    ],

    'actions' => [
        'pending' => 'Nuove impostazioni: rimescola per ascoltarle.',
        'create' => 'Crea la mia lista',
        'remix' => 'Rimescola',
        'mixing' => 'Sto mescolando i brani…',
        'restart' => 'Ricomincia',
        'restart_confirm' => 'Ricominciare da capo? La lista attuale verrà cancellata.',
    ],

    'player' => [
        'your_list' => 'La tua lista',
        'copy' => 'Copia lista',
        'copied' => 'Copiata!',
        'open' => 'Apri in Spotify',
        'full_tracks' => 'Accedi a Spotify in questo browser per ascoltare i brani interi invece delle anteprime.',
        'mixing' => 'Sto preparando una nuova lista…',
    ],

    'errors' => [
        'interpret' => 'Non riusciamo a leggere il tuo mood in questo momento. Riprova tra poco.',
        'compose' => 'Qualcosa è andato storto nella scelta dei brani. Riprova.',
        'no_tracks' => 'Nessun brano corrisponde a questo mix. Prova a cambiare qualche impostazione e rimescola.',
    ],

    'validation' => [
        'phrase_required' => 'Raccontaci prima il tuo momento.',
        'phrase_min' => 'Scrivi almeno :min caratteri.',
        'phrase_max' => 'Resta sotto i :max caratteri.',
    ],

    'options' => [
        'Dawn' => 'Alba',
        'Morning' => 'Mattina',
        'Afternoon' => 'Pomeriggio',
        'Dusk' => 'Tramonto',
        'Evening' => 'Sera',
        'Night' => 'Notte',
        'Amused' => 'Divertimento',
        'Angry' => 'Rabbia',
        'Bored' => 'Noia',
        'Confident' => 'Sicurezza',
        'Cozy' => 'Calore',
        'Excited' => 'Entusiasmo',
        'Grateful' => 'Gratitudine',
        'Hopeful' => 'Speranza',
        'Inspirational' => 'Ispirazione',
        'Joy' => 'Gioia',
        'Loving' => 'Amore',
        'Melancholic' => 'Malinconia',
        'Nostalgic' => 'Nostalgia',
        'Relaxed' => 'Relax',
        'Sad' => 'Tristezza',
        'Tired' => 'Stanchezza',
        'Alone' => 'In solitudine',
        'Colleagues' => 'Colleghi',
        'Family' => 'Famiglia',
        'Friends' => 'Amici',
        'Lovers' => 'In coppia',
        'Classical' => 'Classica',
    ],
];
