<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $resources = [
            'project',
            'ticket',
            'ticket_priority',
            'ticket_comment',
            'notification',
            'user',
            'project_request',
            'issue',
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
            'decide_issue',   // CTO / Chief records decision
            'act_issue',      // PIC performs the action
            'verify_issue',   // Manager verifies, resolves & closes
        ];

        $permissions = array_merge($permissions, $workflowPermissions);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Existing roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $member = Role::firstOrCreate(['name' => 'member']);

        // New SDLC roles
        $cto = Role::firstOrCreate(['name' => 'cto']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $systemAnalyst = Role::firstOrCreate(['name' => 'system_analyst']);
        $programmer = Role::firstOrCreate(['name' => 'programmer']);
        $qa = Role::firstOrCreate(['name' => 'qa']);

        // Generic, division-agnostic roles for non-IT divisions
        $chief = Role::firstOrCreate(['name' => 'chief']);
        $staff = Role::firstOrCreate(['name' => 'staff']);

        // super_admin: all permissions
        $superAdmin->syncPermissions(Permission::all());

        // admin: all permissions except delete_user (kept for backward compatibility)
        $adminPermissions = Permission::whereNotIn('name', ['delete_user'])->get();
        $admin->syncPermissions($adminPermissions);

        // member: view-only + update ticket (kept for backward compatibility)
        $memberPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            // Issues: can view and act as PIC
            'view_issue', 'view_any_issue', 'act_issue',
        ])->get();
        $member->syncPermissions($memberPermissions);

        // staff: generic division member — same view-oriented access as "member"
        $staff->syncPermissions($memberPermissions);

        // CTO: approve requests, manage SDLC, view/manage projects
        $ctoPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project', 'create_project', 'update_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket', 'delete_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_project_request', 'view_any_project_request', 'update_project_request',
            'approve_project_request',
            'manage_sdlc_phase',
            // Issues: CTO/Chief decides
            'view_issue', 'view_any_issue', 'decide_issue',
        ])->get();
        $cto->syncPermissions($ctoPermissions);

        // chief: same capabilities as CTO but division-scoped (a generic C-level lead)
        $chief->syncPermissions($ctoPermissions);

        // Manager: create requests, assign analyst, recommend, manage projects
        $managerPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project', 'update_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_project_request', 'view_any_project_request',
            'create_project_request', 'update_project_request',
            'assign_analyst_project_request',
            'recommend_project_request',
            // Issues: Manager creates, updates, verifies & closes
            'view_issue', 'view_any_issue', 'create_issue', 'update_issue', 'delete_issue', 'verify_issue',
        ])->get();
        $manager->syncPermissions($managerPermissions);

        // System Analyst: submit analysis, view projects
        $saPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            'view_project_request', 'view_any_project_request', 'update_project_request',
            'submit_analysis_project_request',
            // Issues: can view and act as PIC
            'view_issue', 'view_any_issue', 'act_issue',
        ])->get();
        $systemAnalyst->syncPermissions($saPermissions);

        // Programmer: view projects, manage tickets
        $programmerPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            // Issues: can view and act as PIC
            'view_issue', 'view_any_issue', 'act_issue',
        ])->get();
        $programmer->syncPermissions($programmerPermissions);

        // QA: view projects, manage tickets (testing)
        $qaPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            // Issues: can view and act as PIC
            'view_issue', 'view_any_issue', 'act_issue',
        ])->get();
        $qa->syncPermissions($qaPermissions);
    }
}
