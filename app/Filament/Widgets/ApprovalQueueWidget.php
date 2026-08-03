<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProjectRequests\ProjectRequestResource;
use App\Models\ProjectRequest;
use App\Support\DivisionAccess;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Requests waiting for the current user to act on, scoped by role:
 *  - CTO / Chief / global admins: requests recommended by a manager, awaiting decision.
 *  - Manager: requests in their divisions whose analysis is in, awaiting recommendation.
 *  - System Analyst: requests assigned to them that still need analysis.
 */
class ApprovalQueueWidget extends BaseWidget
{
    protected static ?string $heading = 'Requests Awaiting Your Action';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->emptyStateHeading('Nothing waiting on you')
            ->emptyStateDescription('No requests currently need your action.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => ProjectRequest::getPriorityColor($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => ProjectRequest::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => ProjectRequest::getStatuses()[$state] ?? $state),
                TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Age')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->recordUrl(fn (ProjectRequest $record): string => ProjectRequestResource::getUrl('edit', ['record' => $record]))
            ->paginated([5, 10, 25]);
    }

    protected function baseQuery(): Builder
    {
        $user = auth()->user();
        $query = ProjectRequest::query()->with(['division', 'requester']);

        // Global operators and decision makers: everything recommended, awaiting decision.
        if ($user->hasRole(['super_admin', 'admin', 'cto']) || DivisionAccess::hasGlobalAccess($user)) {
            return $query->whereIn('status', [
                ProjectRequest::STATUS_RECOMMENDED_APPROVE,
                ProjectRequest::STATUS_RECOMMENDED_REJECT,
            ]);
        }

        // Chief: same decision queue but only within led divisions.
        if ($user->hasRole('chief')) {
            return $query
                ->whereIn('status', [
                    ProjectRequest::STATUS_RECOMMENDED_APPROVE,
                    ProjectRequest::STATUS_RECOMMENDED_REJECT,
                ])
                ->whereIn('division_id', $user->ledDivisionIds() ?: [0]);
        }

        // Manager: analysis submitted in their divisions, awaiting recommendation.
        if ($user->hasRole('manager')) {
            return $query
                ->where('status', ProjectRequest::STATUS_ANALYSIS_SUBMITTED)
                ->where(function (Builder $q) use ($user) {
                    $q->whereIn('division_id', $user->ledDivisionIds() ?: [0])
                        ->orWhere('requested_by', $user->id);
                });
        }

        // System Analyst: requests assigned to them, still pending analysis.
        if ($user->hasRole('system_analyst')) {
            return $query
                ->where('analyst_id', $user->id)
                ->where('status', ProjectRequest::STATUS_PENDING_ANALYSIS);
        }

        // Fallback: own requests still in flight.
        return $query
            ->where('requested_by', $user->id)
            ->whereNotIn('status', [
                ProjectRequest::STATUS_APPROVED,
                ProjectRequest::STATUS_REJECTED,
            ]);
    }
}
