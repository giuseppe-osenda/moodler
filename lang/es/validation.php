<?php

/*
 * Only the rules the sign-up and sign-in forms use; anything else falls back to English.
 */

return [
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'email' => 'El campo :attribute debe ser un email válido.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'password' => [
        'letters' => 'La :attribute debe contener al menos una letra.',
        'mixed' => 'La :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'La :attribute debe contener al menos un número.',
        'symbols' => 'La :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute ha aparecido en una filtración de datos. Elige otra.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'Este :attribute ya está en uso.',
    'attributes' => [
        'name' => 'nombre',
        'email' => 'email',
        'password' => 'contraseña',
    ],
];
