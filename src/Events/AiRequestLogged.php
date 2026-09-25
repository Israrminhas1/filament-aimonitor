<?php

namespace Filament\AiMonitor\Events;

use Filament\AiMonitor\Models\AiRequest;
use Illuminate\Foundation\Events\Dispatchable;

class AiRequestLogged
{
    use Dispatchable;

    public function __construct(
        public AiRequest $request,
    ) {}
}
