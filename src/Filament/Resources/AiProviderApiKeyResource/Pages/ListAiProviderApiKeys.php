<?php

namespace Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;

class ListAiProviderApiKeys extends ListRecords
{
    protected static string $resource = AiProviderApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
