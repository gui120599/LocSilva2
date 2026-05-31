<?php

namespace App\Filament\Resources\Orcamentos;

use App\Enums\StatusOrcamento;
use App\Filament\Resources\Orcamentos\Pages\CreateOrcamento;
use App\Filament\Resources\Orcamentos\Pages\EditOrcamento;
use App\Filament\Resources\Orcamentos\Pages\ListOrcamentos;
use App\Filament\Resources\Orcamentos\Schemas\OrcamentoForm;
use App\Filament\Resources\Orcamentos\Tables\OrcamentosTable;
use App\Models\Orcamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'Orçamento';

    protected static ?string $pluralModelLabel = 'Orçamentos';

    protected static string|UnitEnum|null $navigationGroup = 'Oficina';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return OrcamentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrcamentosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOrcamentos::route('/'),
            'create' => CreateOrcamento::route('/create'),
            'edit'   => EditOrcamento::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Orcamento::whereIn('status', [
            StatusOrcamento::Rascunho->value,
            StatusOrcamento::AguardandoAprovacao->value,
        ])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Orçamentos pendentes';
    }
}
