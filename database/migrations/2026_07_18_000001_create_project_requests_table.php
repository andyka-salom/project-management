<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description');
            $table->longText('business_justification');
            $table->string('priority')->default('medium');
            $table->date('requested_deadline')->nullable();
            $table->string('status')->default('draft');

            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('analyst_id')->nullable()->constrained('users')->nullOnDelete();

            $table->longText('requirement_analysis')->nullable();
            $table->longText('feasibility_study')->nullable();
            $table->longText('technical_notes')->nullable();
            $table->timestamp('analysis_submitted_at')->nullable();

            $table->string('manager_recommendation')->nullable();
            $table->longText('manager_recommendation_reason')->nullable();
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable();

            $table->string('cto_decision')->nullable();
            $table->longText('cto_decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('requested_by');
            $table->index('analyst_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requests');
    }
};
