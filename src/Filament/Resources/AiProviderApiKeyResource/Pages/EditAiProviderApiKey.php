<?php

namespace Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;

class EditAiProviderApiKey extends EditRecord
{
    protected static string $resource = AiProviderApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
