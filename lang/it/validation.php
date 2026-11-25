<?php

/*
 * Only the rules the sign-up and sign-in forms use; anything else falls back to English.
 */

return [
    'confirmed' => 'La conferma di :attribute non corrisponde.',
    'email' => ':attribute deve essere un indirizzo email valido.',
    'lowercase' => ':attribute deve essere in minuscolo.',
    'max' => [
        'string' => ':attribute non può superare :max caratteri.',
    ],
    'min' => [
        'string' => ':attribute deve avere almeno :min caratteri.',
    ],
    'password' => [
        'letters' => ':attribute deve contenere almeno una lettera.',
        'mixed' => ':attribute deve contenere almeno una maiuscola e una minuscola.',
        'numbers' => ':attribute deve contenere almeno un numero.',
        'symbols' => ':attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Questa :attribute è comparsa in una fuga di dati. Scegline un\'altra.',
    ],
    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => ':attribute deve essere un testo.',
    'unique' => 'Questo :attribute è già in uso.',
    'attributes' => [
        'name' => 'nome',
        'email' => 'email',
        'password' => 'password',
    ],
];
