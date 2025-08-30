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
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('code')->after('id')->unique();
            $table->unsignedBigInteger('school_id')->nullable()->after('priority');
            $table->unsignedBigInteger('created_by')->nullable()->after('school_id');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->softDeletes();
            
            // Add indexes
            $table->index(['code', 'type']);
            $table->index(['school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropIndex(['code', 'type']);
            $table->dropIndex(['school_id']);
            $table->dropSoftDeletes();
            $table->dropColumn(['code', 'school_id', 'created_by', 'updated_by']);
        });
    }
};