<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();

            $table->string('title');
            $table->longText('description');
            $table->string('level')->default('medium');
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->boolean('needs_cto_decision')->default(false);
            $table->string('status')->default('open');

            // CTO decision
            $table->longText('cto_decision_notes')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            // PIC action
            $table->longText('action_notes')->nullable();

            // Resolution
            $table->longText('solution')->nullable();
            $table->longText('prevention')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Ownership / closure
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('pic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
