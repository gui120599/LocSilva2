<?php

namespace App\Filament\Resources\Orcamentos\Pages;

use App\Filament\Resources\Orcamentos\OrcamentoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrcamento extends CreateRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
