<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill projects that were created from an approved project request
     * before the division_id was copied over. Without a division_id these
     * projects (and their tickets/epics) are hidden by DivisionScope for
     * every non-global user, so their timeline/board pages showed nothing.
     */
    public function up(): void
    {
        DB::table('projects')
            ->whereNull('division_id')
            ->whereNotNull('project_request_id')
            ->orderBy('id')
            ->each(function ($project) {
                $divisionId = DB::table('project_requests')
                    ->where('id', $project->project_request_id)
                    ->value('division_id');

                if ($divisionId !== null) {
                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['division_id' => $divisionId]);
                }
            });
    }

    public function down(): void
    {
        // No-op: cannot know which division_ids were originally null.
    }
};
