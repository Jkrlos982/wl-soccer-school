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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('category_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->enum('document_type', ['CC', 'TI', 'CE', 'PP']);
            $table->string('document_number')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('emergency_contact_relationship');
            $table->string('medical_conditions')->nullable();
            $table->string('allergies')->nullable();
            $table->string('medications')->nullable();
            $table->string('position')->nullable(); // goalkeeper, defender, midfielder, forward
            $table->integer('jersey_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('enrollment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->index(['school_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->unique(['school_id', 'document_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
