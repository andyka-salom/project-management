<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Overview';

    protected function getStats(): array
    {
        $user = auth()->user();
        $isGlobalAdmin = $user->hasRole(['super_admin', 'admin']);
        $ledDivisionIds = $user->ledDivisionIds();

        // Builder for projects
        $myProjectIdsQuery = Project::query();
        if (!$isGlobalAdmin) {
            $myProjectIdsQuery->where(function ($query) use ($user, $ledDivisionIds) {
                $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
                if (!empty($ledDivisionIds)) {
                    $query->orWhereIn('division_id', $ledDivisionIds);
                }
            });
        }
        $myProjectIds = $myProjectIdsQuery->pluck('id')->toArray();
        $myProjectsCount = count($myProjectIds);

        $totalTicketsQuery = Ticket::query();
        if (!$isGlobalAdmin) {
            $totalTicketsQuery->whereIn('project_id', $myProjectIds);
        }
        $projectTicketsCount = $totalTicketsQuery->count();

        $myAssignedTickets = DB::table('tickets')
            ->join('ticket_users', 'tickets.id', '=', 'ticket_users.ticket_id')
            ->where('ticket_users.user_id', $user->id)
            ->count();

        $myCreatedTickets = Ticket::where('created_by', $user->id)->count();

        $newTicketsThisWeekQuery = Ticket::query()->where('created_at', '>=', Carbon::now()->subDays(7));
        if (!$isGlobalAdmin) {
            $newTicketsThisWeekQuery->whereIn('project_id', $myProjectIds);
        }
        $newTicketsThisWeek = $newTicketsThisWeekQuery->count();

        $myOverdueTickets = DB::table('tickets')
            ->join('ticket_users', 'tickets.id', '=', 'ticket_users.ticket_id')
            ->join('ticket_statuses', 'tickets.ticket_status_id', '=', 'ticket_statuses.id')
            ->where('ticket_users.user_id', $user->id)
            ->where('tickets.due_date', '<', Carbon::now())
            ->whereNotIn('ticket_statuses.name', ['Completed', 'Done', 'Closed'])
            ->count();

        $myCompletedThisWeek = DB::table('tickets')
            ->join('ticket_users', 'tickets.id', '=', 'ticket_users.ticket_id')
            ->join('ticket_statuses', 'tickets.ticket_status_id', '=', 'ticket_statuses.id')
            ->where('ticket_users.user_id', $user->id)
            ->whereIn('ticket_statuses.name', ['Completed', 'Done', 'Closed'])
            ->where('tickets.updated_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $teamMembersQuery = User::query();
        if (!$isGlobalAdmin) {
            $teamMembersQuery->whereHas('projects', function ($query) use ($myProjectIds) {
                $query->whereIn('projects.id', $myProjectIds);
            })->where('id', '!=', $user->id);
        }
        $teamMembers = $teamMembersQuery->count();

        $stats = [];

        if ($isGlobalAdmin) {
            $stats[] = Stat::make('Total Projects', $myProjectsCount)
                ->description('Active projects in the system')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary');
            $stats[] = Stat::make('Total Tickets', $projectTicketsCount)
                ->description('Tickets across all projects')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success');
        } else {
            $stats[] = Stat::make('Scope Projects', $myProjectsCount)
                ->description('Projects in your scope')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary');
            $stats[] = Stat::make('Scope Tickets', $projectTicketsCount)
                ->description('Total tickets in your scope')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success');
        }

        $stats[] = Stat::make('My Assigned Tickets', $myAssignedTickets)
            ->description('Tickets assigned to you')
            ->descriptionIcon('heroicon-m-user-circle')
            ->color($myAssignedTickets > 10 ? 'danger' : ($myAssignedTickets > 5 ? 'warning' : 'success'));
        
        $stats[] = Stat::make('My Created Tickets', $myCreatedTickets)
            ->description('Tickets you created')
            ->descriptionIcon('heroicon-m-pencil-square')
            ->color('info');

        $stats[] = Stat::make('Completed This Week', $myCompletedThisWeek)
            ->description('Your completed tickets')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color($myCompletedThisWeek > 0 ? 'success' : 'gray');

        $stats[] = Stat::make('New Tasks This Week', $newTicketsThisWeek)
            ->description($isGlobalAdmin ? 'New tickets this week' : 'Created in your scope')
            ->descriptionIcon('heroicon-m-plus-circle')
            ->color('info');

        $stats[] = Stat::make('My Overdue Tasks', $myOverdueTickets)
            ->description('Your past due tickets')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color($myOverdueTickets > 0 ? 'danger' : 'success');

        $stats[] = Stat::make('Team Members', $teamMembers)
            ->description($isGlobalAdmin ? 'Total registered users' : 'People in your scope')
            ->descriptionIcon('heroicon-m-users')
            ->color('gray');

        return $stats;
    }
}
