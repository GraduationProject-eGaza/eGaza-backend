<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Common Fields
            $table->enum('type', ['citizen', 'government-institute', 'government-employee']);
            $table->string('full_name')->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('national_id')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('password');
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->string('email_verified_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('email_verified_code_expiry')->nullable();

            $table->string('bio')->nullable();
            $table->string('profile_picture')->nullable();

            // Government Institute Fields
            $table->string('institution_name')->nullable();
            $table->string('institution_type')->nullable();
            $table->string('institution_email')->nullable();
            $table->string('official_phone')->nullable();
            $table->string('government_id')->nullable();
            $table->string('institution_address')->nullable();
            $table->string('representative_national_id')->nullable();
            $table->string('representative_mobile')->nullable();

            // Government Employee Fields
            $table->string('employee_id')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->unique(['national_id', 'type']); // allow same national_id for different types
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
