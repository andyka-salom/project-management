<?php

namespace App\Filament\Resources\ProjectRequests\Pages;

use App\Filament\Resources\ProjectRequests\ProjectRequestResource;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\ProjectRequest;
use App\Models\User;
use App\Services\ProjectRequestService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewProjectRequest extends ViewRecord
{
    protected static string $resource = ProjectRequestResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $record = $this->record;

        return [
            EditAction::make()
                ->visible(fn () => in_array($record->status, [
                    ProjectRequest::STATUS_DRAFT,
                    ProjectRequest::STATUS_PENDING_ANALYSIS,
                ])),

            Action::make('assign_analyst')
                ->label('Assign Analyst')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn () => $user->can('assign_analyst_project_request')
                    && $record->status === ProjectRequest::STATUS_DRAFT)
                ->form([
                    Select::make('analyst_id')
                        ->label('System Analyst')
                        ->options(
                            User::role('system_analyst')->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    $analyst = User::findOrFail($data['analyst_id']);
                    $service = app(ProjectRequestService::class);
                    $service->assignAnalyst($record, $analyst, $user);

                    Notification::make()
                        ->title('Analyst assigned successfully')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'analyst_id']);
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            Action::make('submit_analysis')
                ->label('Submit Analysis')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->visible(fn () => $user->can('submit_analysis_project_request')
                    && $record->status === ProjectRequest::STATUS_PENDING_ANALYSIS
                    && $record->analyst_id === $user->id)
                ->requiresConfirmation()
                ->modalHeading('Submit Analysis')
                ->modalDescription('Are you sure you want to submit your analysis? Make sure all analysis fields are filled.')
                ->action(function () use ($record, $user) {
                    if (empty($record->requirement_analysis) && empty($record->feasibility_study) && empty($record->technical_notes)) {
                        Notification::make()
                            ->title('Please fill at least one analysis field before submitting')
                            ->danger()
                            ->send();
                        return;
                    }

                    $service = app(ProjectRequestService::class);
                    $service->submitAnalysis($record, $user);

                    Notification::make()
                        ->title('Analysis submitted successfully')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            Action::make('submit_recommendation')
                ->label('Submit Recommendation')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->visible(fn () => $user->can('recommend_project_request')
                    && $record->status === ProjectRequest::STATUS_ANALYSIS_SUBMITTED)
                ->form([
                    Select::make('recommendation')
                        ->label('Recommendation')
                        ->options([
                            'approve' => 'Approve - Request should proceed',
                            'reject' => 'Reject - Request should not proceed',
                        ])
                        ->required(),
                    RichEditor::make('reason')
                        ->label('Reason / Justification')
                        ->required(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    $service = app(ProjectRequestService::class);
                    $service->submitRecommendation($record, $user, $data['recommendation'], $data['reason']);

                    Notification::make()
                        ->title('Recommendation submitted successfully')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            Action::make('approve_request')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $user->can('approve_project_request')
                    && in_array($record->status, [
                        ProjectRequest::STATUS_RECOMMENDED_APPROVE,
                        ProjectRequest::STATUS_RECOMMENDED_REJECT,
                    ]))
                ->form([
                    RichEditor::make('reason')
                        ->label('Approval Notes')
                        ->required(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    $service = app(ProjectRequestService::class);
                    $project = $service->approveAndCreateProject($record, $user, $data['reason']);

                    Notification::make()
                        ->title("Request approved! Project '{$project->name}' created.")
                        ->success()
                        ->send();

                    $this->redirect(ViewProject::getUrl(['record' => $project]));
                }),

            Action::make('reject_request')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $user->can('approve_project_request')
                    && in_array($record->status, [
                        ProjectRequest::STATUS_RECOMMENDED_APPROVE,
                        ProjectRequest::STATUS_RECOMMENDED_REJECT,
                    ]))
                ->form([
                    RichEditor::make('reason')
                        ->label('Rejection Reason')
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Reject Request')
                ->modalDescription('Are you sure you want to reject this request?')
                ->action(function (array $data) use ($record, $user) {
                    $service = app(ProjectRequestService::class);
                    $service->reject($record, $user, $data['reason']);

                    Notification::make()
                        ->title('Request rejected')
                        ->warning()
                        ->send();

                    $this->redirect(static::getUrl(['record' => $record]));
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Request Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => ProjectRequest::getStatusColor($state))
                                    ->formatStateUsing(fn (string $state): string => ProjectRequest::getStatuses()[$state] ?? $state),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('priority')
                                    ->badge()
                                    ->color(fn (string $state): string => ProjectRequest::getPriorityColor($state)),
                                TextEntry::make('requester.name')
                                    ->label('Requested By'),
                                TextEntry::make('requested_deadline')
                                    ->label('Deadline')
                                    ->date('d/m/Y')
                                    ->placeholder('Not set'),
                            ]),
                        TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('business_justification')
                            ->label('Business Justification')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Attachments')
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('file_name')
                                    ->hiddenLabel()
                                    ->icon('heroicon-o-paper-clip')
                                    ->url(fn ($record) => \Illuminate\Support\Facades\Storage::disk('public')->url($record->file_path))
                                    ->openUrlInNewTab()
                                    ->color('primary'),
                            ])
                            ->columns(1),
                    ])
                    ->visible(fn ($record) => $record->attachments()->exists()),

                Section::make('Analyst Assignment')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('analyst.name')
                                    ->label('Assigned Analyst')
                                    ->placeholder('Not assigned'),
                                TextEntry::make('analysis_submitted_at')
                                    ->label('Analysis Submitted At')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Not submitted'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->analyst_id !== null),

                Section::make('Requirement Analysis')
                    ->schema([
                        TextEntry::make('requirement_analysis')
                            ->label('Requirement Analysis')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('Not provided'),
                        TextEntry::make('feasibility_study')
                            ->label('Feasibility Study')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('Not provided'),
                        TextEntry::make('technical_notes')
                            ->label('Technical Notes')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('Not provided'),
                    ])
                    ->visible(fn ($record) => $record->requirement_analysis || $record->feasibility_study || $record->technical_notes)
                    ->collapsible(),

                Section::make('Manager Recommendation')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('manager_recommendation')
                                    ->label('Recommendation')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'approve' => 'success',
                                        'reject' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'approve' => 'Approve',
                                        'reject' => 'Reject',
                                        default => '-',
                                    }),
                                TextEntry::make('recommender.name')
                                    ->label('Recommended By'),
                            ]),
                        TextEntry::make('manager_recommendation_reason')
                            ->label('Reason')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('recommended_at')
                            ->label('Recommended At')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->visible(fn ($record) => $record->manager_recommendation !== null)
                    ->collapsible(),

                Section::make('CTO Decision')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('cto_decision')
                                    ->label('Decision')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'approve' => 'success',
                                        'reject' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'approve' => 'Approved',
                                        'reject' => 'Rejected',
                                        default => '-',
                                    }),
                                TextEntry::make('decider.name')
                                    ->label('Decided By'),
                            ]),
                        TextEntry::make('cto_decision_reason')
                            ->label('Reason')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('decided_at')
                            ->label('Decided At')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->visible(fn ($record) => $record->cto_decision !== null)
                    ->collapsible(),

                Section::make('Linked Project')
                    ->schema([
                        TextEntry::make('project.name')
                            ->label('Project')
                            ->url(fn ($record) => $record->project_id
                                ? ViewProject::getUrl(['record' => $record->project_id])
                                : null)
                            ->color('primary'),
                    ])
                    ->visible(fn ($record) => $record->project_id !== null),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('updated_at')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
