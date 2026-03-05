<?php

return [
    'messages' => [
        'required' => 'The :attribute field is required.',
        'string' => 'The :attribute must be a string.',
        'array' => 'The :attribute must be an array.',
        'boolean' => 'The :attribute field must be true or false.',
        'date' => 'The :attribute is not a valid date.',
        'after' => 'The :attribute must be a date after :date.',
        'min' => 'The :attribute must be at least :min characters.',
        'max' => 'The :attribute may not be greater than :max characters.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'current_password' => 'The current password is incorrect.',
        'image' => 'The :attribute must be an image.',
        'mimes' => 'The :attribute must be a file of type: :values.',
        'in' => 'The selected :attribute is invalid.',
    ],
    'attributes' => [
        'name' => 'token name',
        'nickname' => 'nickname',
        'bio' => 'bio',
        'abilities' => 'abilities',
        'abilities.*' => 'ability',
        'expires_at' => 'expiration date',
        'enabled' => 'enabled state',
        'locale' => 'locale',
        'current_password' => 'current password',
        'new_password' => 'new password',
        'new_password_confirmation' => 'new password confirmation',
        'avatar' => 'avatar',
    ],
];
