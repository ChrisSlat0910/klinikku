<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_id');
            $table->string('name_en');
            $table->string('category', 10)->nullable();
            $table->timestamps();

            $table->index('code');
            $table->fullText(['name_id', 'name_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
