<?php

/*
 * Only the rules the sign-up and sign-in forms use; anything else falls back to English.
 */

return [
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'lowercase' => 'Le champ :attribute doit être en minuscules.',
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'password' => [
        'letters' => 'Le :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Ce :attribute est apparu dans une fuite de données. Choisissez-en un autre.',
    ],
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être du texte.',
    'unique' => 'Cet :attribute est déjà utilisé.',
    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
    ],
];
