<?php

namespace App\Filament\Resources\Orcamentos\Pages;

use App\Filament\Resources\Orcamentos\OrcamentoResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrcamentos extends ListRecords
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('relatorio')
                ->label('Relatório')
                ->icon('heroicon-o-document-text')
                ->url(OrcamentoResource::getUrl('relatorio')),
        ];
    }
}
