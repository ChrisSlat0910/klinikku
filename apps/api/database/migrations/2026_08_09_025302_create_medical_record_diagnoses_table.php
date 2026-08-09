<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['primary', 'secondary'])->default('primary');
            $table->timestamps();

            $table->unique(['medical_record_id', 'diagnosis_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_diagnoses');
    }
};
