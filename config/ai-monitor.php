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
    | User Title Attribute
    |--------------------------------------------------------------------------
    |
    | The user model attribute displayed in tables and widgets.
    |
    */
    'user_title_attribute' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group name for the AI Monitor resources.
    | Set to null to not group them. Can also be set per panel with
    | AiMonitorPlugin::make()->navigationGroup('...').
    |
    */
    'navigation_group' => 'AI Monitor',

    /*
    |--------------------------------------------------------------------------
    | Tenant Support
    |--------------------------------------------------------------------------
    |
    | Enable or disable multi-tenancy support. When enabled, all AI Monitor
    | models are scoped to the current tenant, resolved from the tenant()
    | helper (e.g. stancl/tenancy) or Filament's current panel tenant.
    | Customise with Filament\AiMonitor\Support\Tenancy::resolveUsing().
    |
    */
    'tenant_support' => true,
];
