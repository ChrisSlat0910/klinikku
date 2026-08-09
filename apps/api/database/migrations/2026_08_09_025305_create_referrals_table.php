<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('digital_code', 20)->unique();
            $table->string('destination_facility');
            $table->string('destination_specialty');
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->text('reason');
            $table->text('summary')->nullable();
            $table->string('pdf_path')->nullable();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
            $table->index('digital_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
