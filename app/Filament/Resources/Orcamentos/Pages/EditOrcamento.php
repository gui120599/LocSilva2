<?php

namespace App\Filament\Resources\Orcamentos\Pages;

use App\Filament\Resources\Orcamentos\OrcamentoResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrcamento extends EditRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimir')
                ->label('Imprimir Orçamento')
                ->icon('heroicon-s-printer')
                ->color('gray')
                ->url(fn(): string => route('print-orcamento', ['id' => $this->record->id]))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
