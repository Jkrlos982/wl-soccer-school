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
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->default(1);
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_players')->default(25);
            $table->string('season');
            $table->string('field_location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('registration_open')->default(true);
            $table->unsignedBigInteger('coach_id')->nullable();
            
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('coach_id')->references('id')->on('users');
            $table->index(['school_id', 'category_id']);
            $table->index(['school_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['coach_id']);
            $table->dropIndex(['school_id', 'category_id']);
            $table->dropIndex(['school_id', 'is_active']);
            
            $table->dropColumn([
                'school_id',
                'category_id',
                'name',
                'description',
                'max_players',
                'season',
                'field_location',
                'is_active',
                'registration_open',
                'coach_id'
            ]);
        });
    }
};
