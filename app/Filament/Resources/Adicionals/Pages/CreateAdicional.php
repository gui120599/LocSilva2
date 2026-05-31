<?php

namespace App\Filament\Resources\Adicionals\Pages;

use App\Filament\Resources\Adicionals\AdicionalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdicional extends CreateRecord
{
    protected static string $resource = AdicionalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
