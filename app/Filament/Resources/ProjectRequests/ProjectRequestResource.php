<?php

namespace App\Filament\Resources\ProjectRequests;

use App\Filament\Resources\ProjectRequests\Pages\CreateProjectRequest;
use App\Filament\Resources\ProjectRequests\Pages\EditProjectRequest;
use App\Filament\Resources\ProjectRequests\Pages\ListProjectRequests;
use App\Filament\Resources\ProjectRequests\Pages\ViewProjectRequest;
use App\Filament\Resources\ProjectRequests\RelationManagers\HistoriesRelationManager;
use App\Models\ProjectRequest;
use App\Models\User;
use App\Support\DivisionAccess;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectRequestResource extends Resource
{
    protected static ?string $model = ProjectRequest::class;

    public static function getNavigationIcon(): ?string
    {
        if ('heroicon-o-document-plus' instanceof \BackedEnum) { return 'heroicon-o-document-plus'->value; }
        return (string) 'heroicon-o-document-plus';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Requests';
    }
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationLabel = 'Requests';
    protected static ?string $modelLabel = 'Request';
    protected static ?string $pluralModelLabel = 'Requests';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Select::make('division_id')
                            ->label('Division')
                            ->options(function () {
                                $user = auth()->user();
                                $query = \App\Models\Division::query()->where('is_active', true);
                                if (!DivisionAccess::hasGlobalAccess($user)) {
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
                        Select::make('requested_by')
                            ->label('Requested By (Owner)')
                            ->helperText('Reassign this request to another user. Combined with the Division above, the new owner becomes the creator and can edit it.')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn () => DivisionAccess::hasGlobalAccess(auth()->user())),
                        Select::make('priority')
                            ->options(ProjectRequest::getPriorities())
                            ->default('medium')
                            ->required(),
                        DatePicker::make('requested_deadline')
                            ->label('Requested Deadline')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('request-attachments'),
                        RichEditor::make('business_justification')
                            ->label('Business Justification')
                            ->required()
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('request-attachments'),
                        FileUpload::make('attachment_files')
                            ->label('Attachments')
                            ->multiple()
                            ->directory('project-request-attachments')
                            ->disk('public')
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->preserveFilenames()
                            ->maxSize(10240),
                    ])
                    ->columns(2),

                Section::make('Analysis')
                    ->schema([
                        RichEditor::make('requirement_analysis')
                            ->label('Requirement Analysis')
                            ->columnSpanFull(),
                        RichEditor::make('feasibility_study')
                            ->label('Feasibility Study')
                            ->columnSpanFull(),
                        RichEditor::make('technical_notes')
                            ->label('Technical Notes')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record && in_array($record->status, [
                        ProjectRequest::STATUS_PENDING_ANALYSIS,
                        ProjectRequest::STATUS_ANALYSIS_SUBMITTED,
                        ProjectRequest::STATUS_RECOMMENDED_APPROVE,
                        ProjectRequest::STATUS_RECOMMENDED_REJECT,
                        ProjectRequest::STATUS_APPROVED,
                        ProjectRequest::STATUS_REJECTED,
                    ])),

                Section::make('Manager Recommendation')
                    ->schema([
                        Select::make('manager_recommendation')
                            ->options([
                                'approve' => 'Approve',
                                'reject' => 'Reject',
                            ])
                            ->disabled(),
                        RichEditor::make('manager_recommendation_reason')
                            ->label('Reason')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record && $record->manager_recommendation !== null),

                Section::make('CTO Decision')
                    ->schema([
                        Select::make('cto_decision')
                            ->options([
                                'approve' => 'Approve',
                                'reject' => 'Reject',
                            ])
                            ->disabled(),
                        RichEditor::make('cto_decision_reason')
                            ->label('Reason')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record && $record->cto_decision !== null),
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
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => ProjectRequest::getPriorityColor($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => ProjectRequest::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => ProjectRequest::getStatuses()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->sortable(),
                TextColumn::make('analyst.name')
                    ->label('Analyst')
                    ->placeholder('Not assigned')
                    ->sortable(),
                TextColumn::make('requested_deadline')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ProjectRequest::getStatuses()),
                SelectFilter::make('priority')
                    ->options(ProjectRequest::getPriorities()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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

    public static function getRelations(): array
    {
        return [
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectRequests::route('/'),
            'create' => CreateProjectRequest::route('/create'),
            'view' => ViewProjectRequest::route('/{record}'),
            'edit' => EditProjectRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Division wall enforced by DivisionScope on the model. Global operators
        // see all; division leads see every request in their divisions; everyone
        // else sees requests they raised or were assigned to analyse.
        if (DivisionAccess::hasGlobalAccess($user)) {
            return $query;
        }

        $ledDivisionIds = $user->ledDivisionIds();

        return $query->where(function (Builder $q) use ($user, $ledDivisionIds) {
            $q->where('requested_by', $user->id)
                ->orWhere('analyst_id', $user->id);

            if (!empty($ledDivisionIds)) {
                $q->orWhereIn('project_requests.division_id', $ledDivisionIds);
            }
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        $isCto = method_exists($user, 'hasRole') && $user->hasRole('cto');
        $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('super_admin');

        if ($isCto || $isSuperAdmin) {
            $count = ProjectRequest::whereIn('status', [
                ProjectRequest::STATUS_RECOMMENDED_APPROVE,
                ProjectRequest::STATUS_RECOMMENDED_REJECT,
            ])->count();

            return $count > 0 ? (string) $count : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user || !method_exists($user, 'can')) {
            return false;
        }

        return $user->can('view_any_project::request');
    }
}
