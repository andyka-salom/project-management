<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Issues\IssueResource;
use App\Models\Issue;
use App\Support\DivisionAccess;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Issues that need the current user to act, scoped by role/workflow stage:
 *  - CTO / Chief: issues in the decision queue.
 *  - Manager: issues awaiting verification in their divisions.
 *  - PIC (analyst / programmer / qa / member / staff): open or in-progress issues assigned to them.
 *  - Global admins: every unresolved issue.
 */
class IssueActionQueueWidget extends BaseWidget
{
    protected static ?string $heading = 'Issues Needing Action';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->emptyStateHeading('No issues need your attention')
            ->emptyStateDescription('You are all caught up.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),
                TextColumn::make('level')
                    ->badge()
                    ->color(fn (string $state): string => Issue::getLevelColor($state))
                    ->formatStateUsing(fn (string $state): string => Issue::getLevels()[$state] ?? $state),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Issue::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => Issue::getStatuses()[$state] ?? $state),
                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->placeholder('Unassigned'),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('deadline')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray')
                    ->sortable(),
            ])
            ->defaultSort('deadline', 'asc')
            ->recordUrl(fn (Issue $record): string => IssueResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25]);
    }

    protected function baseQuery(): Builder
    {
        $user = auth()->user();
        $query = Issue::query()->with(['division', 'pic']);

        if ($user->hasRole(['super_admin', 'admin']) || DivisionAccess::hasGlobalAccess($user)) {
            return $query->whereNotIn('status', [Issue::STATUS_RESOLVED, Issue::STATUS_CLOSED]);
        }

        // CTO / Chief: decision queue.
        if ($user->hasRole(['cto', 'chief'])) {
            return $query->where('status', Issue::STATUS_CTO_QUEUE);
        }

        // Manager: verify stage in their divisions (plus anything they raised).
        if ($user->hasRole('manager')) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where(function (Builder $sub) use ($user) {
                    $sub->where('status', Issue::STATUS_VERIFYING)
                        ->whereIn('division_id', $user->ledDivisionIds() ?: [0]);
                })->orWhere(function (Builder $sub) use ($user) {
                    $sub->where('created_by', $user->id)
                        ->whereNotIn('status', [Issue::STATUS_RESOLVED, Issue::STATUS_CLOSED]);
                });
            });
        }

        // Everyone else acts as PIC.
        return $query
            ->where('pic_id', $user->id)
            ->whereIn('status', [Issue::STATUS_OPEN, Issue::STATUS_IN_PROGRESS]);
    }
}
