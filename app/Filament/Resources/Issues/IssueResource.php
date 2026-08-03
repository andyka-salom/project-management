<?php

namespace App\Filament\Resources\Issues;

use App\Filament\Resources\Issues\Pages\CreateIssue;
use App\Filament\Resources\Issues\Pages\EditIssue;
use App\Filament\Resources\Issues\Pages\ListIssues;
use App\Filament\Resources\Issues\Pages\ViewIssue;
use App\Filament\Resources\Issues\RelationManagers\HistoriesRelationManager;
use App\Models\Division;
use App\Models\Issue;
use App\Models\User;
use App\Support\DivisionAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Work';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Issues';

    protected static ?string $modelLabel = 'Issue';

    protected static ?string $pluralModelLabel = 'Issues';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Issue Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('division_id')
                            ->label('Division')
                            ->options(function () {
                                $user = auth()->user();
                                $query = Division::query()->where('is_active', true);
                                if (! DivisionAccess::hasGlobalAccess($user)) {
                                    $query->whereIn('id', $user->divisionIds());
                                }
                                return $query->orderBy('name')->pluck('name', 'id');
                            })
                            ->default(function () {
                                $user = auth()->user();
                                if (DivisionAccess::hasGlobalAccess($user)) {
                                    return null;
                                }
                                $ids = $user->divisionIds();
                                return count($ids) === 1 ? $ids[0] : null;
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('level')
                            ->label('Level')
                            ->options(Issue::getLevels())
                            ->default(Issue::LEVEL_MEDIUM)
                            ->required(),
                        Select::make('pic_id')
                            ->label('PIC (Person in Charge)')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('The person responsible for acting on this issue.'),
                        DatePicker::make('deadline')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Toggle::make('needs_cto_decision')
                            ->label('Needs CTO / Chief decision?')
                            ->helperText('If on, the issue enters the CTO Decision Queue before the PIC acts.')
                            ->default(false),
                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('level')
                    ->badge()
                    ->color(fn (string $state): string => Issue::getLevelColor($state))
                    ->formatStateUsing(fn (string $state): string => Issue::getLevels()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Issue::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => Issue::getStatuses()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->placeholder('Unassigned')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deadline')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(Issue::getStatuses()),
                SelectFilter::make('level')
                    ->options(Issue::getLevels()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Issue $record): bool => in_array($record->status, [
                        Issue::STATUS_OPEN,
                        Issue::STATUS_CTO_QUEUE,
                        Issue::STATUS_IN_PROGRESS,
                    ])),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Division wall enforced by DivisionScope. Leads see every issue in their
        // divisions; others see issues they raised or are the PIC for.
        if (DivisionAccess::hasGlobalAccess($user)) {
            return $query;
        }

        $ledDivisionIds = $user->ledDivisionIds();

        return $query->where(function (Builder $q) use ($user, $ledDivisionIds) {
            $q->where('created_by', $user->id)
                ->orWhere('pic_id', $user->id);

            if (! empty($ledDivisionIds)) {
                $q->orWhereIn('issues.division_id', $ledDivisionIds);
            }
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereNotIn('status', [Issue::STATUS_CLOSED, Issue::STATUS_RESOLVED])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getRelations(): array
    {
        return [
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssues::route('/'),
            'create' => CreateIssue::route('/create'),
            'view' => ViewIssue::route('/{record}'),
            'edit' => EditIssue::route('/{record}/edit'),
        ];
    }
}
