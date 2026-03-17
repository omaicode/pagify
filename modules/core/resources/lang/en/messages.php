<?php

return [
    'dashboard_active' => 'Core admin shell is active.',

    'auth' => [
        'password_reset' => [
            'subject' => 'Reset your administrator password',
            'greeting' => 'Hello,',
            'intro' => 'We received a request to reset the password for your administrator account.',
            'action' => 'Reset Password',
            'expiry' => 'This reset link will expire in :count minutes.',
            'ignore' => 'If you did not request this change, you can safely ignore this email.',
        ],
    ],

    'mail' => [
        'transactional' => [
            'signature_note' => 'This is an automated transactional message. Please do not reply to this email.',
            'contact' => 'Need help? Contact us at :email',
        ],
    ],

    'api' => [
        'authentication_required' => 'Authentication required.',
        'validation_failed' => 'Validation failed.',
        'forbidden' => 'Forbidden.',
        'resource_not_found' => 'Resource not found.',
        'http_error' => 'HTTP error.',
        'internal_server_error' => 'Internal server error.',
        'module_disabled' => 'Module is disabled.',
        'module_not_found' => 'Module not found.',
        'module_locked' => 'Core module cannot be disabled.',
        'token_not_found' => 'Token not found.',
        'permission_not_found' => 'Permission not found.',
        'permission_locked' => 'Permission is locked and cannot be deleted.',
        'admin_group_not_found' => 'Administrator group not found.',
        'admin_group_locked' => 'Default administrator group is locked.',
        'admin_not_found' => 'Administrator not found.',
        'admin_delete_self_forbidden' => 'You cannot delete your own administrator account.',
        'theme_not_found' => 'Theme not found.',
        'theme_locked' => 'Default theme is locked and cannot be deleted.',
        'theme_in_use' => 'Theme is currently used in :count site(s).',
        'theme_invalid' => 'Theme manifest is invalid.',
        'theme_site_required' => 'A target site is required to activate a theme.',
        'theme_site_not_found' => 'Target site was not found.',
    ],
];
