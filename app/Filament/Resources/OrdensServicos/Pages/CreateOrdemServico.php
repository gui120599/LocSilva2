<?php

namespace App\Filament\Resources\OrdensServicos\Pages;

use App\Filament\Resources\OrdensServicos\OrdemServicoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdemServico extends CreateRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
