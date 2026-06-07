<?php

namespace App\Filament\Resources\OrdensServicos;

use App\Enums\StatusOrdemServico;
use App\Filament\Resources\OrdensServicos\Pages\CreateOrdemServico;
use App\Filament\Resources\OrdensServicos\Pages\EditOrdemServico;
use App\Filament\Resources\OrdensServicos\Pages\ListOrdensServicos;
use App\Filament\Resources\OrdensServicos\Schemas\OrdemServicoForm;
use App\Filament\Resources\OrdensServicos\Tables\OrdensServicosTable;
use App\Models\OrdemServico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'Ordem de Serviço';

    protected static ?string $pluralModelLabel = 'Ordens de Serviço';

    protected static string|UnitEnum|null $navigationGroup = 'Oficina';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return OrdemServicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdensServicosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'     => ListOrdensServicos::route('/'),
            'create'    => CreateOrdemServico::route('/create'),
            'edit'      => EditOrdemServico::route('/{record}/edit'),
            'relatorio' => Pages\Relatorio::route('/relatorio'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = OrdemServico::whereIn('status', [
            StatusOrdemServico::Aberta->value,
            StatusOrdemServico::EmAndamento->value,
            StatusOrdemServico::AguardandoPecas->value,
        ])->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'OS em aberto';
    }
}
