<?php

return [
    'messages' => [
        'required' => 'The :attribute field is required.',
        'string' => 'The :attribute must be a string.',
        'array' => 'The :attribute must be an array.',
        'boolean' => 'The :attribute field must be true or false.',
        'date' => 'The :attribute is not a valid date.',
        'after' => 'The :attribute must be a date after :date.',
        'max' => 'The :attribute may not be greater than :max characters.',
        'in' => 'The selected :attribute is invalid.',
    ],
    'attributes' => [
        'name' => 'token name',
        'abilities' => 'abilities',
        'abilities.*' => 'ability',
        'expires_at' => 'expiration date',
        'enabled' => 'enabled state',
        'locale' => 'locale',
    ],
];
