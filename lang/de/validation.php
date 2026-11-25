<?php

/*
 * Only the rules the sign-up and sign-in forms use; anything else falls back to English.
 */

return [
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'lowercase' => ':attribute muss kleingeschrieben sein.',
    'max' => [
        'string' => ':attribute darf höchstens :max Zeichen haben.',
    ],
    'min' => [
        'string' => ':attribute muss mindestens :min Zeichen haben.',
    ],
    'password' => [
        'letters' => ':attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':attribute muss Groß- und Kleinbuchstaben enthalten.',
        'numbers' => ':attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Dieses :attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],
    'required' => ':attribute ist ein Pflichtfeld.',
    'string' => ':attribute muss ein Text sein.',
    'unique' => 'Diese :attribute wird bereits verwendet.',
    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
    ],
];
