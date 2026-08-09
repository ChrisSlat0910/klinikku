<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('queue_number', 10);
            $table->string('token', 20)->unique();
            $table->integer('position')->default(0);
            $table->enum('status', ['waiting', 'called', 'in_progress', 'done', 'skipped'])->default('waiting');
            $table->string('patient_name');
            $table->string('patient_phone', 20);
            $table->text('chief_complaint');
            $table->json('vital_signs')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('queue_date');
            $table->timestamps();

            $table->index(['clinic_id', 'doctor_id', 'status']);
            $table->index(['clinic_id', 'queue_date']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_items');
    }
};
