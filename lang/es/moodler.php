<?php

return [
    'tagline' => 'música para el momento que estás viviendo',
    'language' => 'Idioma',

    'steps' => [
        'label' => 'Pasos',
        'moment' => 'Momento',
        'atmosphere' => 'Ambiente',
        'tuning' => 'Matices',
    ],

    'phrase' => [
        'title' => '¿Qué momento estás viviendo?',
        'subtitle' => 'Una situación, una emoción, un recuerdo. Moodler lo convierte en música.',
        'placeholder' => 'p. ej. atardecer en el coche con mi novia tras una semana larga',
        'submit' => 'Lee mi mood',
        'submit_again' => 'Vuelve a leer mi mood',
        'submitting' => 'Leyendo tu mood…',
        'replace_note' => 'Al volver a leerlo se reemplazará el ambiente que elegiste.',
    ],

    'atmosphere' => [
        'title' => 'Esto es lo que entendimos',
        'subtitle' => 'Déjalo así o toca cualquier opción para cambiar el ambiente.',
        'next' => 'Ajustar matices',
        'groups' => [
            'mood' => 'Mood',
            'genre' => 'Género',
            'dayTime' => 'Momento del día',
            'relationship' => 'En compañía de',
        ],
    ],

    'tuning' => [
        'title' => 'Ajusta la mezcla',
        'subtitle' => 'Mueve los controles y deja que moodler elija las canciones.',
        'knobs' => [
            'energy' => ['label' => 'Energía', 'low' => 'Calma', 'high' => 'Intensa'],
            'popularity' => ['label' => 'Popularidad', 'low' => 'Joyas ocultas', 'high' => 'Grandes éxitos'],
            'instrumentalness' => ['label' => 'Voz', 'low' => 'Cantada', 'high' => 'Instrumental'],
        ],
    ],

    'actions' => [
        'pending' => 'Nuevos ajustes: remezcla para escucharlos.',
        'create' => 'Crear mi lista',
        'remix' => 'Remezclar',
        'mixing' => 'Mezclando tus canciones…',
        'restart' => 'Empezar de nuevo',
        'restart_confirm' => '¿Empezar desde cero? Tu lista actual se borrará.',
    ],

    'player' => [
        'your_list' => 'Tu lista',
        'copy' => 'Copiar lista',
        'copied' => '¡Copiada!',
        'open' => 'Abrir en Spotify',
        'full_tracks' => 'Inicia sesión en Spotify en este navegador para escuchar las canciones completas en lugar de avances.',
        'mixing' => 'Preparando una nueva lista…',
    ],

    'errors' => [
        'interpret' => 'Ahora mismo no podemos leer tu mood. Inténtalo de nuevo en un momento.',
        'compose' => 'Algo salió mal al elegir tus canciones. Inténtalo de nuevo.',
        'no_tracks' => 'Ninguna canción encaja con esta mezcla. Cambia algunos ajustes y remezcla.',
    ],

    'validation' => [
        'phrase_required' => 'Primero cuéntanos tu momento.',
        'phrase_min' => 'Escribe al menos :min caracteres.',
        'phrase_max' => 'No superes los :max caracteres.',
    ],

    'options' => [
        'Dawn' => 'Amanecer',
        'Morning' => 'Mañana',
        'Afternoon' => 'Tarde',
        'Dusk' => 'Atardecer',
        'Evening' => 'Anochecer',
        'Night' => 'Noche',
        'Amused' => 'Diversión',
        'Angry' => 'Enfado',
        'Bored' => 'Aburrimiento',
        'Confident' => 'Seguridad',
        'Cozy' => 'Calidez',
        'Excited' => 'Entusiasmo',
        'Grateful' => 'Gratitud',
        'Hopeful' => 'Esperanza',
        'Inspirational' => 'Inspiración',
        'Joy' => 'Alegría',
        'Loving' => 'Amor',
        'Melancholic' => 'Melancolía',
        'Nostalgic' => 'Nostalgia',
        'Relaxed' => 'Relax',
        'Sad' => 'Tristeza',
        'Tired' => 'Cansancio',
        'Alone' => 'En soledad',
        'Colleagues' => 'Compañeros',
        'Family' => 'Familia',
        'Friends' => 'Amigos',
        'Lovers' => 'En pareja',
        'Classical' => 'Clásica',
        'Latin' => 'Latina',
    ],
];
