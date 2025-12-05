<?php

namespace Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;

class ListAiModelPricings extends ListRecords
{
    protected static string $resource = AiModelPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
