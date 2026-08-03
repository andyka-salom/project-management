<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApprovalQueueWidget;
use App\Filament\Widgets\IssueActionQueueWidget;
use App\Filament\Widgets\MonthlyTicketTrendChart;
use App\Filament\Widgets\MyTasksWidget;
use App\Filament\Widgets\ProjectTimeline;
use App\Filament\Widgets\RecentActivityTable;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TicketsPerProjectChart;
use App\Filament\Widgets\UserStatisticsChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();
        $role = $this->primaryRoleLabel();

        return $user
            ? "Welcome back, {$user->name}" . ($role ? " — {$role}" : '')
            : null;
    }

    /**
     * Role-aware widget set. Every role gets a non-empty, relevant dashboard:
     * global admins see the full analytics suite; workflow roles see their queues;
     * hands-on roles see their personal tasks. StatsOverview + RecentActivity are
     * shared and already scope their data by division/membership.
     */
    public function getWidgets(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole(['super_admin', 'admin'])) {
            return [
                StatsOverview::class,
                ApprovalQueueWidget::class,
                IssueActionQueueWidget::class,
                MonthlyTicketTrendChart::class,
                TicketsPerProjectChart::class,
                UserStatisticsChart::class,
                ProjectTimeline::class,
                RecentActivityTable::class,
            ];
        }

        if ($user->hasRole(['cto', 'chief'])) {
            return [
                StatsOverview::class,
                ApprovalQueueWidget::class,
                IssueActionQueueWidget::class,
                TicketsPerProjectChart::class,
                ProjectTimeline::class,
                RecentActivityTable::class,
            ];
        }

        if ($user->hasRole('manager')) {
            return [
                StatsOverview::class,
                ApprovalQueueWidget::class,
                IssueActionQueueWidget::class,
                MyTasksWidget::class,
                TicketsPerProjectChart::class,
                RecentActivityTable::class,
            ];
        }

        if ($user->hasRole('system_analyst')) {
            return [
                StatsOverview::class,
                ApprovalQueueWidget::class,
                MyTasksWidget::class,
                IssueActionQueueWidget::class,
                RecentActivityTable::class,
            ];
        }

        // programmer, qa, member, staff, and any other role.
        return [
            StatsOverview::class,
            MyTasksWidget::class,
            IssueActionQueueWidget::class,
            RecentActivityTable::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    protected function primaryRoleLabel(): ?string
    {
        $user = auth()->user();
        $role = $user?->getRoleNames()->first();

        if (! $role) {
            return null;
        }

        return \Illuminate\Support\Str::of($role)->replace('_', ' ')->title()->toString();
    }
}
