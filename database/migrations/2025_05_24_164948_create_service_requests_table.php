<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('citizen_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('institute_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('service_type_id')->constrained()->onDelete('cascade');
    $table->string('description');
    $table->date('request_date');
    $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
