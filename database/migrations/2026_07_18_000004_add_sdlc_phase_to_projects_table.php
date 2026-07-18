<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('sdlc_phase')->nullable()->after('end_date');
            $table->foreignId('project_request_id')->nullable()->after('sdlc_phase')->constrained('project_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_request_id');
            $table->dropColumn('sdlc_phase');
        });
    }
};
