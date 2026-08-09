<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->enum('type', [
                'queue_registered',
                'queue_position_3',
                'queue_position_1',
                'queue_called',
                'appointment_confirmed',
                'appointment_reminder',
                'referral_delivered',
                'lab_result_ready',
                'prescription_ready',
            ]);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
            $table->index(['phone', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_notifications');
    }
};
