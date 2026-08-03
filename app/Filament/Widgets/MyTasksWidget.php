<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Open tickets assigned to the current user, soonest deadline first.
 * Shown to hands-on roles (programmer, qa, analyst, manager, member, staff).
 */
class MyTasksWidget extends BaseWidget
{
    protected static ?string $heading = 'My Open Tickets';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->emptyStateHeading('No open tickets assigned to you')
            ->emptyStateDescription('Tickets assigned to you will appear here.')
            ->emptyStateIcon('heroicon-o-ticket')
            ->columns([
                TextColumn::make('name')
                    ->label('Ticket')
                    ->searchable()
                    ->limit(45)
                    ->weight('bold'),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d/m/Y')
                    ->placeholder('No due date')
                    ->color(fn ($state): string => $state && Carbon::parse($state)->isPast() ? 'danger' : 'gray')
                    ->sortable(),
            ])
            ->defaultSort('due_date', 'asc')
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25]);
    }

    protected function baseQuery(): Builder
    {
        $user = auth()->user();

        return Ticket::query()
            ->with(['project', 'status'])
            ->whereHas('assignees', fn (Builder $q) => $q->where('users.id', $user->id))
            ->whereHas('status', fn (Builder $q) => $q->whereNotIn('name', ['Completed', 'Done', 'Closed']));
    }
}
