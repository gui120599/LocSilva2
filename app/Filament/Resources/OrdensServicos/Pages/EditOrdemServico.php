<?php

namespace App\Filament\Resources\OrdensServicos\Pages;

use App\Filament\Resources\OrdensServicos\OrdemServicoResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdemServico extends EditRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimir')
                ->label('Imprimir OS')
                ->icon('heroicon-s-printer')
                ->color('gray')
                ->url(fn(): string => route('print-os', ['id' => $this->record->id]))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
