<?php

namespace App\Filament\Resources\Issues\Pages;

use App\Filament\Resources\Issues\IssueResource;
use App\Models\Issue;
use App\Services\IssueService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewIssue extends ViewRecord
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        /** @var Issue $record */
        $record = $this->record;

        return [
            EditAction::make()
                ->visible(fn () => in_array($record->status, [
                    Issue::STATUS_OPEN,
                    Issue::STATUS_CTO_QUEUE,
                    Issue::STATUS_IN_PROGRESS,
                ])),

            // Perlu keputusan CTO? Ya -> Masuk CTO Decision Queue
            Action::make('cto_decision')
                ->label('Record CTO Decision')
                ->icon('heroicon-o-scale')
                ->color('purple')
                ->visible(fn () => $user->can('decide', $record)
                    && $record->status === Issue::STATUS_CTO_QUEUE)
                ->form([
                    RichEditor::make('notes')
                        ->label('Decision & Direction')
                        ->required(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    app(IssueService::class)->decide($record, $user, $data['notes']);

                    Notification::make()->title('Decision recorded. Handed to PIC.')->success()->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            // PIC melakukan tindakan
            Action::make('perform_action')
                ->label('Submit Action')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->visible(fn () => $record->status === Issue::STATUS_IN_PROGRESS
                    && ($record->pic_id === $user->id || $user->can('act', $record)))
                ->form([
                    RichEditor::make('notes')
                        ->label('What did you do?')
                        ->required(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    app(IssueService::class)->performAction($record, $user, $data['notes']);

                    Notification::make()->title('Action submitted for verification.')->success()->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            // Manager verifikasi hasil -> Sudah selesai? Ya -> Catat solusi dan pencegahan
            Action::make('verify_resolved')
                ->label('Verify: Resolved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $user->can('verify', $record)
                    && $record->status === Issue::STATUS_VERIFYING)
                ->form([
                    RichEditor::make('solution')
                        ->label('Solution')
                        ->required(),
                    RichEditor::make('prevention')
                        ->label('Prevention (how to avoid recurrence)')
                        ->required(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    app(IssueService::class)->resolve($record, $user, $data['solution'], $data['prevention']);

                    Notification::make()->title('Issue resolved. Ready to close.')->success()->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            // Sudah selesai? Belum -> kembali ke PIC melakukan tindakan
            Action::make('verify_reject')
                ->label('Verify: Not Done')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn () => $user->can('verify', $record)
                    && $record->status === Issue::STATUS_VERIFYING)
                ->form([
                    RichEditor::make('reason')
                        ->label('What still needs to be done?')
                        ->required(),
                ])
                ->action(function (array $data) use ($record, $user) {
                    app(IssueService::class)->rejectVerification($record, $user, $data['reason']);

                    Notification::make()->title('Sent back to PIC.')->warning()->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            // Manager menutup Issue
            Action::make('close_issue')
                ->label('Close Issue')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->visible(fn () => $user->can('verify', $record)
                    && $record->status === Issue::STATUS_RESOLVED)
                ->requiresConfirmation()
                ->modalHeading('Close Issue')
                ->modalDescription('Confirm the issue is fully resolved and can be closed.')
                ->action(function () use ($record, $user) {
                    app(IssueService::class)->close($record, $user);

                    Notification::make()->title('Issue closed.')->success()->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Issue')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('title')
                                ->weight(FontWeight::Bold)
                                ->size('lg'),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => Issue::getStatusColor($state))
                                ->formatStateUsing(fn (string $state): string => Issue::getStatuses()[$state] ?? $state),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('level')
                                ->badge()
                                ->color(fn (string $state): string => Issue::getLevelColor($state))
                                ->formatStateUsing(fn (string $state): string => Issue::getLevels()[$state] ?? $state),
                            TextEntry::make('division.name')
                                ->label('Division')
                                ->placeholder('—'),
                            TextEntry::make('pic.name')
                                ->label('PIC')
                                ->placeholder('Unassigned'),
                            TextEntry::make('deadline')
                                ->date('d/m/Y')
                                ->placeholder('Not set'),
                        ]),
                        TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('creator.name')
                            ->label('Created By'),
                    ]),

                Section::make('CTO Decision')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('decider.name')->label('Decided By'),
                            TextEntry::make('decided_at')->dateTime('d/m/Y H:i'),
                        ]),
                        TextEntry::make('cto_decision_notes')
                            ->label('Decision & Direction')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Issue $record) => filled($record->cto_decision_notes))
                    ->collapsible(),

                Section::make('PIC Action')
                    ->schema([
                        TextEntry::make('action_notes')
                            ->label('Action Taken')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Issue $record) => filled($record->action_notes))
                    ->collapsible(),

                Section::make('Resolution')
                    ->schema([
                        TextEntry::make('solution')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('prevention')
                            ->label('Prevention')
                            ->html()
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextEntry::make('resolved_at')->dateTime('d/m/Y H:i')->placeholder('—'),
                            TextEntry::make('closer.name')->label('Closed By')->placeholder('—'),
                        ]),
                    ])
                    ->visible(fn (Issue $record) => filled($record->solution))
                    ->collapsible(),
            ]);
    }
}
