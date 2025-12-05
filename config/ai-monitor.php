<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model class to use for the user relationship on AI requests.
    |
    */
    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group name for the AI Monitor resources.
    | Set to null to not group them.
    |
    */
    'navigation_group' => 'AI Monitor',

    /*
    |--------------------------------------------------------------------------
    | Tenant Support
    |--------------------------------------------------------------------------
    |
    | Enable or disable multi-tenancy support. When enabled, all AI Monitor
    | models will be scoped to the current tenant using the tenant() helper.
    |
    */
    'tenant_support' => true,
];
