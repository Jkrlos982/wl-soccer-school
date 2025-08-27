<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTransactionsTableForApprovalSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Update status enum to include approval workflow states
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'completed'])
                  ->default('pending')
                  ->change();
            
            // Add approval workflow fields
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            
            // Add indexes for better performance
            $table->index(['school_id', 'status', 'transaction_date']);
            $table->index(['approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Remove approval workflow fields
            $table->dropColumn(['approved_by', 'approved_at', 'approval_notes']);
            
            // Revert status enum to original values
            $table->enum('status', ['pending', 'completed', 'cancelled'])
                  ->default('pending')
                  ->change();
            
            // Drop added indexes
            $table->dropIndex(['school_id', 'status', 'transaction_date']);
            $table->dropIndex(['approved_by']);
        });
    }
}
