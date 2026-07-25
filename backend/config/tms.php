<?php

declare(strict_types=1);

return [
    'bootstrap' => [
        'enabled' => env('TMS_BOOTSTRAP_ENABLED', false),

        'organization_name' => env(
            'TMS_BOOTSTRAP_ORGANIZATION_NAME',
        ),

        'owner_user_id' => env(
            'TMS_BOOTSTRAP_OWNER_USER_ID',
        ),

        'owner_name' => env(
            'TMS_BOOTSTRAP_OWNER_NAME',
        ),

        'owner_email' => env(
            'TMS_BOOTSTRAP_OWNER_EMAIL',
        ),

        'owner_password' => env(
            'TMS_BOOTSTRAP_OWNER_PASSWORD',
        ),

        'admin_user_id' => env(
            'TMS_BOOTSTRAP_ADMIN_USER_ID',
        ),

        'admin_name' => env(
            'TMS_BOOTSTRAP_ADMIN_NAME',
        ),

        'admin_email' => env(
            'TMS_BOOTSTRAP_ADMIN_EMAIL',
        ),

        'admin_password' => env(
            'TMS_BOOTSTRAP_ADMIN_PASSWORD',
        ),
    ],
];
