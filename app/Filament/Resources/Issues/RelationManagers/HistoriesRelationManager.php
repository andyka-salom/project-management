<?php

namespace App\Filament\Resources\Issues\RelationManagers;

use App\Models\Issue;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'info',
                        'cto_decided' => 'purple',
                        'action_performed' => 'warning',
                        'verification_rejected' => 'danger',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Created',
                        'cto_decided' => 'CTO Decision',
                        'action_performed' => 'Action Performed',
                        'verification_rejected' => 'Sent Back',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                TextColumn::make('from_status')
                    ->label('From')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Issue::getStatuses()[$state] ?? $state) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? Issue::getStatusColor($state) : 'gray'),
                TextColumn::make('to_status')
                    ->label('To')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Issue::getStatuses()[$state] ?? $state) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? Issue::getStatusColor($state) : 'gray'),
                TextColumn::make('notes')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
