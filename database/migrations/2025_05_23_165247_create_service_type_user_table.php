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
    {//pivot table for many-to-many relationship between service_types and users
        // This table will link service types to the government employees assigned to them
        // The `user_id` must be of type 'government-employee'
        Schema::create('service_type_user', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('service_type_id');
        $table->unsignedBigInteger('user_id');// assigned employee (must be type=government-employee)
        $table->timestamps();

        $table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('cascade');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_type_user');
    }
};
