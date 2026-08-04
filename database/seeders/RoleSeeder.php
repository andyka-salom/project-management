<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Base Permissions (if they don't exist, to prevent foreign key issues)
        $resources = [
            'project',
            'ticket',
            'ticket::priority',
            'ticket::comment',
            'notification',
            'user',
            'project::request',
            'issue',
            'division',
            'role',
        ];

        $actions = ['view', 'view_any', 'create', 'update', 'delete'];
        $permissions = [];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = $action . '_' . $resource;
            }
        }

        $workflowPermissions = [
            'assign_analyst_project_request',
            'submit_analysis_project_request',
            'recommend_project_request',
            'approve_project_request',
            'manage_sdlc_phase',
            // Issue workflow
            'decide_issue',
            'act_issue',
            'verify_issue',
            // Pages & Widgets
            'page_Dashboard',
            'page_EpicsOverview',
            'page_ProjectBoard',
            'page_ProjectTimeline',
            'page_Schedule',
            'page_TicketTimeline',
            'page_UserContributions',
            'page_Leaderboard',
            'widget_StatsOverview',
            'widget_ApprovalQueueWidget',
            'widget_IssueActionQueueWidget',
            'widget_TicketsPerProjectChart',
            'widget_MyTasksWidget',
            'widget_UserStatisticsChart',
            'widget_MonthlyTicketTrendChart',
            'widget_ProjectTimeline',
            'widget_RecentActivityTable',
        ];

        $permissions = array_merge($permissions, $workflowPermissions);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $member = Role::firstOrCreate(['name' => 'member']);
        $cto = Role::firstOrCreate(['name' => 'cto']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $systemAnalyst = Role::firstOrCreate(['name' => 'system_analyst']);
        $programmer = Role::firstOrCreate(['name' => 'programmer']);
        $qa = Role::firstOrCreate(['name' => 'qa']);
        $chief = Role::firstOrCreate(['name' => 'chief']);
        $staff = Role::firstOrCreate(['name' => 'staff']);

        // Note: The actual permission assignment is handled by the individual seeders
        // (SuperAdminSeeder, AdminSeeder, ManagerSeeder, etc.) that are called in DatabaseSeeder.
        // We will only handle the generic 'chief' and 'staff' here since they don't have individual seeders.

        // chief: Same as CTO
        $chiefPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project', 'create_project', 'update_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket', 'delete_ticket',
            'view_ticket::priority', 'view_any_ticket::priority',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_division', 'view_any_division',
            'view_project::request', 'view_any_project::request', 'update_project::request',
            'approve_project_request',
            'manage_sdlc_phase',
            'view_issue', 'view_any_issue', 'decide_issue',
            'page_Dashboard', 'widget_StatsOverview', 'widget_ProjectTimeline', 'page_ProjectTimeline'
        ])->get();
        $chief->syncPermissions($chiefPermissions);

        // staff: Same as member
        $staffPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'update_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_notification', 'view_any_notification',
            'view_issue', 'view_any_issue', 'act_issue',
            'page_Dashboard', 'widget_MyTasksWidget'
        ])->get();
        $staff->syncPermissions($staffPermissions);
    }
}
