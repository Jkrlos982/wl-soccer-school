<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->unsignedBigInteger('school_id')->after('id');
            $table->string('first_name')->after('school_id');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            
            // Foreign key constraint
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            
            // Indexes
            $table->index(['school_id']);
            $table->index(['is_active']);
            $table->index(['email', 'school_id']);
            $table->index(['last_login_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropIndex(['school_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['email', 'school_id']);
            $table->dropIndex(['last_login_at']);
            
            $table->dropColumn([
                'school_id',
                'first_name',
                'last_name',
                'phone',
                'avatar',
                'is_active',
                'last_login_at'
            ]);
        });
    }
}
