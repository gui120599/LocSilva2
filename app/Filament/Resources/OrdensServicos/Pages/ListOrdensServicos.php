<?php

namespace App\Filament\Resources\OrdensServicos\Pages;

use App\Filament\Resources\OrdensServicos\OrdemServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdensServicos extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
