<?php

namespace Filament\AiMonitor\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    protected $guarded = [];

    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
