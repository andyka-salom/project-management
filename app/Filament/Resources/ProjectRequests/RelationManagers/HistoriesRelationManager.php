<?php

namespace App\Filament\Resources\ProjectRequests\RelationManagers;

use App\Models\ProjectRequest;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
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
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'info',
                        'assigned_analyst' => 'info',
                        'analysis_submitted' => 'warning',
                        'recommended' => 'success',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'status_changed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Created',
                        'assigned_analyst' => 'Analyst Assigned',
                        'analysis_submitted' => 'Analysis Submitted',
                        'recommended' => 'Recommendation Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'status_changed' => 'Status Changed',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                TextColumn::make('from_status')
                    ->label('From')
                    ->formatStateUsing(fn (?string $state): string => $state ? (ProjectRequest::getStatuses()[$state] ?? $state) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? ProjectRequest::getStatusColor($state) : 'gray'),
                TextColumn::make('to_status')
                    ->label('To')
                    ->formatStateUsing(fn (string $state): string => ProjectRequest::getStatuses()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => ProjectRequest::getStatusColor($state)),
                TextColumn::make('notes')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($state))))
                        : '-')
                    ->limit(120)
                    ->tooltip(fn (?string $state): ?string => $state
                        ? trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($state))))
                        : null)
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
