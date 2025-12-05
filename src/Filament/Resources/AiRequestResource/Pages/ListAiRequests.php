<?php

namespace Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;

class ListAiRequests extends ListRecords
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
