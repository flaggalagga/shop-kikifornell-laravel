<?php

// Only the rules used on the public site. Filament ships its own French strings.
return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'string' => 'Le champ :attribute doit être un texte.',
    'in' => 'La valeur choisie pour :attribute n’est pas valide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max éléments.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'file' => 'Le fichier :attribute doit faire au moins :min Ko.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
    ],

    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'message' => 'message',
        'zone' => 'destination',
    ],
];
