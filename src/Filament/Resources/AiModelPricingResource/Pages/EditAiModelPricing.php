<?php

namespace Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;

class EditAiModelPricing extends EditRecord
{
    protected static string $resource = AiModelPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
