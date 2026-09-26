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

    /*
    |--------------------------------------------------------------------------
    | Prompt Cache Pricing
    |--------------------------------------------------------------------------
    |
    | Some providers bill prompt-cache tokens separately and report them as
    | their own counters, outside the prompt tokens. Log them in the request
    | meta as `cache_read_tokens` and `cache_write_tokens`: each costs the
    | model's input price times the multiplier below. Anthropic, and Claude
    | on Amazon Bedrock, charge 0.1x for cache reads and 1.25x for cache
    | writes with the default 5-minute TTL (2.0x with the 1-hour TTL).
    |
    | Providers without an entry do not price cache tokens. OpenAI, Gemini
    | and others include cached tokens in the prompt tokens and discount
    | them at model-specific rates, so one factor per provider would be
    | wrong for them. Add an entry for any provider that bills cache tokens
    | on top of the prompt tokens.
    |
    */
    'cache_multipliers' => [
        'anthropic' => ['read' => 0.1, 'write' => 1.25],
        'bedrock' => ['read' => 0.1, 'write' => 1.25],
    ],
];
