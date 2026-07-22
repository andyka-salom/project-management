<?php

namespace App\Filament\Resources\Divisions\RelationManagers;

use App\Models\Division;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'People';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) ($ownerRecord->users_count ?? $ownerRecord->users()->count());
    }

    public function form(Schema $schema): Schema
    {
        // Used by EditAction to change a person's position within this division.
        return $schema
            ->components([
                Select::make('position')
                    ->options(Division::POSITIONS)
                    ->default('staff')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->getStateUsing(fn (Model $record): ?string => $record->pivot?->position)
                    ->formatStateUsing(fn (?string $state): string => Division::POSITIONS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'chief' => 'danger',
                        'manager' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add Person')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('position')
                            ->options(Division::POSITIONS)
                            ->default('staff')
                            ->required()
                            ->helperText('Chief & Manager see all of the division\'s data; Staff see only what they are assigned to.'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Change Position'),
                DetachAction::make()
                    ->label('Remove'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ]);
    }
}
